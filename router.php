<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$path = rtrim($path, '/');

if ($path === '') {
    $path = '/';
}

if ($path === '/') {
    require __DIR__ . '/index.php';
    exit;
}

if (preg_match('#^/article/([a-z0-9-]+)$#', $path, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/article.php';
    exit;
}

if (str_starts_with($path, '/admin')) {
    $target = __DIR__ . $path;
    if (is_file($target)) {
        require $target;
        exit;
    }
}

if (str_starts_with($path, '/api')) {
    $target = __DIR__ . $path;
    if (is_file($target)) {
        require $target;
        exit;
    }
}

$target = __DIR__ . $path;
if (is_file($target)) {
    return false;
}

http_response_code(404);
require __DIR__ . '/index.php';
