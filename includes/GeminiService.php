<?php
declare(strict_types=1);

class GeminiService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function generateArticle(array $video): array
    {
        $key = $this->selectActiveKey();
        if ($key === null) {
            return $this->fallbackArticle($video);
        }

        $this->incrementUsage($key['id']);

        $article = $this->requestGeminiArticle($key['api_key'], $video);
        if ($article !== null) {
            return $article;
        }

        $this->recordError($key['id']);

        return $this->fallbackArticle($video);
    }

    private function selectActiveKey(): ?array
    {
        $stmt = $this->db->query('SELECT * FROM api_keys WHERE provider = "gemini" AND status = "active" ORDER BY usage_count ASC LIMIT 1');
        $key = $stmt->fetch(PDO::FETCH_ASSOC);
        return $key ?: null;
    }

    private function incrementUsage(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE api_keys SET usage_count = usage_count + 1 WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    private function recordError(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE api_keys SET last_error_at = :last_error_at WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':last_error_at' => date('c'),
        ]);
    }

    private function fallbackArticle(array $video): array
    {
        return [
            'title' => $video['title'] . ' - Football News',
            'meta_description' => 'Breaking football update based on the latest video coverage of ' . $video['title'] . '.',
            'content_html' => $this->buildHtml($video),
        ];
    }

    private function buildHtml(array $video): string
    {
        $description = htmlspecialchars($video['description'], ENT_QUOTES, 'UTF-8');
        $channel = htmlspecialchars($video['channel'], ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <p><strong>Headline:</strong> {$video['title']} sparked fresh discussion across the football world. Our AI newsroom reviewed the footage and surfaced the key talking points.</p>
        <h2>Key moments</h2>
        <ul>
            <li>Momentum swings highlighted by tactical adjustments and standout individual performances.</li>
            <li>Coach reactions from {$channel} focused on the match plan execution.</li>
            <li>Fans reacted instantly, amplifying the biggest moments from the broadcast.</li>
        </ul>
        <h2>What it means</h2>
        <p>{$description}</p>
        <p>The story continues to develop as the league calendar heats up.</p>
        HTML;
    }

    private function requestGeminiArticle(string $apiKey, array $video): ?array
    {
        $prompt = $this->buildPrompt($video);
        $payload = json_encode([
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1400,
            ],
        ]);

        if ($payload === false) {
            return null;
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . urlencode($apiKey);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 12,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return null;
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (!is_string($text) || trim($text) === '') {
            return null;
        }

        return $this->parseModelResponse($text, $video);
    }

    private function buildPrompt(array $video): string
    {
        $title = $video['title'];
        $channel = $video['channel'];
        $description = $video['description'];

        return <<<PROMPT
        You are an editor writing a long-form football news article based on a YouTube video.
        Return JSON with keys: title, meta_description, content_html.
        Title should be concise. Meta description should be under 160 characters.
        content_html should include multiple sections with headings, paragraphs, and at least one bullet list.

        Video title: {$title}
        Channel: {$channel}
        Description: {$description}
        Video tags: {$this->formatTags($video)}
        Video statistics: {$this->formatStats($video)}
        Related videos: {$this->formatRelatedVideos($video)}
        PROMPT;
    }

    private function formatTags(array $video): string
    {
        $tags = $video['tags'] ?? [];
        if (!is_array($tags) || $tags === []) {
            return 'None';
        }

        return implode(', ', array_slice($tags, 0, 10));
    }

    private function formatStats(array $video): string
    {
        $stats = $video['statistics'] ?? [];
        if (!is_array($stats) || $stats === []) {
            return 'Unavailable';
        }

        $parts = [];
        if (isset($stats['views'])) {
            $parts[] = 'Views: ' . number_format((int) $stats['views']);
        }
        if (isset($stats['likes'])) {
            $parts[] = 'Likes: ' . number_format((int) $stats['likes']);
        }
        if (isset($stats['comments'])) {
            $parts[] = 'Comments: ' . number_format((int) $stats['comments']);
        }

        return $parts === [] ? 'Unavailable' : implode(' | ', $parts);
    }

    private function formatRelatedVideos(array $video): string
    {
        $related = $video['related_videos'] ?? [];
        if (!is_array($related) || $related === []) {
            return 'None';
        }

        $lines = [];
        foreach (array_slice($related, 0, 3) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = (string) ($item['title'] ?? '');
            $channel = (string) ($item['channel'] ?? '');
            $lines[] = trim($title . ' (' . $channel . ')');
        }

        return $lines === [] ? 'None' : implode('; ', $lines);
    }

    private function parseModelResponse(string $text, array $video): ?array
    {
        $jsonStart = strpos($text, '{');
        if ($jsonStart === false) {
            return null;
        }
        $json = substr($text, $jsonStart);
        $article = json_decode($json, true);
        if (!is_array($article)) {
            return null;
        }

        if (!isset($article['title'], $article['meta_description'], $article['content_html'])) {
            return null;
        }

        return [
            'title' => (string) $article['title'],
            'meta_description' => (string) $article['meta_description'],
            'content_html' => (string) $article['content_html'],
        ];
    }
}
