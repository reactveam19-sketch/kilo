<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/template.php';

$db = get_db();
$settings = get_settings($db);

render_header($settings, 'Admin Dashboard');
?>
<section class="container">
    <div class="admin-nav">
        <a href="/admin/generate.php">Magic Generator</a>
        <a href="/admin/bulk_generate.php">Bulk Generator</a>
        <a href="/admin/api_keys.php">API Keys</a>
        <a href="/admin/settings.php">Settings</a>
        <a href="/admin/users.php">Users</a>
        <a href="/admin/subscribers.php">Subscribers</a>
        <a href="/admin/constants.php">Constants</a>
    </div>
    <div class="article">
        <h1>Admin Dashboard</h1>
        <p>Welcome to the AI newsroom control center. Use the tools above to create new stories, manage API keys, and configure monetization settings.</p>
    </div>
</section>
<?php
render_footer($settings);
