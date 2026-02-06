<?php
declare(strict_types=1);

function get_db(): PDO
{
    static $db = null;
    if ($db instanceof PDO) {
        return $db;
    }

    $dbPath = __DIR__ . '/../data/site.sqlite';
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
            youtube_api_key TEXT
        );'
    );

    $db->exec('INSERT OR IGNORE INTO settings (id) VALUES (1);');

    return $db;
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
            youtube_api_key = :youtube_api_key
        WHERE id = 1'
    );
    $stmt->execute([
        ':site_name' => $data['site_name'],
        ':site_tagline' => $data['site_tagline'],
        ':header_ad' => $data['header_ad'],
        ':body_ad' => $data['body_ad'],
        ':footer_ad' => $data['footer_ad'],
        ':youtube_api_key' => $data['youtube_api_key'],
    ]);
}
