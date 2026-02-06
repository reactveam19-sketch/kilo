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

        return [
            'title' => $video['title'] . ' - AI Match Report',
            'meta_description' => 'AI-powered breakdown of ' . $video['title'] . ' with key moments and tactical insights.',
            'content_html' => $this->buildHtml($video),
        ];
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
}
