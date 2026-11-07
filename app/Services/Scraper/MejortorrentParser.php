<?php

namespace App\Services\Scraper;

use Illuminate\Support\Str;

class MejortorrentParser
{
    /**
     * Parse movie cards from catalog HTML
     *
     * @return array<int, array{id: int, title: string, url: string, image_url: string, quality: string, slug: string}>
     */
    public function parseMovieCards(string $html, string $baseUrl = 'https://dontorrent.tips'): array
    {
        $cards = [];
        $seenIds = [];

        // Pattern for cards: <a href="/pelicula/31202/Nightborn"><img ... src="//images.weserv.nl/?output=webp&w=120&h=165&url=doncdn.com/imagenes/peliculas/Nightborn-[BluRay-1080p]-[DonTorrent]-[3UKC].webp" ...></a>
        $pattern = '/<a\s+href="(\/pelicula\/(\d+)\/([^"]+))"[^>]*>\s*<img[^>]*src="([^"]+)"/i';

        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $contentId = (int) $m[2];
                if (isset($seenIds[$contentId])) {
                    continue;
                }
                $seenIds[$contentId] = true;

                $relUrl = $m[1];
                $urlSlug = $m[3];
                $imgSrc = $m[4];
                if (str_starts_with($imgSrc, '//')) {
                    $imgSrc = 'https:'.$imgSrc;
                }

                // Extract quality from image filename e.g. [BluRay-1080p] or [4K] or [DVDRip]
                $quality = '1080p';
                if (preg_match('/\[(4K|BluRay-1080p|1080p|720p|MicroHD-1080p|DVDRip|HDRip|UHD)\]/i', $imgSrc, $qm)) {
                    $quality = $qm[1];
                }

                // Clean title from URL slug or image path
                $rawTitle = str_replace('-', ' ', $urlSlug);
                $rawTitle = preg_replace('/39s/i', "'s", $rawTitle);
                $title = html_entity_decode(ucwords(trim($rawTitle)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                $cards[] = [
                    'id' => $contentId,
                    'title' => $title,
                    'url' => rtrim($baseUrl, '/').$relUrl,
                    'image_url' => $imgSrc,
                    'quality' => $quality,
                    'slug' => Str::slug($title),
                ];
            }
        }

        return $cards;
    }

