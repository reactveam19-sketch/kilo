<?php
declare(strict_types=1);

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'article';
}

function format_date(string $date): string
{
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    return date('F j, Y', $timestamp);
}

function truncate(string $text, int $limit = 160): string
{
    $text = trim(strip_tags($text));
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return mb_substr($text, 0, $limit - 3) . '...';
}

function fetch_articles(PDO $db, int $limit = 9, int $offset = 0): array
{
    $stmt = $db->prepare('SELECT * FROM articles WHERE status = "published" ORDER BY published_at DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_article_by_slug(PDO $db, string $slug): ?array
{
    $stmt = $db->prepare('SELECT * FROM articles WHERE slug = :slug AND status = "published" LIMIT 1');
    $stmt->execute([':slug' => $slug]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    return $article ?: null;
}

function count_articles(PDO $db): int
{
    $stmt = $db->query('SELECT COUNT(*) FROM articles WHERE status = "published"');
    return (int) $stmt->fetchColumn();
}

function build_article_url(string $slug): string
{
    return '/article/' . $slug;
}
