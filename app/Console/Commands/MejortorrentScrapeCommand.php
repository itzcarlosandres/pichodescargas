<?php

namespace App\Console\Commands;

use App\Services\Scraper\MejortorrentImporter;
use Illuminate\Console\Command;

class MejortorrentScrapeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mejortorrent:scrape 
                            {--type=movies : Tipo de contenido a procesar (movies, series, all)}
                            {--limit=6 : Cantidad de aportes a procesar}
                            {--quality=all : Calidad a buscar (all, hd, 4k)}
                            {--force : Forzar publicación directa ignorando modo goteo/borrador}
                            {--url= : URL específica de una película o serie a importar}
                            {--backlog : Importar lote del catálogo histórico para la cola}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rastrea e importa películas y series torrent en español';

    /**
     * Execute the console command.
     */
    public function handle(MejortorrentImporter $importer): int
    {
        $type = strtolower((string) $this->option('type'));
        $this->info("🎬 Iniciando Scraper MejorTorrent / DonTorrent (Modo: {$type})...");

        $specificUrl = (string) $this->option('url');
        if (! empty($specificUrl)) {
            $this->info("Importando aporte desde URL: {$specificUrl}");
            $force = $this->option('force') ? true : null;

            if (str_contains($specificUrl, '/series') || str_contains($specificUrl, '/serie')) {
                $item = $importer->importSeriesByUrl($specificUrl, true, $force);
            } else {
                $item = $importer->importMovieByUrl($specificUrl, true, $force);
            }

            if ($item) {
                $status = $item->published ? 'Publicada' : 'Borrador (Draft)';
                $this->info("✓ Aporte importado con éxito: [{$item->id}] {$item->name} ({$status})");

                return 0;
            }

            $this->error('✗ No se pudo importar desde la URL proporcionada.');

            return 1;
        }

        $limit = max(1, (int) $this->option('limit'));
        $quality = (string) $this->option('quality');

        if ($this->option('backlog')) {
            $this->info("Extrayendo {$limit} aportes del catálogo histórico...");
            $res = $importer->importBacklogBatch($limit, true, true, $quality);
            $this->info("✓ Importados {$res['imported']} aportes históricos desde la página {$res['page_used']}.");

            return 0;
        }

        $force = (bool) $this->option('force');

        if ($type === 'series') {
            $this->info("Sincronizando últimas series (Meta: {$limit} temporadas, calidad: {$quality})...");
            $res = $importer->syncLatestSeries($limit, $quality);
            $this->info("✓ Sincronización de series finalizada: {$res['imported']} temporadas nuevas importadas.");
        } elseif ($type === 'all') {
            $half = (int) ceil($limit / 2);
            $this->info("Sincronizando novedades mixtas (Meta: {$half} películas, {$half} series)...");
            $resMovies = $importer->syncLatestMovies($force, $half, 3, $quality);
            $resSeries = $importer->syncLatestSeries($half, $quality);
            $this->info("✓ Novedades importadas: {$resMovies['imported']} películas, {$resSeries['imported']} temporadas de series.");
        } else {
            $this->info("Sincronizando últimas películas (Meta: {$limit} películas, calidad: {$quality})...");
            $res = $importer->syncLatestMovies($force, $limit, 3, $quality);
            $this->info("✓ Sincronización finalizada: {$res['imported']} películas nuevas importadas.");
        }

        return 0;
    }
}
