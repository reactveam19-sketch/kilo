<?php
declare(strict_types=1);

class OpenAIService
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
        $article = $this->requestOpenAIArticle($key['api_key'], $video);
        if ($article !== null) {
            return $article;
        }

        $this->recordError($key['id']);
        return $this->fallbackArticle($video);
    }

    private function selectActiveKey(): ?array
    {
        $stmt = $this->db->query('SELECT * FROM api_keys WHERE provider = "openai" AND status = "active" ORDER BY usage_count ASC LIMIT 1');
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

    private function requestOpenAIArticle(string $apiKey, array $video): ?array
    {
        $payload = json_encode([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a sports editor. Return JSON with keys: title, meta_description, content_html.',
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($video),
                ],
            ],
            'temperature' => 0.7,
        ]);

        if ($payload === false) {
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
                'content' => $payload,
                'timeout' => 12,
            ],
        ]);

        $response = @file_get_contents('https://api.openai.com/v1/chat/completions', false, $context);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return null;
        }

        $text = $data['choices'][0]['message']['content'] ?? '';
        if (!is_string($text) || trim($text) === '') {
            return null;
        }

        return $this->parseModelResponse($text);
    }

    private function buildPrompt(array $video): string
    {
        $title = $video['title'];
        $channel = $video['channel'];
        $description = $video['description'];

        return <<<PROMPT
        Write a short football news article based on the video below.
        Return JSON with keys: title, meta_description (<= 160 chars), content_html (with a short list).

        Video title: {$title}
        Channel: {$channel}
        Description: {$description}
        PROMPT;
    }

    private function parseModelResponse(string $text): ?array
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

    private function fallbackArticle(array $video): array
    {
        return [
            'title' => $video['title'] . ' - Football News',
            'meta_description' => 'AI-generated football update based on ' . $video['title'] . '.',
            'content_html' => $this->buildFallbackHtml($video),
        ];
    }

    private function buildFallbackHtml(array $video): string
    {
        $description = htmlspecialchars($video['description'], ENT_QUOTES, 'UTF-8');
        $channel = htmlspecialchars($video['channel'], ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <p>Our editors reviewed {$channel}'s latest upload and captured the biggest talking points for fans.</p>
        <ul>
            <li>Key momentum shifts and standout performances.</li>
            <li>Managerial reactions and tactical takeaways.</li>
            <li>What this result means for the table.</li>
        </ul>
        <p>{$description}</p>
        HTML;
    }
}
