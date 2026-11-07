<?php

namespace App\Services\Scraper;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MejortorrentClient
{
    protected string $defaultBaseUrl = 'https://dontorrent.tips';

    protected array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0',
    ];

    /**
     * Get the active base URL (configurable via Settings)
     */
    public function getBaseUrl(): string
    {
        $url = (string) Setting::get('mejortorrent_base_url', $this->defaultBaseUrl);

        return rtrim(empty($url) ? $this->defaultBaseUrl : $url, '/');
    }

    /**
     * Get a random browser User-Agent
     */
    protected function getRandomUserAgent(): string
    {
        return $this->userAgents[array_rand($this->userAgents)];
    }

    /**
     * Fetch HTML with caching, headers and Cloudflare / FlareSolverr support if needed
     */
    public function fetchHtml(string $url, bool $useCache = true, int $ttlSeconds = 300): ?string
    {
        $cacheKey = 'mejortorrent_html_'.md5($url);

        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        // Option 1: If FlareSolverr is configured in Settings
        $flareUrl = (string) Setting::get('flaresolverr_url', '');
        if (! empty($flareUrl)) {
            try {
                $flareRes = Http::timeout(45)->post(rtrim($flareUrl, '/').'/v1', [
                    'cmd' => 'request.get',
                    'url' => $url,
                    'maxTimeout' => 60000,
                ]);

                if ($flareRes->successful() && ($flareRes->json('status') === 'ok')) {
                    $html = $flareRes->json('solution.response');
                    if ($html) {
                        if ($useCache && strlen($html) < 600000) {
                            Cache::put($cacheKey, $html, $ttlSeconds);
                        }

                        return $html;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("MejortorrentClient: FlareSolverr failed for {$url}: {$e->getMessage()}");
            }
        }

        // Option 2: Direct HTTP request with optional cf_clearance cookie
        try {
            $headers = [
                'User-Agent' => $this->getRandomUserAgent(),
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
            ];

            $cfCookie = (string) Setting::get('mejortorrent_cf_clearance', '');
            if (! empty($cfCookie)) {
                $headers['Cookie'] = 'cf_clearance='.$cfCookie;
            }

            $response = Http::withoutVerifying()
                ->withHeaders($headers)
                ->timeout(25)
                ->retry(2, 500)
                ->get($url);

            if ($response->successful()) {
                $html = $response->body();
                if ($useCache && ! empty($html) && strlen($html) < 600000) {
                    try {
                        Cache::put($cacheKey, $html, $ttlSeconds);
                    } catch (\Throwable) {
                    }
                }

                return $html;
            }

            Log::warning("MejortorrentClient: Failed to fetch {$url} status {$response->status()}");

            return null;
        } catch (\Throwable $e) {
            Log::error("MejortorrentClient exception on {$url}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Fetch catalog movies page
     *
     * @param  string  $quality  'all', 'hd', '4k'
     */
    public function getMoviesCatalogHtml(int $page = 1, string $quality = 'all'): ?string
    {
        $base = $this->getBaseUrl();

        $path = match ($quality) {
            'hd' => '/peliculas/hd',
            '4k' => '/peliculas/4K',
            default => '/peliculas',
        };

        $url = $page > 1 ? "{$base}{$path}/page/{$page}" : "{$base}{$path}";

        return $this->fetchHtml($url);
    }

    /**
     * Fetch catalog series page
     *
     * @param  string  $quality  'all', 'hd', '4k'
     */
    public function getSeriesCatalogHtml(int $page = 1, string $quality = 'all'): ?string
    {
        $base = $this->getBaseUrl();

        $path = match ($quality) {
            'hd' => '/series/hd',
            '4k' => '/series/4k',
            default => '/series',
        };

        $url = $page > 1 ? "{$base}{$path}/page/{$page}" : "{$base}{$path}";

        return $this->fetchHtml($url);
    }

    /**
     * Request Proof of Work challenge for downloading a movie torrent
     */
    public function requestPowChallenge(int $contentId, string $tabla = 'peliculas'): ?string
    {
        $base = $this->getBaseUrl();
        $apiUrl = "{$base}/api_validate_pow.php";

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => $this->getRandomUserAgent(),
                    'Content-Type' => 'application/json',
                    'Referer' => "{$base}/pelicula/{$contentId}/",
                ])
                ->timeout(15)
                ->post($apiUrl, [
                    'action' => 'generate',
                    'content_id' => $contentId,
                    'tabla' => $tabla,
                ]);

            if ($response->successful() && $response->json('success')) {
                return (string) $response->json('challenge');
            }

            Log::warning("MejortorrentClient: PoW challenge generation failed for {$contentId}: {$response->body()}");

            return null;
        } catch (\Throwable $e) {
            Log::error("MejortorrentClient PoW challenge exception for {$contentId}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Solve Proof of Work (finds nonce where sha256(challenge + nonce) starts with '000')
     */
    public function solvePoW(string $challenge, int $difficulty = 3): int
    {
        $target = str_repeat('0', $difficulty);
        $nonce = 0;

        while (true) {
            if (str_starts_with(hash('sha256', $challenge.$nonce), $target)) {
                return $nonce;
            }
            $nonce++;
        }
    }

    /**
     * Validate Proof of Work solution and obtain direct torrent download URL
     */
    public function validatePow(string $challenge, int $nonce, int $contentId = 0): ?string
    {
        $base = $this->getBaseUrl();
        $apiUrl = "{$base}/api_validate_pow.php";

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => $this->getRandomUserAgent(),
                    'Content-Type' => 'application/json',
                    'Referer' => "{$base}/pelicula/{$contentId}/",
                ])
                ->timeout(15)
                ->post($apiUrl, [
                    'action' => 'validate',
                    'challenge' => $challenge,
                    'nonce' => $nonce,
                ]);

            if ($response->successful() && $response->json('success')) {
                $downloadUrl = (string) $response->json('download_url');
                if (str_starts_with($downloadUrl, '//')) {
                    $downloadUrl = 'https:'.$downloadUrl;
                }

                return $downloadUrl;
            }

            Log::warning("MejortorrentClient: PoW validation failed: {$response->body()}");

            return null;
        } catch (\Throwable $e) {
            Log::error("MejortorrentClient PoW validation exception: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Download binary file content (.torrent file or image)
     */
    public function downloadBinary(string $url, ?string $referer = null): ?string
    {
        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        }

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => $this->getRandomUserAgent(),
                    'Accept' => '*/*',
                    'Referer' => $referer ?: $this->getBaseUrl().'/',
                ])
                ->timeout(30)
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning("MejortorrentClient: Failed to download binary from {$url} status {$response->status()}");

            return null;
        } catch (\Throwable $e) {
            Log::error("MejortorrentClient binary download exception for {$url}: {$e->getMessage()}");

            return null;
        }
    }
}
