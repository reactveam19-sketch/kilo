<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/template.php';

require_admin();

$db = get_db();
$settings = get_settings($db);
$adminName = current_admin_user_name() ?? 'Administrator';

$articleCount = (int) $db->query('SELECT COUNT(*) FROM articles')->fetchColumn();
$subscriberCount = (int) $db->query('SELECT COUNT(*) FROM subscribers')->fetchColumn();
$apiKeyCount = (int) $db->query('SELECT COUNT(*) FROM api_keys')->fetchColumn();
$userCount = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();

render_header($settings, 'Admin Dashboard');
?>
<section class="container admin-shell">
    <div class="admin-header">
        <div>
            <p class="admin-eyebrow">Administration</p>
            <h1>Welcome back, <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p>Manage your newsroom, fine-tune automation, and keep your readers informed.</p>
        </div>
        <div class="admin-actions">
            <a class="button button-secondary" href="/admin/generate.php">Create Story</a>
            <a class="button button-primary" href="/admin/settings.php">Site Settings</a>
        </div>
    </div>

    <div class="admin-nav">
        <a href="/admin/index.php" class="is-active">Dashboard</a>
        <a href="/admin/generate.php">Magic Generator</a>
        <a href="/admin/bulk_generate.php">Bulk Generator</a>
        <a href="/admin/api_keys.php">API Keys</a>
        <a href="/admin/settings.php">Settings</a>
        <a href="/admin/users.php">Users</a>
        <a href="/admin/subscribers.php">Subscribers</a>
        <a href="/admin/constants.php">Constants</a>
        <a href="/admin/logout.php">Logout</a>
    </div>

    <div class="admin-grid">
        <div class="admin-card">
            <h3>Total Articles</h3>
            <p class="admin-metric"><?= number_format($articleCount) ?></p>
            <p class="admin-subtext">Published and draft stories across the newsroom.</p>
            <a href="/admin/generate.php">Generate a new article</a>
        </div>
        <div class="admin-card">
            <h3>Subscribers</h3>
            <p class="admin-metric"><?= number_format($subscriberCount) ?></p>
            <p class="admin-subtext">Active newsletter readers and alerts.</p>
            <a href="/admin/subscribers.php">Review subscribers</a>
        </div>
        <div class="admin-card">
            <h3>API Keys</h3>
            <p class="admin-metric"><?= number_format($apiKeyCount) ?></p>
            <p class="admin-subtext">Active integration credentials.</p>
            <a href="/admin/api_keys.php">Manage API keys</a>
        </div>
        <div class="admin-card">
            <h3>Team Access</h3>
            <p class="admin-metric"><?= number_format($userCount) ?></p>
            <p class="admin-subtext">Editors and operators on file.</p>
            <a href="/admin/users.php">Manage users</a>
        </div>
    </div>

    <div class="admin-panel">
        <h2>Admin essentials</h2>
        <p>Everything you need to keep the platform running smoothly is organized in the navigation above. Use the tools to update branding, manage subscribers, and control automation rules.</p>
    </div>
</section>
<?php
render_footer($settings);
