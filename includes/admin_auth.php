<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function start_admin_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('kilo_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function is_admin_authenticated(): bool
{
    start_admin_session();
    return isset($_SESSION['admin_user_id']);
}

function require_admin(): void
{
    if (!is_admin_authenticated()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function admin_logout(): void
{
    start_admin_session();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function count_admin_users(PDO $db): int
{
    $stmt = $db->query('SELECT COUNT(*) FROM admin_users');
    return (int) $stmt->fetchColumn();
}

function create_admin_user(PDO $db, string $name, string $email, string $password): bool
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare(
        'INSERT INTO admin_users (name, email, password_hash, role, status, created_at)
        VALUES (:name, :email, :password_hash, :role, :status, :created_at)'
    );
    return $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password_hash' => $hash,
        ':role' => 'admin',
        ':status' => 'active',
        ':created_at' => date('c'),
    ]);
}

function attempt_admin_login(PDO $db, string $email, string $password): ?array
{
    $stmt = $db->prepare('SELECT * FROM admin_users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return null;
    }

    if (($user['status'] ?? 'inactive') !== 'active') {
        return null;
    }

    if (!password_verify($password, (string) $user['password_hash'])) {
        return null;
    }

    return $user;
}

function login_admin_user(array $user): void
{
    start_admin_session();
    session_regenerate_id(true);
    $_SESSION['admin_user_id'] = $user['id'];
    $_SESSION['admin_user_name'] = $user['name'];
    $_SESSION['admin_user_email'] = $user['email'];
}

function current_admin_user_name(): ?string
{
    start_admin_session();
    return $_SESSION['admin_user_name'] ?? null;
}
