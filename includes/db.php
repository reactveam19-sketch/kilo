<?php
declare(strict_types=1);

function get_db(): PDO
{
    static $db = null;
    if ($db instanceof PDO) {
        return $db;
    }

    $driver = strtolower((string) getenv('DB_DRIVER'));
    if ($driver === '') {
        $driver = 'sqlite';
    }

    if ($driver === 'mysql' || $driver === 'mariadb') {
        $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
        $dbName = getenv('DB_NAME') ?: 'kilo';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASSWORD') ?: '';
        $dbPort = getenv('DB_PORT') ?: '3306';
        $dsn = sprintf('mysql:host=%s;dbname=%s;port=%s;charset=utf8mb4', $dbHost, $dbName, $dbPort);
        $db = new PDO($dsn, $dbUser, $dbPass);
    } else {
        $dbPath = getenv('DB_SQLITE_PATH') ?: __DIR__ . '/../data/site.sqlite';
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0775, true);
        }
        $db = new PDO('sqlite:' . $dbPath);
    }

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    ensure_schema($db);

    return $db;
}

function ensure_schema(PDO $db): void
{
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql') {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS articles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                meta_description TEXT,
                content_html MEDIUMTEXT NOT NULL,
                youtube_id VARCHAR(64),
                author VARCHAR(255),
                status VARCHAR(32) NOT NULL DEFAULT "published",
                published_at VARCHAR(64) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS api_keys (
                id INT AUTO_INCREMENT PRIMARY KEY,
                provider VARCHAR(64) NOT NULL,
                api_key TEXT NOT NULL,
                usage_count INT NOT NULL DEFAULT 0,
                last_error_at VARCHAR(64),
                status VARCHAR(32) NOT NULL DEFAULT "active"
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS settings (
                id TINYINT PRIMARY KEY,
                site_name VARCHAR(255) NOT NULL DEFAULT "Football News AI",
                site_tagline VARCHAR(255) NOT NULL DEFAULT "Fast, AI-generated football news",
                header_ad MEDIUMTEXT,
                body_ad MEDIUMTEXT,
                footer_ad MEDIUMTEXT,
                youtube_api_key TEXT,
                default_ai_provider VARCHAR(32) NOT NULL DEFAULT "gemini"
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                role VARCHAR(64) NOT NULL DEFAULT "admin",
                status VARCHAR(32) NOT NULL DEFAULT "active",
                created_at VARCHAR(64) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS subscribers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                status VARCHAR(32) NOT NULL DEFAULT "active",
                created_at VARCHAR(64) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS constants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                const_key VARCHAR(255) NOT NULL UNIQUE,
                const_value TEXT NOT NULL,
                description TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );
        $db->exec('INSERT IGNORE INTO settings (id) VALUES (1);');
    } else {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS articles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                meta_description TEXT,
                content_html TEXT NOT NULL,
                youtube_id TEXT,
                author TEXT,
                status TEXT NOT NULL DEFAULT "published",
                published_at TEXT NOT NULL
            );'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS api_keys (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                provider TEXT NOT NULL,
                api_key TEXT NOT NULL,
                usage_count INTEGER NOT NULL DEFAULT 0,
                last_error_at TEXT,
                status TEXT NOT NULL DEFAULT "active"
            );'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY CHECK (id = 1),
                site_name TEXT NOT NULL DEFAULT "Football News AI",
                site_tagline TEXT NOT NULL DEFAULT "Fast, AI-generated football news",
                header_ad TEXT,
                body_ad TEXT,
                footer_ad TEXT,
                youtube_api_key TEXT,
                default_ai_provider TEXT NOT NULL DEFAULT "gemini"
            );'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                role TEXT NOT NULL DEFAULT "admin",
                status TEXT NOT NULL DEFAULT "active",
                created_at TEXT NOT NULL
            );'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS subscribers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                status TEXT NOT NULL DEFAULT "active",
                created_at TEXT NOT NULL
            );'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS constants (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                const_key TEXT NOT NULL UNIQUE,
                const_value TEXT NOT NULL,
                description TEXT
            );'
        );

        $db->exec('INSERT OR IGNORE INTO settings (id) VALUES (1);');
    }

    if (!column_exists($db, 'settings', 'default_ai_provider')) {
        if ($driver === 'mysql') {
            $db->exec('ALTER TABLE settings ADD COLUMN default_ai_provider VARCHAR(32) NOT NULL DEFAULT "gemini"');
        } else {
            $db->exec('ALTER TABLE settings ADD COLUMN default_ai_provider TEXT NOT NULL DEFAULT "gemini"');
        }
    }
}

function column_exists(PDO $db, string $table, string $column): bool
{
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
        );
        $stmt->execute([':table' => $table, ':column' => $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    $stmt = $db->query('PRAGMA table_info(' . $table . ')');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $info) {
        if (($info['name'] ?? '') === $column) {
            return true;
        }
    }
    return false;
}

function get_settings(PDO $db): array
{
    $stmt = $db->query('SELECT * FROM settings WHERE id = 1');
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    return $settings ?: [];
}

function update_settings(PDO $db, array $data): void
{
    $stmt = $db->prepare(
        'UPDATE settings
        SET site_name = :site_name,
            site_tagline = :site_tagline,
            header_ad = :header_ad,
            body_ad = :body_ad,
            footer_ad = :footer_ad,
            youtube_api_key = :youtube_api_key,
            default_ai_provider = :default_ai_provider
        WHERE id = 1'
    );
    $stmt->execute([
        ':site_name' => $data['site_name'],
        ':site_tagline' => $data['site_tagline'],
        ':header_ad' => $data['header_ad'],
        ':body_ad' => $data['body_ad'],
        ':footer_ad' => $data['footer_ad'],
        ':youtube_api_key' => $data['youtube_api_key'],
        ':default_ai_provider' => $data['default_ai_provider'],
    ]);
}
