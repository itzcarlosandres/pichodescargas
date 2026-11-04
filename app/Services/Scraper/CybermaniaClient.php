<?php

namespace App\Services\Scraper;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CybermaniaClient
{
    protected string $baseUrl = 'https://www.cybermania.ws';

    protected array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 Edg/129.0.0.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0',
    ];

    /**
     * Get base URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get a random browser User-Agent
     */
    protected function getRandomUserAgent(): string
    {
        return $this->userAgents[array_rand($this->userAgents)];
    }

    /**
     * Standard headers that simulate a real browser to bypass anti-adblock and Cloudflare LiteSpeed checks
     */
    protected function getBrowserHeaders(): array
    {
        return [
            'User-Agent' => $this->getRandomUserAgent(),
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9,es;q=0.8',
            'Referer' => $this->baseUrl.'/',
            'Sec-Ch-Ua' => '"Not/A)Brand";v="8", "Chromium";v="128", "Google Chrome";v="128"',
            'Sec-Ch-Ua-Mobile' => '?0',
            'Sec-Ch-Ua-Platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
        ];
    }

    /**
     * Fetch HTML with headers, timeout, retries and short-term caching
     */
    public function fetchHtml(string $url, bool $useCache = true, int $ttlSeconds = 300): ?string
    {
        $cacheKey = 'cybermania_html_'.md5($url);

        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        try {
            $response = Http::withoutVerifying()
                ->withHeaders($this->getBrowserHeaders())
                ->timeout(20)
                ->retry(2, 500)
                ->get($url);

            if ($response->successful()) {
                $html = $response->body();
                if ($useCache && ! empty($html) && strlen($html) < 600000) {
                    try {
                        Cache::put($cacheKey, $html, $ttlSeconds);
                    } catch (\Throwable) {
                        // Suppress cache storage failures
                    }
                }

                return $html;
            }

            Log::warning("CybermaniaClient: Failed to fetch {$url} status {$response->status()}");

            return null;
        } catch (\Throwable $e) {
            Log::error("CybermaniaClient exception on {$url}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Clear cached HTML for a specific URL
     */
    public function clearUrlCache(string $url): void
    {
        Cache::forget('cybermania_html_'.md5($url));
    }

    /**
     * Fetch home page / all catalog with pagination
     */
    public function getHomePageHtml(int $page = 1): ?string
    {
        if ($page > 1) {
            return $this->fetchHtml($this->baseUrl.'/post/'.$page.'/');
        }

        return $this->fetchHtml($this->baseUrl.'/');
    }

    /**
     * Fetch category page with pagination
     * e.g., 'appz' (Programs), 'android' (Android Apps), 'games' (PC Games), 'hardware' (Drivers)
     */
    public function getCategoryHtml(string $categorySlug, int $page = 1): ?string
    {
        if ($categorySlug === 'all') {
            return $this->getHomePageHtml($page);
        }

        if ($page > 1) {
            return $this->fetchHtml($this->baseUrl.'/category/'.$categorySlug.'/post/'.$page.'/');
        }

        return $this->fetchHtml($this->baseUrl.'/category/'.$categorySlug.'/');
    }

    /**
     * Search CyberMania in real-time with pagination
     */
    public function search(string $query, int $page = 1): ?string
    {
        $encodedQuery = urlencode(trim($query));

        if ($page > 1) {
            return $this->fetchHtml($this->baseUrl.'/post/'.$page.'/?s='.$encodedQuery);
        }

        return $this->fetchHtml($this->baseUrl.'/?s='.$encodedQuery);
    }

    /**
     * Fetch RSS feed for instant new releases detection
     */
    public function fetchRssFeed(?string $categorySlug = null): ?string
    {
        $url = $categorySlug && $categorySlug !== 'all'
            ? $this->baseUrl.'/category/'.$categorySlug.'/feed/'
            : $this->baseUrl.'/feed/';

        return $this->fetchHtml($url, false);
    }
}
