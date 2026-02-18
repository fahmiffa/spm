<div class="space-y-8">
    <!-- Welcome Header -->
    <div class="relative bg-white overflow-hidden rounded-2xl shadow-sm border border-gray-100">
        <div class="p-8 relative z-10">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Selamat Datang, {{ auth()->user()->name }}! 👋</h2>
                    <p class="text-gray-500 mt-1">Berikut adalah ringkasan data dan statistik akreditasi terkini.</p>
                </div>
                <div class="flex items-center gap-3 bg-gray-50 px-4 py-2 rounded-xl border border-gray-100">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-600">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
                </div>
            </div>
        </div>
        <!-- Decorative Background Pattern -->
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-indigo-50 rounded-full opacity-50 blur-2xl"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-blue-50 rounded-full opacity-50 blur-2xl"></div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 {{ auth()->user()->isAdmin() ? 'lg:grid-cols-5' : 'lg:grid-cols-3' }} gap-6">
        <!-- Proses Pengajuan -->
        <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-gray-100 p-6 group hover:-translate-y-1 transition-all duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-blue-500 uppercase tracking-widest mb-1">Pengajuan</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $prosesPengajuan }}</h3>
                    <p class="text-[10px] text-gray-400 font-medium mt-1">Sedang Diproses</p>
                </div>
                <div class="p-3 bg-blue-50 rounded-xl text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Di Tolak -->
        <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-gray-100 p-6 group hover:-translate-y-1 transition-all duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-rose-500 uppercase tracking-widest mb-1">Ditolak</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $ditolak }}</h3>
                    <p class="text-[10px] text-gray-400 font-medium mt-1">Perlu Revisi</p>
                </div>
                <div class="p-3 bg-rose-50 rounded-xl text-rose-600 group-hover:bg-rose-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Selesai -->
        <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-gray-100 p-6 group hover:-translate-y-1 transition-all duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-emerald-500 uppercase tracking-widest mb-1">Selesai</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $selesai }}</h3>
                    <p class="text-[10px] text-gray-400 font-medium mt-1">Terakreditasi</p>
                </div>
                <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        @if(auth()->user()->isAdmin())
        <!-- Total Pesantren -->
        <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-gray-100 p-6 group hover:-translate-y-1 transition-all duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-1">Pesantren</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $totalPesantren }}</h3>
                    <p class="text-[10px] text-gray-400 font-medium mt-1">Total Terdaftar</p>
                </div>
                <div class="p-3 bg-indigo-50 rounded-xl text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Asesor -->
        <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-gray-100 p-6 group hover:-translate-y-1 transition-all duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-purple-500 uppercase tracking-widest mb-1">Asesor</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $totalAsesor }}</h3>
                    <p class="text-[10px] text-gray-400 font-medium mt-1">Total Terdaftar</p>
                </div>
                <div class="p-3 bg-purple-50 rounded-xl text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>