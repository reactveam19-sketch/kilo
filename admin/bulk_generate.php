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
    $urls = array_filter(array_map('trim', explode("\n", $_POST['youtube_urls'] ?? '')));
    $author = trim($_POST['author'] ?? 'Editorial Desk');
    $youtube = new YouTubeService();
    $gemini = new GeminiService($db);

    $created = 0;
    foreach ($urls as $url) {
        $video = $youtube->fetchVideoDetails($url);
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
        $created++;
    }

    if ($created > 0) {
        $notice = "Queued {$created} videos and published them.";
    }
}

render_header($settings, 'Bulk Generator');
?>
<section class="container">
    <div class="admin-nav">
        <a href="/admin/index.php">Dashboard</a>
        <a href="/admin/generate.php">Magic Generator</a>
    </div>

    <div class="form">
        <h1>Bulk Generator</h1>
        <?php if ($notice): ?>
            <div class="notice"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post">
            <label for="youtube_urls">YouTube URLs (one per line)</label>
            <textarea id="youtube_urls" name="youtube_urls" rows="6" placeholder="https://www.youtube.com/watch?v=...\nhttps://youtu.be/..." required></textarea>

            <label for="author">Author</label>
            <input id="author" name="author" type="text" value="Editorial Desk">

            <button class="load-more" type="submit">Process Batch</button>
        </form>
    </div>
</section>
<?php
render_footer($settings);