    /**
     * Parse complete movie details from single movie page HTML
     */
    public function parseMovieDetail(string $html, string $url): ?array
    {
        if (empty($html)) {
            return null;
        }

        // 1. Content ID and Tabla for PoW
        $contentId = null;
        $tabla = 'peliculas';
        if (preg_match('/data-content-id="(\d+)"[^>]*data-tabla="([^"]+)"/i', $html, $cm)) {
            $contentId = (int) $cm[1];
            $tabla = $cm[2];
        } elseif (preg_match('/\/pelicula\/(\d+)/i', $url, $um)) {
            $contentId = (int) $um[1];
        }

        if (! $contentId) {
            return null;
        }

        // 2. Title
        $title = null;
        if (preg_match('/<h1[^>]*class="[^"]*descargarTitulo[^"]*"[^>]*>(.*?)<\/h1>/is', $html, $tm)) {
            $title = html_entity_decode(trim(strip_tags($tm[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } elseif (preg_match('/<meta\s+property="og:title"\s+content="([^"]+)"/i', $html, $ogTm)) {
            $title = html_entity_decode(trim(preg_replace('/^Descargar\s+/i', '', preg_replace('/\s+Año\s+\d+.*$/i', '', $ogTm[1]))), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (empty($title)) {
            if (preg_match('/\/pelicula\/\d+\/([^\/\?]+)/', $url, $sm)) {
                $title = ucwords(str_replace('-', ' ', $sm[1]));
            } else {
                return null;
            }
        }

        // 3. Year
        $year = null;
        if (preg_match('/<b class="bold">Año:<\/b>\s*<a[^>]*>(\d{4})<\/a>/i', $html, $ym)) {
            $year = (int) $ym[1];
        } elseif (preg_match('/Año\s+(\d{4})/i', $html, $ym2)) {
            $year = (int) $ym2[1];
        }

        // 4. Quality
        $quality = '1080p BluRay';
        if (preg_match('/\[(4K|BluRay-1080p|1080p|720p|MicroHD-1080p|DVDRip|HDRip|UHD)\]/i', $html, $qm)) {
            $quality = $qm[1];
        } elseif (preg_match('/(4K\s*UHD|BluRay\s*1080p|1080p|720p|DVDRip)/i', $html, $qm2)) {
            $quality = trim($qm2[1]);
        }

        // 5. Poster / Cover image
        $posterUrl = null;
        if (preg_match('/<meta\s+property="og:image"\s+content="([^"]+)"/i', $html, $im)) {
            $posterUrl = $im[1];
        } elseif (preg_match('/<img\s+class="img-thumbnail[^"]*"[^>]*src=[\'"]([^\'"]+)[\'"]/i', $html, $im2)) {
            $posterUrl = $im2[1];
        }

        if ($posterUrl) {
            if (str_starts_with($posterUrl, '//')) {
                $posterUrl = 'https:'.$posterUrl;
            }
            // Upgrade image resolution from weserv if possible
            if (str_contains($posterUrl, 'w=120&h=165')) {
                $posterUrl = str_replace('w=120&h=165', 'w=600&h=900', $posterUrl);
            }
        }

        // 6. Description / Synopsis
        $synopsis = '';
        if (preg_match('/<b class="bold">Descripci&oacute;n:<\/b>\s*([^<]+)/i', $html, $dm)) {
            $synopsis = trim(html_entity_decode($dm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        } elseif (preg_match('/<meta\s+property="og:description"\s+content="([^"]+)"/i', $html, $ogDm)) {
            $synopsis = trim(html_entity_decode($ogDm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        // 7. Format clean HTML description for post body
        $formattedHtml = $this->formatMovieHtml($title, $year, $quality, $synopsis);

        return [
            'content_id' => $contentId,
            'tabla' => $tabla,
            'title' => $title,
            'clean_title' => $title.($year ? " ({$year})" : ''),
            'year' => $year,
            'quality' => $quality,
            'synopsis' => $synopsis,
            'description_html' => $formattedHtml,
            'poster_url' => $posterUrl,
            'source_url' => $url,
            'slug' => Str::slug($title.($year ? "-{$year}" : '')."-{$quality}"),
        ];
    }

    /**
     * Format an aesthetic HTML description for the movie post
     */
    public function formatMovieHtml(string $title, ?int $year, string $quality, string $synopsis): string
    {
        $qualityEsc = e($quality);
        $titleEsc = e($title);
        $synopsisEsc = e($synopsis);
        $yearEsc = $year ? (int) $year : null;

        $html = "<div class=\"movie-details not-prose my-3 space-y-6 font-sans\">\n";

        // 1. Badges bar (Calidad, Audio, Año, Tipo)
        $html .= "  <div class=\"flex flex-wrap items-center gap-2 pt-1\">\n";
        $html .= "    <span class=\"inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/25 shadow-sm tracking-wide\">\n";
        $html .= "      <svg class=\"w-3.5 h-3.5 text-rose-500 dark:text-rose-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z\"/></svg>\n";
        $html .= "      <span>Calidad: {$qualityEsc}</span>\n";
        $html .= "    </span>\n";

        $html .= "    <span class=\"inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25 shadow-sm tracking-wide\">\n";
        $html .= "      <svg class=\"w-3.5 h-3.5 text-emerald-500 dark:text-emerald-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z\"/></svg>\n";
        $html .= "      <span>Audio: Español / Castellano</span>\n";
        $html .= "    </span>\n";

        if ($yearEsc) {
            $html .= "    <span class=\"inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/25 shadow-sm tracking-wide\">\n";
            $html .= "      <svg class=\"w-3.5 h-3.5 text-amber-500 dark:text-amber-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z\"/></svg>\n";
            $html .= "      <span>Año: {$yearEsc}</span>\n";
            $html .= "    </span>\n";
        }

        $html .= "    <span class=\"inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-[#F4EFEA] dark:bg-[#1F1A15] text-[#786E64] dark:text-[#8C847A] border border-[#E3DBD2] dark:border-[#2D251D] shadow-sm tracking-wide\">\n";
        $html .= "      <svg class=\"w-3.5 h-3.5 text-primary\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4\"/></svg>\n";
        $html .= "      <span>Torrent P2P</span>\n";
        $html .= "    </span>\n";
        $html .= "  </div>\n";

        // 2. Ficha Técnica Sleek Box (Subida arriba)
        $html .= "  <div class=\"rounded-2xl bg-[#F8F4EE] dark:bg-[#14110E] border border-[#E5DDD2] dark:border-[#262019] p-4 sm:p-5 shadow-sm dark:shadow-xl space-y-3.5\">\n";
        $html .= "    <div class=\"flex items-center justify-between border-b border-[#E8E0D5] dark:border-[#221C16] pb-3\">\n";
        $html .= "      <h4 class=\"text-xs font-bold text-[#1C1814] dark:text-white uppercase tracking-wider flex items-center gap-2\">\n";
        $html .= "        <span class=\"text-rose-500 dark:text-rose-400\">🎬</span>\n";
        $html .= "        <span>Ficha Técnica y Detalles de Reproducción</span>\n";
        $html .= "      </h4>\n";
        $html .= "      <span class=\"text-[10px] font-mono text-[#8C847A] bg-[#ECE5DB] dark:bg-[#1A1612] px-2 py-0.5 rounded border border-[#DFD6CA] dark:border-[#2B231B]\">P2P Verified</span>\n";
        $html .= "    </div>\n";

        $html .= "    <div class=\"grid grid-cols-2 sm:grid-cols-4 gap-2.5\">\n";
        $html .= "      <div class=\"bg-white dark:bg-[#181410] border border-[#E8E0D5] dark:border-[#221C16] rounded-xl p-3 flex flex-col gap-1 shadow-sm dark:shadow-none\">\n";
        $html .= "        <span class=\"text-[10px] font-bold uppercase tracking-wider text-[#8C847A]\">Calidad</span>\n";
        $html .= "        <span class=\"text-xs font-bold text-rose-500 dark:text-rose-400 font-mono truncate\">{$qualityEsc}</span>\n";
        $html .= "      </div>\n";

        $html .= "      <div class=\"bg-white dark:bg-[#181410] border border-[#E8E0D5] dark:border-[#221C16] rounded-xl p-3 flex flex-col gap-1 shadow-sm dark:shadow-none\">\n";
        $html .= "        <span class=\"text-[10px] font-bold uppercase tracking-wider text-[#8C847A]\">Audio / Idioma</span>\n";
        $html .= "        <span class=\"text-xs font-bold text-emerald-600 dark:text-emerald-400 truncate\">Castellano</span>\n";
        $html .= "      </div>\n";

        $yearVal = $yearEsc ? (string) $yearEsc : 'N/A';
        $html .= "      <div class=\"bg-white dark:bg-[#181410] border border-[#E8E0D5] dark:border-[#221C16] rounded-xl p-3 flex flex-col gap-1 shadow-sm dark:shadow-none\">\n";
        $html .= "        <span class=\"text-[10px] font-bold uppercase tracking-wider text-[#8C847A]\">Año</span>\n";
        $html .= "        <span class=\"text-xs font-bold text-amber-600 dark:text-amber-400 font-mono\">{$yearVal}</span>\n";
        $html .= "      </div>\n";

        $html .= "      <div class=\"bg-white dark:bg-[#181410] border border-[#E8E0D5] dark:border-[#221C16] rounded-xl p-3 flex flex-col gap-1 shadow-sm dark:shadow-none\">\n";
        $html .= "        <span class=\"text-[10px] font-bold uppercase tracking-wider text-[#8C847A]\">Formato</span>\n";
        $html .= "        <span class=\"text-xs font-bold text-[#1C1814] dark:text-white font-mono truncate\">.torrent (P2P)</span>\n";
        $html .= "      </div>\n";
        $html .= "    </div>\n";
        $html .= "  </div>\n";

        // 3. Sinopsis
        $html .= "  <div class=\"space-y-2 pt-1\">\n";
        $html .= "    <h3 class=\"text-xs font-bold uppercase tracking-wider text-[#786E64] dark:text-[#A8A199] flex items-center gap-2\">\n";
        $html .= "      <span class=\"w-2 h-2 rounded-full bg-rose-500 shadow-sm shadow-rose-500/50\"></span>\n";
        $html .= "      <span>Sinopsis de la Película</span>\n";
        $html .= "    </h3>\n";
        $html .= "    <p class=\"text-[14.5px] sm:text-[15px] text-[#3E3933] dark:text-[#D4CCC0] leading-relaxed font-normal\">\n";
        $html .= "      {$synopsisEsc}\n";
        $html .= "    </p>\n";
        $html .= "  </div>\n";
        $html .= '</div>';

        return $html;
    }

    /**
     * Parse pagination
     */
    public function parsePagination(string $html, int $currentPage = 1): array
    {
        $hasNext = str_contains($html, 'page/'.($currentPage + 1));

        return [
            'current_page' => $currentPage,
            'has_next' => $hasNext,
            'has_prev' => $currentPage > 1,
        ];
    }

    /**
     * Parse series cards from catalog HTML (/series, /series/hd, /series/4k)
     */
    public function parseSeriesCards(string $html, string $baseUrl = 'https://dontorrent.tips'): array
    {
        $series = [];
        $uniqueUrls = [];

        preg_match_all('/<a[^>]+href="(\/serie\/[^"]+)"[^>]*>(.*?)<\/a>/si', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $path = $m[1];
            $inner = $m[2];
            $fullUrl = str_starts_with($path, 'http') ? $path : rtrim($baseUrl, '/').$path;

            if (isset($uniqueUrls[$fullUrl])) {
                continue;
            }
            $uniqueUrls[$fullUrl] = true;

            $imgUrl = null;
            if (preg_match('/src="([^"]+)"/i', $inner, $imgMatch)) {
                $imgUrl = $imgMatch[1];
                if (str_starts_with($imgUrl, '//')) {
                    $imgUrl = 'https:'.$imgUrl;
                }
            }

            $popoverId = null;
            if (preg_match('/data-popover-id="(\d+)"/i', $inner, $pMatch)) {
                $popoverId = (int) $pMatch[1];
            }

            $parts = explode('/', trim($path, '/'));
            $slugPart = end($parts);
            $rawTitle = urldecode(str_replace('-', ' ', $slugPart));
            $title = html_entity_decode($rawTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $quality = 'HDTV';
            if (preg_match('/\[(.*?)\]/i', $imgUrl ?? '', $qMatch)) {
                $quality = $qMatch[1];
            } elseif (str_contains($path, '4k') || str_contains($path, '4K')) {
                $quality = '4K';
            }

            $series[] = [
                'id' => $popoverId ?: ((int) ($parts[1] ?? 0)),
                'title' => $title,
                'url' => $fullUrl,
                'image_url' => $imgUrl,
                'quality' => $quality,
            ];
        }

        return $series;
    }

    /**
     * Parse a series detail page with its episode table
     */
    public function parseSeriesDetail(string $html, string $url): ?array
    {
        $title = '';
        if (preg_match('/<h2[^>]*class="[^"]*descargarTitulo[^"]*"[^>]*>(.*?)<\/h2>/si', $html, $m)) {
            $title = html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } elseif (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
            $rawTitle = preg_replace('/^Descargar\s+/i', '', trim($m[1]));
            $rawTitle = preg_replace('/\s*Torrent\s*Gratis.*$/i', '', $rawTitle);
            $title = html_entity_decode(trim($rawTitle), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (empty($title)) {
            return null;
        }

        // Format
        $format = 'HDTV';
        if (preg_match('/<b class="bold">Formato:<\/b>\s*([^<]+)/i', $html, $m)) {
            $format = trim($m[1]);
        }

        // Synopsis
        $synopsis = '';
        if (preg_match('/<b class="bold">Descripci&oacute;n:<\/b>\s*(.*?)(?:<\/p>|<table)/si', $html, $m)) {
            $synopsis = html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Poster
        $posterUrl = null;
        if (preg_match('/<img[^>]*class="[^"]*img-thumbnail[^"]*"[^>]*src=[\'"]([^\'"]+)[\'"]/i', $html, $m)) {
            $posterUrl = $m[1];
            if (str_starts_with($posterUrl, '//')) {
                $posterUrl = 'https:'.$posterUrl;
            }
        }

        // Parse Episode Rows
        $episodes = [];
        if (preg_match('/<table[^>]*>(.*?)<\/table>/si', $html, $tableMatch)) {
            preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $tableMatch[1], $rows);
            foreach ($rows[1] as $row) {
                if (! str_contains($row, 'protected-download')) {
                    continue;
                }

                $code = '';
                if (preg_match('/<td[^>]*>(.*?)<\/td>/si', $row, $tdMatch)) {
                    $code = trim(strip_tags($tdMatch[1]));
                }

                $contentId = null;
                if (preg_match('/data-content-id="(\d+)"/i', $row, $cidMatch)) {
                    $contentId = (int) $cidMatch[1];
                }

                $date = null;
                if (preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $row, $allTds)) {
                    foreach ($allTds[1] as $td) {
                        $cleanTd = trim(strip_tags($td));
                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $cleanTd)) {
                            $date = $cleanTd;
                            break;
                        }
                    }
                }

                if ($contentId && $code) {
                    $episodes[] = [
                        'code' => $code,
                        'name' => "Capítulo {$code}",
                        'content_id' => $contentId,
                        'date' => $date,
                        'tabla' => 'series',
                        'quality' => $format,
                    ];
                }
            }
        }

        $formattedHtml = $this->formatSeriesHtml($title, $format, count($episodes), $synopsis, $episodes);

        return [
            'title' => $title,
            'clean_title' => $title,
            'format' => $format,
            'synopsis' => $synopsis,
            'description_html' => $formattedHtml,
            'poster_url' => $posterUrl,
            'source_url' => $url,
            'episodes' => $episodes,
            'total_episodes' => count($episodes),
            'slug' => Str::slug($title.'-'.$format),
        ];
    }

    /**
     * Format an aesthetic HTML description for a TV Series post
     */
    public function formatSeriesHtml(string $title, string $format, int $totalEpisodes, string $synopsis, array $episodes = []): string
    {
        $formatEsc = e($format);
        $titleEsc = e($title);
        $synopsisEsc = e($synopsis);

        $html = "<div class=\"series-details not-prose my-3 space-y-6 font-sans\">\n";

        // 1. Badges bar (Tipo, Formato, Episodios, Audio)
        $html .= "  <div class=\"flex flex-wrap items-center gap-2 pt-1\">\n";
        $html .= "    <span class=\"inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/25 shadow-sm tracking-wide\">\n";
        $html .= "      <svg class=\"w-3.5 h-3.5 text-purple-500 dark:text-purple-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z\"/></svg>\n";
        $html .= "      <span>Serie TV</span>\n";
        $html .= "    </span>\n";

        $html .= "    <span class=\"inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/25 shadow-sm tracking-wide\">\n";
        $html .= "      <svg class=\"w-3.5 h-3.5 text-rose-500 dark:text-rose-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z\"/></svg>\n";
        $html .= "      <span>Calidad: {$formatEsc}</span>\n";
        $html .= "    </span>\n";

        $html .= "    <span class=\"inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25 shadow-sm tracking-wide\">\n";
        $html .= "      <svg class=\"w-3.5 h-3.5 text-emerald-500 dark:text-emerald-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z\"/></svg>\n";
        $html .= "      <span>Audio: Español / Castellano</span>\n";
        $html .= "    </span>\n";

        $html .= "    <span class=\"inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-600 dark:text-sky-400 border border-sky-500/25 shadow-sm tracking-wide\">\n";
        $html .= "      <svg class=\"w-3.5 h-3.5 text-sky-500 dark:text-sky-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10\"/></svg>\n";
        $html .= "      <span>{$totalEpisodes} Episodios</span>\n";
        $html .= "    </span>\n";
        $html .= "  </div>\n";

        // 2. Ficha Técnica Sleek Box (Subida arriba)
        $html .= "  <div class=\"rounded-2xl bg-[#F8F4EE] dark:bg-[#14110E] border border-[#E5DDD2] dark:border-[#262019] p-4 sm:p-5 shadow-sm dark:shadow-xl space-y-3.5\">\n";
        $html .= "    <div class=\"flex items-center justify-between border-b border-[#E8E0D5] dark:border-[#221C16] pb-3\">\n";
        $html .= "      <h4 class=\"text-xs font-bold text-[#1C1814] dark:text-white uppercase tracking-wider flex items-center gap-2\">\n";
        $html .= "        <span class=\"text-purple-500 dark:text-purple-400\">📺</span>\n";
        $html .= "        <span>Ficha Técnica de la Temporada</span>\n";
        $html .= "      </h4>\n";
        $html .= "      <span class=\"text-[10px] font-mono text-[#8C847A] bg-[#ECE5DB] dark:bg-[#1A1612] px-2 py-0.5 rounded border border-[#DFD6CA] dark:border-[#2B231B]\">Episodios P2P</span>\n";
        $html .= "    </div>\n";

        $html .= "    <div class=\"grid grid-cols-2 sm:grid-cols-4 gap-2.5\">\n";
        $html .= "      <div class=\"bg-white dark:bg-[#181410] border border-[#E8E0D5] dark:border-[#221C16] rounded-xl p-3 flex flex-col gap-1 shadow-sm dark:shadow-none\">\n";
        $html .= "        <span class=\"text-[10px] font-bold uppercase tracking-wider text-[#8C847A]\">Formato</span>\n";
        $html .= "        <span class=\"text-xs font-bold text-purple-500 dark:text-purple-400 font-mono truncate\">{$formatEsc}</span>\n";
        $html .= "      </div>\n";

        $html .= "      <div class=\"bg-white dark:bg-[#181410] border border-[#E8E0D5] dark:border-[#221C16] rounded-xl p-3 flex flex-col gap-1 shadow-sm dark:shadow-none\">\n";
        $html .= "        <span class=\"text-[10px] font-bold uppercase tracking-wider text-[#8C847A]\">Audio / Idioma</span>\n";
        $html .= "        <span class=\"text-xs font-bold text-emerald-600 dark:text-emerald-400 truncate\">Castellano</span>\n";
        $html .= "      </div>\n";

        $html .= "      <div class=\"bg-white dark:bg-[#181410] border border-[#E8E0D5] dark:border-[#221C16] rounded-xl p-3 flex flex-col gap-1 shadow-sm dark:shadow-none\">\n";
        $html .= "        <span class=\"text-[10px] font-bold uppercase tracking-wider text-[#8C847A]\">Total Capítulos</span>\n";
        $html .= "        <span class=\"text-xs font-bold text-sky-500 dark:text-sky-400 font-mono\">{$totalEpisodes}</span>\n";
        $html .= "      </div>\n";

        $html .= "      <div class=\"bg-white dark:bg-[#181410] border border-[#E8E0D5] dark:border-[#221C16] rounded-xl p-3 flex flex-col gap-1 shadow-sm dark:shadow-none\">\n";
        $html .= "        <span class=\"text-[10px] font-bold uppercase tracking-wider text-[#8C847A]\">Categoría</span>\n";
        $html .= "        <span class=\"text-xs font-bold text-[#1C1814] dark:text-white font-mono truncate\">Series TV</span>\n";
        $html .= "      </div>\n";
        $html .= "    </div>\n";
        $html .= "  </div>\n";

        // 3. Sinopsis
        $html .= "  <div class=\"space-y-2 pt-1\">\n";
        $html .= "    <h3 class=\"text-xs font-bold uppercase tracking-wider text-[#786E64] dark:text-[#A8A199] flex items-center gap-2\">\n";
        $html .= "      <span class=\"w-2 h-2 rounded-full bg-purple-500 shadow-sm shadow-purple-500/50\"></span>\n";
        $html .= "      <span>Sinopsis de la Serie</span>\n";
        $html .= "    </h3>\n";
        $html .= "    <p class=\"text-[14.5px] sm:text-[15px] text-[#3E3933] dark:text-[#D4CCC0] leading-relaxed font-normal\">\n";
        $html .= "      {$synopsisEsc}\n";
        $html .= "    </p>\n";
        $html .= "  </div>\n";
        $html .= '</div>';

        return $html;
    }
}
