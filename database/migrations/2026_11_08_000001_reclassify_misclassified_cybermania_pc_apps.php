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
        $programasCategory = Category::whereIn('slug', ['programas', 'programs'])
            ->orWhereIn('name', ['Programas', 'Programs'])
            ->orWhere('id', 1)
            ->first();

        if (! $programasCategory) {
            $programasCategory = Category::firstOrCreate(
                ['slug' => 'programas'],
                [
                    'name' => 'Programs',
                    'description' => 'Verified programs, software tools, and utilities for Windows PC.',
                    'icon' => 'programas',
                    'sort_order' => 1,
                    'is_active' => true,
                ]
            );
        }

        $androidCategory = Category::whereIn('slug', ['apps-android', 'android'])
            ->orWhereIn('name', ['Apps Android', 'Android Apps'])
            ->orWhere('id', 3)
            ->first();

        if ($androidCategory && $programasCategory) {
            // Find all applications currently filed under Android that are actually PC software
            $candidates = Application::where('category_id', $androidCategory->id)
                ->where(function ($query) {
                    $query->where('platform', 'like', '%Windows%')
                        ->orWhere('platform', 'like', '%PC%')
                        ->orWhere('platform', 'like', '%Win%')
                        ->orWhere('developer', 'CyberMania')
                        ->orWhere('name', 'like', '%Nitro%')
                        ->orWhere('name', 'like', '%QILING%')
                        ->orWhere('name', 'like', '%Winamp%')
                        ->orWhere('name', 'like', '%7-Zip%')
                        ->orWhere('name', 'like', '%Restorer%')
                        ->orWhere('name', 'like', '%Driver%')
                        ->orWhere('name', 'like', '%Office%')
                        ->orWhere('name', 'like', '%Adobe%')
                        ->orWhere('name', 'like', '%AutoCAD%')
                        ->orWhere('name', 'like', '%Parted Magic%')
                        ->orWhere('name', 'like', '%ON1%')
                        ->orWhere('download_url_external', 'like', '%/appz/%')
                        ->orWhere('download_url_external', 'like', '%.exe%');
                })
                ->get();

            foreach ($candidates as $app) {
                $lowerName = strtolower($app->name);

                // Preserve true Android APKs
                if (str_contains($lowerName, ' apk') || str_contains($lowerName, '(android)') || str_contains($lowerName, '[android]')) {
                    continue;
                }

                $platform = (empty($app->platform) || strtolower($app->platform) === 'android')
                    ? 'Windows 11 / 10'
                    : $app->platform;

                $app->update([
                    'category_id' => $programasCategory->id,
                    'platform' => $platform,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: Data reclassification does not need to be rolled back to erroneous state
    }
};
