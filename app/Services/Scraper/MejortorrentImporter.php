<?php

namespace App\Services\Scraper;

use App\Models\Application;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Tag;
use App\Services\Storage\CloudflareR2Service;
use App\Services\Storage\TorrentStorageService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MejortorrentImporter
{
    public function __construct(
        protected MejortorrentClient $client,
        protected MejortorrentParser $parser,
        protected TorrentStorageService $torrentStorage,
        protected CloudflareR2Service $r2Service
    ) {}

    /**
     * Resolve the 'peliculas-gratis' Category model
     */
    public function resolveCategory(): Category
    {
        return Category::firstOrCreate(
            ['slug' => 'peliculas-gratis'],
            [
                'name' => 'Free Movies',
                'description' => 'Full HD movies, releases and cinema streams',
                'icon' => 'film',
                'sort_order' => 5,
                'is_active' => true,
            ]
        );
    }

    /**
     * Resolve the 'series-tv' Category model
     */
    public function resolveSeriesCategory(): Category
    {
        return Category::firstOrCreate(
            ['slug' => 'series-tv'],
            [
                'name' => 'TV Series',
                'description' => 'Complete TV series, full seasons, and high-definition episodes.',
                'icon' => 'tv',
                'sort_order' => 6,
                'is_active' => true,
            ]
        );
    }

    /**
     * Determine storage disk for torrent files ('local' / 'public' vs 'r2')
     */
    protected function getStorageDisk(): string
    {
        $target = Setting::get('torrent_storage_disk', Setting::get('torrentmac_storage_disk', 'local'));

        if ($target === 'r2' && ! $this->r2Service->isConfigured()) {
            return 'public';
        }

        return $target === 'r2' ? 'r2' : 'public';
    }

    /**
     * Import a movie by its detail URL
     */
    public function importMovieByUrl(string $url, bool $downloadImages = true, ?bool $forcePublished = null): ?Application
    {
        $html = $this->client->fetchHtml($url, false);
        if (! $html) {
            Log::error("MejortorrentImporter: Failed to fetch HTML for {$url}");

            return null;
        }

        $data = $this->parser->parseMovieDetail($html, $url);
        if (! $data) {
            Log::warning("MejortorrentImporter: Failed to parse movie data from {$url}");

            return null;
        }

        $category = $this->resolveCategory();

        // 1. Check for existing application by slug or clean_title
        $existing = Application::where('category_id', $category->id)
            ->where(function ($q) use ($data) {
                $q->where('slug', $data['slug'])
                    ->orWhere('name', $data['clean_title'])
                    ->orWhere('name', $data['title']);
            })
            ->first();

        // 2. PoW Challenge & Direct .torrent File Download
        $torrentStorageData = null;
        try {
            $challenge = $this->client->requestPowChallenge($data['content_id'], $data['tabla']);
            if ($challenge) {
                $nonce = $this->client->solvePoW($challenge, 3);
                $downloadUrl = $this->client->validatePow($challenge, $nonce, $data['content_id']);

                if ($downloadUrl) {
                    $torrentBinary = $this->client->downloadBinary($downloadUrl, $url);
                    if ($torrentBinary && strlen($torrentBinary) > 100) {
                        $brandedFilename = $this->torrentStorage->generateBrandedFilename(
                            $data['title'],
                            $data['quality'],
                            $downloadUrl,
                            false // isMac = false (do not append _macOS)
                        );

                        $disk = $this->getStorageDisk();
                        $storagePath = "torrents/{$brandedFilename}";

                        // Always save to public storage
                        Storage::disk('public')->put($storagePath, $torrentBinary);
                        $publicUrl = asset("storage/{$storagePath}");

                        // If R2 is enabled, also replicate to R2
                        if ($disk === 'r2') {
                            $r2Url = $this->r2Service->uploadContent(
                                $torrentBinary,
                                $storagePath,
                                'application/x-bittorrent',
                                [
                                    'title' => $data['title'],
                                    'quality' => $data['quality'],
                                    'source' => 'PichoDescargas-Movies',
                                ]
                            );
                            if ($r2Url) {
                                $publicUrl = $r2Url;
                            }
                        }

                        $torrentStorageData = [
                            'path' => $storagePath,
                            'public_url' => $publicUrl,
                            'filename' => $brandedFilename,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("MejortorrentImporter: Error solving PoW / storing torrent for {$url}: {$e->getMessage()}");
        }

        // 3. Download & Store Poster
        $posterPath = null;
        if ($downloadImages && ! empty($data['poster_url'])) {
            $posterPath = $this->downloadAndStorePoster($data['poster_url'], $data['slug'], $url);
        }

        // 4. Build application payload
        $dripMode = filter_var(Setting::get('scraper_drip_feed_mode', false), FILTER_VALIDATE_BOOLEAN);
        $shouldPublish = $existing
            ? (bool) $existing->published
            : ($forcePublished !== null ? $forcePublished : ! $dripMode);

        $payload = [
            'category_id' => $category->id,
            'name' => $existing ? $existing->name : $data['clean_title'],
            'description' => $data['description_html'],
            'short_description' => Str::limit($data['synopsis'], 160),
            'version' => $data['quality'],
            'size' => $data['quality'],
            'platform' => 'All',
            'has_torrent' => true,
            'published' => $shouldPublish,
            'released_at' => $existing ? $existing->released_at : now(),
        ];

        if ($torrentStorageData) {
            $payload['torrent_url'] = $torrentStorageData['public_url'];
            $payload['torrent_file_path'] = $torrentStorageData['path'];
        }

        if ($posterPath) {
            $payload['icon'] = $posterPath;
            $payload['screenshot'] = $posterPath;
        }

        if ($existing) {
            $existing->update($payload);
            $existing->touch();
            $application = $existing;
        } else {
            $payload['slug'] = $data['slug'];
            $application = Application::create($payload);
        }

        // 5. Version record
        $application->versions()->updateOrCreate(
            ['application_id' => $application->id, 'version' => $data['quality']],
            [
                'torrent_url' => $payload['torrent_url'] ?? null,
                'torrent_file_path' => $payload['torrent_file_path'] ?? null,
                'size' => $data['quality'],
                'is_current' => true,
                'released_at' => now(),
            ]
        );

        // 6. Assign Tags
        $tags = ['Películas', 'Torrents', $data['quality'], 'Castellano'];
        if ($data['year']) {
            $tags[] = (string) $data['year'];
        }
        $this->syncTags($application, $tags);

        return $application;
    }

    /**
     * Get paginated movies catalog annotated with local database status
     *
     * @return array{items: array<int, array>, pagination: array, quality: string}
     */
    public function getMoviesCatalog(int $page = 1, bool $annotate = true, string $quality = 'all'): array
    {
        $html = $this->client->getMoviesCatalogHtml($page, $quality);
        if (! $html) {
            return [
                'items' => [],
                'pagination' => ['current_page' => $page, 'total_pages' => 1, 'has_next' => false, 'has_prev' => false],
                'quality' => $quality,
            ];
        }

        $cards = $this->parser->parseMovieCards($html, $this->client->getBaseUrl());
        $items = $annotate ? $this->annotateCards($cards) : $cards;
        $pagination = $this->parser->parsePagination($html, $page);

        return [
            'items' => $items,
            'pagination' => $pagination,
            'quality' => $quality,
        ];
    }

    /**
     * Annotate parsed movie cards with local database status
     *
     * @param  array<int, array>  $cards
     * @return array<int, array>
     */
    protected function annotateCards(array $cards): array
    {
        if (empty($cards)) {
            return [];
        }

        $category = $this->resolveCategory();
        $slugs = array_values(array_filter(array_column($cards, 'slug')));
        $titles = array_values(array_filter(array_column($cards, 'title')));

        $existingBySlug = Application::where('category_id', $category->id)
            ->whereIn('slug', $slugs)
            ->get(['id', 'slug', 'name', 'has_torrent', 'published'])
            ->keyBy('slug');

        $existingByName = Application::where('category_id', $category->id)
            ->whereIn('name', $titles)
            ->get(['id', 'slug', 'name', 'has_torrent', 'published'])
            ->keyBy('name');

        $results = [];
        foreach ($cards as $card) {
            $existing = $existingBySlug->get($card['slug'])
                ?? $existingByName->get($card['title']);

            $card['is_imported'] = (bool) $existing;
            $card['existing_app_id'] = $existing?->id;
            $card['published'] = $existing ? (bool) $existing->published : false;
            $results[] = $card;
        }

        return $results;
    }

    /**
     * Download poster image and store locally in storage/app/public/posts/
     */
    protected function downloadAndStorePoster(string $imageUrl, string $slug, ?string $referer = null): ?string
    {
        try {
            $binary = $this->client->downloadBinary($imageUrl, $referer);
            if (! $binary || strlen($binary) < 500) {
                return null;
            }

            $extension = 'webp';
            if (preg_match('/\.(jpg|jpeg|png|webp)/i', parse_url($imageUrl, PHP_URL_PATH), $m)) {
                $extension = strtolower($m[1]);
            }

            $filename = "posts/{$slug}-poster.{$extension}";
            Storage::disk('public')->put($filename, $binary);

            return $filename;
        } catch (\Throwable $e) {
            Log::warning("MejortorrentImporter: Failed to download poster from {$imageUrl}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Synchronize latest movies from the catalog (used by the cron)
     *
     * @return array{imported: int, updated: int, movies: array<int, Application>}
     */
    public function syncLatestMovies(bool $autoPublish = true, int $limit = 6, int $maxPages = 2, string $quality = 'all'): array
    {
        $imported = 0;
        $updated = 0;
        $processedMovies = [];

        $dripMode = filter_var(Setting::get('scraper_drip_feed_mode', false), FILTER_VALIDATE_BOOLEAN);
        $forcePublished = $dripMode ? false : $autoPublish;

        for ($page = 1; $page <= $maxPages; $page++) {
            if ($imported >= $limit) {
                break;
            }

            $html = $this->client->getMoviesCatalogHtml($page, $quality);
            if (! $html) {
                continue;
            }

            $cards = $this->parser->parseMovieCards($html, $this->client->getBaseUrl());
            if (empty($cards)) {
                break;
            }

            foreach ($cards as $card) {
                if ($imported >= $limit) {
                    break;
                }

                // Check if already exists in DB
                $category = $this->resolveCategory();
                $exists = Application::where('category_id', $category->id)
                    ->where(function ($q) use ($card) {
                        $q->where('slug', $card['slug'])
                            ->orWhere('name', 'like', $card['title'].'%');
                    })
                    ->exists();

                if ($exists) {
                    continue;
                }

                $movie = $this->importMovieByUrl($card['url'], true, $forcePublished);
                if ($movie) {
                    $imported++;
                    $processedMovies[] = $movie;
                }
            }
        }

        Setting::set('mejortorrent_last_sync', now()->format('Y-m-d H:i:s'), 'scraper');

        return [
            'imported' => $imported,
            'updated' => $updated,
            'movies' => $processedMovies,
        ];
    }

    /**
     * Import a batch of older movies for the Draft Backlog queue
     *
     * @return array{imported: int, page_used: int}
     */
    public function importBacklogBatch(int $limit = 6, bool $asDraft = true, bool $incrementPage = true, string $quality = 'all'): array
    {
        $currentPage = (int) Setting::get('scraper_mejortorrent_backlog_page', 2);
        $imported = 0;

        $html = $this->client->getMoviesCatalogHtml($currentPage, $quality);
        if ($html) {
            $cards = $this->parser->parseMovieCards($html, $this->client->getBaseUrl());
            $category = $this->resolveCategory();

            foreach ($cards as $card) {
                if ($imported >= $limit) {
                    break;
                }

                $exists = Application::where('category_id', $category->id)
                    ->where('slug', $card['slug'])
                    ->exists();

                if (! $exists) {
                    $movie = $this->importMovieByUrl($card['url'], true, ! $asDraft);
                    if ($movie) {
                        $imported++;
                    }
                }
            }
        }

        if ($incrementPage) {
            Setting::set('scraper_mejortorrent_backlog_page', $currentPage + 1, 'scraper');
        }

        return [
            'imported' => $imported,
            'page_used' => $currentPage,
        ];
    }

    /**
     * Get paginated series catalog annotated with local database status
     *
     * @return array{items: array<int, array>, pagination: array, quality: string}
     */
    public function getSeriesCatalog(int $page = 1, bool $annotate = true, string $quality = 'all'): array
    {
        $html = $this->client->getSeriesCatalogHtml($page, $quality);
        if (! $html) {
            return [
                'items' => [],
                'pagination' => ['current_page' => $page, 'total_pages' => 1, 'has_next' => false, 'has_prev' => false],
                'quality' => $quality,
            ];
        }

        $cards = $this->parser->parseSeriesCards($html, $this->client->getBaseUrl());
        $items = $annotate ? $this->annotateSeriesCards($cards) : $cards;
        $pagination = $this->parser->parsePagination($html, $page);

        return [
            'items' => $items,
            'pagination' => $pagination,
            'quality' => $quality,
        ];
    }

    /**
     * Annotate parsed series cards with local database status
     *
     * @param  array<int, array>  $cards
     * @return array<int, array>
     */
    public function annotateSeriesCards(array $cards): array
    {
        if (empty($cards)) {
            return [];
        }

        $category = $this->resolveSeriesCategory();
        $titles = array_values(array_filter(array_column($cards, 'title')));

        $existingSeries = Application::where('category_id', $category->id)
            ->where(function ($q) use ($titles) {
                foreach ($titles as $t) {
                    $q->orWhere('name', 'LIKE', '%'.$t.'%');
                }
            })
            ->get(['id', 'slug', 'name', 'has_torrent', 'published', 'download_mirrors']);

        $results = [];
        foreach ($cards as $card) {
            $existing = $existingSeries->first(function ($app) use ($card) {
                return str_contains(strtolower($app->name), strtolower(substr($card['title'], 0, 15)));
            });

            $results[] = array_merge($card, [
                'is_imported' => (bool) $existing,
                'existing_app_id' => $existing?->id,
                'existing_slug' => $existing?->slug,
                'episodes_count' => is_array($existing?->download_mirrors) ? count($existing->download_mirrors) : 0,
            ]);
        }

        return $results;
    }

    /**
     * Import a TV series by its detail URL with all its episode torrents
     */
    public function importSeriesByUrl(string $url, bool $downloadImages = true, ?bool $forcePublished = null): ?Application
    {
        $html = $this->client->fetchHtml($url, false);
        if (! $html) {
            Log::error("MejortorrentImporter: Failed to fetch series HTML for {$url}");

            return null;
        }

        $data = $this->parser->parseSeriesDetail($html, $url);
        if (! $data) {
            Log::warning("MejortorrentImporter: Failed to parse series data from {$url}");

            return null;
        }

        $category = $this->resolveSeriesCategory();

        // Check for existing application by slug or clean_title
        $existing = Application::where('category_id', $category->id)
            ->where(function ($q) use ($data) {
                $q->where('slug', $data['slug'])
                    ->orWhere('name', $data['clean_title'])
                    ->orWhere('name', $data['title']);
            })
            ->first();

        // Download torrent for each episode
        $downloadMirrors = [];
        $disk = $this->getStorageDisk();

        foreach ($data['episodes'] as $ep) {
            try {
                $challenge = $this->client->requestPowChallenge($ep['content_id'], $ep['tabla']);
                if ($challenge) {
                    $nonce = $this->client->solvePoW($challenge, 3);
                    $downloadUrl = $this->client->validatePow($challenge, $nonce, $ep['content_id']);

                    if ($downloadUrl) {
                        $torrentBinary = $this->client->downloadBinary($downloadUrl, $url);
                        if ($torrentBinary && strlen($torrentBinary) > 100) {
                            $epTitle = "{$data['title']} - {$ep['code']}";
                            $brandedFilename = $this->torrentStorage->generateBrandedFilename(
                                $epTitle,
                                $data['format'],
                                $downloadUrl,
                                false
                            );

                            $storagePath = "torrents/{$brandedFilename}";
                            Storage::disk('public')->put($storagePath, $torrentBinary);
                            $publicUrl = asset("storage/{$storagePath}");

                            if ($disk === 'r2') {
                                $r2Url = $this->r2Service->uploadContent(
                                    $torrentBinary,
                                    $storagePath,
                                    'application/x-bittorrent',
                                    [
                                        'title' => $epTitle,
                                        'format' => $data['format'],
                                        'source' => 'PichoDescargas-Series',
                                    ]
                                );
                                if ($r2Url) {
                                    $publicUrl = $r2Url;
                                }
                            }

                            $downloadMirrors[] = [
                                'episode' => $ep['code'],
                                'name' => "Capítulo {$ep['code']}",
                                'date' => $ep['date'],
                                'content_id' => $ep['content_id'],
                                'url' => $publicUrl,
                                'file_path' => $storagePath,
                                'quality' => $ep['quality'] ?? $data['format'],
                                'type' => 'torrent',
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("MejortorrentImporter: Error downloading episode {$ep['code']} for {$data['title']}: {$e->getMessage()}");
            }
        }

        // Poster Image
        $iconPath = null;
        if ($downloadImages && ! empty($data['poster_url'])) {
            try {
                $imageBinary = $this->client->downloadBinary($data['poster_url'], $url);
                if ($imageBinary && strlen($imageBinary) > 200) {
                    $ext = 'jpg';
                    if (str_contains($data['poster_url'], '.webp')) {
                        $ext = 'webp';
                    } elseif (str_contains($data['poster_url'], '.png')) {
                        $ext = 'png';
                    }
                    $imageFilename = 'series_'.Str::slug($data['title']).'_poster.'.$ext;
                    $imagePath = "app-icons/{$imageFilename}";

                    Storage::disk('public')->put($imagePath, $imageBinary);
                    $iconPath = $imagePath;
                }
            } catch (\Throwable $e) {
                Log::warning("MejortorrentImporter: Error downloading series poster: {$e->getMessage()}");
            }
        }

        // Save Application
        if ($existing) {
            $application = $existing;
            $existingMirrors = is_array($application->download_mirrors) ? $application->download_mirrors : [];
            $mergedMirrors = $downloadMirrors;
            foreach ($existingMirrors as $em) {
                $found = false;
                foreach ($mergedMirrors as $nm) {
                    if (($nm['episode'] ?? '') === ($em['episode'] ?? '')) {
                        $found = true;
                        break;
                    }
                }
                if (! $found) {
                    $mergedMirrors[] = $em;
                }
            }
            $application->download_mirrors = $mergedMirrors;
            $application->description = $data['description_html'];
            $application->version = $data['format'];
            $application->size = count($mergedMirrors).' Capítulos';
            if (! empty($iconPath)) {
                $application->icon = $iconPath;
            }
            if (! empty($mergedMirrors)) {
                $application->has_torrent = true;
                $application->torrent_url = $mergedMirrors[0]['url'];
                $application->torrent_file_path = $mergedMirrors[0]['file_path'];
            }
            $application->save();
        } else {
            $dripEnabled = (bool) Setting::get('drip_mode', true);
            $published = $forcePublished !== null ? $forcePublished : (! $dripEnabled);

            $application = Application::create([
                'name' => $data['title'],
                'slug' => $data['slug'],
                'category_id' => $category->id,
                'short_description' => Str::limit($data['synopsis'], 160),
                'description' => $data['description_html'],
                'version' => $data['format'],
                'size' => count($downloadMirrors).' Capítulos',
                'platform' => 'Series',
                'license' => 'Free',
                'icon' => $iconPath,
                'has_torrent' => ! empty($downloadMirrors),
                'torrent_url' => $downloadMirrors[0]['url'] ?? null,
                'torrent_file_path' => $downloadMirrors[0]['file_path'] ?? null,
                'download_mirrors' => $downloadMirrors,
                'published' => $published,
                'downloads' => rand(20, 100),
                'rating' => 4.9,
                'reviews_count' => rand(3, 18),
            ]);
        }

        // Sync tags
        $tags = ['Series', 'TV', $data['format'], 'Torrent', 'Castellano'];
        $this->syncTags($application, $tags);

        return $application;
    }

    /**
     * Sync latest TV series
     *
     * @return array{imported: int, updated: int, series: array}
     */
    public function syncLatestSeries(int $limit = 6, string $quality = 'all'): array
    {
        $imported = 0;
        $updated = 0;
        $processed = [];

        for ($page = 1; $page <= 3; $page++) {
            if ($imported >= $limit) {
                break;
            }

            $html = $this->client->getSeriesCatalogHtml($page, $quality);
            if (! $html) {
                continue;
            }

            $cards = $this->parser->parseSeriesCards($html, $this->client->getBaseUrl());
            if (empty($cards)) {
                break;
            }

            foreach ($cards as $card) {
                if ($imported >= $limit) {
                    break;
                }

                $category = $this->resolveSeriesCategory();
                $existing = Application::where('category_id', $category->id)
                    ->where(function ($q) use ($card) {
                        $q->where('name', $card['title'])
                            ->orWhere('name', 'LIKE', '%'.$card['title'].'%');
                    })
                    ->first();

                if (! $existing) {
                    $series = $this->importSeriesByUrl($card['url'], true);
                    if ($series) {
                        $imported++;
                        $processed[] = $series->name;
                    }
                } else {
                    // Update episodes if new ones exist
                    $series = $this->importSeriesByUrl($card['url'], false);
                    if ($series) {
                        $updated++;
                    }
                }
            }
        }

        Setting::set('mejortorrent_last_series_sync', now()->format('Y-m-d H:i:s'), 'scraper');

        return [
            'imported' => $imported,
            'updated' => $updated,
            'series' => $processed,
        ];
    }

    /**
     * Attach tags cleanly
     *
     * @param  array<int, string>  $tags
     */
    protected function syncTags(Application $application, array $tags): void
    {
        $tagIds = [];
        foreach ($tags as $tagName) {
            $t = trim($tagName);
            if (empty($t)) {
                continue;
            }
            $tag = Tag::firstOrCreate(['slug' => Str::slug($t)], ['name' => $t]);
            $tagIds[] = $tag->id;
        }

        if (! empty($tagIds)) {
            $application->tags()->syncWithoutDetaching($tagIds);
        }
    }
}
