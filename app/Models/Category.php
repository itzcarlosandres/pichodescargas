<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function () {
            cache()->forget('sitemap_xml');
        });

        static::deleted(function () {
            cache()->forget('sitemap_xml');
        });
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function publishedApplications(): HasMany
    {
        return $this->hasMany(Application::class)->where('published', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getNameAttribute(?string $value): string
    {
        return match ($this->slug) {
            'programas', 'programs' => 'Programs',
            'juegos-pc', 'pc-games' => 'PC Games',
            'apps-android', 'android' => 'Android Apps',
            'mac-apps' => 'Mac Apps',
            'peliculas-gratis', 'movies' => 'Free Movies',
            'series-tv', 'series' => 'TV Series',
            default => $value ?? '',
        };
    }

    public function getDescriptionAttribute(?string $value): string
    {
        if (empty($value) || in_array($value, [
            'Programas y software para PC Windows',
            'Juegos completos para PC Windows',
            'Aplicaciones y herramientas APK para Android',
            'Aplicaciones y software para macOS',
            'Películas, estrenos y series en Full HD',
            'Descarga series de televisión completas por torrent gratis en alta definición.',
        ])) {
            return match ($this->slug) {
                'programas', 'programs' => 'Verified programs, software tools, and utilities for Windows PC.',
                'juegos-pc', 'pc-games' => 'Full PC games, repacks, and direct installers for Windows PC.',
                'apps-android', 'android' => 'Verified APK applications, premium mods, and tools for Android.',
                'mac-apps' => 'Universal applications and productivity software for macOS.',
                'peliculas-gratis', 'movies' => 'High-definition movies, premieres, and cinema releases.',
                'series-tv', 'series' => 'Complete TV series, full seasons, and high-definition episodes.',
                default => $value ?? '',
            };
        }

        return $value;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
