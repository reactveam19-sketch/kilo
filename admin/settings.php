<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/template.php';

$db = get_db();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    update_settings($db, [
        'site_name' => trim($_POST['site_name'] ?? 'Football News AI'),
        'site_tagline' => trim($_POST['site_tagline'] ?? ''),
        'header_ad' => trim($_POST['header_ad'] ?? ''),
        'body_ad' => trim($_POST['body_ad'] ?? ''),
        'footer_ad' => trim($_POST['footer_ad'] ?? ''),
        'youtube_api_key' => trim($_POST['youtube_api_key'] ?? ''),
        'default_ai_provider' => trim($_POST['default_ai_provider'] ?? 'gemini'),
    ]);
    $notice = 'Settings updated.';
}

$settings = get_settings($db);

render_header($settings, 'Settings');
?>
<section class="container">
    <div class="admin-nav">
        <a href="/admin/index.php">Dashboard</a>
        <a href="/admin/api_keys.php">API Keys</a>
        <a href="/admin/users.php">Users</a>
        <a href="/admin/subscribers.php">Subscribers</a>
        <a href="/admin/constants.php">Constants</a>
    </div>

    <div class="form">
        <h1>Site Settings</h1>
        <?php if ($notice): ?>
            <div class="notice"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post">
            <label for="site_name">Site Name</label>
            <input id="site_name" name="site_name" type="text" value="<?= htmlspecialchars($settings['site_name'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="site_tagline">Tagline</label>
            <input id="site_tagline" name="site_tagline" type="text" value="<?= htmlspecialchars($settings['site_tagline'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="header_ad">Header Ad Code</label>
            <textarea id="header_ad" name="header_ad" rows="3"><?= htmlspecialchars($settings['header_ad'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

            <label for="body_ad">Body Ad Code</label>
            <textarea id="body_ad" name="body_ad" rows="3"><?= htmlspecialchars($settings['body_ad'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

            <label for="footer_ad">Footer Ad Code</label>
            <textarea id="footer_ad" name="footer_ad" rows="3"><?= htmlspecialchars($settings['footer_ad'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

            <label for="youtube_api_key">YouTube API Key</label>
            <input id="youtube_api_key" name="youtube_api_key" type="text" value="<?= htmlspecialchars($settings['youtube_api_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="default_ai_provider">Default AI Provider</label>
            <select id="default_ai_provider" name="default_ai_provider">
                <option value="gemini" <?= ($settings['default_ai_provider'] ?? 'gemini') === 'gemini' ? 'selected' : '' ?>>Gemini</option>
                <option value="openai" <?= ($settings['default_ai_provider'] ?? 'gemini') === 'openai' ? 'selected' : '' ?>>ChatGPT</option>
            </select>

            <button class="load-more" type="submit">Save Settings</button>
        </form>
    </div>
</section>
<?php
render_footer($settings);
