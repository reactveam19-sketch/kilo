<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/template.php';

$db = get_db();
$settings = get_settings($db);
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $provider = trim($_POST['provider'] ?? 'gemini');
    $apiKey = trim($_POST['api_key'] ?? '');
    if ($apiKey !== '') {
        $stmt = $db->prepare('INSERT INTO api_keys (provider, api_key) VALUES (:provider, :api_key)');
        $stmt->execute([
            ':provider' => $provider,
            ':api_key' => $apiKey,
        ]);
        $notice = 'API key saved.';
    }
}

$keys = $db->query('SELECT * FROM api_keys ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

render_header($settings, 'API Keys');
?>
<section class="container">
    <div class="admin-nav">
        <a href="/admin/index.php">Dashboard</a>
        <a href="/admin/settings.php">Settings</a>
    </div>

    <div class="form">
        <h1>API Key Manager</h1>
        <?php if ($notice): ?>
            <div class="notice"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post">
            <label for="provider">Provider</label>
            <select id="provider" name="provider">
                <option value="gemini">Gemini</option>
                <option value="youtube">YouTube</option>
            </select>

            <label for="api_key">API Key</label>
            <input id="api_key" name="api_key" type="text" required>

            <button class="load-more" type="submit">Add Key</button>
        </form>
    </div>

    <div class="article">
        <h2>Stored Keys</h2>
        <ul>
            <?php foreach ($keys as $key): ?>
                <li><?= htmlspecialchars($key['provider'], ENT_QUOTES, 'UTF-8') ?> · Usage <?= (int) $key['usage_count'] ?> · Status <?= htmlspecialchars($key['status'], ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php
render_footer($settings);
