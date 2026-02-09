<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/template.php';

require_admin();

$db = get_db();
$settings = get_settings($db);
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    if ($action === 'add') {
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

    if ($action === 'status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');
        if ($id > 0) {
            $stmt = $db->prepare('UPDATE api_keys SET status = :status WHERE id = :id');
            $stmt->execute([
                ':status' => $status,
                ':id' => $id,
            ]);
            $notice = 'Key status updated.';
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare('DELETE FROM api_keys WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $notice = 'API key removed.';
        }
    }
}

$keys = $db->query('SELECT * FROM api_keys ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

render_header($settings, 'API Keys');
?>
<section class="container admin-shell">
    <div class="admin-nav">
        <a href="/admin/index.php">Dashboard</a>
        <a href="/admin/generate.php">Magic Generator</a>
        <a href="/admin/bulk_generate.php">Bulk Generator</a>
        <a href="/admin/api_keys.php" class="is-active">API Keys</a>
        <a href="/admin/settings.php">Settings</a>
        <a href="/admin/users.php">Users</a>
        <a href="/admin/subscribers.php">Subscribers</a>
        <a href="/admin/constants.php">Constants</a>
        <a href="/admin/logout.php">Logout</a>
    </div>

    <div class="form admin-panel">
        <h1>API Key Manager</h1>
        <?php if ($notice): ?>
            <div class="notice"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post">
            <label for="provider">Provider</label>
            <select id="provider" name="provider">
                <option value="gemini">Gemini</option>
                <option value="openai">ChatGPT</option>
                <option value="youtube">YouTube</option>
            </select>

            <label for="api_key">API Key</label>
            <input id="api_key" name="api_key" type="text" required>

            <button class="button button-primary" type="submit">Add Key</button>
            <input type="hidden" name="action" value="add">
        </form>
    </div>

    <div class="article admin-panel">
        <h2>Stored Keys</h2>
        <ul>
            <?php foreach ($keys as $key): ?>
                <li>
                    <?= htmlspecialchars($key['provider'], ENT_QUOTES, 'UTF-8') ?>
                    · Usage <?= (int) $key['usage_count'] ?>
                    · Status <?= htmlspecialchars($key['status'], ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!empty($key['last_error_at'])): ?>
                        · Last error <?= htmlspecialchars($key['last_error_at'], ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="status">
                        <input type="hidden" name="id" value="<?= (int) $key['id'] ?>">
                        <select name="status">
                            <option value="active" <?= $key['status'] === 'active' ? 'selected' : '' ?>>active</option>
                            <option value="paused" <?= $key['status'] === 'paused' ? 'selected' : '' ?>>paused</option>
                        </select>
                        <button type="submit">Update</button>
                    </form>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $key['id'] ?>">
                        <button type="submit" onclick="return confirm('Remove this key?')">Delete</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php
render_footer($settings);
