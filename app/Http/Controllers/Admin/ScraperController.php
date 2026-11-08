<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationVersion;
use App\Models\Category;
use App\Models\Setting;
use App\Services\Scraper\CybermaniaImporter;
use App\Services\Scraper\HaxmacImporter;
use App\Services\Scraper\MejortorrentImporter;
use App\Services\Scraper\TorrentmacImporter;
use App\Services\Storage\CloudflareR2Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScraperController extends Controller
{
    public function __construct(
        protected HaxmacImporter $importer,
        protected TorrentmacImporter $torrentmacImporter,
        protected CybermaniaImporter $cybermaniaImporter,
        protected MejortorrentImporter $mejortorrentImporter
    ) {}

    /**
     * Show scraper management dashboard
     */
    public function index()
    {
        $stats = [
            'total_apps' => Application::published()->count(),
            'total_torrent_apps' => Application::where('has_torrent', true)->count(),
            'total_categories' => Category::count(),
            'total_versions' => ApplicationVersion::count(),
            'last_sync' => Setting::get('scraper_last_sync', 'Nunca'),
            'last_torrent_sync' => Setting::get('torrentmac_last_sync', 'Nunca'),
            'last_cybermania_sync' => Setting::get('cybermania_last_sync', 'Nunca'),
            'last_mejortorrent_sync' => Setting::get('mejortorrent_last_sync', 'Nunca'),
            'total_movies' => Application::where('category_id', 6)->count(),
            'mejortorrent_cron_enabled' => (bool) Setting::get('mejortorrent_cron_enabled', true),
            'cron_enabled' => (bool) Setting::get('scraper_cron_enabled', false),
            'cron_time' => Setting::get('scraper_cron_time', '03:00'),
            'cron_pages' => (int) Setting::get('scraper_cron_pages', 2),
            'cron_limit' => (int) Setting::get('scraper_cron_limit', 10),
            'cron_frequency' => Setting::get('scraper_cron_frequency', '2hours'),
            'draft_apps' => Application::where('published', false)->count(),
            'drip_mode' => (bool) Setting::get('scraper_drip_feed_mode', false),
            'storage_disk' => $this->torrentmacImporter->getStorageDisk(),
            'torrent_storage_target' => $this->torrentmacImporter->getConfiguredStorageTarget(),
            'r2_configured' => app(CloudflareR2Service::class)->isConfigured(),
        ];

        $categories = Category::withCount('applications')->ordered()->get();
        $recentApps = Application::with('category')->latest()->take(15)->get();

        $latestData = [
            'items' => [],
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 1,
                'has_next' => false,
                'has_prev' => false,
            ],
        ];

        try {
            $latestData = $this->importer->getLatestHaxmacApps(1, true);
        } catch (\Throwable $e) {
            Log::warning("Could not fetch latest HaxMac apps: {$e->getMessage()}");
        }

        $haxmacLatest = $latestData['items'];
        $haxmacPagination = $latestData['pagination'];

        $haxmacCategories = [
            ['slug' => 'all', 'name' => 'Todos los Posts', 'icon' => '🌐'],
            ['slug' => 'mac-games', 'name' => 'Juegos (Games)', 'icon' => '🎮'],
            ['slug' => 'apple-arcade', 'name' => 'Apple Arcade', 'icon' => '🕹️'],
            ['slug' => 'system-utilities', 'name' => 'System Utilities', 'icon' => '⚙️'],
            ['slug' => 'productivity-business', 'name' => 'Productivity & Business', 'icon' => '💼'],
            ['slug' => 'media-design', 'name' => 'Media & Design', 'icon' => '🎨'],
            ['slug' => 'developer-tools', 'name' => 'Developer Tools', 'icon' => '💻'],
            ['slug' => 'mobile-tools', 'name' => 'Mobile Tools', 'icon' => '📱'],
            ['slug' => 'lifestyle-everyday', 'name' => 'Lifestyle & Everyday', 'icon' => '☕'],
            ['slug' => 'adobe', 'name' => 'Adobe Collection', 'icon' => '🅰️'],
            ['slug' => 'editors-choice', 'name' => 'Editor’s Choice', 'icon' => '⭐'],
            ['slug' => 'assassins-creed', 'name' => 'Assassin’s Creed', 'icon' => '🗡️'],
            ['slug' => 'gta-grand-theft-auto', 'name' => 'GTA: Grand Theft Auto', 'icon' => '🚗'],
            ['slug' => 'the-trilogy', 'name' => 'GTA: The Trilogy', 'icon' => '🔥'],
        ];

        $torrentmacCategories = [
            ['slug' => 'all', 'name' => 'Todos los Torrents', 'icon' => '🧲'],
            ['slug' => 'apps', 'name' => 'Todas las Apps', 'icon' => '💻'],
            ['slug' => 'games', 'name' => 'Juegos (Mac Games)', 'icon' => '🎮'],
            ['slug' => 'utilities-tools', 'name' => 'Utilidades y Herramientas', 'icon' => '⚙️'],
            ['slug' => 'audio-editors', 'name' => 'Audio y Música', 'icon' => '🎵'],
            ['slug' => 'developer-tools', 'name' => 'Developer Tools', 'icon' => '🧑‍💻'],
            ['slug' => 'productivity', 'name' => 'Productividad', 'icon' => '💼'],
            ['slug' => 'video', 'name' => 'Video y Edición', 'icon' => '🎬'],
            ['slug' => 'graphics-design', 'name' => 'Diseño Gráfico', 'icon' => '🎨'],
            ['slug' => 'photography', 'name' => 'Fotografía', 'icon' => '📷'],
            ['slug' => 'plugins', 'name' => 'Plugins', 'icon' => '🔌'],
        ];

        $mejortorrentCategories = [
            ['slug' => 'all', 'name' => 'Todas las Películas', 'icon' => '🎬'],
            ['slug' => 'hd', 'name' => 'Películas HD (1080p)', 'icon' => '🍿'],
            ['slug' => '4k', 'name' => 'Películas 4K UHD', 'icon' => '🌟'],
        ];

        return view('admin.scraper.index', compact(
            'stats',
            'categories',
            'recentApps',
            'haxmacLatest',
            'haxmacPagination',
            'haxmacCategories',
            'torrentmacCategories',
            'mejortorrentCategories'
        ));
    }

    /**
     * Get paginated latest programs from HaxMac (with category filter support)
     */
    public function getLatest(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $category = (string) $request->input('category', 'all');

        try {
            $data = $this->importer->getLatestHaxmacApps($page, true, $category);

            return response()->json([
                'success' => true,
                'items' => $data['items'],
                'pagination' => $data['pagination'],
                'category' => $category,
            ]);
        } catch (\Throwable $e) {
            Log::error("ScraperController getLatest error on page {$page} cat {$category}: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cargar la página '.$page.' de HaxMac',
                'items' => [],
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => 1,
                    'has_next' => false,
                    'has_prev' => false,
                ],
            ], 500);
        }
    }

    /**
     * Clear cached pending updates
     */
    protected function clearPendingUpdatesCache(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Cache::forget("scraper_pending_updates_{$i}");
        }
    }

    /**
     * Get list of applications that exist locally and have newer versions available on HaxMac
     */
    public function getPendingUpdates(Request $request): JsonResponse
    {
        $pages = max(1, min(5, (int) $request->input('pages', 2)));
        $forceRefresh = $request->boolean('refresh', false);
        $cacheKey = "scraper_pending_updates_{$pages}";

        try {
            if ($forceRefresh) {
                Cache::forget($cacheKey);
            }

            $updates = Cache::remember($cacheKey, 90, function () use ($pages) {
                return $this->importer->getPendingUpdates($pages);
            });

            return response()->json([
                'success' => true,
                'count' => count($updates),
                'items' => $updates,
            ]);
        } catch (\Throwable $e) {
            Log::error("ScraperController getPendingUpdates error: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Error al escanear actualizaciones: '.$e->getMessage(),
                'count' => 0,
                'items' => [],
            ], 500);
        }
    }

    /**
     * Search HaxMac in real-time with pagination
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'page' => 'nullable|integer|min:1',
        ]);

        $page = max(1, (int) $request->input('page', 1));
        $data = $this->importer->searchHaxmac($request->q, $page, true);

        return response()->json([
            'success' => true,
            'query' => $request->q,
            'total' => count($data['items']),
            'results' => $data['items'],
            'pagination' => $data['pagination'],
        ]);
    }

    /**
     * Import a single application with 1-click
     */
    public function importSingle(Request $request): JsonResponse
    {
        $request->validate([
            'url_or_slug' => 'required|string',
            'download_images' => 'nullable|boolean',
        ]);

        $downloadImages = $request->boolean('download_images', true);
        $app = $this->importer->importApp($request->url_or_slug, $downloadImages);

        if ($app) {
            Setting::set('scraper_last_sync', now()->format('Y-m-d H:i:s'), 'scraper');
            $this->clearPendingUpdatesCache();

            return response()->json([
                'success' => true,
                'message' => "Aplicación {$app->name} importada con éxito.",
                'app' => [
                    'id' => $app->id,
                    'name' => $app->name,
                    'version' => $app->version,
                    'category' => $app->category?->name,
                    'size' => $app->size,
                    'download_url' => $app->download_url_external,
                    'public_url' => route('app', $app->slug),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se pudo importar la aplicación desde HaxMac.',
        ], 422);
    }

    /**
     * Import an entire category with configurable pages
     */
    public function importCategory(Request $request): JsonResponse
    {
        $request->validate([
            'category_slug' => 'required|string',
            'pages' => 'nullable|integer|min:1|max:10',
            'download_images' => 'nullable|boolean',
        ]);

        $pages = $request->integer('pages', 1);
        $downloadImages = $request->boolean('download_images', true);

        $summary = $this->importer->importCategory($request->category_slug, $pages, $downloadImages);
        Setting::set('scraper_last_sync', now()->format('Y-m-d H:i:s'), 'scraper');
        $this->clearPendingUpdatesCache();

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'message' => "Importación completada: {$summary['imported']} nuevas, {$summary['updated']} actualizadas.",
        ]);
    }

    /**
     * Synchronize categories from HaxMac main menu
     */
    public function syncCategories(): JsonResponse
    {
        $result = $this->importer->syncCategories();

        return response()->json([
            'success' => true,
            'created' => $result['created'],
            'updated' => $result['updated'],
            'categories' => $result['categories'],
            'message' => "Categorías sincronizadas: {$result['created']} creadas, {$result['updated']} actualizadas.",
        ]);
    }

    /**
     * Scan and automatically import new additions and updates from HaxMac
     */
    public function syncUpdates(): JsonResponse
    {
        $result = $this->importer->syncLatestUpdates(true);
        Setting::set('scraper_last_sync', now()->format('Y-m-d H:i:s'), 'scraper');
        $this->clearPendingUpdatesCache();

        return response()->json([
            'success' => true,
            'new_imported' => $result['new_imported'],
            'updated' => $result['updated'],
            'total_checked' => $result['total_checked'],
            'items' => $result['items'],
            'message' => "Sincronización finalizada: {$result['new_imported']} programas nuevos importados, {$result['updated']} actualizados a su última versión.",
        ]);
    }

    /**
     * Update automated Cron configuration
     */
    public function updateCronSettings(Request $request)
    {
        $request->validate([
            'cron_enabled' => 'nullable|boolean',
            'cron_time' => 'nullable|string',
            'cron_pages' => 'nullable|integer|min:1|max:5',
            'cron_limit' => 'nullable|integer|min:1|max:50',
            'cron_frequency' => 'nullable|string',
            'drip_mode' => 'nullable|boolean',
        ]);

        $cronTime = $request->filled('cron_time')
            ? $request->input('cron_time')
            : Setting::get('scraper_cron_time', '03:00');
        $cronPages = $request->filled('cron_pages')
            ? $request->integer('cron_pages')
            : (int) Setting::get('scraper_cron_pages', 2);
        $cronLimit = $request->filled('cron_limit')
            ? $request->integer('cron_limit')
            : (int) Setting::get('scraper_cron_limit', 10);
        $cronFrequency = $request->filled('cron_frequency')
            ? $request->input('cron_frequency')
            : Setting::get('scraper_cron_frequency', '2hours');

        Setting::set('scraper_cron_enabled', $request->boolean('cron_enabled') ? '1' : '0', 'scraper');
        Setting::set('scraper_cron_time', $cronTime, 'scraper');
        Setting::set('scraper_cron_pages', $cronPages, 'scraper');
        Setting::set('scraper_cron_limit', $cronLimit, 'scraper');
        Setting::set('scraper_cron_frequency', $cronFrequency, 'scraper');
        Setting::set('scraper_drip_feed_mode', $request->boolean('drip_mode') ? '1' : '0', 'scraper');
        if ($request->has('mejortorrent_cron_enabled')) {
            Setting::set('mejortorrent_cron_enabled', $request->boolean('mejortorrent_cron_enabled') ? '1' : '0', 'scraper');
        }
        if ($request->filled('mejortorrent_base_url')) {
            Setting::set('mejortorrent_base_url', trim($request->input('mejortorrent_base_url')), 'scraper');
        }
        if ($request->has('mejortorrent_cf_clearance')) {
            Setting::set('mejortorrent_cf_clearance', trim($request->input('mejortorrent_cf_clearance', '')), 'scraper');
        }
        if ($request->has('flaresolverr_url')) {
            Setting::set('flaresolverr_url', trim($request->input('flaresolverr_url', '')), 'scraper');
        }
        Setting::clearCache();

        $redirectUrl = $request->filled('tab')
            ? route('admin.scraper', ['tab' => $request->input('tab')])
            : route('admin.scraper');

        return redirect()->to($redirectUrl)->with('success', 'Configuración de automatización guardada correctamente.');
    }

    /**
     * Instant toggle of Drip Feed Mode (Cola Draft)
     */
    public function toggleDrip(Request $request): JsonResponse
    {
        $current = (bool) Setting::get('scraper_drip_feed_mode', false);
        $new = $request->has('drip_mode') ? $request->boolean('drip_mode') : ! $current;
        Setting::set('scraper_drip_feed_mode', $new ? '1' : '0', 'scraper');
        Setting::clearCache();

        return response()->json([
            'success' => true,
            'drip_mode' => $new,
            'message' => $new
                ? 'Modo Goteo activado: Las nuevas apps se guardarán en Cola Draft.'
                : 'Modo Goteo desactivado: Las nuevas apps se publicarán directamente.',
        ]);
    }

    /**
     * Instant toggle of automated Cron status
     */
    public function toggleCron(Request $request): JsonResponse
    {
        $current = (bool) Setting::get('scraper_cron_enabled', false);
        $new = ! $current;
        Setting::set('scraper_cron_enabled', $new ? '1' : '0', 'scraper');
        Setting::clearCache();

        return response()->json([
            'success' => true,
            'cron_enabled' => $new,
            'message' => $new ? 'Cron activado exitosamente.' : 'Cron pausado.',
        ]);
    }

    /**
     * Release a batch of draft applications to published status (Drip Feed / Goteo)
     */
    public function releaseDripBatch(Request $request): JsonResponse
    {
        $limit = max(1, min(50, (int) $request->input('limit', Setting::get('scraper_cron_limit', 10))));

        $result = Application::releaseDripBatch($limit);
        $count = $result['published_count'];
        $torrentCount = $result['torrent_count'];
        $ddlCount = $result['ddl_count'];

        $remaining = Application::where('published', false)->count();
        $totalPublished = Application::where('published', true)->count();

        return response()->json([
            'success' => true,
            'published_count' => $count,
            'torrent_count' => $torrentCount,
            'ddl_count' => $ddlCount,
            'remaining_drafts' => $remaining,
            'total_published' => $totalPublished,
            'message' => $count > 0
                ? "Goteo exitoso: {$count} programas publicados ({$torrentCount} Torrent, {$ddlCount} DDL). Quedan {$remaining} en Cola Draft."
                : 'No hay programas en Cola Draft para publicar.',
        ]);
    }

    /**
     * Replenish the draft queue by importing from HaxMac & TorrentMac backlog catalogs
     */
    public function fillDraftBatch(Request $request): JsonResponse
    {
        $limit = max(2, min(50, (int) $request->input('limit', 10)));
        $half = (int) ceil($limit / 2);

        $haxmacResult = $this->importer->importBacklogBatch($half, true, true);
        $torrentResult = $this->torrentmacImporter->importBacklogBatch($half, true, true);

        $totalImported = $haxmacResult['imported'] + $torrentResult['imported'];
        $remainingDrafts = Application::where('published', false)->count();

        return response()->json([
            'success' => true,
            'imported' => $totalImported,
            'haxmac_imported' => $haxmacResult['imported'],
            'torrent_imported' => $torrentResult['imported'],
            'total_drafts' => $remainingDrafts,
            'haxmac_next_page' => $haxmacResult['next_page'] ?? null,
            'torrent_next_page' => $torrentResult['next_page'] ?? null,
            'message' => "Se cargaron {$totalImported} nuevos borradores desde el catálogo histórico ({$haxmacResult['imported']} DDL, {$torrentResult['imported']} Torrent). Total en cola: {$remainingDrafts} borradores.",
        ]);
    }

    /**
     * Get paginated latest programs from TorrentMac (with category filter support)
     */
    public function getTorrentmacLatest(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $category = (string) $request->input('category', 'all');

        try {
            $data = $this->torrentmacImporter->getTorrentmacApps($page, true, $category);

            return response()->json([
                'success' => true,
                'items' => $data['items'],
                'pagination' => $data['pagination'],
                'category' => $category,
            ]);
        } catch (\Throwable $e) {
            Log::error("ScraperController getTorrentmacLatest error on page {$page} cat {$category}: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cargar la página '.$page.' de TorrentMac: '.$e->getMessage(),
                'items' => [],
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => 1,
                    'has_next' => false,
                    'has_prev' => false,
                ],
            ], 500);
        }
    }

    /**
     * Clear cached TorrentMac pending updates
     */
    protected function clearTorrentmacPendingUpdatesCache(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Cache::forget("scraper_torrentmac_pending_updates_{$i}");
        }
    }

    /**
     * Get list of applications that exist locally and have newer versions available on TorrentMac
     */
    public function getTorrentmacPendingUpdates(Request $request): JsonResponse
    {
        $pages = max(1, min(5, (int) $request->input('pages', 2)));
        $forceRefresh = $request->boolean('refresh', false);
        $cacheKey = "scraper_torrentmac_pending_updates_{$pages}";

        try {
            if ($forceRefresh) {
                Cache::forget($cacheKey);
            }

            $updates = Cache::remember($cacheKey, 90, function () use ($pages) {
                return $this->torrentmacImporter->getPendingUpdates($pages);
            });

            return response()->json([
                'success' => true,
                'count' => count($updates),
                'items' => $updates,
            ]);
        } catch (\Throwable $e) {
            Log::error("ScraperController getTorrentmacPendingUpdates error: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Error al escanear actualizaciones de TorrentMac: '.$e->getMessage(),
                'count' => 0,
                'items' => [],
            ], 500);
        }
    }

    /**
     * Search TorrentMac in real-time with pagination
     */
    public function searchTorrentmac(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'page' => 'nullable|integer|min:1',
        ]);

        $page = max(1, (int) $request->input('page', 1));

        try {
            $data = $this->torrentmacImporter->searchTorrentmac($request->q, $page, true);

            return response()->json([
                'success' => true,
                'query' => $request->q,
                'total' => count($data['items']),
                'items' => $data['items'],
                'results' => $data['items'],
                'pagination' => $data['pagination'],
            ]);
        } catch (\Throwable $e) {
            Log::error("ScraperController searchTorrentmac error: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Error al buscar en TorrentMac: '.$e->getMessage(),
                'results' => [],
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => 1,
                    'has_next' => false,
                    'has_prev' => false,
                ],
            ], 500);
        }
    }

    /**
     * Import a single application from TorrentMac with 1-click
     */
    public function importTorrentmacSingle(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
            'download_images' => 'nullable|boolean',
        ]);

        $downloadImages = $request->boolean('download_images', true);
        $app = $this->torrentmacImporter->importAppByUrl($request->url, $downloadImages);

        if ($app) {
            $this->clearTorrentmacPendingUpdatesCache();

            return response()->json([
                'success' => true,
                'message' => "Aplicación {$app->name} importada con torrent con éxito.",
                'app' => [
                    'id' => $app->id,
                    'name' => $app->name,
                    'version' => $app->version,
                    'category' => $app->category?->name,
                    'size' => $app->size,
                    'has_torrent' => $app->has_torrent,
                    'torrent_url' => $app->torrent_url,
                    'is_dual' => ($app->has_torrent && ! empty($app->download_url_external)),
                    'public_url' => route('app', $app->slug),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se pudo importar la aplicación desde TorrentMac.',
        ], 422);
    }

    /**
     * Scan and automatically import new additions from TorrentMac
     */
    public function syncTorrentmacLatest(Request $request): JsonResponse
    {
        $pages = max(1, min(5, (int) $request->input('pages', 1)));
        $downloadImages = $request->boolean('download_images', true);

        $result = $this->torrentmacImporter->syncLatestUpdates($pages, $downloadImages);
        $this->clearTorrentmacPendingUpdatesCache();

        return response()->json([
            'success' => true,
            'new_imported' => $result['new_imported'],
            'updated' => $result['updated'],
            'total_checked' => $result['total_checked'],
            'items' => $result['items'],
            'message' => "Sincronización TorrentMac finalizada: {$result['new_imported']} programas nuevos importados, {$result['updated']} actualizados.",
        ]);
    }

    /**
     * Update TorrentMac destination storage driver ('local' or 'r2')
     */
    public function updateTorrentStorageSettings(Request $request): JsonResponse
    {
        $request->validate([
            'storage_disk' => 'required|in:local,r2',
        ]);

        $disk = $request->input('storage_disk');
        Setting::set('torrentmac_storage_disk', $disk, 'scraper');
        Setting::clearCache();

        $r2Configured = ! empty(config('filesystems.disks.r2.key')) && ! empty(config('filesystems.disks.r2.bucket'));

        return response()->json([
            'success' => true,
            'storage_disk' => $disk,
            'effective_disk' => ($disk === 'r2' && $r2Configured) ? 'r2' : 'public',
            'r2_configured' => $r2Configured,
            'message' => $disk === 'r2'
                ? ($r2Configured ? 'Almacenamiento de TorrentMac configurado en Cloudflare R2.' : 'R2 seleccionado, pero faltan credenciales en .env; se usará almacenamiento local como respaldo.')
                : 'Almacenamiento de TorrentMac configurado en Servidor Local (public).',
        ]);
    }

    /**
     * Get paginated latest programs from CyberMania
     */
    public function getCybermaniaLatest(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $category = (string) $request->input('category', 'all');

        try {
            $data = $this->cybermaniaImporter->getCybermaniaApps($page, true, $category);

            return response()->json([
                'success' => true,
                'items' => $data['items'],
                'pagination' => $data['pagination'],
                'category' => $category,
            ]);
        } catch (\Throwable $e) {
            Log::error("ScraperController getCybermaniaLatest error: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cargar la página '.$page.' de CyberMania: '.$e->getMessage(),
                'items' => [],
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => 1,
                    'has_next' => false,
                    'has_prev' => false,
                ],
            ], 500);
        }
    }

    /**
     * Search CyberMania in real-time with pagination
     */
    public function searchCybermania(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'page' => 'nullable|integer|min:1',
        ]);

        $page = max(1, (int) $request->input('page', 1));

        try {
            $data = $this->cybermaniaImporter->searchCybermania($request->q, $page, true);

            return response()->json([
                'success' => true,
                'query' => $request->q,
                'total' => count($data['items']),
                'items' => $data['items'],
                'results' => $data['items'],
                'pagination' => $data['pagination'],
            ]);
        } catch (\Throwable $e) {
            Log::error("ScraperController searchCybermania error: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Error al buscar en CyberMania: '.$e->getMessage(),
                'results' => [],
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => 1,
                    'has_next' => false,
                    'has_prev' => false,
                ],
            ], 500);
        }
    }

    /**
     * Import a single application from CyberMania with 1-click
     */
    public function importCybermaniaSingle(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
            'download_images' => 'nullable|boolean',
            'category_slug' => 'nullable|string',
        ]);

        $downloadImages = $request->boolean('download_images', true);
        $categorySlug = $request->input('category_slug');
        $app = $this->cybermaniaImporter->importAppByUrl($request->url, $downloadImages, null, $categorySlug);

        if ($app) {
            return response()->json([
                'success' => true,
                'message' => "Aplicación {$app->name} importada con éxito desde CyberMania.",
                'app' => [
                    'id' => $app->id,
                    'name' => $app->name,
                    'version' => $app->version,
                    'category' => $app->category?->name,
                    'size' => $app->size,
                    'platform' => $app->platform,
                    'has_torrent' => $app->has_torrent,
                    'download_url_external' => $app->download_url_external,
                    'public_url' => route('app', $app->slug),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se pudo importar la aplicación desde CyberMania.',
        ], 422);
    }

    /**
     * Scan and automatically import new additions from CyberMania
     */
    public function syncCybermaniaLatest(Request $request): JsonResponse
    {
        $pages = max(1, min(5, (int) $request->input('pages', 1)));
        $limit = max(1, min(50, (int) $request->input('limit', 10)));
        $category = (string) $request->input('category', 'all');

        $result = $this->cybermaniaImporter->syncLatestUpdates(true, $limit, $pages, $category);
        Setting::set('cybermania_last_sync', now()->format('Y-m-d H:i:s'), 'scraper');

        return response()->json([
            'success' => true,
            'imported' => $result['imported'],
            'updated' => $result['updated'],
            'skipped' => $result['skipped'],
            'failed' => $result['failed'],
            'items' => $result['items'],
            'message' => "Sincronización CyberMania completada: {$result['imported']} nuevas, {$result['updated']} actualizadas.",
        ]);
    }

    /**
     * Get applications that have newer versions available on CyberMania
     */
    public function getCybermaniaPendingUpdates(Request $request): JsonResponse
    {
        $pages = max(1, min(5, (int) $request->input('pages', 2)));
        $category = (string) $request->input('category', 'all');

        try {
            $updates = $this->cybermaniaImporter->getPendingUpdates($pages, $category);

            return response()->json([
                'success' => true,
                'count' => count($updates),
                'items' => $updates,
            ]);
        } catch (\Throwable $e) {
            Log::error("ScraperController getCybermaniaPendingUpdates error: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Error al escanear actualizaciones de CyberMania: '.$e->getMessage(),
                'count' => 0,
                'items' => [],
            ], 500);
        }
    }

    /**
     * Get paginated latest movies from MejorTorrent / DonTorrent
     */
    public function getMejortorrentLatest(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $quality = (string) $request->input('quality', 'all');

        try {
            $data = $this->mejortorrentImporter->getMoviesCatalog($page, true, $quality);

            return response()->json([
                'success' => true,
                'items' => $data['items'],
                'pagination' => $data['pagination'],
                'quality' => $quality,
            ]);
        } catch (\Throwable $e) {
            Log::error("ScraperController getMejortorrentLatest error: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cargar el catálogo de películas: '.$e->getMessage(),
                'items' => [],
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => 1,
                    'has_next' => false,
                    'has_prev' => false,
                ],
            ], 500);
        }
    }

    /**
     * Import a single movie by URL from MejorTorrent / DonTorrent
     */
    public function importMejortorrentSingle(Request $request): JsonResponse
    {
        $url = trim($request->input('url', ''));
        if (empty($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json([
                'success' => false,
                'message' => 'Por favor, proporciona una URL válida de la película.',
            ], 422);
        }

        try {
            $movie = $this->mejortorrentImporter->importMovieByUrl($url, true, true);
            if ($movie) {
                return response()->json([
                    'success' => true,
                    'message' => "Película importada correctamente: {$movie->name}",
                    'item' => [
                        'id' => $movie->id,
                        'name' => $movie->name,
                        'slug' => $movie->slug,
                        'version' => $movie->version,
                        'public_url' => route('app', $movie->slug),
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudo importar la película. Revisa la URL e inténtalo de nuevo.',
            ], 422);
        } catch (\Throwable $e) {
            Log::error("ScraperController importMejortorrentSingle error: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Error importando película: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sincronizar novedades de películas torrent
     */
    public function syncMejortorrentLatest(Request $request): JsonResponse
    {
        $limit = max(1, min(50, (int) $request->input('limit', 6)));
        $quality = (string) $request->input('quality', 'all');
        $pages = max(1, min(5, (int) $request->input('pages', 2)));

        try {
            $result = $this->mejortorrentImporter->syncLatestMovies(true, $limit, $pages, $quality);

            return response()->json([
                'success' => true,
                'imported' => $result['imported'],
                'updated' => $result['updated'],
                'message' => "Sincronización de Películas finalizada: {$result['imported']} nuevas películas importadas.",
            ]);
        } catch (\Throwable $e) {
            Log::error("ScraperController syncMejortorrentLatest error: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar películas: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get paginated latest TV series from MejorTorrent / DonTorrent
     */
    public function getMejortorrentSeriesLatest(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $quality = (string) $request->input('quality', 'all');

        try {
            $data = $this->mejortorrentImporter->getSeriesCatalog($page, true, $quality);

            return response()->json([
                'success' => true,
                'items' => $data['items'],
                'pagination' => $data['pagination'],
                'quality' => $quality,
            ]);
        } catch (\Throwable $e) {
            Log::error("ScraperController getMejortorrentSeriesLatest error: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cargar el catálogo de series: '.$e->getMessage(),
                'items' => [],
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => 1,
                    'has_next' => false,
                    'has_prev' => false,
                ],
            ], 500);
        }
    }

    /**
     * Import a single TV series by URL from MejorTorrent / DonTorrent
     */
    public function importMejortorrentSeriesSingle(Request $request): JsonResponse
    {
        $url = trim($request->input('url', ''));
        if (empty($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json([
                'success' => false,
                'message' => 'Por favor, proporciona una URL válida de la serie.',
            ], 422);
        }

        try {
            $series = $this->mejortorrentImporter->importSeriesByUrl($url, true, true);
            if ($series) {
                $count = is_array($series->download_mirrors) ? count($series->download_mirrors) : 0;

                return response()->json([
                    'success' => true,
                    'message' => "Serie importada correctamente con {$count} capítulos: {$series->name}",
                    'item' => [
                        'id' => $series->id,
                        'name' => $series->name,
                        'slug' => $series->slug,
                        'version' => $series->version,
                        'episodes_count' => $count,
                        'public_url' => route('app', $series->slug),
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudo importar la serie. Revisa la URL e inténtalo de nuevo.',
            ], 422);
        } catch (\Throwable $e) {
            Log::error("ScraperController importMejortorrentSeriesSingle error: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Error importando serie: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sincronizar novedades de series TV torrent
     */
    public function syncMejortorrentSeriesLatest(Request $request): JsonResponse
    {
        $limit = max(1, min(30, (int) $request->input('limit', 6)));
        $quality = (string) $request->input('quality', 'all');

        try {
            $result = $this->mejortorrentImporter->syncLatestSeries($limit, $quality);

            return response()->json([
                'success' => true,
                'imported' => $result['imported'],
                'updated' => $result['updated'],
                'message' => "Sincronización de Series finalizada: {$result['imported']} nuevas temporadas importadas.",
            ]);
        } catch (\Throwable $e) {
            Log::error("ScraperController syncMejortorrentSeriesLatest error: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar series: '.$e->getMessage(),
            ], 500);
        }
    }
}
