<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/template.php';

$db = get_db();
$settings = get_settings($db);

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;
$articles = fetch_articles($db, $perPage, $offset);
$totalArticles = count_articles($db);
$totalPages = max(1, (int) ceil($totalArticles / $perPage));

render_header($settings, 'Latest Football News');
?>
<section class="hero">
    <div class="container">
        <h1>Latest Football News</h1>
        <p>AI-generated match reports and breaking transfer updates, refreshed from YouTube coverage.</p>
    </div>
</section>
<section class="container">
    <div class="grid" data-article-grid>
        <?php foreach ($articles as $article): ?>
            <article class="card">
                <h3><?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars(truncate($article['content_html'], 120), ENT_QUOTES, 'UTF-8') ?></p>
                <a href="<?= build_article_url($article['slug']) ?>">Read article</a>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if ($totalArticles > $perPage): ?>
        <button class="load-more" data-load-more data-offset="<?= $perPage ?>">Load more</button>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="/?page=<?= $i ?>">Page <?= $i ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</section>
<?php
render_footer($settings);
