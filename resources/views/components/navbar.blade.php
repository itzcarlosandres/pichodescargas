<header class="site-header is-sticky" role="banner" x-data="{ mobileOpen: false }">
    <div class="container-app header-inner flex items-center justify-between h-[65px]">
        <!-- Left Section: Site Branding & Main Navigation Grouped -->
        <!-- Left Section: Site Branding -->
            <!-- Site Branding: Icon + PichoDescargas Logo -->
            <div class="site-branding shrink-0">
                @php
                    $navLogoSize = (int) setting('logo_size', 24);
                    $navIconSize = max(20, $navLogoSize + 8);
                @endphp
                <a class="custom-logo-link logo-dual flex items-center gap-2.5 text-[#1C1814] dark:text-white font-bold tracking-tight hover:opacity-95 transition-opacity"
                   href="{{ route('home') }}"
                   rel="home">
                    @if(setting('logo_type') === 'image' && setting('logo_image'))
                        <img src="{{ setting('logo_image') }}" alt="{{ setting('site_name', 'PichoDescargas') }}{{ setting('site_name_highlight', '') }}" style="height: {{ $navLogoSize + 10 }}px;" class="max-h-[52px] max-w-[220px] object-contain flex-shrink-0">
                    @else
                        <div style="width: {{ $navIconSize }}px; height: {{ $navIconSize }}px;" class="flex-shrink-0 flex items-center justify-center">
                            <x-logo-icon :icon="setting('logo_icon', 'download')" class="w-full h-full" />
                        </div>

                        <span style="font-size: {{ $navLogoSize }}px; line-height: 1;" class="text-[#1C1814] dark:text-white font-black tracking-[-0.03em] font-sans inline-flex items-center">
                            {{ setting('site_name', 'Picho') }}<span class="text-primary font-black">{{ setting('site_name_highlight', 'Descargas') }}</span>
                        </span>
                    @endif
                </a>
            </div>

            <!-- Navigation Links -->
            <!-- Center Section: Navigation Links (Centrado) -->
        <nav class="main-nav hidden lg:flex items-center justify-center flex-1 mx-3 xl:mx-6" aria-label="Top menu">
            <ul id="menu-main-menu" class="menu flex items-center justify-center gap-1 xl:gap-1.5 text-[13.5px] xl:text-[14px] font-medium text-[#38322B] dark:text-[#F6F3EC]">
                    <!-- Programs -->
                    <li class="menu-item shrink-0">
                        <a href="{{ route('category', 'programas') }}"
                           class="hover:text-[#0071E3] dark:hover:text-white transition-all flex items-center gap-1.5 py-1.5 px-2 xl:px-2.5 rounded-lg hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer whitespace-nowrap shrink-0 {{ request()->is('category/programas*') ? 'text-[#0071E3] dark:text-white font-semibold' : 'text-[#38322B]/90 dark:text-[#F6F3EC]/90' }}">
                            <x-lucide-monitor class="w-4 h-4 text-[#0071E3] flex-shrink-0" />
                            <span class="whitespace-nowrap">Programs</span>
                        </a>
                    </li>

                    <!-- Mac Apps -->
                    <li class="menu-item shrink-0">
                        <a href="{{ route('category', 'mac-apps') }}"
                           class="hover:text-[#0071E3] dark:hover:text-white transition-all flex items-center gap-1.5 py-1.5 px-2 xl:px-2.5 rounded-lg hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer whitespace-nowrap shrink-0 {{ request()->is('category/mac-apps*') ? 'text-[#0071E3] dark:text-white font-semibold' : 'text-[#38322B]/90 dark:text-[#F6F3EC]/90' }}">
                            <x-lucide-apple class="w-4 h-4 text-neutral-400 dark:text-neutral-200 flex-shrink-0" />
                            <span class="whitespace-nowrap">Mac Apps</span>
                        </a>
                    </li>

                    <!-- Android Apps -->
                    <li class="menu-item shrink-0">
                        <a href="{{ route('category', 'apps-android') }}"
                           class="hover:text-[#0071E3] dark:hover:text-white transition-all flex items-center gap-1.5 py-1.5 px-2 xl:px-2.5 rounded-lg hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer whitespace-nowrap shrink-0 {{ request()->is('category/apps-android*') ? 'text-[#0071E3] dark:text-white font-semibold' : 'text-[#38322B]/90 dark:text-[#F6F3EC]/90' }}">
                            <x-lucide-smartphone class="w-4 h-4 text-emerald-500 flex-shrink-0" />
                            <span class="whitespace-nowrap">Android Apps</span>
                        </a>
                    </li>

                    <!-- PC Games -->
                    <li class="menu-item shrink-0">
                        <a href="{{ route('category', 'juegos-pc') }}"
                           class="hover:text-[#0071E3] dark:hover:text-white transition-all flex items-center gap-1.5 py-1.5 px-2 xl:px-2.5 rounded-lg hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer whitespace-nowrap shrink-0 {{ request()->is('category/juegos-pc*') ? 'text-[#0071E3] dark:text-white font-semibold' : 'text-[#38322B]/90 dark:text-[#F6F3EC]/90' }}">
                            <x-lucide-gamepad-2 class="w-4 h-4 text-purple-500 flex-shrink-0" />
                            <span class="whitespace-nowrap">PC Games</span>
                        </a>
                    </li>

                    <!-- Free Movies -->
                    <li class="menu-item shrink-0">
                        <a href="{{ route('category', 'peliculas-gratis') }}"
                           class="hover:text-[#0071E3] dark:hover:text-white transition-all flex items-center gap-1.5 py-1.5 px-2 xl:px-2.5 rounded-lg hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer whitespace-nowrap shrink-0 {{ request()->is('category/peliculas-gratis*') ? 'text-[#0071E3] dark:text-white font-semibold' : 'text-[#38322B]/90 dark:text-[#F6F3EC]/90' }}">
                            <x-lucide-film class="w-4 h-4 text-rose-500 flex-shrink-0" />
                            <span class="whitespace-nowrap">Movies</span>
                        </a>
                    </li>

                    <!-- TV Series -->
                    <li class="menu-item shrink-0">
                        <a href="{{ route('category', 'series-tv') }}"
                           class="hover:text-[#0071E3] dark:hover:text-white transition-all flex items-center gap-1.5 py-1.5 px-2 xl:px-2.5 rounded-lg hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer whitespace-nowrap shrink-0 {{ request()->is('category/series-tv*') || request()->is('category/series*') ? 'text-[#0071E3] dark:text-white font-semibold' : 'text-[#38322B]/90 dark:text-[#F6F3EC]/90' }}">
                            <x-lucide-tv class="w-4 h-4 text-violet-400 flex-shrink-0" />
                            <span class="whitespace-nowrap">TV Series</span>
                        </a>
                    </li>

                    <!-- Top Downloads -->
                    <li class="menu-item shrink-0">
                        <a href="{{ route('popular') }}"
                           class="hover:text-[#0071E3] dark:hover:text-white transition-all flex items-center gap-1.5 py-1.5 px-2 xl:px-2.5 rounded-lg hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer whitespace-nowrap shrink-0 {{ request()->routeIs('popular') ? 'text-[#0071E3] dark:text-white font-semibold' : 'text-[#38322B]/90 dark:text-[#F6F3EC]/90' }}">
                            <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 3q1 4 4 6.5t3 5.5a1 1 0 0 1-14 0 5 5 0 0 1 1-3 1 1 0 0 0 5 0c0-2-1.5-3-1.5-5q0-2 2.5-4"/>
                            </svg>
                            <span class="whitespace-nowrap">Top Downloads</span>
                        </a>
                    </li>
                </ul>
        </nav>

        <!-- Right Controls: Theme Toggle, Admin Profile & Mobile Toggle -->
        <div class="header-actions flex items-center gap-1.5 sm:gap-2.5 shrink-0">

            <!-- Theme Toggle Button (Light / Dark) -->
            <button type="button"
                    onclick="toggleTheme()"
                    class="icon-btn theme-toggle w-9 h-9 rounded-lg text-[#4A433B] dark:text-[#F6F3EC]/80 hover:text-[#0071E3] dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5 flex items-center justify-center transition-colors cursor-pointer"
                    aria-label="Toggle dark/light mode"
                    title="Toggle Light / Dark mode">
                <!-- Moon icon shown when in light mode (to switch to dark) -->
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-[18px] h-[18px] dark:hidden block" aria-hidden="true">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
                <!-- Sun icon shown when in dark mode (to switch to light) -->
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-[18px] h-[18px] hidden dark:block" aria-hidden="true">
                    <circle cx="12" cy="12" r="5"></circle>
                    <path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>
                </svg>
            </button>

            @auth
                <!-- Admin quick indicator -->
                <a href="{{ route('admin.dashboard') }}"
                   class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#F5F5F7] dark:bg-[#242426] border border-[#E5E7EB] dark:border-[#333336] text-[#1D1D1F] dark:text-white text-xs hover:border-[#0071E3] transition-colors ml-1"
                   title="Go to Admin Panel">
                    <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                    <span class="font-medium text-[11px]">{{ auth()->user()->name }}</span>
                </a>
            @endauth

            <!-- Mobile Menu Toggle Button -->
            <button type="button"
                    class="icon-btn menu-toggle w-9 h-9 rounded-lg text-[#4A433B] dark:text-[#F6F3EC]/80 hover:text-[#1C1814] dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5 flex items-center justify-center transition-colors lg:hidden cursor-pointer"
                    @click="mobileOpen = !mobileOpen"
                    aria-label="Menu"
                    aria-expanded="false"
                    :aria-expanded="mobileOpen">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5" aria-hidden="true">
                    <path d="M3 6h18M3 12h18M3 18h18"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Drawer -->
    <div x-show="mobileOpen" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="lg:hidden border-t border-[#E5DFD7] dark:border-[#222938] bg-[#F5F5F7]/95 dark:bg-[#131822]/95 backdrop-blur-xl">
        <div class="container-app py-4 space-y-3">
            <nav class="flex flex-col gap-1.5 text-sm text-[#38322B] dark:text-[#F6F3EC]">
                <a href="{{ route('popular') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-black/5 dark:hover:bg-white/5 transition-colors group">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-orange-500/10 text-orange-500 dark:bg-orange-500/20 border border-orange-500/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 3q1 4 4 6.5t3 5.5a1 1 0 0 1-14 0 5 5 0 0 1 1-3 1 1 0 0 0 5 0c0-2-1.5-3-1.5-5q0-2 2.5-4"/>
                            </svg>
                        </div>
                        <div class="truncate">
                            <span class="block font-semibold text-xs leading-tight group-hover:text-[#0071E3] dark:group-hover:text-white transition-colors">Top Downloads</span>
                            <span class="block text-[10px] text-[#8C847A] dark:text-[#A1A1AA]">Trending &amp; Most Popular</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-[#8C847A] group-hover:text-[#0071E3] dark:group-hover:text-white transition-colors flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </a>

                <!-- Programs -->
                <a href="{{ route('category', 'programas') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-black/5 dark:hover:bg-white/5 transition-colors group">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-[#0071E3]/10 text-[#0071E3] dark:bg-[#0071E3]/20 border border-[#0071E3]/20 flex items-center justify-center flex-shrink-0">
                            <x-lucide-monitor class="w-4 h-4" />
                        </div>
                        <div class="truncate">
                            <span class="block font-semibold text-xs leading-tight group-hover:text-[#0071E3] dark:group-hover:text-white transition-colors">Programs</span>
                            <span class="block text-[10px] text-[#8C847A] dark:text-[#A1A1AA]">Software &amp; Utilities for Windows</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-[#8C847A] group-hover:text-[#0071E3] dark:group-hover:text-white transition-colors flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </a>

                <!-- Mac Apps -->
                <a href="{{ route('category', 'mac-apps') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-black/5 dark:hover:bg-white/5 transition-colors group">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-neutral-500/10 text-neutral-400 dark:bg-neutral-500/20 border border-neutral-500/20 flex items-center justify-center flex-shrink-0">
                            <x-lucide-apple class="w-4 h-4" />
                        </div>
                        <div class="truncate">
                            <span class="block font-semibold text-xs leading-tight group-hover:text-[#0071E3] dark:group-hover:text-white transition-colors">Mac Apps</span>
                            <span class="block text-[10px] text-[#8C847A] dark:text-[#A1A1AA]">Applications &amp; Software for macOS</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-[#8C847A] group-hover:text-[#0071E3] dark:group-hover:text-white transition-colors flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </a>

                <!-- Android Apps -->
                <a href="{{ route('category', 'apps-android') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-black/5 dark:hover:bg-white/5 transition-colors group">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 dark:bg-emerald-500/20 border border-emerald-500/20 flex items-center justify-center flex-shrink-0">
                            <x-lucide-smartphone class="w-4 h-4" />
                        </div>
                        <div class="truncate">
                            <span class="block font-semibold text-xs leading-tight group-hover:text-[#0071E3] dark:group-hover:text-white transition-colors">Android Apps</span>
                            <span class="block text-[10px] text-[#8C847A] dark:text-[#A1A1AA]">APKs &amp; Mobile Tools</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-[#8C847A] group-hover:text-[#0071E3] dark:group-hover:text-white transition-colors flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </a>

                <!-- PC Games -->
                <a href="{{ route('category', 'juegos-pc') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-black/5 dark:hover:bg-white/5 transition-colors group">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-500 dark:bg-purple-500/20 border border-purple-500/20 flex items-center justify-center flex-shrink-0">
                            <x-lucide-gamepad-2 class="w-4 h-4" />
                        </div>
                        <div class="truncate">
                            <span class="block font-semibold text-xs leading-tight group-hover:text-[#0071E3] dark:group-hover:text-white transition-colors">PC Games</span>
                            <span class="block text-[10px] text-[#8C847A] dark:text-[#A1A1AA]">Full Games for Windows PC</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-[#8C847A] group-hover:text-[#0071E3] dark:group-hover:text-white transition-colors flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </a>

                <!-- Free Movies -->
                <a href="{{ route('category', 'peliculas-gratis') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-black/5 dark:hover:bg-white/5 transition-colors group">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-500 dark:bg-rose-500/20 border border-rose-500/20 flex items-center justify-center flex-shrink-0">
                            <x-lucide-film class="w-4 h-4" />
                        </div>
                        <div class="truncate">
                            <span class="block font-semibold text-xs leading-tight group-hover:text-[#0071E3] dark:group-hover:text-white transition-colors">Free Movies</span>
                            <span class="block text-[10px] text-[#8C847A] dark:text-[#A1A1AA]">Full HD Movies &amp; Streaming</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-[#8C847A] group-hover:text-[#0071E3] dark:group-hover:text-white transition-colors flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </a>

                <!-- TV Series -->
                <a href="{{ route('category', 'series-tv') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-black/5 dark:hover:bg-white/5 transition-colors group">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-violet-500/10 text-violet-400 dark:bg-violet-500/20 border border-violet-500/20 flex items-center justify-center flex-shrink-0">
                            <x-lucide-tv class="w-4 h-4" />
                        </div>
                        <div class="truncate">
                            <span class="block font-semibold text-xs leading-tight group-hover:text-[#0071E3] dark:group-hover:text-white transition-colors">TV Series</span>
                            <span class="block text-[10px] text-[#8C847A] dark:text-[#A1A1AA]">Full Seasons &amp; HD Episodes</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-[#8C847A] group-hover:text-[#0071E3] dark:group-hover:text-white transition-colors flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </a>

                @auth
                    <div class="border-t border-[#E5DFD7] dark:border-[#222938] my-1"></div>
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between p-2.5 rounded-xl bg-primary/10 text-primary border border-primary/20 hover:bg-primary/20 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-primary text-white flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <span class="font-bold text-xs">Admin Panel</span>
                        </div>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-primary/20 text-primary border border-primary/30">PRO</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="pt-1">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 text-xs text-rose-500 hover:text-rose-600 font-medium cursor-pointer">Sign Out</button>
                    </form>
                @endauth
            </nav>
        </div>
    </div>
</header>
