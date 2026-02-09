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
        $email = trim($_POST['email'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        if ($email !== '') {
            $stmt = $db->prepare(
                'INSERT INTO subscribers (email, status, created_at)
                VALUES (:email, :status, :created_at)'
            );
            $stmt->execute([
                ':email' => $email,
                ':status' => $status,
                ':created_at' => date('c'),
            ]);
            $notice = 'Subscriber added.';
        }
    }

    if ($action === 'status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');
        if ($id > 0) {
            $stmt = $db->prepare('UPDATE subscribers SET status = :status WHERE id = :id');
            $stmt->execute([
                ':status' => $status,
                ':id' => $id,
            ]);
            $notice = 'Subscriber status updated.';
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare('DELETE FROM subscribers WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $notice = 'Subscriber removed.';
        }
    }
}

$subscribers = $db->query('SELECT * FROM subscribers ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

render_header($settings, 'Subscriber Manager');
?>
<section class="container admin-shell">
    <div class="admin-nav">
        <a href="/admin/index.php">Dashboard</a>
        <a href="/admin/generate.php">Magic Generator</a>
        <a href="/admin/bulk_generate.php">Bulk Generator</a>
        <a href="/admin/api_keys.php">API Keys</a>
        <a href="/admin/settings.php">Settings</a>
        <a href="/admin/users.php">Users</a>
        <a href="/admin/subscribers.php" class="is-active">Subscribers</a>
        <a href="/admin/constants.php">Constants</a>
        <a href="/admin/logout.php">Logout</a>
    </div>

    <div class="form admin-panel">
        <h1>Manage Subscribers</h1>
        <?php if ($notice): ?>
            <div class="notice"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required>

            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="active">Active</option>
                <option value="paused">Paused</option>
            </select>

            <input type="hidden" name="action" value="add">
            <button class="button button-primary" type="submit">Add Subscriber</button>
        </form>
    </div>

    <div class="article admin-panel">
        <h2>Current Subscribers</h2>
        <ul>
            <?php foreach ($subscribers as $subscriber): ?>
                <li>
                    <?= htmlspecialchars($subscriber['email'], ENT_QUOTES, 'UTF-8') ?>
                    · Status <?= htmlspecialchars($subscriber['status'], ENT_QUOTES, 'UTF-8') ?>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="status">
                        <input type="hidden" name="id" value="<?= (int) $subscriber['id'] ?>">
                        <select name="status">
                            <option value="active" <?= $subscriber['status'] === 'active' ? 'selected' : '' ?>>active</option>
                            <option value="paused" <?= $subscriber['status'] === 'paused' ? 'selected' : '' ?>>paused</option>
                        </select>
                        <button type="submit">Update</button>
                    </form>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $subscriber['id'] ?>">
                        <button type="submit" onclick="return confirm('Remove this subscriber?')">Delete</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php
render_footer($settings);
