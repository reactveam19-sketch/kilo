<?php
declare(strict_types=1);

function render_header(array $settings, string $title = ''): void
{
    $pageTitle = $title !== '' ? $title . ' | ' . $settings['site_name'] : $settings['site_name'];
    $tagline = htmlspecialchars($settings['site_tagline'], ENT_QUOTES, 'UTF-8');
    $siteName = htmlspecialchars($settings['site_name'], ENT_QUOTES, 'UTF-8');
    $headerAd = $settings['header_ad'] ?? '';

    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{$pageTitle}</title>
        <meta name="description" content="{$tagline}">
        <link rel="stylesheet" href="/assets/style.css">
    </head>
    <body>
        <header class="site-header">
            <div class="container">
                <a class="logo" href="/">{$siteName}</a>
                <p class="tagline">{$tagline}</p>
                <nav class="nav">
                    <a href="/">Home</a>
                    <a href="/admin/index.php">Admin</a>
                </nav>
            </div>
        </header>
    HTML;

    if (!empty($headerAd)) {
        echo '<div class="ad-block">' . $headerAd . '</div>';
    }
}

function render_footer(array $settings): void
{
    $footerAd = $settings['footer_ad'] ?? '';
    $year = date('Y');

    if (!empty($footerAd)) {
        echo '<div class="ad-block">' . $footerAd . '</div>';
    }

    echo <<<HTML
        <footer class="site-footer">
            <div class="container">
                <p>&copy; {$year} {$settings['site_name']} &middot; AI-powered football newsroom.</p>
            </div>
        </footer>
        <script src="/assets/main.js"></script>
    </body>
    </html>
    HTML;
}
