<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/template.php';
require_once __DIR__ . '/../includes/YouTubeService.php';
require_once __DIR__ . '/../includes/GeminiService.php';

$db = get_db();
$settings = get_settings($db);
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['youtube_url'] ?? '');
    $author = trim($_POST['author'] ?? 'Editorial Desk');

    $youtube = new YouTubeService();
    $video = $youtube->fetchVideoDetails($url);

    $gemini = new GeminiService($db);
    $articleData = $gemini->generateArticle($video);

    $slugBase = slugify($articleData['title']);
    $slug = $slugBase;
    $suffix = 1;

    while (get_article_by_slug($db, $slug)) {
        $slug = $slugBase . '-' . $suffix;
        $suffix++;
    }

    $stmt = $db->prepare(
        'INSERT INTO articles (title, slug, meta_description, content_html, youtube_id, author, status, published_at)
        VALUES (:title, :slug, :meta_description, :content_html, :youtube_id, :author, :status, :published_at)'
    );
    $stmt->execute([
        ':title' => $articleData['title'],
        ':slug' => $slug,
        ':meta_description' => $articleData['meta_description'],
        ':content_html' => $articleData['content_html'],
        ':youtube_id' => $video['id'],
        ':author' => $author,
        ':status' => 'published',
        ':published_at' => date('c'),
    ]);

    $notice = 'Article generated and published successfully.';
}

render_header($settings, 'Magic Generator');
?>
<section class="container">
    <div class="admin-nav">
        <a href="/admin/index.php">Dashboard</a>
        <a href="/admin/bulk_generate.php">Bulk Generator</a>
    </div>

    <div class="form">
        <h1>Magic Generator</h1>
        <?php if ($notice): ?>
            <div class="notice"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post">
            <label for="youtube_url">YouTube URL</label>
            <input id="youtube_url" name="youtube_url" type="url" required placeholder="https://www.youtube.com/watch?v=...">

            <label for="author">Author</label>
            <input id="author" name="author" type="text" value="Editorial Desk">

            <button class="load-more" type="submit">Generate Article</button>
        </form>
    </div>
</section>
<?php
render_footer($settings);
