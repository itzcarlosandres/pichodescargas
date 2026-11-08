<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Programs',
                'slug' => 'programas',
                'description' => 'Verified programs, software tools, and utilities for Windows PC.',
                'icon' => 'programas',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Mac Apps',
                'slug' => 'mac-apps',
                'description' => 'Universal applications and productivity software for macOS.',
                'icon' => 'mac-apps',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Android Apps',
                'slug' => 'apps-android',
                'description' => 'Verified APK applications, premium mods, and mobile tools for Android.',
                'icon' => 'apps-android',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'PC Games',
                'slug' => 'juegos-pc',
                'description' => 'Full PC games, repacks, and direct installers for Windows PC.',
                'icon' => 'juegos-pc',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Free Movies',
                'slug' => 'peliculas-gratis',
                'description' => 'High-definition movies, premieres, and cinema releases.',
                'icon' => 'peliculas-gratis',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'TV Series',
                'slug' => 'series-tv',
                'description' => 'Complete TV series, full seasons, and high-definition episodes.',
                'icon' => 'series-tv',
                'sort_order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }
    }
}
