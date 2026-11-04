<div x-data="{
        isOpen: false,
        query: '',
        results: [],
        loading: false,
        selectedIndex: 0,
        timeout: null,

        search() {
            clearTimeout(this.timeout);
            this.selectedIndex = 0;
            const term = this.query.trim();
            if (term.length < 2) {
                this.results = [];
                this.loading = false;
                return;
            }
            this.loading = true;
            this.timeout = setTimeout(async () => {
                try {
                    const res = await fetch('{{ route('api.search.live') }}?q=' + encodeURIComponent(term));
                    if (res.ok) {
                        this.results = await res.json();
                    }
                } catch(e) {
                    console.error('Search error:', e);
                } finally {
                    this.loading = false;
                }
            }, 180);
        },

        open(initialQuery = '') {
            this.isOpen = true;
            this.selectedIndex = 0;
            if (initialQuery) {
                this.query = initialQuery;
                this.search();
            }
            document.body.classList.add('overflow-hidden');
            this.$nextTick(() => {
                if (this.$refs.searchInput) {
                    this.$refs.searchInput.focus();
                    if (this.query) {
                        this.$refs.searchInput.select();
                    }
                }
            });
        },

        close() {
            this.isOpen = false;
            document.body.classList.remove('overflow-hidden');
        },

        selectNext() {
            if (this.results.length === 0) return;
            this.selectedIndex = (this.selectedIndex + 1) % this.results.length;
            this.scrollToSelected();
        },

        selectPrev() {
            if (this.results.length === 0) return;
            this.selectedIndex = (this.selectedIndex - 1 + this.results.length) % this.results.length;
            this.scrollToSelected();
        },

        selectCurrent() {
            if (this.results.length > 0 && this.results[this.selectedIndex]) {
                window.location.href = this.results[this.selectedIndex].url;
            } else if (this.query.trim().length > 0) {
                window.location.href = '{{ route('search') }}?q=' + encodeURIComponent(this.query.trim());
            }
        },

        scrollToSelected() {
            this.$nextTick(() => {
                const el = this.$refs.resultsList?.children[this.selectedIndex];
                if (el) {
                    el.scrollIntoView({ block: 'nearest' });
                }
            });
        },

        setQuery(term) {
            this.query = term;
            this.search();
            this.$nextTick(() => {
                this.$refs.searchInput && this.$refs.searchInput.focus();
            });
        }
    }"
    @open-search-modal.window="open($event.detail?.query || '')"
    @keydown.escape.window="if (isOpen) { close(); }"
    @keydown.window="
        if ((($event.metaKey || $event.ctrlKey) && $event.key.toLowerCase() === 'k') || 
            ($event.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName))) {
            $event.preventDefault();
            open();
        }
    "
    x-cloak>

    <!-- Fullscreen Backdrop & Centering Container -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[120] flex items-start justify-center pt-8 sm:pt-20 px-3 sm:px-6 pb-6 bg-black/75 backdrop-blur-md overflow-y-auto"
         @click.self="close()">

        <!-- Modal Dialog Box (macOS Spotlight / Raycast Style) -->
        <div x-show="isOpen"
             x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-250 transform"
             x-transition:enter-start="opacity-0 scale-95 -translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 -translate-y-4"
             class="w-full max-w-2xl bg-[#0F141F]/95 text-[#1C1814] dark:text-[#F6F3EC] rounded-2xl border border-white/10 shadow-[0_30px_90px_rgba(0,0,0,0.9),0_0_0_1px_rgba(255,255,255,0.08)] overflow-hidden flex flex-col font-sans backdrop-blur-2xl"
             @click.stop>

            <!-- Search Input Header -->
            <div class="relative p-3.5 sm:p-4.5 border-b border-white/10 flex items-center gap-3 bg-white/[0.03]">
                <!-- Search Icon with Glow -->
                <div class="w-8 h-8 rounded-lg bg-[#0071E3]/15 text-[#38BDF8] flex items-center justify-center shrink-0 border border-[#0071E3]/30">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="w-4 h-4 text-[#38BDF8]" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                </div>

                <!-- Input -->
                <input x-ref="searchInput"
                       x-model="query"
                       @input="search()"
                       @keydown.down.prevent="selectNext()"
                       @keydown.up.prevent="selectPrev()"
                       @keydown.enter.prevent="selectCurrent()"
                       type="text"
                       placeholder="Search programs, mac apps, games, movies..."
                       class="w-full bg-transparent text-base sm:text-lg font-medium text-white placeholder-neutral-400 focus:outline-none focus:ring-0 tracking-tight"
                       autocomplete="off"
                       spellcheck="false">

                <!-- Loading Spinner -->
                <div x-show="loading" class="shrink-0 flex items-center">
                    <svg class="animate-spin w-4 h-4 text-[#38BDF8]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <!-- Clear query button -->
                <button type="button"
                        x-show="query.length > 0"
                        @click="query = ''; results = []; $refs.searchInput.focus()"
                        class="text-neutral-400 hover:text-white p-1 rounded-md transition-colors cursor-pointer"
                        title="Clear search">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <!-- ESC Keyboard Badge -->
                <kbd class="hidden sm:inline-flex items-center px-2 py-0.5 text-[10px] font-mono font-medium rounded bg-white/10 text-neutral-300 border border-white/10 select-none">
                    ESC
                </kbd>

                <!-- Close Button -->
                <button type="button"
                        @click="close()"
                        class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 text-neutral-400 hover:text-white flex items-center justify-center transition-colors cursor-pointer border border-white/5"
                        aria-label="Close modal">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Content Body Area (Scrollable) -->
            <div class="max-h-[62vh] sm:max-h-[460px] overflow-y-auto p-3 sm:p-4 space-y-4 custom-scrollbar">

                <!-- STATE A: Suggestions & Fast Categories (When query < 2 chars) -->
                <div x-show="query.trim().length < 2" class="space-y-4 py-1">
                    <!-- Quick Categories Section -->
                    <div>
                        <div class="flex items-center justify-between mb-2.5 px-1">
                            <span class="text-[11px] font-bold tracking-wider uppercase text-neutral-400 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#0071E3]"></span>
                                Main Categories
                            </span>
                            <span class="text-[11px] text-neutral-400">Quick Access</span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <!-- Programas -->
                            <a href="{{ route('category', 'programas') }}"
                               class="group flex items-center gap-2.5 p-2.5 rounded-xl bg-white/[0.03] hover:bg-white/[0.08] border border-white/5 hover:border-[#0071E3]/40 transition-all">
                                <div class="w-8 h-8 rounded-lg bg-[#0071E3]/15 text-[#0071E3] flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-white group-hover:text-[#38BDF8] transition-colors truncate">PC Software</p>
                                    <p class="text-[10px] text-neutral-400">Windows &amp; tools</p>
                                </div>
                            </a>

                            <!-- Mac Apps -->
                            <a href="{{ route('category', 'mac-apps') }}"
                               class="group flex items-center gap-2.5 p-2.5 rounded-xl bg-white/[0.03] hover:bg-white/[0.08] border border-white/5 hover:border-neutral-400/40 transition-all">
                                <div class="w-8 h-8 rounded-lg bg-neutral-700/30 text-neutral-200 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.42c.59-.72 1-1.72.89-2.72-.88.04-1.95.59-2.57 1.31-.55.63-.99 1.66-.88 2.64.98.08 1.98-.51 2.56-1.23z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-white group-hover:text-neutral-200 transition-colors truncate">Mac Apps</p>
                                    <p class="text-[10px] text-neutral-400">Native macOS</p>
                                </div>
                            </a>

                            <!-- Apps Android -->
                            <a href="{{ route('category', 'apps-android') }}"
                               class="group flex items-center gap-2.5 p-2.5 rounded-xl bg-white/[0.03] hover:bg-white/[0.08] border border-white/5 hover:border-emerald-500/40 transition-all">
                                <div class="w-8 h-8 rounded-lg bg-emerald-500/15 text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-white group-hover:text-emerald-400 transition-colors truncate">Android Apps</p>
                                    <p class="text-[10px] text-neutral-400">APKs &amp; tools</p>
                                </div>
                            </a>

                            <!-- Juegos PC -->
                            <a href="{{ route('category', 'juegos-pc') }}"
                               class="group flex items-center gap-2.5 p-2.5 rounded-xl bg-white/[0.03] hover:bg-white/[0.08] border border-white/5 hover:border-purple-500/40 transition-all">
                                <div class="w-8 h-8 rounded-lg bg-purple-500/15 text-purple-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><line x1="6" x2="10" y1="12" y2="12"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="15" x2="15.01" y1="13" y2="13"/><line x1="18" x2="18.01" y1="11" y2="11"/><rect width="20" height="12" x="2" y="6" rx="2"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-white group-hover:text-purple-400 transition-colors truncate">PC Games</p>
                                    <p class="text-[10px] text-neutral-400">Full &amp; updated</p>
                                </div>
                            </a>

                            <!-- Películas Gratis -->
                            <a href="{{ route('category', 'peliculas-gratis') }}"
                               class="group flex items-center gap-2.5 p-2.5 rounded-xl bg-white/[0.03] hover:bg-white/[0.08] border border-white/5 hover:border-rose-500/40 transition-all">
                                <div class="w-8 h-8 rounded-lg bg-rose-500/15 text-rose-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18"/><path d="M3 7.5h4"/><path d="M3 12h18"/><path d="M3 16.5h4"/><path d="M17 3v18"/><path d="M17 7.5h4"/><path d="M17 16.5h4"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-white group-hover:text-rose-400 transition-colors truncate">Movies</p>
                                    <p class="text-[10px] text-neutral-400">HD &amp; 4K streaming</p>
                                </div>
                            </a>

                            <!-- Top Descargas -->
                            <a href="{{ route('popular') }}"
                               class="group flex items-center gap-2.5 p-2.5 rounded-xl bg-white/[0.03] hover:bg-white/[0.08] border border-white/5 hover:border-amber-500/40 transition-all">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/15 text-amber-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M12 3q1 4 4 6.5t3 5.5a1 1 0 0 1-14 0 5 5 0 0 1 1-3 1 1 0 0 0 5 0c0-2-1.5-3-1.5-5q0-2 2.5-4"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-white group-hover:text-amber-400 transition-colors truncate">Top Downloads</p>
                                    <p class="text-[10px] text-neutral-400">Most popular</p>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Trending Suggestions Tags -->
                    <div class="pt-2">
                        <span class="text-[11px] font-bold tracking-wider uppercase text-neutral-400 flex items-center gap-1.5 mb-2 px-1">
                            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            Suggested Searches
                        </span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach(['Photoshop', 'Office', 'GTA', 'Spotify', 'Windows 11', 'Premiere Pro', 'Telegram', 'Illustrator', 'CapCut', 'FL Studio'] as $tag)
                                <button type="button"
                                        @click="setQuery('{{ $tag }}')"
                                        class="px-2.5 py-1 text-xs rounded-lg bg-white/5 hover:bg-white/10 text-neutral-300 hover:text-white border border-white/5 hover:border-white/15 transition-all cursor-pointer">
                                    #{{ $tag }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- STATE B: Live Results Found -->
                <div x-show="results.length > 0" class="space-y-1">
                    <div class="flex items-center justify-between mb-2 px-1">
                        <span class="text-[11px] font-bold tracking-wider uppercase text-neutral-400 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            Results found (<span x-text="results.length"></span>)
                        </span>
                        <span class="text-[11px] text-neutral-400">Use arrows ↑↓ and press Enter</span>
                    </div>

                    <div x-ref="resultsList" class="space-y-1">
                        <template x-for="(item, index) in results" :key="item.id">
                            <a :href="item.url"
                               @mouseenter="selectedIndex = index"
                               :class="selectedIndex === index ? 'bg-[#0071E3]/20 dark:bg-sky-500/20 border-l-4 border-l-[#0071E3] dark:border-l-sky-400 shadow-md' : 'bg-white/[0.02] hover:bg-white/[0.05] border-l-4 border-l-transparent'"
                               class="flex items-center justify-between p-2.5 sm:p-3 rounded-xl border border-white/5 transition-all group cursor-pointer">
                                
                                <div class="flex items-center gap-3 min-w-0">
                                    <!-- App Icon Frame -->
                                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-black/40 border border-white/10 p-0.5 shrink-0 overflow-hidden flex items-center justify-center group-hover:scale-105 transition-transform">
                                        <img :src="item.icon_url" :alt="item.name" class="w-full h-full object-cover rounded-lg">
                                    </div>

                                    <!-- Details -->
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-bold text-white group-hover:text-[#38BDF8] transition-colors truncate" x-text="item.name"></p>
                                            <span class="hidden sm:inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono bg-white/10 text-neutral-300" x-text="'v' + item.version"></span>
                                        </div>

                                        <div class="flex items-center gap-2 text-[11px] text-neutral-400 mt-0.5">
                                            <span class="text-[#38BDF8] font-medium" x-text="item.category"></span>
                                            <span>•</span>
                                            <span x-text="item.size"></span>
                                            <span>•</span>
                                            <span class="flex items-center gap-1 text-emerald-400 font-medium">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                <span x-text="item.downloads"></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Indicator -->
                                <div class="shrink-0 flex items-center gap-2 pl-3">
                                    <span :class="selectedIndex === index ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-1 sm:opacity-50'"
                                          class="transition-all text-xs font-mono font-semibold text-[#38BDF8] flex items-center gap-1">
                                        <span class="hidden sm:inline">Open</span>
                                        <kbd class="px-1.5 py-0.5 rounded bg-white/10 text-[10px] border border-white/10">↵</kbd>
                                    </span>
                                </div>
                            </a>
                        </template>
                    </div>

                    <!-- View All Results in full search page -->
                    <div class="pt-2 text-center">
                        <a :href="'{{ route('search') }}?q=' + encodeURIComponent(query.trim())"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-medium text-neutral-300 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 transition-colors">
                            <span>View all results for "<span class="font-bold text-[#38BDF8]" x-text="query"></span>"</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                </div>

                <!-- STATE C: No Results Found (query >= 2 chars, loading finished, results empty) -->
                <div x-show="!loading && query.trim().length >= 2 && results.length === 0" class="py-8 text-center space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 text-neutral-400 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
                            <path d="m21 21-4.3-4.3" stroke-width="2"></path>
                            <line x1="8" y1="11" x2="14" y2="11" stroke-width="2"></line>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">No results found for "<span class="text-[#38BDF8]" x-text="query"></span>"</p>
                        <p class="text-xs text-neutral-400 max-w-sm mx-auto mt-1">Check the spelling or try searching by category, developer or related keyword.</p>
                    </div>
                    <div class="pt-2">
                        <a :href="'{{ route('search') }}?q=' + encodeURIComponent(query.trim())"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-[#0071E3] hover:bg-[#0077ED] transition-colors shadow-lg shadow-[#0071E3]/20">
                            Search full catalog
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                </div>

            </div>

            <!-- Footer Toolbar / Shortcuts HUD -->
            <div class="p-3 px-4 sm:px-5 border-t border-white/10 flex items-center justify-between text-[11px] text-neutral-400 bg-black/30 select-none">
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1">
                        <kbd class="px-1.5 py-0.5 rounded bg-white/10 text-neutral-300 font-mono text-[10px]">↑</kbd>
                        <kbd class="px-1.5 py-0.5 rounded bg-white/10 text-neutral-300 font-mono text-[10px]">↓</kbd>
                        <span class="hidden sm:inline">Navigate</span>
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="px-1.5 py-0.5 rounded bg-white/10 text-neutral-300 font-mono text-[10px]">↵</kbd>
                        <span class="hidden sm:inline">Select</span>
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="px-1.5 py-0.5 rounded bg-white/10 text-neutral-300 font-mono text-[10px]">ESC</kbd>
                        <span class="hidden sm:inline">Close</span>
                    </span>
                </div>

                <div class="flex items-center gap-2 font-mono text-[10px] text-neutral-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>PichoDescargas Spotlight</span>
                </div>
            </div>

        </div>
    </div>
</div>

