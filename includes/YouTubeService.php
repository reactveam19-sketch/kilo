<?php
declare(strict_types=1);

class YouTubeService
{
    private ?string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $apiKey = $apiKey !== null ? trim($apiKey) : '';
        $this->apiKey = $apiKey !== '' ? $apiKey : null;
    }

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
                'tags' => [],
                'duration' => null,
                'statistics' => [],
                'related_videos' => [],
            ];
        }

        $apiDetails = $this->fetchFromApi($videoId);
        if ($apiDetails !== null) {
            return $apiDetails;
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
                'tags' => [],
                'duration' => null,
                'statistics' => [],
                'related_videos' => [],
            ];
        }

        $data = json_decode($response, true);
        return [
            'id' => $videoId,
            'title' => $data['title'] ?? 'YouTube Video',
            'description' => $data['author_name'] ?? 'YouTube creator',
            'channel' => $data['author_name'] ?? 'YouTube creator',
            'published_at' => date('c'),
            'tags' => [],
            'duration' => null,
            'statistics' => [],
            'related_videos' => [],
        ];
    }

    private function fetchFromApi(string $videoId): ?array
    {
        if ($this->apiKey === null) {
            return null;
        }

        $videoUrl = 'https://www.googleapis.com/youtube/v3/videos?part=snippet,contentDetails,statistics&id='
            . urlencode($videoId)
            . '&key=' . urlencode($this->apiKey);
        $response = @file_get_contents($videoUrl);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['items'][0])) {
            return null;
        }

        $item = $data['items'][0];
        $snippet = $item['snippet'] ?? [];
        $stats = $item['statistics'] ?? [];
        $contentDetails = $item['contentDetails'] ?? [];

        return [
            'id' => $videoId,
            'title' => (string) ($snippet['title'] ?? 'YouTube Video'),
            'description' => (string) ($snippet['description'] ?? 'No description available.'),
            'channel' => (string) ($snippet['channelTitle'] ?? 'YouTube'),
            'published_at' => (string) ($snippet['publishedAt'] ?? date('c')),
            'tags' => is_array($snippet['tags'] ?? null) ? $snippet['tags'] : [],
            'duration' => (string) ($contentDetails['duration'] ?? ''),
            'statistics' => [
                'views' => isset($stats['viewCount']) ? (int) $stats['viewCount'] : null,
                'likes' => isset($stats['likeCount']) ? (int) $stats['likeCount'] : null,
                'comments' => isset($stats['commentCount']) ? (int) $stats['commentCount'] : null,
            ],
            'related_videos' => $this->fetchRelatedVideos($videoId),
        ];
    }

    private function fetchRelatedVideos(string $videoId): array
    {
        if ($this->apiKey === null) {
            return [];
        }

        $searchUrl = 'https://www.googleapis.com/youtube/v3/search?part=snippet&type=video&maxResults=3&relatedToVideoId='
            . urlencode($videoId)
            . '&key=' . urlencode($this->apiKey);
        $response = @file_get_contents($searchUrl);
        if ($response === false) {
            return [];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['items'])) {
            return [];
        }

        $related = [];
        foreach ($data['items'] as $item) {
            $snippet = $item['snippet'] ?? [];
            $related[] = [
                'id' => (string) (($item['id']['videoId'] ?? '') ?: ''),
                'title' => (string) ($snippet['title'] ?? ''),
                'channel' => (string) ($snippet['channelTitle'] ?? ''),
                'published_at' => (string) ($snippet['publishedAt'] ?? ''),
            ];
        }

        return $related;
    }
}
