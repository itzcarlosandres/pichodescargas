<?php

use App\Models\Application;
use App\Models\BrokenLinkReport;
use App\Models\Category;
use App\Models\Setting;
use App\Services\LinkHealth\AutoLinkHealerService;
use App\Services\LinkHealth\LinkHealthCheckerService;
use App\Services\Scraper\CybermaniaImporter;
use App\Services\Scraper\HaxmacImporter;
use App\Services\Scraper\TorrentmacImporter;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('scraper:sync {--force : Ejecutar incluso si el cron está desactivado en la configuración} {--fill-drafts=0 : Rellenar la cola con N borradores del catálogo}', function (HaxmacImporter $haxmac, TorrentmacImporter $torrentmac, CybermaniaImporter $cybermania) {
    $isEnabled = filter_var(Setting::get('scraper_cron_enabled', false), FILTER_VALIDATE_BOOLEAN) || $this->option('force');

    if (! $isEnabled) {
        $this->warn('El cron del scraper está desactivado en la configuración. Usa --force para ejecutar manualmente.');

        return 1;
    }

    $limit = max(1, (int) Setting::get('scraper_cron_limit', 20));
    $dripMode = filter_var(Setting::get('scraper_drip_feed_mode', false), FILTER_VALIDATE_BOOLEAN);

    $torrentEnabled = filter_var(Setting::get('torrentmac_cron_enabled', true), FILTER_VALIDATE_BOOLEAN);
    $cyberEnabled = filter_var(Setting::get('cybermania_cron_enabled', true), FILTER_VALIDATE_BOOLEAN);

    // 1. Cálculo de cuotas equitativas para los 3 scrapers (ej. 20 -> DDL: 7, Torrent: 7, PC/Android: 6)
    $activeScrapers = 1 + ($torrentEnabled ? 1 : 0) + ($cyberEnabled ? 1 : 0);
    $ddlQuota = (int) ceil($limit / $activeScrapers);
    $remainingAfterDdl = max(0, $limit - $ddlQuota);

    if ($torrentEnabled && $cyberEnabled) {
        $torrentQuota = (int) ceil($remainingAfterDdl / 2);
        $cyberQuota = max(0, $remainingAfterDdl - $torrentQuota);
    } elseif ($torrentEnabled) {
        $torrentQuota = $remainingAfterDdl;
        $cyberQuota = 0;
    } elseif ($cyberEnabled) {
        $torrentQuota = 0;
        $cyberQuota = $remainingAfterDdl;
    } else {
        $ddlQuota = $limit;
        $torrentQuota = 0;
        $cyberQuota = 0;
    }

    $this->info("🎯 Límite Global: {$limit} programas. Cuotas asignadas -> DDL (HaxMac): {$ddlQuota}, Torrent (TorrentMac): {$torrentQuota}, PC/Android (CyberMania): {$cyberQuota}");

    // 2. Si se solicita rellenar borradores manualmente o la cola está seca en modo goteo
    $requestedFill = (int) $this->option('fill-drafts');
    $currentDrafts = Application::where('published', false)->count();
    $fillNeeded = $requestedFill > 0 ? $requestedFill : ($dripMode && $currentDrafts < $limit ? $limit : 0);

    if ($fillNeeded > 0) {
        $part = (int) ceil($fillNeeded / max(1, $activeScrapers));
        $this->info("Rellenando Cola Draft con {$fillNeeded} programas del catálogo histórico (página HaxMac: ".Setting::get('scraper_haxmac_backlog_page', 4).', TorrentMac: '.Setting::get('scraper_torrentmac_backlog_page', 4).')...');
        $bHax = $haxmac->importBacklogBatch($part, true, true);
        $bTor = $torrentEnabled ? $torrentmac->importBacklogBatch($part, true, true) : ['imported' => 0];
        $totalBacklog = ($bHax['imported'] ?? 0) + ($bTor['imported'] ?? 0);
        $this->info("✓ Cola Draft reabastecida: +{$totalBacklog} borradores. Total en cola: ".Application::where('published', false)->count());
    }

    $reallocatedPool = 0;

    // 3. Sincronización de novedades desde portadas con reasignación inteligente
    // 3a. HaxMac (macOS DDL -> categoría mac-apps)
    $this->info("Iniciando sincronización DDL de portada (HaxMac, cuota: {$ddlQuota} apps a mac-apps)...");
    $ddlResult = $haxmac->syncLatestUpdates(true, $ddlQuota, 3);
    $ddlImported = (int) ($ddlResult['new_imported'] ?? 0);
    if ($ddlImported < $ddlQuota) {
        $reallocatedPool += ($ddlQuota - $ddlImported);
    }
    Setting::set('scraper_last_sync', now()->format('Y-m-d H:i:s'), 'scraper');
    $this->info("✓ DDL completado: {$ddlImported} nuevos, {$ddlResult['updated']} actualizados.");

    // 3b. TorrentMac (macOS Torrents -> categoría mac-apps)
    $torrentResult = ['new_imported' => 0, 'updated' => 0];
    if ($torrentEnabled) {
        $torrentTarget = $torrentQuota + $reallocatedPool;
        $reallocatedPool = 0;
        $this->info("Iniciando sincronización Torrent de portada (TorrentMac, cuota: {$torrentTarget} apps a mac-apps)...");
        $torrentResult = $torrentmac->syncLatestUpdates(1, true, $torrentTarget, 3);
        $torrentImported = (int) ($torrentResult['new_imported'] ?? 0);
        if ($torrentImported < $torrentTarget) {
            $reallocatedPool += ($torrentTarget - $torrentImported);
        }
        Setting::set('torrentmac_last_sync', now()->format('Y-m-d H:i:s'), 'scraper');
        $this->info("✓ Torrent completado: {$torrentImported} nuevos, {$torrentResult['updated']} actualizados.");
    }

    // 3c. CyberMania (Windows PC & Android -> categorías programas, juegos-pc, apps-android)
    $cyberResult = ['imported' => 0, 'updated' => 0];
    if ($cyberEnabled) {
        $cyberTarget = $cyberQuota + $reallocatedPool;
        $reallocatedPool = 0;
        $this->info("Iniciando sincronización CyberMania (PC & APK, cuota: {$cyberTarget} apps)...");
        $cyberResult = $cybermania->syncLatestUpdates(true, $cyberTarget, 2, 'all');
        $cyberImported = (int) ($cyberResult['imported'] ?? 0);
        if ($cyberImported < $cyberTarget) {
            $reallocatedPool += ($cyberTarget - $cyberImported);
        }
        Setting::set('cybermania_last_sync', now()->format('Y-m-d H:i:s'), 'scraper');
        $this->info("✓ CyberMania completado: {$cyberImported} nuevos, {$cyberResult['updated']} actualizados.");
    }

    // 4. Si no hay modo goteo y no se alcanzaron novedades suficientes, rellenar el déficit desde el archivo
    if (! $dripMode) {
        $totalNew = $ddlImported + ($torrentResult['new_imported'] ?? 0) + ($cyberResult['imported'] ?? 0);
        $deficit = $limit - $totalNew;
        if ($deficit > 0) {
            $halfDeficit = (int) ceil($deficit / 2);
            $this->info("No hubo suficientes novedades en portada ({$totalNew}/{$limit}). Extrayendo {$deficit} programas antiguos de archivo para cumplir la meta...");
            $haxDirect = $haxmac->importBacklogBatch($halfDeficit, false, true);
            $torDirect = $torrentEnabled ? $torrentmac->importBacklogBatch($halfDeficit, false, true) : ['imported' => 0];
            $this->info("✓ Publicados desde archivo: +{$haxDirect['imported']} DDL, +{$torDirect['imported']} Torrent.");
        }
    } else {
        // 5. Goteo de drafts (publicación escalonada equilibrada 3-way)
        $dripResult = Application::releaseDripBatch($limit);
        if ($dripResult['published_count'] > 0) {
            $remaining = Application::where('published', false)->count();
            $this->info("✓ Goteo Draft: {$dripResult['published_count']} apps publicadas ({$dripResult['torrent_count']} Torrent, {$dripResult['ddl_count']} DDL, {$dripResult['pc_android_count']} PC/Android). Quedan {$remaining} en cola draft.");
        }
    }

    $this->info('Sincronización finalizada correctamente.');

    return 0;
})->purpose('Ejecuta la sincronización automática de novedades del scraper');

