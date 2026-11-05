<?php

namespace App\Services\Scraper;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class CybermaniaParser
{
    /**
     * Trusted direct download hosts
     */
    protected array $trustedHostKeywords = [
        'mediafire.com',
        'github.com',
        'mega.nz',
        'mega.co.nz',
        'drive.google.com',
        'pixeldrain.com',
        '1fichier.com',
        'rapidgator.net',
        'gofile.io',
        'krakenfiles.com',
        'qiwi.gg',
        'dropbox.com',
        'send.cm',
        'userscloud.com',
        'upload.ee',
    ];

    /**
     * Parse list of cards from catalog or search results
     *
     * @return array<int, array{
     *     name: string,
     *     clean_name: string,
     *     slug: string,
     *     url: string,
     *     version: ?string,
     *     icon_url: ?string,
     *     category_slug: string,
     *     category_name: string,
     *     source: string
     * }>
     */
    public function parseAppCards(string $html): array
    {
        $crawler = new Crawler($html);
        $apps = [];
        $seenUrls = [];

        $crawler->filter('article.bg-stuff, article.post')->each(function (Crawler $node) use (&$apps, &$seenUrls) {
            $linkNode = $node->filter('.img a, h2 a, a.insidepost');
            if ($linkNode->count() === 0) {
                return;
            }

            $url = $linkNode->first()->attr('href');
            if (! $url || isset($seenUrls[$url])) {
                return;
            }

            // Exclude pagination navigation articles
            if (str_contains($url, '/post/') && ! str_contains($url, '/appz/') && ! str_contains($url, '/android/') && ! str_contains($url, '/games/')) {
                return;
            }

            $seenUrls[$url] = true;

            // Extract Raw Title
            $rawTitle = '';
            if ($linkNode->first()->attr('title')) {
                $rawTitle = trim($linkNode->first()->attr('title'));
            } elseif ($node->filter('h2, .post-title')->count() > 0) {
                $rawTitle = trim($node->filter('h2, .post-title')->first()->text());
            }

            if (empty($rawTitle)) {
                $rawTitle = trim($linkNode->first()->text());
            }

            if (empty($rawTitle)) {
                return;
            }

            // Extract Version
            $version = $this->extractVersion($rawTitle);

            // Clean Name
            $cleanName = $this->cleanAppName($rawTitle, $version);

            // Extract Icon / Image
            $iconUrl = null;
            $imgNode = $node->filter('.img img, img.wp-post-image, img');
            if ($imgNode->count() > 0) {
                $iconUrl = $imgNode->first()->attr('src');
            }

            // Determine Target Category Slug
            $categoryInfo = $this->detectCategoryFromUrl($url, $node);

            $apps[] = [
                'name' => $rawTitle,
                'clean_name' => $cleanName,
                'slug' => Str::slug($cleanName),
                'url' => $url,
                'version' => $version,
                'icon_url' => $iconUrl,
                'category_slug' => $categoryInfo['slug'],
                'category_name' => $categoryInfo['name'],
                'source' => 'cybermania',
            ];
        });

        return $apps;
    }

