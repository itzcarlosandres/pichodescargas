@props(['categories' => []])

@php
    $categoryMeta = [
        'programas' => [
            'platform' => 'WINDOWS PC',
            'badge' => 'Windows 11 / 10 • 64-bit',
            'badge_bg' => 'bg-sky-500/10 text-sky-400 border-sky-500/25',
            'accent_glow' => 'from-sky-500/20 via-sky-500/5 to-transparent',
            'border_hover' => 'group-hover:border-sky-500/50',
            'icon_bg' => 'bg-gradient-to-br from-sky-500/20 to-blue-600/30 text-sky-400 border-sky-500/30 shadow-sky-500/20',
            'tags' => ['Productivity', 'Design', 'Office', 'Antivirus'],
            'tag_style' => 'bg-sky-500/10 text-sky-300 border-sky-500/20',
            'btn_text' => 'Explore Programs',
            'btn_hover' => 'group-hover:bg-sky-500 group-hover:text-white',
            'default_desc' => 'Full productivity software, editing, design tools, and utilities for Windows.',
        ],
        'mac-apps' => [
            'platform' => 'MACOS SYSTEM',
            'badge' => 'Apple Silicon & Intel • Universal',
            'badge_bg' => 'bg-slate-200/10 text-slate-300 border-slate-300/25',
            'accent_glow' => 'from-slate-200/15 via-slate-400/5 to-transparent',
            'border_hover' => 'group-hover:border-slate-300/50',
            'icon_bg' => 'bg-gradient-to-br from-slate-200/20 to-slate-400/30 text-slate-200 border-slate-300/30 shadow-slate-300/20',
            'tags' => ['M1 - M4', 'DMG / PKG', 'Gatekeeper', 'SIP-Free'],
            'tag_style' => 'bg-slate-400/10 text-slate-200 border-slate-400/20',
            'btn_text' => 'Explore Mac Apps',
            'btn_hover' => 'group-hover:bg-slate-200 group-hover:text-slate-950',
            'default_desc' => 'Native applications optimized for macOS, accelerated on Apple Silicon and Intel.',
        ],
        'apps-android' => [
            'platform' => 'ANDROID OS',
            'badge' => 'APK • Mod • Premium Unlocked',
            'badge_bg' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/25',
            'accent_glow' => 'from-emerald-500/20 via-emerald-500/5 to-transparent',
            'border_hover' => 'group-hover:border-emerald-500/50',
            'icon_bg' => 'bg-gradient-to-br from-emerald-500/20 to-teal-600/30 text-emerald-400 border-emerald-500/30 shadow-emerald-500/20',
            'tags' => ['Ad-Free', 'Pro / VIP', 'Anti-Ban', 'No Root'],
            'tag_style' => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
            'btn_text' => 'Explore Android',
            'btn_hover' => 'group-hover:bg-emerald-500 group-hover:text-white',
            'default_desc' => 'Modified APKs, unlocked premium tools, and verified mobile utilities.',
        ],
        'juegos-pc' => [
            'platform' => 'PC GAMING',
            'badge' => 'Full English & Multi • Repacks • Direct',
            'badge_bg' => 'bg-purple-500/10 text-purple-400 border-purple-500/25',
            'accent_glow' => 'from-purple-500/25 via-pink-500/5 to-transparent',
            'border_hover' => 'group-hover:border-purple-500/50',
            'icon_bg' => 'bg-gradient-to-br from-purple-500/20 to-pink-600/30 text-purple-400 border-purple-500/30 shadow-purple-500/20',
            'tags' => ['All DLCs', 'Steam Fix', 'Action', 'Repack'],
            'tag_style' => 'bg-purple-500/10 text-purple-300 border-purple-500/20',
            'btn_text' => 'Explore PC Games',
            'btn_hover' => 'group-hover:bg-purple-500 group-hover:text-white',
            'default_desc' => 'Complete PC game releases, updated installers, and clean packages.',
        ],
    ];
@endphp