Artisan::command('scraper:fill-drafts {count=10 : Cantidad de programas a cargar desde el catálogo}', function (HaxmacImporter $haxmac, TorrentmacImporter $torrentmac) {
    $count = max(2, (int) $this->argument('count'));
    $half = (int) ceil($count / 2);

    $this->info("Extrayendo {$count} aplicaciones desde el catálogo histórico para la Cola Draft...");
    $hax = $haxmac->importBacklogBatch($half, true, true);
    $tor = $torrentmac->importBacklogBatch($half, true, true);

    $total = $hax['imported'] + $tor['imported'];
    $currentDrafts = Application::where('published', false)->count();

    $this->info("✓ Se importaron {$total} nuevos borradores (+{$hax['imported']} DDL, +{$tor['imported']} Torrent).");
    $this->info("✓ Total actual en Cola Draft: {$currentDrafts} aplicaciones listas para goteo.");

    return 0;
})->purpose('Carga programas antiguos desde el archivo de HaxMac y TorrentMac a la Cola Draft');

// Dynamic frequency and time settings from DB
$frequency = (string) Setting::get('scraper_cron_frequency', '2hours');
$deepTime = (string) Setting::get('scraper_cron_time', '03:00');

$applyFrequency = function ($event, string $freq) {
    return match ($freq) {
        'hourly' => $event->hourly(),
        '4hours' => $event->cron('0 */4 * * *'),
        'daily' => $event->daily(),
        default => $event->everyTwoHours(),
    };
};

