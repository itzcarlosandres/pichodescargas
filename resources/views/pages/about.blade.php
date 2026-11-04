<x-app-layout>
    @section('title', 'Sobre Nosotros - ' . config('app.name'))
    @section('description', 'Sobre PichoDescargas - El portal líder para programas de Windows, Mac, Apps Android y Juegos de PC.')

    <div class="container-app pt-4 sm:pt-6 pb-16">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="bg-[#242426] border border-[#333336] rounded-2xl p-6 sm:p-9 shadow-2xl space-y-6 text-xs sm:text-sm text-[#A1A1A6] leading-relaxed">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#2C2C2F] via-[#242426] to-[#161617] border border-[#333336] flex items-center justify-center p-2 shadow-inner">
                        <svg class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Sobre PichoDescargas</h1>
                        <p class="text-xs text-[#A1A1A6]">Software, Juegos, Apps y Utilidades</p>
                    </div>
                </div>

                <p>
                    <strong>PichoDescargas</strong> fue creado con una misión clara: ofrecer a los usuarios un directorio rápido, seguro y verificado de programas para Windows, Mac, aplicaciones Android y juegos para PC.
                </p>

                <!-- Live stats grid -->
                @php
                    $totAboutDl = (int) ($stats['total_downloads'] ?? 0);
                    if ($totAboutDl >= 1000000) {
                        $formattedAboutDl = number_format($totAboutDl / 1000000, 1) . 'M';
                    } elseif ($totAboutDl >= 1000) {
                        $formattedAboutDl = number_format($totAboutDl / 1000, 1) . 'K';
                    } else {
                        $formattedAboutDl = number_format($totAboutDl);
                    }
                @endphp
                <div class="grid grid-cols-3 gap-3 p-4 rounded-xl bg-[#161617] border border-[#333336] text-center my-4">
                    <div>
                        <p class="text-2xl font-extrabold text-primary">{{ number_format($stats['total_apps'] ?? 0) }}</p>
                        <p class="text-[10px] uppercase font-bold text-[#A1A1A6] tracking-wider mt-1">Apps &amp; Games</p>
                    </div>
                    <div>
                        <p class="text-2xl font-extrabold text-white">{{ $formattedAboutDl }}</p>
                        <p class="text-[10px] uppercase font-bold text-[#A1A1A6] tracking-wider mt-1">Downloads Served</p>
                    </div>
                    <div>
                        <p class="text-2xl font-extrabold text-success">{{ $stats['total_categories'] ?? 0 }}</p>
                        <p class="text-[10px] uppercase font-bold text-[#A1A1A6] tracking-wider mt-1">Categories</p>
                    </div>
                </div>

                <h2 class="text-base font-bold text-white pt-2">Our Quality Standard</h2>
                <ul class="list-disc pl-5 space-y-1.5 text-[#CBD5E1]">
                    <li>Every release is scanned and checked against malicious software.</li>
                    <li>Direct high-speed mirrors and cloud downloads.</li>
                    <li>Comprehensive guides for Gatekeeper bypass and SIP management.</li>
                    <li>Community feedback, threaded discussions, and verification by real Mac users.</li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
