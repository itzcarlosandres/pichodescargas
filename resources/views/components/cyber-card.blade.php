@props(['app', 'theme' => null])

@php
    $catSlug = $theme ?? ($app->category?->slug ?? 'programas');

    $themeConfig = match($catSlug) {
        'programas' => [
            'card_border' => 'border-slate-200/90 hover:border-sky-400 dark:border-sky-500/20 dark:hover:border-sky-400/60',
            'corner' => 'border-sky-500 dark:border-sky-400',
            'glow' => 'hover:shadow-[0_12px_32px_rgba(56,189,248,0.18)] dark:hover:shadow-[0_12px_36px_rgba(56,189,248,0.22)]',
            'icon_border' => 'border-slate-200/90 group-hover:border-sky-400 dark:border-sky-500/30 dark:group-hover:border-sky-400 dark:group-hover:shadow-[0_0_24px_rgba(56,189,248,0.4)]',
            'icon_bg' => 'bg-sky-50 text-sky-600 dark:bg-sky-950 dark:text-sky-400',
            'title_hover' => 'group-hover:text-sky-600 dark:group-hover:text-sky-300',
            'btn_bg' => 'bg-sky-500/10 hover:bg-sky-500 text-sky-600 hover:text-white border-sky-500/20 dark:bg-sky-500/15 dark:hover:bg-sky-500 dark:text-sky-400 dark:hover:text-black dark:border-sky-500/30 dark:group-hover:shadow-[0_0_12px_rgba(56,189,248,0.5)]',
            'badge' => 'text-sky-700 bg-sky-50 border-sky-200/80 dark:text-sky-400 dark:bg-sky-500/10 dark:border-sky-500/20',
        ],
        'apps-android' => [
            'card_border' => 'border-slate-200/90 hover:border-emerald-400 dark:border-emerald-500/20 dark:hover:border-emerald-400/60',
            'corner' => 'border-emerald-500 dark:border-emerald-400',
            'glow' => 'hover:shadow-[0_12px_32px_rgba(52,211,153,0.18)] dark:hover:shadow-[0_12px_36px_rgba(52,211,153,0.22)]',
            'icon_border' => 'border-slate-200/90 group-hover:border-emerald-400 dark:border-emerald-500/30 dark:group-hover:border-emerald-400 dark:group-hover:shadow-[0_0_24px_rgba(52,211,153,0.4)]',
            'icon_bg' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400',
            'title_hover' => 'group-hover:text-emerald-600 dark:group-hover:text-emerald-300',
            'btn_bg' => 'bg-emerald-500/10 hover:bg-emerald-500 text-emerald-600 hover:text-white border-emerald-500/20 dark:bg-emerald-500/15 dark:hover:bg-emerald-500 dark:text-emerald-400 dark:hover:text-black dark:border-emerald-500/30 dark:group-hover:shadow-[0_0_12px_rgba(52,211,153,0.5)]',
            'badge' => 'text-emerald-700 bg-emerald-50 border-emerald-200/80 dark:text-emerald-400 dark:bg-emerald-500/10 dark:border-emerald-500/20',
        ],
        'juegos-pc' => [
            'card_border' => 'border-slate-200/90 hover:border-purple-400 dark:border-purple-500/20 dark:hover:border-purple-400/60',
            'corner' => 'border-purple-500 dark:border-purple-400',
            'glow' => 'hover:shadow-[0_12px_32px_rgba(168,85,247,0.18)] dark:hover:shadow-[0_12px_36px_rgba(168,85,247,0.22)]',
            'icon_border' => 'border-slate-200/90 group-hover:border-purple-400 dark:border-purple-500/30 dark:group-hover:border-purple-400 dark:group-hover:shadow-[0_0_24px_rgba(168,85,247,0.4)]',
            'icon_bg' => 'bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400',
            'title_hover' => 'group-hover:text-purple-600 dark:group-hover:text-purple-300',
            'btn_bg' => 'bg-purple-500/10 hover:bg-purple-500 text-purple-600 hover:text-white border-purple-500/20 dark:bg-purple-500/15 dark:hover:bg-purple-500 dark:text-purple-400 dark:hover:text-black dark:border-purple-500/30 dark:group-hover:shadow-[0_0_12px_rgba(168,85,247,0.5)]',
            'badge' => 'text-purple-700 bg-purple-50 border-purple-200/80 dark:text-purple-400 dark:bg-purple-500/10 dark:border-purple-500/20',
        ],
        'peliculas-gratis' => [
            'card_border' => 'border-slate-200/90 hover:border-rose-400 dark:border-rose-500/20 dark:hover:border-rose-400/60',
            'corner' => 'border-rose-500 dark:border-rose-400',
            'glow' => 'hover:shadow-[0_12px_32px_rgba(244,63,94,0.18)] dark:hover:shadow-[0_12px_36px_rgba(244,63,94,0.22)]',
            'icon_border' => 'border-slate-200/90 group-hover:border-rose-400 dark:border-rose-500/30 dark:group-hover:border-rose-400 dark:group-hover:shadow-[0_0_24px_rgba(244,63,94,0.4)]',
            'icon_bg' => 'bg-rose-50 text-rose-600 dark:bg-rose-950 dark:text-rose-400',
            'title_hover' => 'group-hover:text-rose-600 dark:group-hover:text-rose-300',
            'btn_bg' => 'bg-rose-500/10 hover:bg-rose-500 text-rose-600 hover:text-white border-rose-500/20 dark:bg-rose-500/15 dark:hover:bg-rose-500 dark:text-rose-400 dark:hover:text-black dark:border-rose-500/30 dark:group-hover:shadow-[0_0_12px_rgba(244,63,94,0.5)]',
            'badge' => 'text-rose-700 bg-rose-50 border-rose-200/80 dark:text-rose-400 dark:bg-rose-500/10 dark:border-rose-500/20',
        ],
        'series-tv' => [
            'card_border' => 'border-slate-200/90 hover:border-violet-400 dark:border-violet-500/20 dark:hover:border-violet-400/60',
            'corner' => 'border-violet-500 dark:border-violet-400',
            'glow' => 'hover:shadow-[0_12px_32px_rgba(139,92,246,0.18)] dark:hover:shadow-[0_12px_36px_rgba(139,92,246,0.22)]',
            'icon_border' => 'border-slate-200/90 group-hover:border-violet-400 dark:border-violet-500/30 dark:group-hover:border-violet-400 dark:group-hover:shadow-[0_0_24px_rgba(139,92,246,0.4)]',
            'icon_bg' => 'bg-violet-50 text-violet-600 dark:bg-violet-950 dark:text-violet-400',
            'title_hover' => 'group-hover:text-violet-600 dark:group-hover:text-violet-300',
            'btn_bg' => 'bg-violet-500/10 hover:bg-violet-500 text-violet-600 hover:text-white border-violet-500/20 dark:bg-violet-500/15 dark:hover:bg-violet-500 dark:text-violet-400 dark:hover:text-black dark:border-violet-500/30 dark:group-hover:shadow-[0_0_12px_rgba(139,92,246,0.5)]',
            'badge' => 'text-violet-700 bg-violet-50 border-violet-200/80 dark:text-violet-400 dark:bg-violet-500/10 dark:border-violet-500/20',
        ],
        default => [ // mac-apps & others
            'card_border' => 'border-slate-200/90 hover:border-sky-400 dark:border-sky-500/20 dark:hover:border-sky-400/60',
            'corner' => 'border-sky-500 dark:border-sky-400',
            'glow' => 'hover:shadow-[0_12px_32px_rgba(56,189,248,0.18)] dark:hover:shadow-[0_12px_36px_rgba(56,189,248,0.22)]',
            'icon_border' => 'border-slate-200/90 group-hover:border-sky-400 dark:border-sky-500/30 dark:group-hover:border-sky-400 dark:group-hover:shadow-[0_0_24px_rgba(56,189,248,0.4)]',
            'icon_bg' => 'bg-sky-50 text-sky-600 dark:bg-sky-950 dark:text-sky-400',
            'title_hover' => 'group-hover:text-sky-600 dark:group-hover:text-sky-300',
            'btn_bg' => 'bg-sky-500/10 hover:bg-sky-500 text-sky-600 hover:text-white border-sky-500/20 dark:bg-sky-500/15 dark:hover:bg-sky-500 dark:text-sky-400 dark:hover:text-black dark:border-sky-500/30 dark:group-hover:shadow-[0_0_12px_rgba(56,189,248,0.5)]',
            'badge' => 'text-sky-700 bg-sky-50 border-sky-200/80 dark:text-sky-400 dark:bg-sky-500/10 dark:border-sky-500/20',
        ],
    };
