<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/template.php';

$db = get_db();
$settings = get_settings($db);
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'admin');
        $status = trim($_POST['status'] ?? 'active');
        if ($name !== '' && $email !== '') {
            $stmt = $db->prepare(
                'INSERT INTO users (name, email, role, status, created_at)
                VALUES (:name, :email, :role, :status, :created_at)'
            );
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':role' => $role,
                ':status' => $status,
                ':created_at' => date('c'),
            ]);
            $notice = 'User added.';
        }
    }

    if ($action === 'status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');
        if ($id > 0) {
            $stmt = $db->prepare('UPDATE users SET status = :status WHERE id = :id');
            $stmt->execute([
                ':status' => $status,
                ':id' => $id,
            ]);
            $notice = 'User status updated.';
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $notice = 'User removed.';
        }
    }
}

$users = $db->query('SELECT * FROM users ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

render_header($settings, 'User Manager');
?>
<section class="container">
    <div class="admin-nav">
        <a href="/admin/index.php">Dashboard</a>
        <a href="/admin/subscribers.php">Subscribers</a>
        <a href="/admin/constants.php">Constants</a>
    </div>

    <div class="form">
        <h1>Manage Users</h1>
        <?php if ($notice): ?>
            <div class="notice"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post">
            <label for="name">Name</label>
            <input id="name" name="name" type="text" required>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" required>

            <label for="role">Role</label>
            <select id="role" name="role">
                <option value="admin">Admin</option>
                <option value="editor">Editor</option>
                <option value="viewer">Viewer</option>
            </select>

            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="active">Active</option>
                <option value="disabled">Disabled</option>
            </select>

            <input type="hidden" name="action" value="add">
            <button class="load-more" type="submit">Add User</button>
        </form>
    </div>

    <div class="article">
        <h2>Existing Users</h2>
        <ul>
            <?php foreach ($users as $user): ?>
                <li>
                    <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>
                    · <?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>
                    · Role <?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>
                    · Status <?= htmlspecialchars($user['status'], ENT_QUOTES, 'UTF-8') ?>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="status">
                        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                        <select name="status">
                            <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>active</option>
                            <option value="disabled" <?= $user['status'] === 'disabled' ? 'selected' : '' ?>>disabled</option>
                        </select>
                        <button type="submit">Update</button>
                    </form>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                        <button type="submit" onclick="return confirm('Remove this user?')">Delete</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php
render_footer($settings);
