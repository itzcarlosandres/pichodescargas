<x-app-layout>
    @section('title', setting('seo_meta_title', 'Las mejores descargas de programas, apps y juegos - ' . config('app.name')))
    @section('description', setting('seo_meta_description', 'Descargas directas y verificadas para Windows, Mac, Android y juegos.'))
    @section('og_image', asset('images/og-share.jpg'))
    @section('canonical', route('home'))

    @section('schema')
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebSite",
        "name": "{{ setting('site_name', 'Picho') }}{{ setting('site_name_highlight', 'Descargas') }}",
        "url": "{{ route('home') }}",
        "description": "{{ addslashes(setting('seo_meta_description', 'Descargas directas y verificadas.')) }}",
        "potentialAction": {
            "@@type": "SearchAction",
            "target": "{{ route('search') }}?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "Organization",
        "name": "{{ setting('site_name', 'Picho') }}{{ setting('site_name_highlight', 'Descargas') }}",
        "url": "{{ route('home') }}",
        "logo": "{{ asset('favicon.svg') }}",
        "sameAs": {!! json_encode(array_values(array_filter([setting('twitter_url'), setting('telegram_channel')]))) !!}
    }
    </script>
    @endsection

    <div class="container-app pt-3 sm:pt-4 pb-12">
        <section class="home-hero has-stats grid relative items-center gap-6 lg:gap-10 grid-cols-1 lg:grid-cols-[minmax(0px,_1.25fr)_minmax(0px,_1fr)] p-5 sm:p-7 lg:p-[42px] mt-0.5 mb-7 text-[16.5px] leading-relaxed overflow-hidden cursor-default" role="region" aria-label="Discover the best free Mac apps &amp; games">
            <div class="hero-main">
                <h1 class="font-heading font-bricolage text-2xl sm:text-3xl lg:text-[2.25rem] font-extrabold tracking-tight" style="font-family: 'Bricolage Grotesque', sans-serif !important;">
                    {{ setting('site_tagline', 'Discover the best free Mac apps & games') }}
                </h1>
                <p class="text-sm sm:text-[15px] text-[#A1A1A6] mb-5 sm:mb-6 max-w-xl">
                    {{ setting('site_description', 'Curated, fast and safe downloads for macOS — updated daily.') }}
                </p>
                <form class="hero-search w-full max-w-full lg:max-w-[480px] cursor-pointer" role="search" method="get" action="{{ route('search') }}"
                      @click.prevent="$dispatch('open-search-modal')"
                      onclick="window.dispatchEvent(new CustomEvent('open-search-modal'))">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <input type="search" name="s" aria-label="Search apps and games" placeholder="Search apps &amp; games… (Ctrl + K)" value="{{ request('s', request('q', '')) }}" readonly class="cursor-pointer">
                    <button type="button">Search</button>
                </form>
                <div class="hero-chips flex flex-wrap items-center gap-1.5 sm:gap-2 text-xs">
                    <span class="hero-chips-label">Categories:</span>
                    <a href="{{ route('category', 'programas') }}">Programs</a>
                    <a href="{{ route('category', 'mac-apps') }}">Mac Apps</a>
                    <a href="{{ route('category', 'apps-android') }}">Android Apps</a>
                    <a href="{{ route('category', 'juegos-pc') }}">PC Games</a>
                    <a href="{{ route('category', 'peliculas-gratis') }}">Free Movies</a>
                    <a href="{{ route('category', 'series-tv') }}">Series TV</a>
                    <a href="{{ route('popular') }}">Top Downloads</a>
                </div>
            </div>

            @php
                $totDownloads = (int) ($stats['total_downloads'] ?? 0);
                if ($totDownloads >= 1000000) {
                    $formattedTotal = number_format($totDownloads / 1000000, 1) . 'M';
                } elseif ($totDownloads >= 1000) {
                    $formattedTotal = number_format($totDownloads / 1000, 1) . 'K';
                } else {
                    $formattedTotal = number_format($totDownloads);
                }
                $totalAppsReal = $stats['total_apps'] ?? \App\Models\Application::published()->count();
                $updatedWeekReal = $stats['updated_this_week'] ?? \App\Models\Application::published()->where('updated_at', '>=', now()->subWeek())->count();
            @endphp
            <div class="hero-stats grid grid-cols-3 lg:flex lg:flex-col relative z-[1] py-4 px-3 sm:py-5 sm:px-6 font-sans text-center lg:text-left gap-1 sm:gap-2 lg:gap-0">
                <div class="hero-stat pr-1.5 sm:pr-3 lg:pr-0 border-r border-slate-200 dark:border-white/10 lg:border-r-0 lg:border-b pb-0 lg:pb-4 lg:mb-4">
                    <span class="hero-stat-v block font-mono text-lg sm:text-2xl lg:text-[28.8px] font-semibold leading-tight lg:leading-relaxed tracking-tight text-[#ffffff]">{{ number_format($totalAppsReal) }}</span>
                    <span class="hero-stat-l text-[10px] sm:text-xs lg:text-[13px] text-[#A1A1A6] uppercase tracking-wider block mt-0.5 sm:mt-1">Apps &amp; games</span>
                </div>
                <div class="hero-stat px-1.5 sm:px-3 lg:px-0 border-r border-slate-200 dark:border-white/10 lg:border-r-0 lg:border-b pb-0 lg:pb-4 lg:mb-4">
                    <span class="hero-stat-v block font-mono text-lg sm:text-2xl lg:text-[28.8px] font-semibold leading-tight lg:leading-relaxed tracking-tight text-[#ffffff]">{{ $formattedTotal }}</span>
                    <span class="hero-stat-l text-[10px] sm:text-xs lg:text-[13px] text-[#A1A1A6] uppercase tracking-wider block mt-0.5 sm:mt-1">Downloads</span>
                </div>
                <div class="hero-stat pl-1.5 sm:pl-3 lg:pl-0">
                    <span class="hero-stat-v block font-mono text-lg sm:text-2xl lg:text-[28.8px] font-semibold leading-tight lg:leading-relaxed tracking-tight text-[#ffffff]">{{ number_format($updatedWeekReal) }}</span>
                    <span class="hero-stat-l text-[10px] sm:text-xs lg:text-[13px] text-[#A1A1A6] uppercase tracking-wider block mt-0.5 sm:mt-1">Updated weekly</span>
                </div>
            </div>
        </section>

        @php
            $catMeta = [
                'programas' => [
                    'dot' => 'bg-sky-400 shadow-sky-400/50',
                    'border' => 'border-sky-500/20',
                    'tag' => 'WINDOWS PC',
                    'text' => 'Verified software and utilities for Windows PC.',
                ],
                'mac-apps' => [
                    'dot' => 'bg-sky-400 shadow-sky-400/50',
                    'border' => 'border-sky-500/20',
                    'tag' => 'MACOS SYSTEM',
                    'text' => 'Universal applications and software for macOS.',
                ],
                'apps-android' => [
                    'dot' => 'bg-emerald-400 shadow-emerald-400/50',
                    'border' => 'border-emerald-500/20',
                    'tag' => 'ANDROID OS',
                    'text' => 'APK applications, mods and tools for Android.',
                ],
                'juegos-pc' => [
                    'dot' => 'bg-purple-400 shadow-purple-400/50',
                    'border' => 'border-purple-500/20',
                    'tag' => 'PC GAMING',
                    'text' => 'Full games and installers for Windows PC.',
                ],
                'peliculas-gratis' => [
                    'dot' => 'bg-rose-400 shadow-rose-400/50',
                    'border' => 'border-rose-500/20',
                    'tag' => 'CINE & STREAMING',
                    'text' => 'Full HD movies, releases and cinema streams.',
                ],
                'series-tv' => [
                    'dot' => 'bg-violet-400 shadow-violet-400/50',
                    'border' => 'border-violet-500/20',
                    'tag' => 'SERIES & TEMPORADAS',
                    'text' => 'Full TV series, complete seasons and HD episodes.',
                ],
            ];
        @endphp

        <!-- 5 CATEGORÍAS EN CUADRÍCULA CYBER HUD (6 COLUMNAS) -->
        <div class="space-y-12">
            @foreach($sections as $category)
                @php
                    $meta = $catMeta[$category->slug] ?? [
                        'dot' => 'bg-sky-400 shadow-sky-400/50',
                        'border' => 'border-sky-500/20',
                        'tag' => strtoupper($category->name),
                        'text' => $category->description,
                    ];
                    $catApps = $category->applications;
                @endphp

                <section class="space-y-4" aria-labelledby="cat-heading-{{ $category->slug }}">
                    <!-- HUD Category Section Header -->
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2.5 pb-2.5 border-b border-slate-200/80 dark:border-white/[0.08]">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-[3px] {{ $meta['dot'] }} inline-block shadow-sm"></span>
                            <div class="flex items-center gap-2">
                                <h2 id="cat-heading-{{ $category->slug }}" class="font-heading text-sm sm:text-base font-black text-slate-900 dark:text-white tracking-wider uppercase">
                                    {{ $category->name }}
                                </h2>
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 dark:bg-white/[0.06] text-slate-600 dark:text-[#A1A1A6] border border-slate-200/80 dark:border-white/10 uppercase tracking-widest hidden sm:inline-block">
                                    {{ $meta['tag'] }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-3 text-xs">
                            <a href="{{ route('category', $category->slug) }}" class="text-[11px] font-bold text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-white transition-colors flex items-center gap-1 font-mono uppercase tracking-wider group">
                                <span>View All {{ $category->name }}</span>
                                <svg class="w-3.5 h-3.5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- 6-Column Responsive Cyber HUD Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3.5 sm:gap-4">
                        @foreach($catApps as $app)
                            <x-cyber-card :app="$app" :theme="$category->slug" />
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