// 1. Automated Unified Smart Sync (Orquesta Haxmac, Torrentmac y Cybermania con cuotas exactas y reasignación)
$syncEvent = Schedule::command('scraper:sync')
    ->name('scraper-unified-sync')
    ->withoutOverlapping(60);

$applyFrequency($syncEvent, $frequency);

// 3. Automated Deep Category Sync (Comprehensive crawl at configured time)
Schedule::call(function () {
    if (filter_var(Setting::get('scraper_cron_enabled', false), FILTER_VALIDATE_BOOLEAN)) {
        $importer = app(HaxmacImporter::class);
        $categories = Category::where('is_active', true)->get();
        $pages = (int) Setting::get('scraper_cron_pages', 2);

        foreach ($categories as $cat) {
            $importer->importCategory($cat->slug, $pages, true);
        }

        Setting::set('scraper_last_sync', now()->format('Y-m-d H:i:s'), 'scraper');
    }
})->dailyAt($deepTime)->name('haxmac-deep-sync')->withoutOverlapping(120);

// 4. Automated Daily Sitemap Generation
Schedule::command('sitemap:generate')->dailyAt('04:00')->name('generate-sitemap');

// 5. Automated Telegram Drip Dispatcher (1 app per minute if auto pilot is active)
Schedule::call(function () {
    app(TelegramBotService::class)->dispatchCron();
})->everyMinute()->name('telegram-auto-pilot')->withoutOverlapping(10);

Artisan::command('telegram:dispatch {--force : Despacha incluso si el piloto automático está apagado}', function (TelegramBotService $telegram) {
    if ($this->option('force')) {
        $result = $telegram->dispatchNext();
    } else {
        $result = $telegram->dispatchCron();
    }

    if (! empty($result['success'])) {
        $this->info($result['message']);

        return 0;
    }

    if (! empty($result['skipped'])) {
        $this->warn($result['reason']);

        return 0;
    }

    $this->error($result['message'] ?? 'No se pudo despachar.');

    return 1;
})->purpose('Despacha 1 aplicación pendiente hacia el canal de Telegram');

Artisan::command('links:auto-repair {--limit=10 : Máximo de reportes a procesar}', function (AutoLinkHealerService $healer) {
    $limit = (int) $this->option('limit');
    $reports = BrokenLinkReport::with('application')
        ->where('status', 'pending')
        ->latest()
        ->limit($limit)
        ->get();

    if ($reports->isEmpty()) {
        $this->info('No hay reportes de enlaces caídos pendientes.');

        return 0;
    }

    $this->info("Iniciando auto-reparación inteligente para {$reports->count()} reportes...");
    $repaired = 0;

    foreach ($reports as $report) {
        if (! $report->application) {
            continue;
        }

        $this->line("Procesando: {$report->application->name} (v{$report->application->version})...");
        $result = $healer->healApplication($report->application, $report);

        if (! empty($result['success'])) {
            $this->info("✓ {$result['message']}");
            $repaired++;
        } else {
            $this->warn("✕ {$result['message']}");
        }
    }

    $this->info("Finalizado: {$repaired} de {$reports->count()} enlaces reparados automáticamente.");

    return 0;
})->purpose('Rastrea y auto-repara enlaces caídos reportados buscando en Torrentmac y Haxmac');

Artisan::command('links:check-health {--limit=20 : Número de aplicaciones a comprobar}', function (LinkHealthCheckerService $checker) {
    $limit = (int) $this->option('limit');
    $apps = Application::where('published', true)->inRandomOrder()->limit($limit)->get();

    $this->info("Comprobando salud de enlaces en {$apps->count()} aplicaciones...");
    $deadFound = 0;

    foreach ($apps as $app) {
        $health = $checker->checkApplication($app);
        if (! $health['has_working_link'] && $health['checked_count'] > 0) {
            $this->error("✕ Enlace caído detectado en: {$app->name}");
            BrokenLinkReport::create([
                'application_id' => $app->id,
                'type' => 'broken_link',
                'notes' => 'Detector automático: ningún servidor de descarga responde correctamente.',
                'ip_address' => '127.0.0.1 (Health-Checker)',
                'status' => 'pending',
            ]);
            $deadFound++;
        } else {
            $this->line("✓ {$app->name} - OK ({$health['checked_count']} enlaces)");
        }
    }

    $this->info("Diagnóstico finalizado: {$deadFound} enlaces caídos registrados en reportes.");

    return 0;
})->purpose('Comprueba la salud de los enlaces de descarga y registra reportes automáticos si están caídos');
