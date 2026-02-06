<?php
declare(strict_types=1);

class YouTubeService
{
    public function extractVideoId(string $url): ?string
    {
        if (preg_match('~(?:v=|youtu\.be/|embed/)([a-zA-Z0-9_-]{11})~', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function fetchVideoDetails(string $url): array
    {
        $videoId = $this->extractVideoId($url);
        if ($videoId === null) {
            return [
                'id' => null,
                'title' => 'Untitled Video',
                'description' => 'No description available.',
                'channel' => 'Unknown Channel',
                'published_at' => date('c'),
            ];
        }

        $oembedUrl = 'https://www.youtube.com/oembed?url=' . urlencode($url) . '&format=json';
        $response = @file_get_contents($oembedUrl);
        if ($response === false) {
            return [
                'id' => $videoId,
                'title' => 'YouTube Video',
                'description' => 'Metadata unavailable. Please check API connectivity.',
                'channel' => 'YouTube',
                'published_at' => date('c'),
            ];
        }

        $data = json_decode($response, true);
        return [
            'id' => $videoId,
            'title' => $data['title'] ?? 'YouTube Video',
            'description' => $data['author_name'] ?? 'YouTube creator',
            'channel' => $data['author_name'] ?? 'YouTube creator',
            'published_at' => date('c'),
        ];
    }
}