    /**
     * Parse full details of a single post from CyberMania
     *
     * @return array{
     *     name: string,
     *     clean_name: string,
     *     slug: string,
     *     url: string,
     *     version: ?string,
     *     icon_url: ?string,
     *     category_slug: string,
     *     category_name: string,
     *     platform: string,
     *     developer: ?string,
     *     size: ?string,
     *     has_torrent: bool,
     *     download_url: ?string,
     *     download_links: array<int, array{name: string, url: string, type: string}>,
     *     torrent_magnet: ?string,
     *     short_description: ?string,
     *     description: string,
     *     features: array<int, string>,
     *     screenshots: array<int, string>,
     *     source: string
     * }|null
     */
    public function parseAppDetail(string $html, string $sourceUrl): ?array
    {
        $crawler = new Crawler($html);

        // Extract Title: Look specifically for insidepost / post header, NOT the site logo h1
        $titleNode = $crawler->filter('h1.insidepost, #content h1, .post h1, h1.entry-title');
        if ($titleNode->count() === 0) {
            // Fallback: any h1 that does not have class site-title
            $crawler->filter('h1')->each(function (Crawler $node) use (&$titleNode) {
                if (! str_contains((string) $node->attr('class'), 'site-title') && empty($titleNode)) {
                    $titleNode = $node;
                }
            });
        }

        if (! $titleNode || $titleNode->count() === 0) {
            return null;
        }

        $rawTitle = trim($titleNode->first()->text());
        if (empty($rawTitle) || strtolower($rawTitle) === 'cybermania') {
            return null;
        }

        $version = $this->extractVersion($rawTitle);
        $cleanName = $this->cleanAppName($rawTitle, $version);
        $categoryInfo = $this->detectCategoryFromUrl($sourceUrl, $crawler);

        // Extract Icon
        $iconUrl = null;
        $iconNode = $crawler->filter('.post_img img, .post img, img.wp-post-image');
        if ($iconNode->count() > 0) {
            $iconUrl = $iconNode->first()->attr('src');
        }

        // Platform
        $platform = match ($categoryInfo['slug']) {
            'apps-android' => 'Android',
            'juegos-pc' => 'Windows PC',
            default => 'Windows 11 / 10',
        };

        // Parse Post Body & Paragraphs
        $postNode = $crawler->filter('#content .post, .post, .entry-content');
        $descriptionHtml = '';
        $shortDesc = null;
        $features = [];
        $screenshots = [];
        $downloadLinks = [];
        $torrentMagnet = null;
        $fileSize = null;

        if ($postNode->count() > 0) {
            $post = $postNode->first();

            // Extract Download Links (Mediafire, GitHub, Magnet, etc.)
            $post->filter('a')->each(function (Crawler $a) use (&$downloadLinks, &$torrentMagnet, &$fileSize) {
                $href = trim((string) $a->attr('href'));
                $text = trim($a->text());

                if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
                    return;
                }

                // Check for Magnet Link
                if (str_starts_with($href, 'magnet:?')) {
                    $torrentMagnet = $href;
                    $downloadLinks[] = [
                        'name' => ! empty($text) ? $text : 'Magnet Torrent',
                        'url' => $href,
                        'type' => 'torrent',
                    ];

                    return;
                }

                // Check for Direct / Cloud Download Hosts
                foreach ($this->trustedHostKeywords as $host) {
                    if (str_contains($href, $host)) {
                        $linkName = ! empty($text) && strtolower($text) !== 'download' ? $text : ucfirst(explode('.', $host)[0]);

                        // Try to extract file size from link text or surrounding context
                        if (! $fileSize && preg_match('/\b(\d+(\.\d+)?\s*(GB|MB|KB))\b/i', $text, $sizeMatch)) {
                            $fileSize = strtoupper($sizeMatch[1]);
                        }

                        $downloadLinks[] = [
                            'name' => $linkName,
                            'url' => $href,
                            'type' => 'direct',
                        ];
                        break;
                    }
                }
            });

            // Extract Screenshots (exclude main icon and ads)
            $post->filter('img')->each(function (Crawler $img) use (&$screenshots, $iconUrl) {
                $src = $img->attr('src');
                if (! $src || $src === $iconUrl) {
                    return;
                }

                if (str_contains($src, 'CMBlack') || str_contains($src, 'Paypal') || str_contains($src, 'btc') || str_contains($src, 'CyberMania.png') || str_contains($src, 'pt_standard')) {
                    return;
                }

                $screenshots[] = $src;
            });

            // Extract Feature bullets from <ul><li>
            $post->filter('ul li')->each(function (Crawler $li) use (&$features) {
                $t = trim($li->text());
                if (! empty($t) && strlen($t) > 5 && strlen($t) < 300) {
                    $features[] = $t;
                }
            });

            // Extract clean Description paragraphs
            $paragraphs = [];
            $post->filter('p')->each(function (Crawler $p) use (&$paragraphs, &$fileSize) {
                $t = trim($p->text());
                if (empty($t) || str_contains($t, 'Download') || str_contains($t, 'Ads Blocker') || str_contains($t, 'More Info')) {
                    return;
                }

                // Extract file size if mentioned in text e.g. "File Size: 1.2 GB"
                if (! $fileSize && preg_match('/(?:File\s*Size|Size)\s*:\s*(\d+(?:\.\d+)?\s*(?:GB|MB|KB))/i', $t, $sm)) {
                    $fileSize = strtoupper(trim($sm[1]));
                }

                if (strlen($t) > 20) {
                    $paragraphs[] = $t;
                }
            });

            if (! empty($paragraphs)) {
                $shortDesc = Str::limit($paragraphs[0], 180);
                $descriptionHtml = implode("\n\n", array_slice($paragraphs, 0, 6));
            }

            // Extract post tags if present
            $postTags = [];
            $post->filter('.post-tags a, .tags a, .entry-tags a, a[rel="tag"]')->each(function (Crawler $tag) use (&$postTags) {
                $t = trim($tag->text());
                if (! empty($t) && strlen($t) > 2 && strlen($t) < 40) {
                    $postTags[] = $t;
                }
            });
        }

