<?php

namespace App\Services\Scraper;

use App\Models\Application;
use App\Models\ApplicationImage;
use App\Models\ApplicationVersion;
use App\Models\Category;
use App\Models\Setting;
use App\Services\AI\ContentAiService;
use App\Services\Scraper\Traits\AppResolverTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CybermaniaImporter
{
    use AppResolverTrait;

    protected ContentAiService $aiService;

    public function __construct(
        protected CybermaniaClient $client,
        protected CybermaniaParser $parser,
        ?ContentAiService $aiService = null
    ) {
        $this->aiService = $aiService ?? app(ContentAiService::class);
    }

    /**
     * Get paginated CyberMania applications annotated with local database status
     *
     * @return array{items: array<int, array>, pagination: array, category: string}
     */
    public function getCybermaniaApps(int $page = 1, bool $annotate = true, string $category = 'all'): array
    {
        $html = $this->client->getCategoryHtml($category, $page);

        if (! $html) {
            return [
                'items' => [],
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => 1,
                    'has_next' => false,
                    'has_prev' => false,
                ],
                'category' => $category,
            ];
        }

        $cards = $this->parser->parseAppCards($html);
        $items = $annotate ? $this->annotateCards($cards) : $cards;
        $pagination = $this->parser->parsePagination($html, $page);

        return [
            'items' => $items,
            'pagination' => $pagination,
            'category' => $category,
        ];
    }

    /**
     * Search CyberMania in real-time with local database annotation
     *
     * @return array{items: array<int, array>, pagination: array, query: string}
     */
    public function searchCybermania(string $query, int $page = 1, bool $annotate = true): array
    {
        $html = $this->client->search($query, $page);

        if (! $html) {
            return [
                'items' => [],
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => 1,
                    'has_next' => false,
                    'has_prev' => false,
                ],
                'query' => $query,
            ];
        }

        $cards = $this->parser->parseAppCards($html);
        $items = $annotate ? $this->annotateCards($cards) : $cards;
        $pagination = $this->parser->parsePagination($html, $page);

        return [
            'items' => $items,
            'pagination' => $pagination,
            'query' => $query,
        ];
    }

    /**
     * Annotate parsed cards with local database status (already imported, version, has_update)
     *
     * @param  array<int, array>  $cards
     * @return array<int, array>
     */
    protected function annotateCards(array $cards): array
    {
        if (empty($cards)) {
            return [];
        }

        $results = [];

        $slugs = array_values(array_filter(array_column($cards, 'slug')));
        $names = array_values(array_filter(array_column($cards, 'name')));
        $cleanNames = array_values(array_filter(array_column($cards, 'clean_name')));

        $existingBySlug = Application::whereIn('slug', $slugs)->get(['id', 'slug', 'name', 'version'])->keyBy('slug');
        $existingByName = Application::whereIn('name', array_merge($names, $cleanNames))->get(['id', 'slug', 'name', 'version'])->keyBy('name');

        foreach ($cards as $card) {
            $existing = $existingBySlug->get($card['slug'])
                ?? $existingByName->get($card['clean_name'] ?? '')
                ?? $existingByName->get($card['name'] ?? '');

            $hasUpdate = false;

            if ($existing && $card['version'] && $existing->version) {
                $hasUpdate = version_compare($card['version'], $existing->version, '>');
            }

            $card['already_imported'] = (bool) $existing;
            $card['local_app_id'] = $existing?->id;
            $card['local_version'] = $existing?->version;
            $card['has_update'] = $hasUpdate;

            $results[] = $card;
        }

        return $results;
    }

    /**
     * Import a single application from CyberMania by its URL
     */
    public function importAppByUrl(string $url, bool $downloadImages = true, ?bool $forcePublished = null): ?Application
    {
        $html = $this->client->fetchHtml($url, false);
        if (! $html) {
            Log::error("CybermaniaImporter: Could not fetch HTML from {$url}");

            return null;
        }

        $appData = $this->parser->parseAppDetail($html, $url);
        if (! $appData) {
            Log::error("CybermaniaImporter: Could not parse details from {$url}");

            return null;
        }

        // 1. Resolve Target Category in PichoDescargas (Strict mapping to programas, juegos-pc, or apps-android)
        $targetSlug = $appData['category_slug'] ?? 'programas';
        $category = Category::where('slug', $targetSlug)->first();
        if (! $category) {
            $catNames = [
                'programas' => 'Programas',
                'juegos-pc' => 'Juegos PC',
                'apps-android' => 'Apps Android',
            ];
            $category = Category::firstOrCreate(
                ['slug' => $targetSlug],
                [
                    'name' => $catNames[$targetSlug] ?? ($appData['category_name'] ?? ucfirst($targetSlug)),
                    'description' => "Descargas para {$targetSlug}",
                    'icon' => $targetSlug,
                    'is_active' => true,
                    'sort_order' => match ($targetSlug) {
                        'programas' => 1,
                        'apps-android' => 3,
                        'juegos-pc' => 4,
                        default => 5,
                    },
                ]
            );
        }

        // 2. Resolve Existing Application via 4-tier deduplication
        $existing = $this->resolveExistingApplication($appData);

        // 3. Download Icon
        $iconLocalPath = null;
        $needsIcon = $downloadImages && ! empty($appData['icon_url']) && (! $existing || empty($existing->icon));

        if ($needsIcon) {
            $iconLocalPath = $this->downloadAndStoreImage($appData['icon_url'], "{$appData['slug']}-icon");
        }

        // 4. Content AI Enrichment (optional, graceful fallback to scraped description)
        $description = $appData['description'];
        $features = $appData['features'];

        if ($this->aiService->isEnabled() && ! empty($appData['name'])) {
            try {
                $aiContent = $this->aiService->generateAppContent(
                    name: $appData['name'],
                    category: $category->name,
                    version: $appData['version'] ?? null,
                    shortDesc: $appData['short_description'] ?? null,
                    scrapedDesc: $appData['description'] ?? null,
                    categorySlug: $category->slug
                );

                if (! empty($aiContent['description'])) {
                    $description = $aiContent['description'];
                }
                if (! empty($aiContent['features'])) {
                    $features = $aiContent['features'];
                }
            } catch (\Throwable $e) {
                Log::warning("CybermaniaImporter: AI enrichment skipped: {$e->getMessage()}");
            }
        } else {
            // Clean, concise ~4-line editorial fallback with internal SEO linking when AI is not yet configured
            $rawText = strip_tags($appData['short_description'] ?: $appData['description']);
            $words = preg_split('/\s+/', trim($rawText));
            if (count($words) > 65) {
                $trimmed = implode(' ', array_slice($words, 0, 58)).'...';
            } else {
                $trimmed = $rawText;
            }
            $catUrl = "/category/{$category->slug}";
            $description = '<p>'.htmlspecialchars($trimmed).' Explore more verified software in <a href="'.$catUrl.'">'.htmlspecialchars($category->name).'</a>.</p>';
        }

        // Format features as HTML string if array
        $featuresHtml = null;
        if (! empty($features)) {
            $featuresHtml = is_array($features)
                ? '<ul><li>'.implode('</li><li>', array_map('htmlspecialchars', $features)).'</li></ul>'
                : (string) $features;
        }

        // 5. Update or Create Application
        $application = $existing ?? Application::where('slug', $appData['slug'])->first();

        $isDirectUrl = ! empty($appData['download_url']) && str_starts_with($appData['download_url'], 'http');
        $magnetLink = $appData['torrent_magnet'] ?? null;
        $hasTorrent = ! empty($magnetLink);

        // Format clean download mirrors from parsed links
        $cleanMirrors = [];
        if (! empty($appData['download_links'])) {
            foreach ($appData['download_links'] as $dl) {
                if ($dl['type'] === 'direct' && ! empty($dl['url'])) {
                    $cleanMirrors[] = [
                        'name' => $dl['name'] ?? 'Direct Mirror',
                        'host' => $dl['name'] ?? 'Direct Mirror',
                        'url' => $dl['url'],
                    ];
                }
            }
        }

        // Optimal 160-char SEO meta description
        $shortDesc = ! empty($appData['short_description'])
            ? Str::limit(strip_tags($appData['short_description']), 160)
            : Str::limit(strip_tags($description), 160);

        $payload = [
            'category_id' => $category->id,
            'name' => $appData['clean_name'],
            'short_description' => $shortDesc,
            'description' => $description,
            'changelog' => null,
            'features' => $featuresHtml,
            'version' => $appData['version'],
            'size' => $appData['size'],
            'platform' => $appData['platform'],
            'developer' => $appData['developer'] ?? 'CyberMania',
            'license' => 'Full / Activated',
            'download_url_external' => $isDirectUrl ? $appData['download_url'] : null,
            'download_mirrors' => ! empty($cleanMirrors) ? $cleanMirrors : null,
            'magnet_link' => $magnetLink,
            'has_torrent' => $hasTorrent,
            'published' => $existing ? (bool) $existing->published : ($forcePublished !== null ? $forcePublished : (! (bool) Setting::get('scraper_drip_feed_mode', false))),
            'released_at' => now(),
        ];

        if ($iconLocalPath) {
            $payload['icon'] = $iconLocalPath;
        }

        // Schema.org review stars rating and count for Google Rich Snippets
        if (! $existing || empty($existing->rating)) {
            $payload['rating'] = round(4.5 + (abs(crc32($appData['name'])) % 5) / 10, 1);
            $payload['reviews_count'] = 18 + (abs(crc32($appData['name'])) % 32);
        }

        $isVersionUpgrade = $existing && ! empty($appData['version']) && ! empty($existing->version) && ($appData['version'] !== $existing->version);

        if ($application) {
            $application->update($payload);
            $application->touch(); // Siempre actualiza updated_at para que vuelva a salir en Home al actualizarse
        } else {
            $payload['slug'] = $appData['slug'];
            $application = Application::create($payload);
        }

        // 6. Download Screenshots & Set Primary Screenshot for OG/Rich Snippets
        $needsScreenshots = $downloadImages
            && ! empty($appData['screenshots'])
            && (! $existing || $existing->version !== $appData['version'] || $existing->images->isEmpty());

        if ($needsScreenshots) {
            if ($existing && $existing->images->isNotEmpty()) {
                foreach ($existing->images as $oldImg) {
                    if (! empty($oldImg->path) && Storage::disk('public')->exists($oldImg->path)) {
                        Storage::disk('public')->delete($oldImg->path);
                    }
                }
            }

            $application->images()->delete();

            $firstScreenshot = null;
            foreach (array_slice($appData['screenshots'], 0, 6) as $index => $screenshotUrl) {
                $screenPath = $this->downloadAndStoreImage($screenshotUrl, "{$application->slug}-screen-{$index}");
                if ($screenPath) {
                    ApplicationImage::create([
                        'application_id' => $application->id,
                        'path' => $screenPath,
                        'alt' => "{$application->name} Screenshot ".($index + 1),
                        'is_primary' => $index === 0,
                        'sort_order' => $index,
                    ]);

                    if ($firstScreenshot === null) {
                        $firstScreenshot = $screenPath;
                    }
                }
            }

            if ($firstScreenshot && empty($application->screenshot)) {
                $application->update(['screenshot' => $firstScreenshot]);
            }
        }

        // 7. Store Application Version Record
        if ($appData['version'] && ($appData['download_url'] || $appData['torrent_magnet'])) {
            $application->versions()->where('version', '!=', $appData['version'])->update(['is_current' => false]);

            ApplicationVersion::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'version' => $appData['version'],
                ],
                [
                    'download_url' => $appData['download_url'],
                    'download_mirrors' => ! empty($cleanMirrors) ? $cleanMirrors : null,
                    'magnet_link' => $appData['torrent_magnet'],
                    'size' => $appData['size'],
                    'is_current' => true,
                    'released_at' => now(),
                ]
            );
        }

        // 8. Concise SEO Tags Synchronization (Exact 3 Top Tags)
        $seoTags = [];
        // 1. Category name
        $seoTags[] = $category->name;

        // 2. Platform tag
        if (! empty($appData['platform'])) {
            $seoTags[] = $appData['platform'];
        }

        // 3. Category descriptor or top specific tag
        if (! empty($appData['tags']) && count($appData['tags']) > 0) {
            foreach ($appData['tags'] as $pt) {
                if (! in_array($pt, $seoTags) && strlen($pt) < 30) {
                    $seoTags[] = $pt;
                    break;
                }
            }
        }

        if (count($seoTags) < 3) {
            if ($category->slug === 'apps-android') {
                $seoTags[] = 'Android APK';
            } elseif ($category->slug === 'juegos-pc') {
                $seoTags[] = 'PC Game';
            } else {
                $seoTags[] = 'Windows Software';
            }
        }

        $this->syncAppTags($application, array_slice(array_values(array_unique($seoTags)), 0, 3), true);

        return $application->fresh();
    }

    /**
     * Scan recent pages on CyberMania for updates of existing applications
     */
    public function getPendingUpdates(int $maxPages = 2, string $category = 'all'): array
    {
        $pending = [];
        $seenSlugs = [];

        for ($p = 1; $p <= $maxPages; $p++) {
            $data = $this->getCybermaniaApps($p, true, $category);
            $cards = $data['items'] ?? [];

            foreach ($cards as $card) {
                if (! empty($card['has_update']) && ! in_array($card['slug'], $seenSlugs)) {
                    $seenSlugs[] = $card['slug'];
                    $pending[] = $card;
                }
            }
        }

        return $pending;
    }

    /**
     * Synchronize latest additions from CyberMania.
     * When $category is 'all' or 'balanced', it balances imports across CyberMania's
     * 3 primary categories: 'appz' (Programs), 'games' (PC Games), and 'android' (Android Apps)
     * with cascading spillover so all categories are consistently imported.
     *
     * @return array{imported: int, updated: int, skipped: int, failed: int, items: array}
     */
    public function syncLatestUpdates(bool $publishImmediately = true, int $limit = 10, int $pages = 2, string $category = 'all'): array
    {
        if ($category === 'all' || $category === 'balanced') {
            return $this->syncMultiCategoryUpdates($publishImmediately, $limit, $pages);
        }

        return $this->syncSingleCategoryUpdates($publishImmediately, $limit, $pages, $category);
    }

    /**
     * Multi-category balanced sync across Programs (appz), PC Games (games), and Android Apps (android)
     *
     * @return array{imported: int, updated: int, skipped: int, failed: int, items: array}
     */
    public function syncMultiCategoryUpdates(bool $publishImmediately = true, int $limit = 10, int $pages = 2): array
    {
        // 3 primary CyberMania sections
        $categories = [
            'appz' => 'Programs',
            'games' => 'PC Games',
            'android' => 'Android Apps',
        ];

        // Calculate balanced distribution across the 3 sections
        // e.g. for limit 10: appz = 4, games = 3, android = 3
        $appzQuota = (int) ceil($limit / 3);
        $gamesQuota = (int) ceil(($limit - $appzQuota) / 2);
        $androidQuota = max(0, $limit - $appzQuota - $gamesQuota);

        $quotas = [
            'appz' => $appzQuota,
            'games' => $gamesQuota,
            'android' => $androidQuota,
        ];

        $totalImported = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalFailed = 0;
        $allItems = [];
        $spillover = 0;

        foreach ($quotas as $catSlug => $baseQuota) {
            $catTarget = $baseQuota + $spillover;
            $spillover = 0;

            if ($catTarget <= 0 && ($totalImported + $totalUpdated) < $limit) {
                $catTarget = 1;
            }

            if ($catTarget <= 0) {
                continue;
            }

            $catResult = $this->syncSingleCategoryUpdates($publishImmediately, $catTarget, $pages, $catSlug);

            $catNew = (int) ($catResult['imported'] ?? 0);
            $totalImported += $catNew;
            $totalUpdated += (int) ($catResult['updated'] ?? 0);
            $totalSkipped += (int) ($catResult['skipped'] ?? 0);
            $totalFailed += (int) ($catResult['failed'] ?? 0);
            $allItems = array_merge($allItems, $catResult['items'] ?? []);

            if ($catNew < $catTarget) {
                $spillover += ($catTarget - $catNew);
            }
        }

        // Final pass: If quota remains unfilled, attempt fallback sweep from main catalog
        $remainingQuota = $limit - ($totalImported + $totalUpdated);
        if ($remainingQuota > 0) {
            $fallbackResult = $this->syncSingleCategoryUpdates($publishImmediately, $remainingQuota, $pages, 'all');
            $totalImported += (int) ($fallbackResult['imported'] ?? 0);
            $totalUpdated += (int) ($fallbackResult['updated'] ?? 0);
            $totalSkipped += (int) ($fallbackResult['skipped'] ?? 0);
            $totalFailed += (int) ($fallbackResult['failed'] ?? 0);
            $allItems = array_merge($allItems, $fallbackResult['items'] ?? []);
        }

        return [
            'imported' => $totalImported,
            'updated' => $totalUpdated,
            'skipped' => $totalSkipped,
            'failed' => $totalFailed,
            'items' => $allItems,
        ];
    }

    /**
     * Synchronize updates for a single category
     *
     * @return array{imported: int, updated: int, skipped: int, failed: int, items: array}
     */
    public function syncSingleCategoryUpdates(bool $publishImmediately = true, int $limit = 10, int $pages = 2, string $category = 'all'): array
    {
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $items = [];

        for ($p = 1; $p <= $pages; $p++) {
            if ($imported + $updated >= $limit) {
                break;
            }

            $data = $this->getCybermaniaApps($p, true, $category);
            $cards = $data['items'] ?? [];

            foreach ($cards as $card) {
                if ($imported + $updated >= $limit) {
                    break;
                }

                if ($card['already_imported'] && ! $card['has_update']) {
                    $skipped++;

                    continue;
                }

                try {
                    $app = $this->importAppByUrl($card['url'], true, $publishImmediately);
                    if ($app) {
                        if ($card['has_update']) {
                            $updated++;
                            $card['action'] = 'updated';
                        } else {
                            $imported++;
                            $card['action'] = 'imported';
                        }
                        $items[] = $card;
                    } else {
                        $failed++;
                    }
                } catch (\Throwable $e) {
                    Log::error("CybermaniaImporter: Failed to import {$card['url']}: {$e->getMessage()}");
                    $failed++;
                }

                // Friendly delay between detail requests
                usleep(400000);
            }
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
            'items' => $items,
        ];
    }

    /**
     * Download and save remote image to public storage
     */
    protected function downloadAndStoreImage(string $url, string $filenamePrefix): ?string
    {
        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
                    'Referer' => 'https://www.cybermania.ws/',
                ])
                ->timeout(15)
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $body = $response->body();
            if (empty($body) || strlen($body) < 100) {
                return null;
            }

            // Determine image extension
            $contentType = $response->header('Content-Type');
            $ext = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif') => 'gif',
                str_contains($contentType, 'svg') => 'svg',
                default => 'jpg',
            };

            $safeFilename = Str::slug($filenamePrefix).'-'.substr(md5($url.time()), 0, 8).'.'.$ext;
            $path = 'apps/icons/'.$safeFilename;

            Storage::disk('public')->put($path, $body);

            return $path;
        } catch (\Throwable $e) {
            Log::warning("CybermaniaImporter: Failed to download image {$url}: {$e->getMessage()}");

            return null;
        }
    }
}