<section class="mb-10" aria-label="Main categories">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2.5 mb-5">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <span class="w-2.5 h-2.5 rounded-[3px] bg-primary shadow-sm shadow-primary/50"></span>
                <span class="text-[11px] font-bold text-primary uppercase tracking-widest font-heading">
                    Download Ecosystems
                </span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-[#1C1814] dark:text-white tracking-tight font-heading">
                Explore by Platform
            </h2>
        </div>
        <p class="text-xs text-[#7E776F] dark:text-[#A1A1A6] max-w-sm">
            Direct downloads, clean packages, and verified software organized for every device.
        </p>
    </div>

    <!-- 4 Category Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($categories as $category)
            @php
                $meta = $categoryMeta[$category->slug] ?? [
                    'platform' => strtoupper($category->name),
                    'badge' => 'Verified',
                    'badge_bg' => 'bg-primary/10 text-primary border-primary/20',
                    'accent_glow' => 'from-primary/20 via-primary/5 to-transparent',
                    'border_hover' => 'group-hover:border-primary/50',
                    'icon_bg' => 'bg-primary/10 text-primary border-primary/30 shadow-primary/20',
                    'tags' => ['Direct Download', 'Safe'],
                    'tag_style' => 'bg-white/5 text-white/80 border-white/10',
                    'btn_text' => 'View Category',
                    'btn_hover' => 'group-hover:bg-primary group-hover:text-white',
                    'default_desc' => $category->description ?: 'Explore the best verified downloads available in this category.',
                ];
                $appsCount = $category->published_applications_count ?? $category->applications()->count();
            @endphp

            <a href="{{ route('category', $category->slug) }}"
               class="group relative flex flex-col justify-between overflow-hidden rounded-2xl bg-white dark:bg-[#141720] border border-[#E5DFD7] dark:border-[#232734] p-5 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-black/20 dark:hover:shadow-black/50 {{ $meta['border_hover'] }} cursor-pointer">

                <!-- Ambient Radial Glow on Hover -->
                <div class="absolute -right-12 -top-12 w-44 h-44 rounded-full bg-gradient-to-br {{ $meta['accent_glow'] }} blur-3xl opacity-0 group-hover:opacity-100 group-hover:scale-125 transition-all duration-500 pointer-events-none"></div>

                <!-- Top Platform Badge & Icon Row -->
                <div class="relative z-10 space-y-4">
                    <div class="flex items-center justify-between gap-2">
                        <!-- 3D Squircle Icon -->
                        <div class="w-12 h-12 rounded-xl {{ $meta['icon_bg'] }} border flex items-center justify-center shadow-lg group-hover:scale-110 group-hover:rotate-1 transition-all duration-300 flex-shrink-0">
                            <x-category-icon :icon="$category->icon" :name="$category->slug" class="w-6 h-6" />
                        </div>

                        <!-- Platform Pill Badge -->
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase border {{ $meta['badge_bg'] }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current animate-pulse"></span>
                            <span>{{ $meta['platform'] }}</span>
                        </span>
                    </div>

                    <!-- Title & Description -->
                    <div>
                        <h3 class="text-lg font-black text-[#1C1814] dark:text-white tracking-tight font-heading group-hover:text-primary transition-colors flex items-center justify-between">
                            <span>{{ $category->name }}</span>
                            <span class="text-xs font-mono font-medium text-[#7E776F] dark:text-[#8C847A]">
                                {{ $appsCount > 0 ? $appsCount . ' apps' : 'Nuevos' }}
                            </span>
                        </h3>
                        <p class="text-xs text-[#7E776F] dark:text-[#94A3B8] leading-relaxed mt-1.5 line-clamp-2">
                            {{ $category->description ?: $meta['default_desc'] }}
                        </p>
                    </div>

                    <!-- Platform Feature Chips -->
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        @foreach($meta['tags'] as $tag)
                            <span class="inline-block px-2 py-0.5 rounded-md text-[10.5px] font-medium border {{ $meta['tag_style'] }}">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <!-- Footer Action Row -->
                <div class="relative z-10 pt-4 mt-4 border-t border-[#EFE9DF] dark:border-[#1E2330] flex items-center justify-between">
                    <span class="text-[11px] font-medium text-[#7E776F] dark:text-[#8C847A] group-hover:text-[#1C1814] dark:group-hover:text-white transition-colors">
                        {{ $meta['badge'] }}
                    </span>

                    <span class="inline-flex items-center gap-1 text-xs font-bold text-primary group-hover:translate-x-1 transition-transform">
                        <span>Entrar</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                        </svg>
                    </span>
                </div>
            </a>
        @endforeach
    </div>
</section>
    </div>
</section>
    </div>
</section>

