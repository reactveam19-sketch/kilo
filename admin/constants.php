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
        $key = trim($_POST['const_key'] ?? '');
        $value = trim($_POST['const_value'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($key !== '' && $value !== '') {
            $stmt = $db->prepare(
                'INSERT INTO constants (const_key, const_value, description)
                VALUES (:const_key, :const_value, :description)'
            );
            $stmt->execute([
                ':const_key' => $key,
                ':const_value' => $value,
                ':description' => $description,
            ]);
            $notice = 'Constant added.';
        }
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $value = trim($_POST['const_value'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($id > 0) {
            $stmt = $db->prepare(
                'UPDATE constants SET const_value = :const_value, description = :description WHERE id = :id'
            );
            $stmt->execute([
                ':const_value' => $value,
                ':description' => $description,
                ':id' => $id,
            ]);
            $notice = 'Constant updated.';
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare('DELETE FROM constants WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $notice = 'Constant removed.';
        }
    }
}

$constants = $db->query('SELECT * FROM constants ORDER BY const_key ASC')->fetchAll(PDO::FETCH_ASSOC);

render_header($settings, 'Constants Manager');
?>
<section class="container">
    <div class="admin-nav">
        <a href="/admin/index.php">Dashboard</a>
        <a href="/admin/users.php">Users</a>
        <a href="/admin/subscribers.php">Subscribers</a>
    </div>

    <div class="form">
        <h1>Manage Constants</h1>
        <?php if ($notice): ?>
            <div class="notice"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post">
            <label for="const_key">Key</label>
            <input id="const_key" name="const_key" type="text" required>

            <label for="const_value">Value</label>
            <input id="const_value" name="const_value" type="text" required>

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"></textarea>

            <input type="hidden" name="action" value="add">
            <button class="load-more" type="submit">Add Constant</button>
        </form>
    </div>

    <div class="article">
        <h2>Stored Constants</h2>
        <ul>
            <?php foreach ($constants as $constant): ?>
                <li>
                    <strong><?= htmlspecialchars($constant['const_key'], ENT_QUOTES, 'UTF-8') ?></strong>
                    · <?= htmlspecialchars($constant['const_value'], ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!empty($constant['description'])): ?>
                        · <?= htmlspecialchars($constant['description'], ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= (int) $constant['id'] ?>">
                        <input type="text" name="const_value" value="<?= htmlspecialchars($constant['const_value'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="text" name="description" value="<?= htmlspecialchars($constant['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit">Update</button>
                    </form>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $constant['id'] ?>">
                        <button type="submit" onclick="return confirm('Remove this constant?')">Delete</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php
render_footer($settings);
