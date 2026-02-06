<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/template.php';

$db = get_db();
$settings = get_settings($db);
$slug = $_GET['slug'] ?? '';
$article = $slug ? get_article_by_slug($db, $slug) : null;

if ($article === null) {
    http_response_code(404);
    render_header($settings, 'Article Not Found');
    echo '<section class="container"><div class="article"><h1>Article not found</h1><p>Try the latest coverage on the homepage.</p></div></section>';
    render_footer($settings);
    exit;
}

$related = fetch_articles($db, 3, 0);
$title = htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8');
$metaDescription = htmlspecialchars($article['meta_description'] ?: truncate($article['content_html'], 160), ENT_QUOTES, 'UTF-8');
$videoId = $article['youtube_id'] ?? '';
$publishedAt = $article['published_at'];

render_header($settings, $title);
?>
<section class="container">
    <article class="article">
        <h1><?= $title ?></h1>
        <p class="meta">Published <?= format_date($publishedAt) ?> · By <?= htmlspecialchars($article['author'] ?: 'Editorial Desk', ENT_QUOTES, 'UTF-8') ?></p>
        <?php if (!empty($settings['body_ad'])): ?>
            <div class="ad-block"><?= $settings['body_ad'] ?></div>
        <?php endif; ?>
        <?php if (!empty($videoId)): ?>
            <div class="video-embed">
                <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8') ?>" title="YouTube video" loading="lazy" allowfullscreen></iframe>
            </div>
        <?php endif; ?>
        <div class="content">
            <?= $article['content_html'] ?>
        </div>
    </article>

    <section>
        <h2>Related Articles</h2>
        <div class="grid">
            <?php foreach ($related as $item): ?>
                <article class="card">
                    <h3><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars(truncate($item['content_html'], 90), ENT_QUOTES, 'UTF-8') ?></p>
                    <a href="<?= build_article_url($item['slug']) ?>">Read article</a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</section>

<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $article['title'],
    'datePublished' => $publishedAt,
    'author' => [
        '@type' => 'Organization',
        'name' => $article['author'] ?: 'Editorial Desk',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => $settings['site_name'],
    ],
    'description' => $article['meta_description'] ?: $metaDescription,
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => build_article_url($article['slug']),
    ],
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>

<?php if (!empty($videoId)): ?>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'VideoObject',
    'name' => $article['title'],
    'uploadDate' => $publishedAt,
    'description' => $article['meta_description'] ?: $metaDescription,
    'embedUrl' => 'https://www.youtube.com/embed/' . $videoId,
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
<?php endif; ?>
<?php
render_footer($settings);
