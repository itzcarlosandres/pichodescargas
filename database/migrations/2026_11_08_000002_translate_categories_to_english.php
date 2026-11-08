<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $translations = [
            'programas' => [
                'name' => 'Programs',
                'description' => 'Verified programs, software tools, and utilities for Windows PC.',
            ],
            'mac-apps' => [
                'name' => 'Mac Apps',
                'description' => 'Universal applications and productivity software for macOS.',
            ],
            'apps-android' => [
                'name' => 'Android Apps',
                'description' => 'Verified APK applications, premium mods, and mobile tools for Android.',
            ],
            'juegos-pc' => [
                'name' => 'PC Games',
                'description' => 'Full PC games, repacks, and direct installers for Windows PC.',
            ],
            'peliculas-gratis' => [
                'name' => 'Free Movies',
                'description' => 'High-definition movies, premieres, and cinema releases.',
            ],
            'series-tv' => [
                'name' => 'TV Series',
                'description' => 'Complete TV series, full seasons, and high-definition episodes.',
            ],
        ];

        foreach ($translations as $slug => $data) {
            Category::where('slug', $slug)->update([
                'name' => $data['name'],
                'description' => $data['description'],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
