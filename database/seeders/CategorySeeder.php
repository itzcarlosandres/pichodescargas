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
                'name' => 'Programas',
                'slug' => 'programas',
                'description' => 'Programas y software para PC Windows',
                'icon' => 'programas',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Mac Apps',
                'slug' => 'mac-apps',
                'description' => 'Aplicaciones y software para macOS',
                'icon' => 'mac-apps',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Apps Android',
                'slug' => 'apps-android',
                'description' => 'Aplicaciones y herramientas APK para Android',
                'icon' => 'apps-android',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Juegos PC',
                'slug' => 'juegos-pc',
                'description' => 'Juegos completos para PC Windows',
                'icon' => 'juegos-pc',
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        Category::query()->delete();

        foreach ($categories as $cat) {
            Category::create($cat);
        }
    }
}
