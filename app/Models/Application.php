<?php

namespace App\Models;

use App\Services\Scraper\TorrentmacImporter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'version',
        'size',
        'developer',
        'license',
        'platform',
        'icon',
        'screenshot',
        'changelog',
        'features',
        'download_url',
        'download_url_external',
        'download_mirrors',
        'torrent_url',
        'torrent_file_path',
        'magnet_link',
        'has_torrent',
        'featured',
        'popular',
        'published',
        'downloads',
        'rating',
        'reviews_count',
        'released_at',
        'telegram_sent',
        'telegram_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'download_mirrors' => 'array',
            'has_torrent' => 'boolean',
            'featured' => 'boolean',
            'popular' => 'boolean',
            'published' => 'boolean',
            'downloads' => 'integer',
            'rating' => 'decimal:2',
            'reviews_count' => 'integer',
            'released_at' => 'datetime',
            'telegram_sent' => 'boolean',
            'telegram_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $clearHomeCaches = function () {
            cache()->forget('sitemap_xml');
            cache()->forget('home_stats');
            cache()->forget('home_featured_apps');
            cache()->forget('home_recently_updated_apps');
            cache()->forget('home_top_categories');
        };

        static::saved($clearHomeCaches);
        static::deleted($clearHomeCaches);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ApplicationVersion::class)->orderBy('released_at', 'desc');
    }

    public function currentVersion()
    {
        return $this->hasOne(ApplicationVersion::class)->where('is_current', true);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ApplicationImage::class)->orderBy('sort_order');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('approved', true);
    }

    public function rootApprovedReviews(): HasMany
    {
        return $this->hasMany(Review::class)
            ->where('approved', true)
            ->whereNull('parent_id')
            ->with('approvedReplies');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    public function scopePendingTelegram($query)
    {
        return $query->where('published', true)
            ->when(
                Schema::hasColumn('applications', 'telegram_sent'),
                fn ($q) => $q->where('telegram_sent', false),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->oldest('id');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopePopular($query)
    {
        return $query->where('popular', true);
    }

    public function scopeOrderedByDownloads($query)
    {
        return $query->orderBy('downloads', 'desc');
    }

    public function scopeOrderedByRating($query)
    {
        return $query->orderBy('rating', 'desc');
    }

    public function scopeOrderedByNewest($query)
    {
        return $query->orderBy('released_at', 'desc');
    }

    public function scopeRecentlyAdded($query)
    {
        return $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
    }

    public function scopeRecentlyUpdated($query)
    {
        return $query->whereColumn('updated_at', '>', 'created_at')
            ->orderBy('updated_at', 'desc')
            ->orderBy('id', 'desc');
    }

    public function scopeSearch($query, ?string $term)
    {
        $term = trim($term ?? '');
        if ($term === '') {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('developer', 'like', "%{$term}%")
                ->orWhereHas('category', function ($cat) use ($term) {
                    $cat->where('name', 'like', "%{$term}%");
                })
                ->orWhereHas('tags', function ($tag) use ($term) {
                    $tag->where('name', 'like', "%{$term}%");
                });
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getFormattedDownloadsAttribute(): string
    {
        if ($this->downloads >= 1000000) {
            return number_format($this->downloads / 1000000, 1).'M';
        }
        if ($this->downloads >= 1000) {
            return number_format($this->downloads / 1000, 1).'K';
        }

        return (string) $this->downloads;
    }

    public function getFormattedSizeAttribute(): string
    {
        return $this->size ?? 'N/A';
    }

    public function getIconUrlAttribute(): string
    {
        if ($this->icon) {
            if (str_starts_with($this->icon, 'http://') || str_starts_with($this->icon, 'https://')) {
                return $this->icon;
            }

            return asset('storage/'.$this->icon);
        }

        return asset('images/default-icon.svg');
    }

    public function getScreenshotUrlAttribute(): ?string
    {
        if ($this->screenshot) {
            if (str_starts_with($this->screenshot, 'http://') || str_starts_with($this->screenshot, 'https://')) {
                return $this->screenshot;
            }

            return asset('storage/'.$this->screenshot);
        }

        $firstImage = $this->images->first();
        if ($firstImage) {
            return $firstImage->url;
        }

        return null;
    }

    public function getCoverImageUrlAttribute(): string
    {
        return route('app.og-image', $this->slug);
    }

    public function getWhatsNewAttribute(): ?string
    {
        return $this->changelog ?? $this->currentVersion?->changelog;
    }

    /**
     * Determine if this application has been updated to a newer version
     */
    public function getIsUpdatedAttribute(): bool
    {
        if ($this->relationLoaded('versions')) {
            if ($this->versions->where('is_current', false)->isNotEmpty() || $this->versions->count() > 1) {
                return true;
            }
        }

        if ($this->updated_at && $this->created_at) {
            return $this->updated_at->gt($this->created_at->addMinutes(5))
                && $this->updated_at->gte(now()->subDays(30));
        }

        return false;
    }

    public function incrementDownloads(): void
    {
        static::withoutTimestamps(function () {
            $this->increment('downloads');
        });
    }

    public function recalculateRating(): void
    {
        $avg = $this->approvedReviews()->avg('rating');
        $count = $this->approvedReviews()->count();

        $this->update([
            'rating' => $avg ? round($avg, 2) : 0,
            'reviews_count' => $count,
        ]);
    }

    /**
     * Release a balanced batch of draft applications (e.g. 5 Torrent and 5 DDL)
     * If one source lacks enough drafts, fills remainder with the oldest available drafts.
     *
     * @return array{published_count: int, torrent_count: int, ddl_count: int, apps: Collection}
     */
    public static function releaseDripBatch(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));

        $pcAndroidCategoryIds = Category::whereIn('slug', ['programas', 'juegos-pc', 'apps-android'])->pluck('id')->all();
        $macCategoryIds = Category::whereIn('slug', ['mac-apps'])->pluck('id')->all();
        $movieCategoryIds = Category::whereIn('slug', ['peliculas-gratis'])->pluck('id')->all();

        // Check if movies are in draft queue to decide 4-way vs 3-way split
        $hasMovieDrafts = ! empty($movieCategoryIds) && static::where('published', false)->whereIn('category_id', $movieCategoryIds)->exists();
        $ways = $hasMovieDrafts ? 4 : 3;

        // Balanced split quotas (e.g. 20 with movies -> 5 Torrent Mac, 5 DDL Mac, 5 PC/Android, 5 Películas)
        $quota1 = (int) ceil($limit / $ways);
        $rem1 = max(0, $limit - $quota1);
        $quota2 = (int) ceil($rem1 / ($ways - 1));
        $rem2 = max(0, $rem1 - $quota2);
        $quota3 = $hasMovieDrafts ? (int) ceil($rem2 / 2) : $rem2;
        $quota4 = $hasMovieDrafts ? max(0, $rem2 - $quota3) : 0;

        // 1. Up to quota1 Torrent drafts (oldest first, excluding movies if movies handled separately)
        $torrentQuery = static::where('published', false)->where('has_torrent', true);
        if ($hasMovieDrafts) {
            $torrentQuery->whereNotIn('category_id', $movieCategoryIds);
        }
        $torrentDrafts = $torrentQuery->orderBy('id', 'asc')->limit($quota1)->get();

        $selectedIds = $torrentDrafts->pluck('id')->all();

        // 2. Up to quota2 Mac DDL drafts (oldest first)
        $ddlQuery = static::where('published', false)
            ->where('has_torrent', false)
            ->whereNotIn('id', $selectedIds);

        if (! empty($macCategoryIds)) {
            $ddlDrafts = (clone $ddlQuery)->whereIn('category_id', $macCategoryIds)
                ->orderBy('id', 'asc')
                ->limit($quota2)
                ->get();
            if ($ddlDrafts->count() < $quota2) {
                $additionalDdl = $ddlQuery->whereNotIn('id', array_merge($selectedIds, $ddlDrafts->pluck('id')->all()))
                    ->orderBy('id', 'asc')
                    ->limit($quota2 - $ddlDrafts->count())
                    ->get();
                $ddlDrafts = $ddlDrafts->concat($additionalDdl);
            }
        } else {
            $ddlDrafts = $ddlQuery->orderBy('id', 'asc')->limit($quota2)->get();
        }

        $selectedIds = array_merge($selectedIds, $ddlDrafts->pluck('id')->all());

        // 3. Up to quota3 PC & Android drafts (balanced across Programas, Juegos PC, and Apps Android)
        $pcAndroidDrafts = collect();
        if (! empty($pcAndroidCategoryIds) && $quota3 > 0) {
            $catCount = count($pcAndroidCategoryIds);
            $subQuota = max(1, (int) ceil($quota3 / $catCount));

            // First pass: pull balanced drafts from each PC/Android category
            foreach ($pcAndroidCategoryIds as $subCatId) {
                if ($pcAndroidDrafts->count() >= $quota3) {
                    break;
                }
                $take = min($subQuota, $quota3 - $pcAndroidDrafts->count());
                $subDrafts = static::where('published', false)
                    ->where('category_id', $subCatId)
                    ->whereNotIn('id', array_merge($selectedIds, $pcAndroidDrafts->pluck('id')->all()))
                    ->orderBy('id', 'asc')
                    ->limit($take)
                    ->get();
                $pcAndroidDrafts = $pcAndroidDrafts->concat($subDrafts);
            }

            // Second pass: if some subcategories had fewer drafts, fill remaining quota3 from any available PC/Android category
            if ($pcAndroidDrafts->count() < $quota3) {
                $remPCDrafts = static::where('published', false)
                    ->whereIn('category_id', $pcAndroidCategoryIds)
                    ->whereNotIn('id', array_merge($selectedIds, $pcAndroidDrafts->pluck('id')->all()))
                    ->orderBy('id', 'asc')
                    ->limit($quota3 - $pcAndroidDrafts->count())
                    ->get();
                $pcAndroidDrafts = $pcAndroidDrafts->concat($remPCDrafts);
            }

            $selectedIds = array_merge($selectedIds, $pcAndroidDrafts->pluck('id')->all());
        }

        // 4. Up to quota4 Movie drafts (oldest first)
        $movieDrafts = collect();
        if ($hasMovieDrafts && $quota4 > 0) {
            $movieDrafts = static::where('published', false)
                ->whereIn('category_id', $movieCategoryIds)
                ->whereNotIn('id', $selectedIds)
                ->orderBy('id', 'asc')
                ->limit($quota4)
                ->get();
            $selectedIds = array_merge($selectedIds, $movieDrafts->pluck('id')->all());
        }

        // 5. Fallback: If not reached $limit, fill with any remaining oldest available drafts regardless of source
        $remainderNeeded = $limit - count($selectedIds);
        $fillDrafts = collect();
        if ($remainderNeeded > 0) {
            $fillDrafts = static::where('published', false)
                ->whereNotIn('id', $selectedIds)
                ->orderBy('id', 'asc')
                ->limit($remainderNeeded)
                ->get();
        }

        $allDrafts = $torrentDrafts->concat($ddlDrafts)->concat($pcAndroidDrafts)->concat($movieDrafts)->concat($fillDrafts);

        if ($allDrafts->isNotEmpty()) {
            $now = now();
            foreach ($allDrafts as $draftApp) {
                $draftApp->update([
                    'published' => true,
                    'created_at' => $now,
                    'released_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        return [
            'published_count' => $allDrafts->count(),
            'torrent_count' => $allDrafts->where('has_torrent', true)->whereNotIn('category_id', $movieCategoryIds)->count(),
            'ddl_count' => $allDrafts->where('has_torrent', false)->whereNotIn('category_id', array_merge($pcAndroidCategoryIds, $movieCategoryIds))->count(),
            'pc_android_count' => $allDrafts->whereIn('category_id', $pcAndroidCategoryIds)->count(),
            'movies_count' => $allDrafts->whereIn('category_id', $movieCategoryIds)->count(),
            'apps' => $allDrafts,
        ];
    }

    /**
     * Retrieve or dynamically extract the magnet link from torrent file if missing.
     */
    public function getMagnetLinkAttribute(?string $value): ?string
    {
        if (! empty($value)) {
            return $value;
        }

        if (! empty($this->torrent_url)) {
            $parsedPath = parse_url($this->torrent_url, PHP_URL_PATH);
            $filename = basename($parsedPath ?? '');
            if (! empty($filename)) {
                $paths = [
                    public_path('storage/torrents/'.$filename),
                    storage_path('app/public/torrents/'.$filename),
                ];

                foreach ($paths as $path) {
                    if (file_exists($path)) {
                        $content = @file_get_contents($path);
                        if ($content) {
                            $importer = app(TorrentmacImporter::class);
                            $extracted = $importer->extractMagnetFromTorrent($content, $this->name);
                            if ($extracted) {
                                $this->attributes['magnet_link'] = $extracted;
                                @$this->updateQuietly(['magnet_link' => $extracted]);

                                return $extracted;
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Resolve the operating system / platform label for this application.
     */
    public function resolvePlatformLabel(): string
    {
        return match (true) {
            str_contains(strtolower($this->platform ?? ''), 'android') || $this->category?->slug === 'apps-android' => 'Android',
            str_contains(strtolower($this->platform ?? ''), 'win') || in_array($this->category?->slug, ['programas', 'juegos-pc']) => 'Windows',
            default => 'Mac',
        };
    }

    /**
     * Resolve the dynamic SEO Title based on global admin settings pattern.
     * Supports: {title}, {version}, {category}, {site_name}
     * Intelligently prevents duplicate versions (e.g., if title already contains version).
     */
    public function resolveSeoTitle(?string $platform = null): string
    {
        $pattern = (string) setting('seo_app_title_pattern', '{title} for {category} - {site_name}');
        if (trim($pattern) === '') {
            $pattern = '{title} for {category} - {site_name}';
        }

        $platformLabel = $platform ?? $this->resolvePlatformLabel();
        $siteName = (string) setting('site_name', 'PichoDescargas');
        $version = trim((string) ($this->version ?? ''));
        $title = trim((string) ($this->name ?? ''));

        // Anti-duplication logic:
        // If the pattern explicitly contains {version} AND the app name already ends with or includes the version,
        // strip the version from the title so it does not duplicate (e.g. "NWinfo 1.6.7 1.6.7").
        if ($version !== '') {
            if (str_contains($pattern, '{version}')) {
                $patternRegex = '/\s+v?'.preg_quote($version, '/').'$/i';
                $title = preg_replace($patternRegex, '', $title) ?? $title;
            }
        }

        $replacements = [
            '{title}' => $title,
            '{version}' => $version,
            '{category}' => $platformLabel,
            '{site_name}' => $siteName,
        ];

        $resolved = str_replace(array_keys($replacements), array_values($replacements), $pattern);

        return trim((string) preg_replace('/\s+/', ' ', $resolved));
    }

    /**
     * Resolve the dynamic SEO Meta Description based on global admin settings pattern.
     * Supports: {description}, {title}, {version}, {category}, {site_name}
     */
    public function resolveSeoDescription(?string $platform = null): string
    {
        $pattern = (string) setting('seo_app_description_pattern', '{title} for {category}. Safe, clean and verified software for {category}.');
        if (trim($pattern) === '') {
            $pattern = '{title} for {category}. Safe, clean and verified software for {category}.';
        }

        $platformLabel = $platform ?? $this->resolvePlatformLabel();
        $siteName = (string) setting('site_name', 'PichoDescargas');
        $version = trim((string) ($this->version ?? ''));
        $title = trim((string) ($this->name ?? ''));

        $rawDesc = strip_tags((string) ($this->short_description ?: $this->description));
        $cleanDesc = Str::limit(trim((string) preg_replace('/\s+/', ' ', $rawDesc)), 160);
        if ($cleanDesc === '') {
            $cleanDesc = "{$title} for {$platformLabel}. Safe, clean and verified software.";
        }

        $replacements = [
            '{description}' => $cleanDesc,
            '{title}' => $title,
            '{version}' => $version,
            '{category}' => $platformLabel,
            '{site_name}' => $siteName,
        ];

        $resolved = str_replace(array_keys($replacements), array_values($replacements), $pattern);

        return trim((string) preg_replace('/\s+/', ' ', $resolved));
    }
}
