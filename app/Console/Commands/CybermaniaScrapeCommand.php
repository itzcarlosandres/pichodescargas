<?php

namespace App\Console\Commands;

use App\Services\Scraper\CybermaniaImporter;
use Illuminate\Console\Command;

class CybermaniaScrapeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cybermania:scrape
                            {--app= : Importar un programa o juego específico por URL}
                            {--category=all : Categoría específica (appz, android, games, hardware o all)}
                            {--sync-latest : Sincronizar automáticamente novedades desde la portada o categoría}
                            {--pending-updates : Listar únicamente las aplicaciones que tienen nueva versión disponible}
                            {--pages=1 : Número de páginas a recorrer}
                            {--limit=10 : Límite de aplicaciones a importar}
                            {--no-images : No descargar las imágenes ni capturas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extrae programas Windows, aplicaciones Android y juegos PC desde CyberMania (https://www.cybermania.ws/)';

    /**
     * Execute the console command.
     */
    public function handle(CybermaniaImporter $importer): int
    {
        $downloadImages = ! $this->option('no-images');

        // 1. Audit pending updates
        if ($this->option('pending-updates')) {
            $pages = max(1, (int) $this->option('pages'));
            $category = (string) $this->option('category');
            $this->info("Buscando aplicaciones con actualizaciones pendientes en CyberMania ({$pages} páginas, categoría: {$category})...");
            $pending = $importer->getPendingUpdates($pages, $category);

            if (empty($pending)) {
                $this->info('✓ Todos tus programas de CyberMania están al día. No hay actualizaciones pendientes.');

                return Command::SUCCESS;
            }

            $this->warn('Se encontraron '.count($pending).' aplicaciones que requieren actualización:');
            foreach ($pending as $item) {
                $localVer = $item['local_version'] ? "v{$item['local_version']}" : 'desconocida';
                $newVer = $item['version'] ? "v{$item['version']}" : 'N/A';
                $this->line("  • <info>{$item['clean_name']}</info> (Local: <comment>{$localVer}</comment> → CyberMania: <fg=green>{$newVer}</>)");
                $this->line("    URL: {$item['url']}");
            }

            return Command::SUCCESS;
        }

        // 2. Import single app by URL
        if ($appUrl = $this->option('app')) {
            $this->info("Importando aplicación individual desde {$appUrl}...");
            $app = $importer->importAppByUrl($appUrl, $downloadImages);

            if ($app) {
                $this->info("✓ Aplicación importada con éxito: {$app->name} (v{$app->version})");
                $this->line("  Categoría: {$app->category?->name}");
                $this->line('  Descarga directa: '.($app->download_url_external ?: 'N/A'));
                if ($app->has_torrent) {
                    $this->line('  Torrent / Magnet: Sí');
                }

                return Command::SUCCESS;
            }

            $this->error("No se pudo importar la aplicación desde {$appUrl}");

            return Command::FAILURE;
        }

        // 3. Sync latest releases
        if ($this->option('sync-latest')) {
            $limit = max(1, (int) $this->option('limit'));
            $pages = max(1, (int) $this->option('pages'));
            $category = (string) $this->option('category');

            $this->info("Sincronizando últimas novedades de CyberMania (límite: {$limit}, páginas: {$pages}, categoría: {$category})...");
            $result = $importer->syncLatestUpdates(true, $limit, $pages, $category);

            $this->info("✓ Proceso completado: {$result['imported']} nuevas, {$result['updated']} actualizadas, {$result['skipped']} omitidas, {$result['failed']} fallidas.");

            return Command::SUCCESS;
        }

        // 4. Default: list latest catalog items
        $category = (string) $this->option('category');
        $this->info("Consultando últimos títulos publicados en CyberMania (Categoría: {$category})...");
        $data = $importer->getCybermaniaApps(1, true, $category);

        $headers = ['Título', 'Versión', 'Categoría', 'Estado Local'];
        $rows = [];

        foreach ($data['items'] as $item) {
            $status = $item['already_imported']
                ? ($item['has_update'] ? '<comment>Actualización disponible</comment>' : '<info>Ya importada</info>')
                : '<fg=gray>No importada</>';

            $rows[] = [
                $item['clean_name'],
                $item['version'] ?: 'N/A',
                $item['category_name'],
                $status,
            ];
        }

        $this->table($headers, $rows);
        $this->info('Usa --sync-latest para importar o --app="URL" para importar una específica.');

        return Command::SUCCESS;
    }
}
