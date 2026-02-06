<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$db = get_db();
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$limit = 6;
$articles = fetch_articles($db, $limit, $offset);

$html = '';
foreach ($articles as $article) {
    $title = htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8');
    $excerpt = htmlspecialchars(truncate($article['content_html'], 120), ENT_QUOTES, 'UTF-8');
    $url = build_article_url($article['slug']);
    $html .= "<article class=\"card\"><h3>{$title}</h3><p>{$excerpt}</p><a href=\"{$url}\">Read article</a></article>";
}

$total = count_articles($db);
$hasMore = ($offset + $limit) < $total;

echo json_encode([
    'html' => $html,
    'count' => count($articles),
    'has_more' => $hasMore,
]);
