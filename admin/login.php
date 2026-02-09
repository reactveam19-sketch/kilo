<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/template.php';

$db = get_db();
$settings = get_settings($db);
$errors = [];
$notice = '';
$hasAdmin = count_admin_users($db) > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'setup') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '') {
            $errors[] = 'Name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if (strlen($password) < 10) {
            $errors[] = 'Password must be at least 10 characters.';
        }

        if (!$errors) {
            create_admin_user($db, $name, $email, $password);
            $notice = 'Admin account created. Please sign in.';
            $hasAdmin = true;
        }
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $user = attempt_admin_login($db, $email, $password);
        if ($user) {
            login_admin_user($user);
            header('Location: /admin/index.php');
            exit;
        }
        $errors[] = 'Invalid email or password.';
    }
}

render_header($settings, 'Admin Login');
?>
<section class="container auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <p class="auth-eyebrow">Secure Admin Access</p>
            <h1><?= $hasAdmin ? 'Welcome back' : 'Create your admin account' ?></h1>
            <p><?= $hasAdmin ? 'Sign in to manage content, settings, and platform tools.' : 'Set up the first admin user to unlock the control center.' ?></p>
        </div>

        <?php if ($notice): ?>
            <div class="notice"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="notice notice-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!$hasAdmin): ?>
            <form class="form" method="post">
                <input type="hidden" name="action" value="setup">
                <label for="name">Full Name</label>
                <input id="name" name="name" type="text" placeholder="Alex Morgan" required>

                <label for="email">Email Address</label>
                <input id="email" name="email" type="email" placeholder="admin@example.com" required>

                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="Use at least 10 characters" required>

                <button class="button button-primary" type="submit">Create Admin</button>
            </form>
        <?php else: ?>
            <form class="form" method="post">
                <label for="email">Email Address</label>
                <input id="email" name="email" type="email" placeholder="admin@example.com" required>

                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="Enter your password" required>

                <button class="button button-primary" type="submit">Sign in</button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php
render_footer($settings);
