# Football News AI (PHP)

A lightweight PHP 8.2+ football news platform that turns YouTube coverage into AI-assisted articles.

## Features
- **Homepage grid** with lazy-loaded cards and an infinite-style “Load more” endpoint.
- **Article page** with embedded YouTube video, JSON-LD schema, and related articles.
- **Magic Generator** to turn a YouTube URL into a published article.
- **Bulk Generator** for processing multiple video URLs at once.
- **Admin settings** for site branding, ad placement, AI provider defaults, and API key storage.
- **Admin panel** to manage users, subscribers, constants, and API keys.

## Quick Start
```bash
php -S localhost:8000 router.php
```

Then visit:
- `http://localhost:8000/`
- `http://localhost:8000/admin/index.php`

## Storage
SQLite is used by default (`data/site.sqlite`). The database and tables are created automatically on first run.

To switch to MySQL/MariaDB, set environment variables before starting PHP:

```bash
export DB_DRIVER=mysql
export DB_HOST=127.0.0.1
export DB_NAME=kilo
export DB_USER=root
export DB_PASSWORD=secret
export DB_PORT=3306
```