        // Primary download link selection (only HTTP/HTTPS direct links)
        $primaryDownloadUrl = null;
        if (! empty($downloadLinks)) {
            foreach ($downloadLinks as $dl) {
                if ($dl['type'] === 'direct' && str_starts_with($dl['url'], 'http')) {
                    $primaryDownloadUrl = $dl['url'];
                    break;
                }
            }
        }

        return [
            'name' => $cleanName,
            'clean_name' => $cleanName,
            'slug' => Str::slug($cleanName),
            'url' => $sourceUrl,
            'version' => $version,
            'icon_url' => $iconUrl,
            'category_slug' => $categoryInfo['slug'],
            'category_name' => $categoryInfo['name'],
            'platform' => $platform,
            'developer' => $this->detectDeveloper($cleanName),
            'size' => $fileSize,
            'has_torrent' => ! empty($torrentMagnet),
            'download_url' => $primaryDownloadUrl,
            'download_links' => $downloadLinks,
            'torrent_magnet' => $torrentMagnet,
            'short_description' => $shortDesc,
            'description' => $descriptionHtml,
            'features' => array_slice($features, 0, 12),
            'screenshots' => array_slice(array_unique($screenshots), 0, 6),
            'tags' => array_values(array_unique($postTags ?? [])),
            'source' => 'cybermania',
        ];
    }

    /**
     * Parse pagination controls
     *
     * @return array{current_page: int, total_pages: int, has_next: bool, has_prev: bool}
     */
    public function parsePagination(string $html, int $currentPage = 1): array
    {
        $crawler = new Crawler($html);
        $hasNext = false;
        $hasPrev = $currentPage > 1;

        if ($crawler->filter('.nav-previous a, a:contains("Older posts")')->count() > 0) {
            $hasNext = true;
        }

        return [
            'current_page' => $currentPage,
            'total_pages' => $hasNext ? $currentPage + 1 : $currentPage,
            'has_next' => $hasNext,
            'has_prev' => $hasPrev,
        ];
    }

    /**
     * Map CyberMania category URL or DOM content to PichoDescargas Category
     *
     * @return array{slug: string, name: string}
     */
    public function detectCategoryFromUrl(string $url, ?Crawler $crawler = null): array
    {
        $lowerUrl = strtolower($url);

        if (str_contains($lowerUrl, '/android/') || str_contains($lowerUrl, 'android')) {
            return [
                'slug' => 'apps-android',
                'name' => 'Android Apps',
            ];
        }

        if (str_contains($lowerUrl, '/games/') || str_contains($lowerUrl, 'games')) {
            return [
                'slug' => 'juegos-pc',
                'name' => 'PC Games',
            ];
        }

        if ($crawler) {
            $catText = '';
            try {
                $crawler->filter('a[rel*="category"], .category a, .cat-links a, .post-category a, a[href*="/category/"], .breadcrumbs a')->each(function (Crawler $node) use (&$catText) {
                    $catText .= ' '.strtolower($node->text()).' '.strtolower((string) $node->attr('href'));
                });
            } catch (\Throwable) {
                // Ignore DOM selection exceptions
            }

            if (str_contains($catText, 'android')) {
                return [
                    'slug' => 'apps-android',
                    'name' => 'Android Apps',
                ];
            }

            if (str_contains($catText, 'games') || str_contains($catText, 'game') || str_contains($catText, 'juegos')) {
                return [
                    'slug' => 'juegos-pc',
                    'name' => 'PC Games',
                ];
            }
        }

        // Default to Programs (Windows PC) for /appz/, /hardware/, etc.
        return [
            'slug' => 'programas',
            'name' => 'Programs',
        ];
    }

    /**
     * Extract semver or version string from title
     */
    public function extractVersion(string $title): ?string
    {
        // 1. Matches formats like "v1.2.3", "12.4.15", "2024.003", "16.0.17932.21056"
        if (preg_match('/\b(?:v|ver\.?|version)?\s*(\d+(?:\.\d+){1,4}(?:\.\d+)?(?:\s*(?:Beta|Final|Pro|LTSC|WHQL|RC\d*))?)\b/i', $title, $m)) {
            return trim($m[1]);
        }

        // 2. Matches single dot version e.g. "4.15" or year formats e.g. "2024"
        if (preg_match('/\b(\d{1,2}\.\d{1,3})\b/', $title, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /**
     * Strip version and edition tags to get a clean canonical title
     */
    public function cleanAppName(string $title, ?string $version = null): string
    {
        $clean = $title;

        // Remove version if found
        if ($version) {
            $clean = str_ireplace($version, '', $clean);
        }

        // Remove typical suffixes like "[Paid]", "[Mod Extra]", "(Android)", "[Premium]", "(万兴PDF)", etc.
        $clean = preg_replace('/\[[^\]]*\]/i', '', $clean);
        $clean = preg_replace('/\([^\)]*(?:Android|x64|x86|Portable|\p{Han})[^\)]*\)/iu', '', $clean);
        $clean = preg_replace('/[\p{Han}]+/u', '', $clean);
        $clean = preg_replace('/\b(?:v|ver|version)?\s*\d+(\.\d+)*\b/i', '', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);

        return trim($clean, " -–—\t\n\r\0\x0B");
    }

    /**
     * Detect developer from software title
     */
    protected function detectDeveloper(string $cleanName): string
    {
        $known = [
            'Adobe' => 'Adobe Inc.',
            'Microsoft' => 'Microsoft',
            'Wondershare' => 'Wondershare',
            'Google' => 'Google LLC',
            'TechSmith' => 'TechSmith',
            'ESET' => 'ESET',
            'Nitro' => 'Nitro Software',
            'Rufus' => 'Pete Batard',
            'OBS' => 'OBS Project',
            'WinRAR' => 'RARLab',
            'IDM' => 'Tonec Inc.',
            'Internet Download Manager' => 'Tonec Inc.',
            'Revo' => 'VS Revo Group',
            'Spotify' => 'Spotify AB',
            'Telegram' => 'Telegram FZ-LLC',
            'CapCut' => 'ByteDance',
            'InShot' => 'InShot Video Editor',
            'Nova' => 'TeslaCoil Software',
        ];

        foreach ($known as $prefix => $dev) {
            if (stripos($cleanName, $prefix) !== false) {
                return $dev;
            }
        }

        return 'CyberMania';
    }
}