@endphp

<article class="group relative flex flex-col items-center justify-between text-center p-3 sm:p-3.5 rounded-2xl bg-white hover:bg-slate-50/80 dark:bg-[#0b1017]/95 dark:hover:bg-[#0f1722] border {{ $themeConfig['card_border'] }} shadow-[0_2px_12px_rgba(0,0,0,0.04),0_1px_3px_rgba(0,0,0,0.02)] dark:shadow-[0_8px_30px_rgba(0,0,0,0.7)] transition-all duration-300 hover:-translate-y-1.5 {{ $themeConfig['glow'] }} cursor-pointer h-full">
    <!-- Corner Tick Marks (Cyber HUD) -->
    <div class="absolute top-0 right-0 w-3 h-3 border-t-2 border-r-2 border-slate-300 dark:border-white/20 group-hover:{{ $themeConfig['corner'] }} transition-colors pointer-events-none rounded-tr-md"></div>
    <div class="absolute bottom-0 left-0 w-3 h-3 border-b-2 border-l-2 border-slate-300 dark:border-white/20 group-hover:{{ $themeConfig['corner'] }} transition-colors pointer-events-none rounded-bl-md"></div>

    <!-- Top Telemetry Micro-Pill -->
    <div class="w-full flex items-center justify-between gap-1 mb-2 text-[10px] font-mono text-slate-500 dark:text-[#6E7681]">
        <span class="inline-flex items-center gap-1 font-bold px-1.5 py-0.5 rounded border {{ $themeConfig['badge'] }}">
            <span class="w-1.5 h-1.5 rounded-full bg-current animate-pulse"></span>
            {{ $app->has_torrent ? 'TORRENT' : 'DIRECT' }}
        </span>
        <div class="flex items-center gap-1 max-w-[120px] truncate">
            @if($app->is_updated)
            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20" title="Actualizado">
                <span class="w-1 h-1 rounded-full bg-amber-500 animate-ping"></span>
                Actualizado
            </span>
            @elseif($app->version)
            <span class="text-slate-600 bg-slate-100 border border-slate-200/80 dark:text-[#8BA0B6] dark:bg-white/[0.04] dark:border-white/5 px-1.5 py-0.5 rounded truncate max-w-[85px]" title="{{ $app->version }}">
                {{ $app->version }}
            </span>
            @endif
        </div>
    </div>

    <!-- Center Content: Icon + App Name -->
    <div class="w-full flex flex-col items-center my-auto py-1">
        <!-- Big 3D Squircle Icon with Cyber Frame -->
        <a href="{{ route('app', $app->slug) }}" class="relative block my-1 group-hover:scale-105 transition-transform duration-300 shrink-0">
            <div class="w-[82px] h-[82px] sm:w-[88px] sm:h-[88px] rounded-[22px] p-1.5 bg-slate-50 border border-slate-200/80 shadow-sm dark:bg-[#05080c] dark:shadow-black/80 {{ $themeConfig['icon_border'] }} flex items-center justify-center transition-all overflow-hidden">
                <img width="84" height="84" src="{{ $app->icon_url }}" class="w-full h-full object-cover rounded-[18px]" alt="{{ $app->name }}" loading="lazy"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="w-full h-full {{ $themeConfig['icon_bg'] }} flex items-center justify-center font-mono font-black text-2xl rounded-[18px]" style="display: none;">
                    {{ strtoupper(substr($app->name, 0, 1)) }}
                </div>
            </div>
        </a>

        <!-- App Title -->
        <h3 class="mt-2 text-[14px] sm:text-[15px] font-bold text-slate-900 dark:text-white tracking-tight truncate w-full font-heading {{ $themeConfig['title_hover'] }} transition-colors" title="{{ $app->name }}">
            <a href="{{ route('app', $app->slug) }}" class="after:absolute after:inset-0 after:z-[1]">{{ $app->name }}</a>
        </h3>
    </div>

    <!-- Bottom Cyber HUD Action & Telemetry -->
    <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-white/[0.08] w-full flex items-center justify-between gap-1 relative z-[2]">
        <span class="text-[10.5px] font-mono text-slate-500 dark:text-[#7E8B99] truncate font-medium">
            {{ $app->size ?? 'Verified' }}
        </span>

        <a href="{{ route('download', $app->slug) }}" class="px-2.5 py-1 rounded {{ $themeConfig['btn_bg'] }} font-mono text-[11px] font-bold transition-all flex items-center gap-1">
            <span>GET</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
        </a>
    </div>
</article>