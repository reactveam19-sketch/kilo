
# Architectural Blueprint: Next-Gen AI Football News Platform
**Author:** Senior Systems Architect & SEO Engineer
**Date:** October 2023
**Version:** 2.0 (Enterprise Ready)

---

## 1. Executive Summary

This document outlines the technical specification for rebuilding the Football News Aggregator into a scalable, high-performance, and SEO-dominant platform. The goal is to move beyond a simple "script" to a robust **Content Engine** that leverages AI responsibly, prioritizes user experience (Core Web Vitals), and creates an architectural foundation capable of handling millions of visitors.

### Core Philosophy
1.  **Speed is a Feature:** sub-100ms server response times.
2.  **Content is King, Context is Queen:** AI shouldn't just summarize; it must analyze, contextualize, and link entities.
3.  **Resilience:** The system must handle API failures (YouTube/Gemini) gracefully without breaking the user experience.

---

## 2. Technical Stack

We will use a **Modern PHP** approach, adhering to PSR standards, without the overhead of a heavy full-stack framework (like Laravel) to maintain raw performance, but with enough structure to be maintainable.

*   **Language:** PHP 8.3+ (Strict Types enabled)
*   **Database:** MySQL 8.0 or MariaDB 10.6 (InnoDB engine)
*   **Caching:** Redis (Object Cache + Job Queue)
*   **Frontend:** HTML5, Alpine.js (lightweight reactivity), TailwindCSS (utility-first)
*   **Dependency Management:** Composer
*   **Server:** Nginx + PHP-FPM

### Key Libraries (via Composer)
*   `vlucas/phpdotenv`: Environment configuration.
*   `guzzlehttp/guzzle`: Robust HTTP client for API calls.
*   `bramus/router`: Lightweight, fast routing.
*   `filp/whoops`: Better error handling (Dev).
*   `monolog/monolog`: PSR-3 Logging.
*   `symfony/dependency-injection`: For IoC Container (clean architecture).

---

## 3. Directory Structure (Domain-Driven Design Lite)

We separate the "App" (Core Logic) from the "Public" (Web Entry).

```text
/
├── app/
│   ├── Config/          # Configuration loaders
│   ├── Controllers/     # HTTP Request Handlers
│   ├── Core/            # Framework base (Container, Router, Database)
│   ├── Domain/          # Business Logic
│   │   ├── Articles/    # Article Entity, Repository, Services
│   │   ├── AI/          # AI Prompt Managers, Adapters
│   │   └── Videos/      # YouTube Integration
│   ├── Middleware/      # Auth, CSRF, RateLimiting
│   └── Jobs/            # Background Tasks (GenerateArticleJob)
├── public/
│   ├── assets/
│   └── index.php        # Single Entry Point
├── storage/
│   ├── cache/
│   ├── logs/
│   └── database/        # Migrations/Seeds
├── views/               # Twig or Blade-like templates
├── .env                 # Secrets (Never committed)
├── composer.json
└── docker-compose.yml
```

---

## 4. Advanced Database Schema

We need a normalized schema that supports high-volume reads and complex relationships.

### Key Tables

1.  **`articles`**
    *   `id` (BIGINT PK)
    *   `slug` (VARCHAR, Unique, Indexed) -> *Optimized for URL lookups*
    *   `title` (VARCHAR)
    *   `seo_title` (VARCHAR) -> *For A/B testing headlines*
    *   `content_html` (LONGTEXT)
    *   `content_json` (JSON) -> *For future app API*
    *   `published_at` (DATETIME, Indexed)
    *   `status` (ENUM: 'draft', 'scheduled', 'published', 'archived')
    *   `source_video_id` (VARCHAR, Indexed)

2.  **`entities`** (The SEO Secret Weapon)
    *   `id` (INT PK)
    *   `name` (VARCHAR) -> e.g., "Lionel Messi", "Real Madrid"
    *   `type` (ENUM: 'player', 'team', 'league', 'manager')
    *   `slug` (VARCHAR)
    *   *Purpose: Enables "Entity SEO". We tag articles with entities to create Topic Clusters automatically.*

3.  **`article_entities`** (Pivot Table)
    *   `article_id`, `entity_id`

4.  **`jobs`** (Simple Queue System)
    *   `id` (BIGINT PK)
    *   `queue` (VARCHAR) -> 'default', 'high_priority'
    *   `payload` (JSON)
    *   `attempts` (INT)
    *   `available_at` (DATETIME) -> *For scheduling/retries*

