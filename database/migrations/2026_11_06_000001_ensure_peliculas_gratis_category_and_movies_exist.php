<?php

use App\Models\Application;
use App\Models\Category;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'peliculas-gratis'],
            [
                'name' => 'Free Movies',
                'description' => 'Películas, estrenos y series en Full HD',
                'icon' => 'peliculas-gratis',
                'sort_order' => 5,
                'is_active' => true,
            ]
        );

        if (! $category->is_active) {
            $category->update(['is_active' => true, 'sort_order' => 5]);
        }

        if (app()->environment('testing')) {
            return;
        }

        $movies = [
            [
                'name' => 'Deadpool & Wolverine',
                'slug' => 'deadpool-and-wolverine-2024',
                'short_description' => 'El mercenario bocazas y Logan unen fuerzas para salvar el multiverso.',
                'version' => '2024 • 4K UHD',
                'size' => '3.2 GB',
                'developer' => 'Marvel Studios',
                'platform' => 'Dual Latino / Inglés',
                'download_url' => 'https://theuser.cloud/demo-file.html',
                'download_url_external' => 'https://theuser.cloud/demo-file.html',
                'has_torrent' => true,
                'published' => true,
                'downloads' => 6800,
            ],
            [
                'name' => 'Dune: Part Two',
                'slug' => 'dune-parte-dos-2024',
                'short_description' => 'Paul Atreides se une a Chani y a los Fremen en busca de venganza en Arrakis.',
                'version' => '2024 • 1080p',
                'size' => '3.8 GB',
                'developer' => 'Warner Bros.',
                'platform' => 'Dual Latino / Sub',
                'download_url' => 'https://theuser.cloud/demo-file.html',
                'download_url_external' => 'https://theuser.cloud/demo-file.html',
                'has_torrent' => true,
                'published' => true,
                'downloads' => 5400,
            ],
            [
                'name' => 'Oppenheimer',
                'slug' => 'oppenheimer-2023',
                'short_description' => 'La historia del físico J. Robert Oppenheimer y el Proyecto Manhattan.',
                'version' => '2023 • IMAX HDR',
                'size' => '4.2 GB',
                'developer' => 'Universal Pictures',
                'platform' => 'Dual Audio',
                'download_url' => 'https://theuser.cloud/demo-file.html',
                'download_url_external' => 'https://theuser.cloud/demo-file.html',
                'has_torrent' => true,
                'published' => true,
                'downloads' => 4100,
            ],
            [
                'name' => 'Inside Out 2',
                'slug' => 'intensamente-2-inside-out-2',
                'short_description' => 'Riley entra a la adolescencia y nuevas emociones toman el cuartel general.',
                'version' => '2024 • 1080p Full',
                'size' => '2.1 GB',
                'developer' => 'Pixar / Disney',
                'platform' => 'Español Latino',
                'download_url' => 'https://theuser.cloud/demo-file.html',
                'download_url_external' => 'https://theuser.cloud/demo-file.html',
                'has_torrent' => false,
                'published' => true,
                'downloads' => 5900,
            ],
            [
                'name' => 'Gladiator II',
                'slug' => 'gladiator-2-2024',
                'short_description' => 'Lucio regresa a Roma obligado a luchar en el Coliseo por la gloria del Imperio.',
                'version' => '2024 • 1080p',
                'size' => '3.5 GB',
                'developer' => 'Paramount Pictures',
                'platform' => 'Dual Latino / Sub',
                'download_url' => 'https://theuser.cloud/demo-file.html',
                'download_url_external' => 'https://theuser.cloud/demo-file.html',
                'has_torrent' => true,
                'published' => true,
                'downloads' => 3800,
            ],
            [
                'name' => 'Avatar: The Way of Water',
                'slug' => 'avatar-el-sentido-del-agua',
                'short_description' => 'Jake Sully y Neytiri forman una familia y exploran las regiones de Pandora.',
                'version' => '2023 • 4K HDR',
                'size' => '5.1 GB',
                'developer' => '20th Century Studios',
                'platform' => 'Dual Audio',
                'download_url' => 'https://theuser.cloud/demo-file.html',
                'download_url_external' => 'https://theuser.cloud/demo-file.html',
                'has_torrent' => true,
                'published' => true,
                'downloads' => 4700,
            ],
        ];

        foreach ($movies as $movie) {
            Application::firstOrCreate(
                ['slug' => $movie['slug']],
                array_merge($movie, ['category_id' => $category->id])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep data intact
    }
};
