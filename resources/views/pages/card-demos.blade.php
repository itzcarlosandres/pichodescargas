<x-app-layout>
    @section('title', 'Showcase de 5 Diseños de Card Lista - PichoDescargas')
    @section('description', 'Demostración interactiva en vivo de 5 propuestas de diseño para las cards de aplicaciones.')

    <div class="container-app py-8" x-data="{
        activeTab: 'all',
        gridCols: '2',
        themeMode: 'dark',
        copiedSnippet: '',
        showCodeModal: false,
        modalTitle: '',
        modalCode: '',
        openCode(title, snippet) {
            this.modalTitle = title;
            this.modalCode = snippet;
            this.showCodeModal = true;
        },
        copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            this.copiedSnippet = text;
            setTimeout(() => { this.copiedSnippet = ''; }, 2500);
        }
    }">
        <!-- Header Banner & Showcase Title -->
        <div class="mb-8 p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-[#13161c] via-[#0e1117] to-[#090b0f] border border-white/10 relative overflow-hidden shadow-2xl">
            <div class="absolute -right-16 -top-16 w-80 h-80 rounded-full bg-primary/15 blur-3xl pointer-events-none"></div>
            <div class="absolute -left-16 -bottom-16 w-80 h-80 rounded-full bg-purple-500/10 blur-3xl pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-3">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-primary/15 text-primary border border-primary/30">
                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                        <span>Lab de Diseño UI • 6 Conceptos Exclusivos</span>
                    </div>

                    <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs text-[#A1A1A6] hover:text-white transition-colors bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-lg border border-white/10">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>Volver al Home Real</span>
                    </a>
                </div>

                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight text-white font-heading mb-2">
                    Diseños Premium para Cards de Aplicaciones
                </h1>
                <p class="text-sm sm:text-[15px] text-[#A1A1A6] max-w-2xl leading-relaxed">
                    Diseñados con precisión quirúrgica, tipografía de alta jerarquía y micro-interacciones. Prueba cada diseño interactuando con los botones, hover states y modales de código.
                </p>
            </div>

            <!-- Interactive Toolbar & Filter Tabs -->
            <div class="relative z-10 mt-6 pt-6 border-t border-white/10 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                <!-- Tab Selector -->
                <div class="flex flex-wrap items-center gap-1.5 p-1 bg-black/40 border border-white/10 rounded-xl backdrop-blur-md">
                    <button @click="activeTab = 'all'" :class="activeTab === 'all' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-[#A1A1A6] hover:text-white hover:bg-white/5'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                        Todos (1 a 6)
                    </button>
                    <button @click="activeTab = '6'" :class="activeTab === '6' ? 'bg-sky-500 text-black font-bold shadow-lg shadow-sky-500/30' : 'text-sky-400 hover:text-white hover:bg-sky-500/10'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border border-sky-500/30">
                        ★ 6. Cyber Cuadros (NUEVO)
                    </button>
                    <button @click="activeTab = '2'" :class="activeTab === '2' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-[#A1A1A6] hover:text-white hover:bg-white/5'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                        2. Cyber Terminal
                    </button>
                    <button @click="activeTab = '1'" :class="activeTab === '1' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-[#A1A1A6] hover:text-white hover:bg-white/5'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                        1. Cupertino Glass
                    </button>
                    <button @click="activeTab = '3'" :class="activeTab === '3' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-[#A1A1A6] hover:text-white hover:bg-white/5'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                        3. Editorial Studio
                    </button>
                    <button @click="activeTab = '4'" :class="activeTab === '4' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-[#A1A1A6] hover:text-white hover:bg-white/5'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                        4. Aurora Glow
                    </button>
                    <button @click="activeTab = '5'" :class="activeTab === '5' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-[#A1A1A6] hover:text-white hover:bg-white/5'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                        5. Bento Compact
                    </button>
                </div>

                <!-- Grid Columns Selector -->
                <div class="flex items-center gap-2 self-end md:self-auto">
                    <span class="text-xs text-[#8C847A] font-medium hidden sm:inline">Disposición:</span>
                    <div class="inline-flex p-1 bg-black/40 border border-white/10 rounded-xl">
                        <button @click="gridCols = '2'" :class="gridCols === '2' ? 'bg-white/20 text-white' : 'text-[#A1A1A6] hover:text-white'" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-all" title="2 Columnas (Igual que en el Home)">
                            2 Col (Home)
                        </button>
                        <button @click="gridCols = '1'" :class="gridCols === '1' ? 'bg-white/20 text-white' : 'text-[#A1A1A6] hover:text-white'" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-all" title="1 Columna (Lista extendida)">
                            1 Col (Ancha)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- DISEÑO 6: Cyber Terminal HUD en Cuadros (Apps Grid Vertical) - NUEVO     -->
        <!-- ========================================================================= -->
        <section x-show="activeTab === 'all' || activeTab === '6'" class="mb-14 p-6 sm:p-7 rounded-3xl bg-[#070b10] border-2 border-sky-500/30 shadow-[0_0_40px_rgba(56,189,248,0.12)] relative overflow-hidden" x-data="{ cuadrosCols: '6' }">
            <!-- Background ambient cyan grid glow -->
            <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full bg-sky-500/10 blur-3xl pointer-events-none"></div>
            <div class="absolute -left-20 -bottom-20 w-80 h-80 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>

            <div class="flex flex-wrap items-center justify-between gap-3 mb-6 pb-4 border-b border-sky-500/20 relative z-10">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-sky-500/20 border border-sky-400 text-sky-300 flex items-center justify-center font-bold text-sm font-mono shadow-[0_0_12px_rgba(56,189,248,0.4)]">06</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold text-white font-heading">Cyber Terminal HUD en Cuadros (Apps Grid)</h2>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-sky-400 text-black uppercase tracking-wider">Tu Petición</span>
                        </div>
                        <p class="text-xs text-[#8BA0B6] mt-0.5">Iconos 3D prominentes, título, descripción corta y telemetría táctica cyber en cuadrícula vertical tipo App Store.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Column view buttons for this section -->
                    <div class="inline-flex p-1 bg-black/60 border border-sky-500/30 rounded-xl">
                        <button @click="cuadrosCols = '6'" :class="cuadrosCols === '6' ? 'bg-sky-500 text-black font-bold' : 'text-sky-400 hover:text-white'" class="px-2.5 py-1 rounded-lg text-xs font-mono transition-all">
                            6 Columnas (Imagen)
                        </button>
                        <button @click="cuadrosCols = '4'" :class="cuadrosCols === '4' ? 'bg-sky-500 text-black font-bold' : 'text-sky-400 hover:text-white'" class="px-2.5 py-1 rounded-lg text-xs font-mono transition-all">
                            4 Columnas
                        </button>
                        <button @click="cuadrosCols = '3'" :class="cuadrosCols === '3' ? 'bg-sky-500 text-black font-bold' : 'text-sky-400 hover:text-white'" class="px-2.5 py-1 rounded-lg text-xs font-mono transition-all">
                            3 Columnas
                        </button>
                    </div>

                    <button @click="openCode('Diseño 6: Cyber Terminal HUD en Cuadros', $refs.codeSnippet6.innerText)" class="text-xs font-medium text-sky-400 hover:text-sky-300 flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-sky-500/10 border border-sky-500/20 hover:bg-sky-500/20 transition-all font-mono">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        <span>Ver Código Blade</span>
                    </button>
                </div>
            </div>

            <!-- The Grid of Cuadros -->
            <div :class="{
                'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3.5 sm:gap-4': cuadrosCols === '6',
                'grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-4': cuadrosCols === '4',
                'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5': cuadrosCols === '3'
            }" class="relative z-10">
                @foreach($applications->take(6) as $app)
                <article class="group relative flex flex-col items-center text-center p-4 rounded-2xl bg-[#0b1017]/95 hover:bg-[#0f1722] border border-sky-500/20 hover:border-sky-400/60 shadow-[0_8px_30px_rgba(0,0,0,0.7)] transition-all duration-300 hover:-translate-y-1.5 hover:shadow-[0_12px_36px_rgba(56,189,248,0.2)] cursor-pointer">
                    <!-- Corner Tick Marks (Cyber HUD) -->
                    <div class="absolute top-0 right-0 w-3 h-3 border-t-2 border-r-2 border-sky-400/40 group-hover:border-sky-400 transition-colors pointer-events-none rounded-tr-md"></div>
                    <div class="absolute bottom-0 left-0 w-3 h-3 border-b-2 border-l-2 border-sky-400/40 group-hover:border-sky-400 transition-colors pointer-events-none rounded-bl-md"></div>

                    <!-- Top Telemetry Micro-Pill -->
                    <div class="w-full flex items-center justify-between gap-1 mb-3 text-[10px] font-mono text-[#6E7681]">
                        <span class="inline-flex items-center gap-1 text-emerald-400 font-bold bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            OK
                        </span>
                        @if($app->version)
                        <span class="text-[#8BA0B6] bg-white/[0.04] px-1.5 py-0.5 rounded border border-white/5 truncate max-w-[80px]">
                            v{{ $app->version }}
                        </span>
                        @endif
                    </div>

                    <!-- Big 3D Squircle Icon with Cyber Frame (Idéntico a la imagen) -->
                    <a href="{{ route('app', $app->slug) }}" class="relative block my-1 group-hover:scale-105 transition-transform duration-300">
                        <div class="w-[84px] h-[84px] sm:w-[92px] sm:h-[92px] rounded-[22px] p-1.5 bg-[#05080c] border border-sky-500/30 group-hover:border-sky-400 flex items-center justify-center shadow-lg shadow-black/80 group-hover:shadow-[0_0_24px_rgba(56,189,248,0.4)] transition-all overflow-hidden">
                            <img width="84" height="84" src="{{ $app->icon_url }}" class="w-full h-full object-cover rounded-[18px]" alt="{{ $app->name }}" loading="lazy"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-full h-full bg-sky-950 flex items-center justify-center text-sky-400 font-mono font-black text-2xl rounded-[18px]" style="display: none;">
                                {{ strtoupper(substr($app->name, 0, 1)) }}
                            </div>
                        </div>
                    </a>

                    <!-- App Title (En negrita como la imagen) -->
                    <h3 class="mt-2 text-[14.5px] sm:text-[15.5px] font-bold text-white tracking-tight truncate w-full font-heading group-hover:text-sky-300 transition-colors">
                        <a href="{{ route('app', $app->slug) }}" class="after:absolute after:inset-0 after:z-[1]">{{ $app->name }}</a>
                    </h3>

                    <!-- Bottom Cyber HUD Action & Telemetry -->
                    <div class="mt-4 pt-3 border-t border-sky-500/15 w-full flex items-center justify-between gap-1 relative z-[2]">
                        <span class="text-[10px] font-mono text-[#6E7681] truncate">
                            {{ $app->size ?? 'Universal' }}
                        </span>

                        <a href="{{ route('download', $app->slug) }}" class="px-2.5 py-1 rounded bg-sky-500/15 hover:bg-sky-500 text-sky-400 hover:text-black border border-sky-500/30 font-mono text-[11px] font-bold transition-all flex items-center gap-1 group-hover:shadow-[0_0_12px_rgba(56,189,248,0.5)]">
                            <span>GET</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                        </a>
                    </div>
                </article>
                @endforeach
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- DISEÑO 1: Cupertino Refined (Evolución Apple Sequoia)                    -->
        <!-- ========================================================================= -->
        <section x-show="activeTab === 'all' || activeTab === '1'" class="mb-14">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-2 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-[#0071E3]/20 border border-[#0071E3]/40 text-[#0071E3] flex items-center justify-center font-bold text-xs">01</span>
                    <div>
                        <h2 class="text-lg font-bold text-white font-heading">Cupertino Refined (Evolución Apple Sequoia)</h2>
                        <p class="text-xs text-[#A1A1A6]">Acrílico oscuro translúcido, bisel interior specular, micro-píldoras SF Pro y botón de descarga integrado.</p>
                    </div>
                </div>
                <button @click="openCode('Diseño 1: Cupertino Refined', $refs.codeSnippet1.innerText)" class="text-xs font-medium text-primary hover:text-primary-hover flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary/10 border border-primary/20 hover:bg-primary/20 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    <span>Ver Código Blade</span>
                </button>
            </div>

            <div :class="gridCols === '2' ? 'grid md:grid-cols-2 gap-4' : 'grid grid-cols-1 gap-4'">
                @foreach($applications->take(4) as $app)
                <article class="group relative flex items-center gap-4 p-3.5 sm:p-4 rounded-[20px] bg-[#161618]/85 hover:bg-[#1c1c1f] border border-white/[0.08] hover:border-white/20 shadow-[0_10px_30px_rgba(0,0,0,0.5),inset_0_1px_0_0_rgba(255,255,255,0.12)] transition-all duration-300 hover:-translate-y-0.5">
                    <!-- Squircle Icon with Continuous Curvature -->
                    <a href="{{ route('app', $app->slug) }}" class="relative shrink-0 block">
                        <img width="80" height="80" src="{{ $app->icon_url }}" class="w-[72px] h-[72px] sm:w-[80px] sm:h-[80px] rounded-[18px] object-cover bg-[#0d0d0f] shadow-lg shadow-black/60 group-hover:scale-105 transition-transform duration-300" alt="{{ $app->name }}" loading="lazy"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-[72px] h-[72px] sm:w-[80px] sm:h-[80px] rounded-[18px] bg-[#0071E3]/20 border border-[#0071E3]/30 items-center justify-center text-[#0071E3] font-bold text-2xl" style="display: none;">
                            {{ strtoupper(substr($app->name, 0, 1)) }}
                        </div>
                    </a>

                    <!-- Main Info -->
                    <div class="flex-1 min-w-0 pr-1">
                        <div class="flex items-center gap-2 mb-1.5">
                            <h3 class="text-[15.5px] sm:text-[16.5px] font-bold text-white tracking-tight truncate font-heading">
                                <a href="{{ route('app', $app->slug) }}" class="hover:text-[#2997FF] transition-colors after:absolute after:inset-0 after:z-[1]">{{ $app->name }}</a>
                            </h3>
                            @if($app->is_updated)
                            <span class="relative flex h-3.5 w-3.5 shrink-0 z-[2]" title="Actualizado recientemente">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#0071E3] opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-gradient-to-tr from-[#0071E3] to-[#2997FF] shadow-sm shadow-[#0071E3]"></span>
                            </span>
                            @endif
                        </div>

                        <!-- Meta Row 1: Badges -->
                        <div class="flex flex-wrap items-center gap-1.5 mb-2 relative z-[2]">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-[6px] text-[10.5px] font-semibold bg-[#0071E3]/15 text-[#4D9BE9] border border-[#0071E3]/30">
                                {{ $app->category?->name ?? 'Mac Apps' }}
                            </span>
                            @if($app->has_torrent)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-[6px] text-[10px] font-mono font-bold bg-[#30D158]/15 text-[#30D158] border border-[#30D158]/30">
                                TORRENT
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-[6px] text-[10px] font-mono font-bold bg-sky-500/15 text-sky-400 border border-sky-500/30">
                                DDL
                            </span>
                            @endif
                            @if($app->version)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-[6px] text-[10.5px] font-mono text-[#D1D1D6] bg-white/[0.06] border border-white/10">
                                v{{ $app->version }}
                            </span>
                            @endif
                        </div>

                        <!-- Meta Row 2: Secondary Info -->
                        <div class="flex items-center gap-3 text-[11px] text-[#86868B]">
                            <span class="flex items-center gap-1">
                                <svg class="w-3 h-3 text-[#86868B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span>{{ $app->updated_at?->format('M j, Y') ?? 'Recently' }}</span>
                            </span>
                            @if($app->size)
                            <span class="hidden sm:inline-block">•</span>
                            <span class="hidden sm:inline-block font-mono">{{ $app->size }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Right Action: Download Icon Capsule -->
                    <div class="shrink-0 relative z-[2]">
                        <a href="{{ route('download', $app->slug) }}" class="w-10 h-10 rounded-xl bg-white/[0.05] hover:bg-[#0071E3] text-[#A1A1A6] hover:text-white border border-white/10 hover:border-[#0071E3] flex items-center justify-center transition-all duration-200 group-hover:scale-105 shadow-sm" title="Descargar {{ $app->name }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 21h16"/></svg>
                        </a>
                    </div>
                </article>
                @endforeach
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- DISEÑO 2: Cyber Terminal HUD (Ingeniería & Software Técnico)              -->
        <!-- ========================================================================= -->
        <section x-show="activeTab === 'all' || activeTab === '2'" class="mb-14">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-2 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center font-bold text-xs font-mono">02</span>
                    <div>
                        <h2 class="text-lg font-bold text-white font-heading">Cyber Terminal HUD (Ingeniería de Software)</h2>
                        <p class="text-xs text-[#A1A1A6]">Estética cockpit para desarrolladores: tipografía monoespaciada, corchetes tácticos y beacon de telemetría.</p>
                    </div>
                </div>
                <button @click="openCode('Diseño 2: Cyber Terminal HUD', $refs.codeSnippet2.innerText)" class="text-xs font-medium text-emerald-400 hover:text-emerald-300 flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 hover:bg-emerald-500/20 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    <span>Ver Código Blade</span>
                </button>
            </div>

            <div :class="gridCols === '2' ? 'grid md:grid-cols-2 gap-4' : 'grid grid-cols-1 gap-4'">
                @foreach($applications->take(4) as $app)
                <article class="group relative flex items-center gap-4 p-4 rounded-xl bg-[#0b1017] hover:bg-[#0f1722] border border-sky-500/20 hover:border-sky-400/50 shadow-[0_4px_24px_rgba(0,0,0,0.6)] transition-all duration-200">
                    <!-- Corner Tick Mark Accent -->
                    <div class="absolute top-0 right-0 w-2.5 h-2.5 border-t-2 border-r-2 border-sky-400/40 group-hover:border-sky-400 transition-colors pointer-events-none rounded-tr-sm"></div>
                    <div class="absolute bottom-0 left-0 w-2.5 h-2.5 border-b-2 border-l-2 border-sky-400/40 group-hover:border-sky-400 transition-colors pointer-events-none rounded-bl-sm"></div>

                    <!-- High-Tech Framed Icon -->
                    <a href="{{ route('app', $app->slug) }}" class="relative shrink-0 block">
                        <div class="w-[70px] h-[70px] rounded-lg p-1 bg-[#05080c] border border-sky-500/30 group-hover:border-sky-400 transition-all duration-300 flex items-center justify-center overflow-hidden">
                            <img width="62" height="62" src="{{ $app->icon_url }}" class="w-full h-full object-cover rounded-md" alt="{{ $app->name }}" loading="lazy"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-full h-full bg-sky-950 flex items-center justify-center text-sky-400 font-mono font-bold text-xl" style="display: none;">
                                {{ strtoupper(substr($app->name, 0, 1)) }}
                            </div>
                        </div>
                    </a>

                    <!-- Telemetry Data Body -->
                    <div class="flex-1 min-w-0 font-sans">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-[15px] font-bold text-white tracking-tight truncate font-heading group-hover:text-sky-300 transition-colors">
                                <a href="{{ route('app', $app->slug) }}" class="after:absolute after:inset-0 after:z-[1]">{{ $app->name }}</a>
                            </h3>
                            <span class="inline-flex items-center gap-1 text-[10px] font-mono text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/25 shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                VERIFIED
                            </span>
                        </div>

                        <!-- Technical Tags -->
                        <div class="flex flex-wrap items-center gap-2 mb-2 font-mono text-[11px] text-[#8BA0B6] relative z-[2]">
                            <span class="text-sky-400">[{{ $app->category?->name ?? 'APP' }}]</span>
                            @if($app->version)
                            <span class="text-[#D1D9E0] bg-white/[0.04] px-1 rounded border border-white/5">v{{ $app->version }}</span>
                            @endif
                            @if($app->size)
                            <span class="text-amber-400/90">{{ $app->size }}</span>
                            @endif
                        </div>

                        <!-- Telemetry Stats -->
                        <div class="flex items-center gap-3 text-[11px] font-mono text-[#6E7681]">
                            <span>ARCH: <strong class="text-[#8B949E]">UNIVERSAL</strong></span>
                            <span>•</span>
                            <span>PROTO: <strong class="{{ $app->has_torrent ? 'text-emerald-400' : 'text-sky-400' }}">{{ $app->has_torrent ? 'TORRENT' : 'HTTPS' }}</strong></span>
                        </div>
                    </div>

                    <!-- Technical Action Button -->
                    <div class="shrink-0 relative z-[2]">
                        <a href="{{ route('download', $app->slug) }}" class="px-3 py-1.5 rounded bg-sky-500/15 hover:bg-sky-500 text-sky-400 hover:text-black border border-sky-500/30 font-mono text-xs font-bold transition-all flex items-center gap-1.5 group-hover:shadow-[0_0_15px_rgba(56,189,248,0.4)]">
                            <span>GET</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                        </a>
                    </div>
                </article>
                @endforeach
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- DISEÑO 3: Editorial Studio / Swiss Clean (Revista & Minimalismo)         -->
        <!-- ========================================================================= -->
        <section x-show="activeTab === 'all' || activeTab === '3'" class="mb-14">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-2 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-orange-500/20 border border-orange-500/40 text-orange-400 flex items-center justify-center font-bold text-xs">03</span>
                    <div>
                        <h2 class="text-lg font-bold text-white font-heading">Editorial Studio / Swiss Clean</h2>
                        <p class="text-xs text-[#A1A1A6]">Tipografía de alto contraste Bricolage Grotesque, numeración editorial (#01) y separación geométrica limpia.</p>
                    </div>
                </div>
                <button @click="openCode('Diseño 3: Editorial Studio', $refs.codeSnippet3.innerText)" class="text-xs font-medium text-orange-400 hover:text-orange-300 flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-500/10 border border-orange-500/20 hover:bg-orange-500/20 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    <span>Ver Código Blade</span>
                </button>
            </div>

            <div :class="gridCols === '2' ? 'grid md:grid-cols-2 gap-4' : 'grid grid-cols-1 gap-4'">
                @foreach($applications->take(4) as $app)
                <article class="group relative flex items-center gap-4 p-4 rounded-2xl bg-[#121214] hover:bg-[#18181b] border border-white/10 hover:border-orange-500/40 transition-all duration-300 shadow-lg">
                    <!-- Editorial Index Number -->
                    <span class="text-xs font-mono font-bold text-white/30 group-hover:text-orange-400/80 transition-colors shrink-0">
                        0{{ $loop->iteration }}
                    </span>

                    <!-- Icon -->
                    <a href="{{ route('app', $app->slug) }}" class="shrink-0 relative block">
                        <img width="72" height="72" src="{{ $app->icon_url }}" class="w-[64px] h-[64px] sm:w-[72px] sm:h-[72px] rounded-xl object-cover bg-white/5 border border-white/10" alt="{{ $app->name }}" loading="lazy"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-[64px] h-[64px] sm:w-[72px] sm:h-[72px] rounded-xl bg-orange-500/20 text-orange-400 font-bold text-xl items-center justify-center" style="display: none;">
                            {{ strtoupper(substr($app->name, 0, 1)) }}
                        </div>
                    </a>

                    <!-- Title & Typography Focus -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <h3 class="text-[16px] font-black tracking-tight text-white font-heading truncate">
                                <a href="{{ route('app', $app->slug) }}" class="hover:text-orange-400 transition-colors after:absolute after:inset-0 after:z-[1]">{{ $app->name }}</a>
                            </h3>
                            <span class="text-[10px] font-mono text-[#7E776F] uppercase tracking-wider shrink-0">
                                {{ $app->category?->name ?? 'SYSTEM' }}
                            </span>
                        </div>

                        <!-- Editorial Meta Divider -->
                        <div class="flex items-center gap-2 text-xs text-[#A1A1A6] mb-1.5 relative z-[2]">
                            @if($app->version)
                            <span class="font-mono text-white/80 font-medium">Build {{ $app->version }}</span>
                            <span>/</span>
                            @endif
                            <span>{{ $app->updated_at?->format('d M Y') }}</span>
                            @if($app->size)
                            <span>/</span>
                            <span class="text-orange-300/80 font-mono">{{ $app->size }}</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 relative z-[2]">
                            <span class="text-[10px] font-medium text-[#7E776F] uppercase tracking-widest">Descarga Directa + Torrent</span>
                        </div>
                    </div>

                    <!-- Minimalist Arrow Action -->
                    <div class="shrink-0 relative z-[2] pr-1">
                        <span class="w-8 h-8 rounded-full border border-white/20 group-hover:border-orange-500 group-hover:bg-orange-500 text-white/60 group-hover:text-black flex items-center justify-center transition-all duration-200 group-hover:translate-x-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/></svg>
                        </span>
                    </div>
                </article>
                @endforeach
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- DISEÑO 4: Aurora Glow / Atmospheric Depth (Neón & Profundidad)            -->
        <!-- ========================================================================= -->
        <section x-show="activeTab === 'all' || activeTab === '4'" class="mb-14">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-2 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-purple-500/20 border border-purple-500/40 text-purple-400 flex items-center justify-center font-bold text-xs">04</span>
                    <div>
                        <h2 class="text-lg font-bold text-white font-heading">Aurora Glow / Atmospheric Depth</h2>
                        <p class="text-xs text-[#A1A1A6]">Halo de luz ambiental que reacciona al hover, gradientes multitonos y botón de acción con brillo de neón.</p>
                    </div>
                </div>
                <button @click="openCode('Diseño 4: Aurora Glow', $refs.codeSnippet4.innerText)" class="text-xs font-medium text-purple-400 hover:text-purple-300 flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-500/10 border border-purple-500/20 hover:bg-purple-500/20 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    <span>Ver Código Blade</span>
                </button>
            </div>

            <div :class="gridCols === '2' ? 'grid md:grid-cols-2 gap-4' : 'grid grid-cols-1 gap-4'">
                @foreach($applications->take(4) as $app)
                <article class="group relative flex items-center gap-4 p-4 rounded-2xl bg-[#0f111a]/90 hover:bg-[#151824] border border-white/10 hover:border-purple-500/40 shadow-[0_8px_32px_rgba(0,0,0,0.5)] overflow-hidden transition-all duration-300 hover:-translate-y-1">
                    <!-- Dynamic Ambient Glow on Hover -->
                    <div class="absolute -right-12 -top-12 w-48 h-48 rounded-full bg-gradient-to-br from-purple-500/25 via-blue-500/15 to-transparent blur-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

                    <!-- Icon with Ambient Shadow -->
                    <a href="{{ route('app', $app->slug) }}" class="relative shrink-0 block">
                        <div class="w-[74px] h-[74px] rounded-2xl p-0.5 bg-gradient-to-br from-white/20 via-purple-500/20 to-transparent shadow-lg group-hover:shadow-purple-500/25 transition-all">
                            <img width="70" height="70" src="{{ $app->icon_url }}" class="w-full h-full object-cover rounded-[14px]" alt="{{ $app->name }}" loading="lazy"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-full h-full bg-purple-900/40 flex items-center justify-center text-purple-300 font-bold text-xl rounded-[14px]" style="display: none;">
                                {{ strtoupper(substr($app->name, 0, 1)) }}
                            </div>
                        </div>
                    </a>

                    <!-- Info -->
                    <div class="flex-1 min-w-0 relative z-10">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-[16px] font-bold text-white tracking-tight truncate font-heading group-hover:text-purple-300 transition-colors">
                                <a href="{{ route('app', $app->slug) }}" class="after:absolute after:inset-0 after:z-[1]">{{ $app->name }}</a>
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold tracking-wider uppercase bg-gradient-to-r from-purple-500/20 to-pink-500/20 text-purple-300 border border-purple-500/30">
                                PRO
                            </span>
                        </div>

                        <!-- Gradient Tags -->
                        <div class="flex flex-wrap items-center gap-1.5 mb-2 relative z-[2]">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-white/5 text-[#E2E8F0] border border-white/10">
                                {{ $app->category?->name ?? 'Mac Apps' }}
                            </span>
                            @if($app->version)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-mono text-purple-200 bg-purple-500/10 border border-purple-500/20">
                                {{ $app->version }}
                            </span>
                            @endif
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-mono text-emerald-300 bg-emerald-500/10 border border-emerald-500/20">
                                DUAL-DL
                            </span>
                        </div>

                        <!-- Date & Downloads -->
                        <div class="flex items-center gap-3 text-xs text-[#94A3B8]">
                            <span>{{ $app->updated_at?->diffForHumans() }}</span>
                            <span>•</span>
                            <span class="flex items-center gap-1 text-purple-300">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span>{{ $app->downloads }} downloads</span>
                            </span>
                        </div>
                    </div>

                    <!-- Radiant Download Button -->
                    <div class="shrink-0 relative z-10">
                        <a href="{{ route('download', $app->slug) }}" class="w-10 h-10 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-pink-500 text-white flex items-center justify-center shadow-lg shadow-purple-600/30 group-hover:scale-110 transition-all duration-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        </a>
                    </div>
                </article>
                @endforeach
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- DISEÑO 5: Bento Compact Utility (Máxima Densidad & Acciones Rápidas)      -->
        <!-- ========================================================================= -->
        <section x-show="activeTab === 'all' || activeTab === '5'" class="mb-14">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-2 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-teal-500/20 border border-teal-500/40 text-teal-400 flex items-center justify-center font-bold text-xs">05</span>
                    <div>
                        <h2 class="text-lg font-bold text-white font-heading">Bento Compact Utility (Máxima Densidad)</h2>
                        <p class="text-xs text-[#A1A1A6]">Diseño optimizado para power users: botones directos divididos (DDL + Torrent) y badge de Gatekeeper.</p>
                    </div>
                </div>
                <button @click="openCode('Diseño 5: Bento Compact Utility', $refs.codeSnippet5.innerText)" class="text-xs font-medium text-teal-400 hover:text-teal-300 flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-teal-500/10 border border-teal-500/20 hover:bg-teal-500/20 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    <span>Ver Código Blade</span>
                </button>
            </div>

            <div :class="gridCols === '2' ? 'grid md:grid-cols-2 gap-3.5' : 'grid grid-cols-1 gap-3.5'">
                @foreach($applications->take(4) as $app)
                <article class="group relative flex items-center justify-between gap-3 p-3.5 rounded-xl bg-[#14171f] hover:bg-[#181d28] border border-white/10 hover:border-teal-500/30 transition-all duration-200">
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- Compact 58px Icon -->
                        <a href="{{ route('app', $app->slug) }}" class="shrink-0 relative block">
                            <img width="58" height="58" src="{{ $app->icon_url }}" class="w-[54px] h-[54px] rounded-xl object-cover bg-black/40 border border-white/10 group-hover:scale-105 transition-transform" alt="{{ $app->name }}" loading="lazy"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-[54px] h-[54px] rounded-xl bg-teal-900/40 text-teal-300 font-bold text-lg items-center justify-center" style="display: none;">
                                {{ strtoupper(substr($app->name, 0, 1)) }}
                            </div>
                        </a>

                        <!-- App Meta -->
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <h3 class="text-sm font-bold text-white tracking-tight truncate font-heading group-hover:text-teal-300 transition-colors">
                                    <a href="{{ route('app', $app->slug) }}" class="after:absolute after:inset-0 after:z-[1]">{{ $app->name }}</a>
                                </h3>
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-mono bg-teal-500/15 text-teal-400 border border-teal-500/30">
                                    {{ $app->size ?? '50MB' }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2 text-xs text-[#8C847A] mt-0.5 relative z-[2]">
                                <span class="text-white/80 font-mono text-[11px]">v{{ $app->version ?? '1.0' }}</span>
                                <span>•</span>
                                <span class="truncate">{{ $app->category?->name ?? 'Mac App' }}</span>
                                <span>•</span>
                                <span class="text-emerald-400 font-semibold text-[10.5px]">✓ Limpio</span>
                            </div>
                        </div>
                    </div>

                    <!-- Split Action Button Rail -->
                    <div class="flex items-center gap-1.5 shrink-0 relative z-[2]">
                        <a href="{{ route('download', $app->slug) }}" class="px-2.5 py-1 rounded-lg bg-teal-500/15 hover:bg-teal-500 text-teal-300 hover:text-black border border-teal-500/30 text-xs font-semibold transition-colors flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                            <span>DDL</span>
                        </a>
                        @if($app->has_torrent)
                        <a href="{{ route('download', $app->slug) }}#torrent" class="px-2.5 py-1 rounded-lg bg-white/5 hover:bg-[#30D158] text-[#30D158] hover:text-black border border-white/10 text-xs font-semibold transition-colors flex items-center gap-1">
                            <span>TOR</span>
                        </a>
                        @endif
                    </div>
                </article>
                @endforeach
            </div>
        </section>

        <!-- Hidden Code Snippets for Copying -->
        <div class="hidden">
            <pre x-ref="codeSnippet1">&lt;!-- DISEÑO 1: Cupertino Refined (Evolución Apple Sequoia) --&gt;
&lt;article class="group relative flex items-center gap-4 p-4 rounded-[20px] bg-[#161618]/85 hover:bg-[#1c1c1f] border border-white/[0.08] hover:border-white/20 shadow-[0_10px_30px_rgba(0,0,0,0.5),inset_0_1px_0_0_rgba(255,255,255,0.12)] transition-all duration-300 hover:-translate-y-0.5"&gt;
  &lt;a href="&#123;&#123; route('app', $app->slug) &#125;&#125;" class="relative shrink-0 block"&gt;
    &lt;img width="80" height="80" src="&#123;&#123; $app->icon_url &#125;&#125;" class="w-[72px] h-[72px] sm:w-[80px] sm:h-[80px] rounded-[18px] object-cover bg-[#0d0d0f] shadow-lg shadow-black/60 group-hover:scale-105 transition-transform duration-300" alt="&#123;&#123; $app->name &#125;&#125;" loading="lazy"&gt;
  &lt;/a&gt;
  &lt;div class="flex-1 min-w-0 pr-1"&gt;
    &lt;h3 class="text-[16px] font-bold text-white tracking-tight truncate font-heading"&gt;
      &lt;a href="&#123;&#123; route('app', $app->slug) &#125;&#125;" class="hover:text-[#2997FF] transition-colors after:absolute after:inset-0 after:z-[1]"&gt;&#123;&#123; $app->name &#125;&#125;&lt;/a&gt;
    &lt;/h3&gt;
    &lt;div class="flex flex-wrap items-center gap-1.5 mb-2 relative z-[2]"&gt;
      &lt;span class="inline-flex items-center px-2 py-0.5 rounded-[6px] text-[10.5px] font-semibold bg-[#0071E3]/15 text-[#4D9BE9] border border-[#0071E3]/30"&gt;&#123;&#123; $app->category?-&gt;name &#125;&#125;&lt;/span&gt;
      &lt;span class="inline-flex items-center px-2 py-0.5 rounded-[6px] text-[10.5px] font-mono text-[#D1D1D6] bg-white/[0.06] border border-white/10"&gt;v&#123;&#123; $app->version &#125;&#125;&lt;/span&gt;
    &lt;/div&gt;
    &lt;div class="flex items-center gap-3 text-[11px] text-[#86868B]"&gt;
      &lt;span&gt;&#123;&#123; $app->updated_at?-&gt;format('M j, Y') &#125;&#125;&lt;/span&gt;
    &lt;/div&gt;
  &lt;/div&gt;
  &lt;div class="shrink-0 relative z-[2]"&gt;
    &lt;a href="&#123;&#123; route('download', $app->slug) &#125;&#125;" class="w-10 h-10 rounded-xl bg-white/[0.05] hover:bg-[#0071E3] text-[#A1A1A6] hover:text-white border border-white/10 hover:border-[#0071E3] flex items-center justify-center transition-all"&gt;
      &lt;svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"&gt;&lt;path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 21h16"/&gt;&lt;/svg&gt;
    &lt;/a&gt;
  &lt;/div&gt;
&lt;/article&gt;</pre>

            <pre x-ref="codeSnippet2">&lt;!-- DISEÑO 2: Cyber Terminal HUD --&gt;
&lt;article class="group relative flex items-center gap-4 p-4 rounded-xl bg-[#0b1017] hover:bg-[#0f1722] border border-sky-500/20 hover:border-sky-400/50 shadow-[0_4px_24px_rgba(0,0,0,0.6)] transition-all"&gt;
  &lt;div class="w-[70px] h-[70px] rounded-lg p-1 bg-[#05080c] border border-sky-500/30 flex items-center justify-center overflow-hidden shrink-0"&gt;
    &lt;img width="62" height="62" src="&#123;&#123; $app->icon_url &#125;&#125;" class="w-full h-full object-cover rounded-md" alt="&#123;&#123; $app->name &#125;&#125;"&gt;
  &lt;/div&gt;
  &lt;div class="flex-1 min-w-0 font-sans"&gt;
    &lt;h3 class="text-[15px] font-bold text-white tracking-tight truncate font-heading group-hover:text-sky-300"&gt;
      &lt;a href="&#123;&#123; route('app', $app->slug) &#125;&#125;" class="after:absolute after:inset-0 after:z-[1]"&gt;&#123;&#123; $app->name &#125;&#125;&lt;/a&gt;
    &lt;/h3&gt;
    &lt;div class="flex flex-wrap items-center gap-2 mb-2 font-mono text-[11px] text-[#8BA0B6] relative z-[2]"&gt;
      &lt;span class="text-sky-400"&gt;[&#123;&#123; $app->category?-&gt;name &#125;&#125;]&lt;/span&gt;
      &lt;span class="text-[#D1D9E0]"&gt;v&#123;&#123; $app->version &#125;&#125;&lt;/span&gt;
    &lt;/div&gt;
  &lt;/div&gt;
  &lt;div class="shrink-0 relative z-[2]"&gt;
    &lt;a href="&#123;&#123; route('download', $app->slug) &#125;&#125;" class="px-3 py-1.5 rounded bg-sky-500/15 hover:bg-sky-500 text-sky-400 hover:text-black border border-sky-500/30 font-mono text-xs font-bold"&gt;GET&lt;/a&gt;
  &lt;/div&gt;
&lt;/article&gt;</pre>

            <pre x-ref="codeSnippet3">&lt;!-- DISEÑO 3: Editorial Studio --&gt;
&lt;article class="group relative flex items-center gap-4 p-4 rounded-2xl bg-[#121214] hover:bg-[#18181b] border border-white/10 hover:border-orange-500/40 transition-all shadow-lg"&gt;
  &lt;img width="72" height="72" src="&#123;&#123; $app->icon_url &#125;&#125;" class="w-[64px] h-[64px] rounded-xl object-cover bg-white/5 border border-white/10 shrink-0" alt="&#123;&#123; $app->name &#125;&#125;"&gt;
  &lt;div class="flex-1 min-w-0"&gt;
    &lt;h3 class="text-[16px] font-black tracking-tight text-white font-heading truncate"&gt;
      &lt;a href="&#123;&#123; route('app', $app->slug) &#125;&#125;" class="hover:text-orange-400 transition-colors after:absolute after:inset-0 after:z-[1]"&gt;&#123;&#123; $app->name &#125;&#125;&lt;/a&gt;
    &lt;/h3&gt;
    &lt;div class="flex items-center gap-2 text-xs text-[#A1A1A6] mb-1.5 relative z-[2]"&gt;
      &lt;span class="font-mono text-white/80"&gt;Build &#123;&#123; $app->version &#125;&#125;&lt;/span&gt;
      &lt;span&gt;/&lt;/span&gt;
      &lt;span&gt;&#123;&#123; $app->updated_at?-&gt;format('d M Y') &#125;&#125;&lt;/span&gt;
    &lt;/div&gt;
  &lt;/div&gt;
  &lt;div class="shrink-0 relative z-[2]"&gt;
    &lt;span class="w-8 h-8 rounded-full border border-white/20 group-hover:border-orange-500 group-hover:bg-orange-500 text-white/60 group-hover:text-black flex items-center justify-center transition-all group-hover:translate-x-1"&gt;
      &lt;svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"&gt;&lt;path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/&gt;&lt;/svg&gt;
    &lt;/span&gt;
  &lt;/div&gt;
&lt;/article&gt;</pre>

            <pre x-ref="codeSnippet4">&lt;!-- DISEÑO 4: Aurora Glow --&gt;
&lt;article class="group relative flex items-center gap-4 p-4 rounded-2xl bg-[#0f111a]/90 hover:bg-[#151824] border border-white/10 hover:border-purple-500/40 shadow-[0_8px_32px_rgba(0,0,0,0.5)] overflow-hidden transition-all duration-300 hover:-translate-y-1"&gt;
  &lt;div class="absolute -right-12 -top-12 w-48 h-48 rounded-full bg-gradient-to-br from-purple-500/25 via-blue-500/15 to-transparent blur-3xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"&gt;&lt;/div&gt;
  &lt;img width="70" height="70" src="&#123;&#123; $app->icon_url &#125;&#125;" class="w-[70px] h-[70px] rounded-[14px] object-cover shrink-0" alt="&#123;&#123; $app->name &#125;&#125;"&gt;
  &lt;div class="flex-1 min-w-0 relative z-10"&gt;
    &lt;h3 class="text-[16px] font-bold text-white tracking-tight truncate font-heading group-hover:text-purple-300"&gt;
      &lt;a href="&#123;&#123; route('app', $app->slug) &#125;&#125;" class="after:absolute after:inset-0 after:z-[1]"&gt;&#123;&#123; $app->name &#125;&#125;&lt;/a&gt;
    &lt;/h3&gt;
    &lt;div class="flex flex-wrap items-center gap-1.5 mb-2 relative z-[2]"&gt;
      &lt;span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-white/5 text-[#E2E8F0] border border-white/10"&gt;&#123;&#123; $app->category?-&gt;name &#125;&#125;&lt;/span&gt;
      &lt;span class="px-2 py-0.5 rounded-md text-[10px] font-mono text-purple-200 bg-purple-500/10 border border-purple-500/20"&gt;&#123;&#123; $app->version &#125;&#125;&lt;/span&gt;
    &lt;/div&gt;
  &lt;/div&gt;
  &lt;div class="shrink-0 relative z-10"&gt;
    &lt;a href="&#123;&#123; route('download', $app->slug) &#125;&#125;" class="w-10 h-10 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-pink-500 text-white flex items-center justify-center shadow-lg shadow-purple-600/30"&gt;
      &lt;svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"&gt;&lt;path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/&gt;&lt;/svg&gt;
    &lt;/a&gt;
  &lt;/div&gt;
&lt;/article&gt;</pre>

            <pre x-ref="codeSnippet5">&lt;!-- DISEÑO 5: Bento Compact Utility --&gt;
&lt;article class="group relative flex items-center justify-between gap-3 p-3.5 rounded-xl bg-[#14171f] hover:bg-[#181d28] border border-white/10 hover:border-teal-500/30 transition-all"&gt;
  &lt;div class="flex items-center gap-3 min-w-0"&gt;
    &lt;img width="54" height="54" src="&#123;&#123; $app->icon_url &#125;&#125;" class="w-[54px] h-[54px] rounded-xl object-cover bg-black/40 border border-white/10 shrink-0" alt="&#123;&#123; $app->name &#125;&#125;"&gt;
    &lt;div class="min-w-0"&gt;
      &lt;h3 class="text-sm font-bold text-white tracking-tight truncate font-heading group-hover:text-teal-300"&gt;
        &lt;a href="&#123;&#123; route('app', $app->slug) &#125;&#125;" class="after:absolute after:inset-0 after:z-[1]"&gt;&#123;&#123; $app->name &#125;&#125;&lt;/a&gt;
      &lt;/h3&gt;
      &lt;div class="flex items-center gap-2 text-xs text-[#8C847A] mt-0.5 relative z-[2]"&gt;
        &lt;span class="text-white/80 font-mono text-[11px]"&gt;v&#123;&#123; $app->version &#125;&#125;&lt;/span&gt;
        &lt;span&gt;•&lt;/span&gt;
        &lt;span class="text-emerald-400 font-semibold"&gt;✓ Limpio&lt;/span&gt;
      &lt;/div&gt;
    &lt;/div&gt;
  &lt;/div&gt;
  &lt;div class="flex items-center gap-1.5 shrink-0 relative z-[2]"&gt;
    &lt;a href="&#123;&#123; route('download', $app->slug) &#125;&#125;" class="px-2.5 py-1 rounded-lg bg-teal-500/15 hover:bg-teal-500 text-teal-300 hover:text-black border border-teal-500/30 text-xs font-semibold"&gt;DDL&lt;/a&gt;
  &lt;/div&gt;
&lt;/article&gt;</pre>

            <pre x-ref="codeSnippet6">&lt;!-- DISEÑO 6: Cyber Terminal HUD en Cuadros (Apps Grid) --&gt;
&lt;article class="group relative flex flex-col items-center text-center p-4 rounded-2xl bg-[#0b1017]/95 hover:bg-[#0f1722] border border-sky-500/20 hover:border-sky-400/60 shadow-[0_8px_30px_rgba(0,0,0,0.7)] transition-all duration-300 hover:-translate-y-1.5 hover:shadow-[0_12px_36px_rgba(56,189,248,0.2)] cursor-pointer"&gt;
  &lt;!-- Corner Tick Marks (Cyber HUD) --&gt;
  &lt;div class="absolute top-0 right-0 w-3 h-3 border-t-2 border-r-2 border-sky-400/40 group-hover:border-sky-400 transition-colors pointer-events-none rounded-tr-md"&gt;&lt;/div&gt;
  &lt;div class="absolute bottom-0 left-0 w-3 h-3 border-b-2 border-l-2 border-sky-400/40 group-hover:border-sky-400 transition-colors pointer-events-none rounded-bl-md"&gt;&lt;/div&gt;

  &lt;!-- Top Telemetry Micro-Pill --&gt;
  &lt;div class="w-full flex items-center justify-between gap-1 mb-3 text-[10px] font-mono text-[#6E7681]"&gt;
    &lt;span class="inline-flex items-center gap-1 text-emerald-400 font-bold bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20"&gt;
      &lt;span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"&gt;&lt;/span&gt;
      OK
    &lt;/span&gt;
    &lt;span class="text-[#8BA0B6] bg-white/[0.04] px-1.5 py-0.5 rounded border border-white/5 truncate max-w-[80px]"&gt;
      v&#123;&#123; $app->version &#125;&#125;
    &lt;/span&gt;
  &lt;/div&gt;

  &lt;!-- Big 3D Squircle Icon with Cyber Frame --&gt;
  &lt;a href="&#123;&#123; route('app', $app->slug) &#125;&#125;" class="relative block my-1 group-hover:scale-105 transition-transform duration-300"&gt;
    &lt;div class="w-[88px] h-[88px] rounded-[22px] p-1.5 bg-[#05080c] border border-sky-500/30 group-hover:border-sky-400 flex items-center justify-center shadow-lg shadow-black/80 group-hover:shadow-[0_0_24px_rgba(56,189,248,0.4)] transition-all overflow-hidden"&gt;
      &lt;img width="84" height="84" src="&#123;&#123; $app->icon_url &#125;&#125;" class="w-full h-full object-cover rounded-[18px]" alt="&#123;&#123; $app->name &#125;&#125;" loading="lazy"&gt;
    &lt;/div&gt;
  &lt;/a&gt;

  &lt;!-- App Title --&gt;
  &lt;h3 class="mt-3 text-[15px] font-bold text-white tracking-tight truncate w-full font-heading group-hover:text-sky-300 transition-colors"&gt;
    &lt;a href="&#123;&#123; route('app', $app->slug) &#125;&#125;" class="after:absolute after:inset-0 after:z-[1]"&gt;&#123;&#123; $app->name &#125;&#125;&lt;/a&gt;
  &lt;/h3&gt;

  &lt;!-- Subtitle / Short Description --&gt;
  &lt;p class="mt-1 text-xs text-[#8BA0B6] leading-relaxed line-clamp-2 w-full px-1"&gt;
    &#123;&#123; $app->short_description ?: 'Software verificado y optimizado para macOS.' &#125;&#125;
  &lt;/p&gt;

  &lt;!-- Bottom Cyber HUD Action &amp; Telemetry --&gt;
  &lt;div class="mt-4 pt-3 border-t border-sky-500/15 w-full flex items-center justify-between gap-1 relative z-[2]"&gt;
    &lt;span class="text-[10px] font-mono text-[#6E7681] truncate"&gt;
      &#123;&#123; $app->size ?? 'Universal' &#125;&#125;
    &lt;/span&gt;

    &lt;a href="&#123;&#123; route('download', $app->slug) &#125;&#125;" class="px-2.5 py-1 rounded bg-sky-500/15 hover:bg-sky-500 text-sky-400 hover:text-black border border-sky-500/30 font-mono text-[11px] font-bold transition-all flex items-center gap-1 group-hover:shadow-[0_0_12px_rgba(56,189,248,0.5)]"&gt;
      &lt;span&gt;GET&lt;/span&gt;
      &lt;svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"&gt;&lt;path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/&gt;&lt;/svg&gt;
    &lt;/a&gt;
  &lt;/div&gt;
&lt;/article&gt;</pre>
        </div>

        <!-- Code Modal for Easy Copying -->
        <div x-show="showCodeModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md" @keydown.escape.window="showCodeModal = false">
            <div class="bg-[#12151c] border border-white/15 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl relative" @click.away="showCodeModal = false">
                <div class="flex items-center justify-between p-4 border-b border-white/10 bg-white/[0.02]">
                    <h3 class="text-sm font-bold text-white font-heading" x-text="modalTitle"></h3>
                    <button @click="showCodeModal = false" class="text-[#A1A1A6] hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-4 max-h-[60vh] overflow-y-auto">
                    <pre class="p-4 rounded-xl bg-black/60 border border-white/10 text-xs font-mono text-[#D1D9E0] overflow-x-auto whitespace-pre-wrap" x-text="modalCode"></pre>
                </div>
                <div class="flex items-center justify-between p-4 border-t border-white/10 bg-white/[0.02]">
                    <span class="text-xs text-[#8C847A]" x-text="copiedSnippet ? '¡Copiado con éxito al portapapeles!' : 'Puedes copiarlo y pegarlo en components/app-card.blade.php'"></span>
                    <button @click="copyToClipboard(modalCode)" class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold transition-all shadow-lg shadow-primary/30 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        <span x-text="copiedSnippet ? '¡Copiado!' : 'Copiar Código'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