5.  **`api_keys`** (Rotation & Health)
    *   `id`, `provider` ('google', 'youtube'), `key`, `usage_count`, `last_error_at`, `status`

---

## 5. The "Senior" SEO Strategy

This is where we differentiate from a generic script.

### A. Programmatic Schema.org
Every page must output dynamic JSON-LD.
*   **NewsArticle**: For the main content.
*   **VideoObject**: Embedding the YouTube metadata helps Google index the video *on your site*.
*   **BreadcrumbList**: For site structure understanding.

### B. Internal Linking Engine
When generating an article, the system should:
1.  Scan the content for known Entity names (from `entities` table).
2.  Auto-link the first occurrence of "Lionel Messi" to `/topic/lionel-messi`.
*Why?* This passes PageRank deeper into your site and establishes topical authority.

### C. Performance (Core Web Vitals)
*   **CLS (Layout Shift):** Reserve space for the YouTube iframe before it loads.
*   **LCP (Largest Contentful Paint):** The featured image must be preloaded or fetched with `fetchpriority="high"`.
*   **Caching:** Implement Full Page Caching (FPC) for logged-out users. Nginx should serve `.html` files directly if they exist.

---

## 6. Core Logic Implementation

### The "Generator" Pipeline (Async)

**Problem:** Fetching transcript + Prompting AI + Saving Image takes 10-30 seconds.
**Solution:** Background Jobs.

1.  **Admin Action:** User pastes URL -> Clicks "Generate".
2.  **Controller:** Validates URL -> Pushes `GenerateArticleJob` to Redis/Database -> Returns "Job Queued" UI immediately.
3.  **Worker (Background Process):**
    *   Picks up job.
    *   **Step 1:** Fetch YouTube Metadata & Captions (if available).
    *   **Step 2 (The Prompt):**
        *   *Role:* "Senior Sports Journalist".
        *   *Context:* Provide transcript + video title.
        *   *Instruction:* "Write an inverted-pyramid style news piece. Extract 3 key quotes. Identify players involved."
    *   **Step 3:** Gemini API Call.
    *   **Step 4:** Entity Parsing (Map extracted names to DB Entities).
    *   **Step 5:** Save to DB & Purge Cache.

---

## 7. Security & Reliability

1.  **Circuit Breaker Pattern**:
    *   If YouTube API fails 3 times in 1 minute, stop calling it for 5 minutes.
    *   Prevent cascading failures taking down the admin panel.

2.  **Key Rotation Strategy**:
    *   Middleware checks `api_keys` table.
    *   If a key hits Quota (429), mark it as `cooldown` for 1 hour and switch to the next active key automatically.

3.  **Input Sanitation**:
    *   Never trust the AI output 100%. Run all generated HTML through `HTMLPurifier` to strip potential `<script>` tags before saving.

---

## 8. Implementation Steps

### Phase 1: Foundation
1.  Initialize `composer.json`.
2.  Set up Docker environment (Nginx/PHP/MySQL).
3.  Build the Core Router and DI Container.

### Phase 2: Domain Logic
1.  Implement `YouTubeService` with Guzzle.
2.  Implement `GeminiService` with robust error handling.
3.  Create the `JobQueue` system (or use a library like `symfony/messenger` if simpler).

### Phase 3: SEO & Frontend
1.  Build the Article View with dynamic Schema.org.
2.  Implement the Entity Linking system.
3.  Set up the Sitemap generator.

### Phase 4: Admin & Polish
1.  Build the Dashboard.
2.  Add Analytics tracking.
3.  Stress test with 100 simultaneous generations.

---

## 9. Example Code Snippet: The Intelligent Prompt

```php
// app/Domain/AI/PromptFactory.php

public function createNewsPrompt(VideoDTO $video, array $transcripts): string
{
    return <<<EOT
    You are a veteran football journalist for The Athletic. 
    Draft a breaking news article based on this video: "{$video->title}".
    
    Context:
    {$transcripts}
    
    Requirements:
    1. HEADLINE: Punchy, <60 chars, includes the main entity.
    2. STRUCTURE:
       - Lede: The most important fact immediately.
       - Body: Context, analysis, and tactical breakdown.
       - Quotes: Reconstruct likely quotes based on the transcript narration.
    3. SEO DATA:
       - Return a JSON object with fields: { headline, slug_suggestion, meta_desc, content_html, entities_detected[] }.
    4. TONE: Professional, objective, yet engaging. No "In conclusion" or "Let's dive in".
    EOT;
}
```
