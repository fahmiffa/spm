<div class="space-y-8" x-data="{ 
    initCharts() {
        @if($isAdmin)
        // Monthly Chart
        new Chart(document.getElementById('monthlyChart'), {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [{
                    label: 'Pengajuan',
                    data: @json($chartData),
                    backgroundColor: '#3b82f6',
                    borderRadius: 4,
                    barThickness: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: true, drawBorder: false } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Status Pie Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Terakreditasi', 'Ditolak'],
                datasets: [{
                    data: [{{ $stats['terakreditasi'] }}, {{ $stats['ditolak'] }}],
                    backgroundColor: ['#10b981', '#f43f5e'],
                    borderWidth: 0,
                    cutout: '70%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
        @endif
    }
}" x-init="initCharts()">
    <!-- Welcome Header -->
    <div class="relative bg-white overflow-hidden rounded-2xl shadow-sm border border-gray-100 mb-8">
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
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-indigo-50 rounded-full opacity-50 blur-2xl"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-blue-50 rounded-full opacity-50 blur-2xl"></div>
    </div>

    @if($isAdmin)
    <!-- Stats Grid Admin -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Total Pengajuan Aktif -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
            <div class="p-3 bg-blue-50 w-fit rounded-xl text-blue-600 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
            </div>
            <div>
                <h3 class="text-4xl font-extrabold text-gray-800">{{ $stats['total_aktif'] }}</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Total Pengajuan Aktif</p>
            </div>
        </div>

        <!-- Menunggu Verifikasi -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
            <div class="p-3 bg-yellow-50 w-fit rounded-xl text-yellow-600 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-4xl font-extrabold text-gray-800">{{ $stats['verifikasi'] }}</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Menunggu Verifikasi</p>
            </div>
        </div>

        <!-- Sedang Assessment -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
            <div class="p-3 bg-indigo-50 w-fit rounded-xl text-indigo-600 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <h3 class="text-4xl font-extrabold text-gray-800">{{ $stats['assessment'] }}</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Sedang Assessment</p>
            </div>
        </div>

        <!-- Menunggu Visitasi -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
            <div class="p-3 bg-orange-50 w-fit rounded-xl text-orange-600 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-4xl font-extrabold text-gray-800">{{ $stats['visitasi'] }}</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Menunggu Visitasi</p>
            </div>
        </div>

        <!-- Terakreditasi -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
            <div class="p-3 bg-emerald-50 w-fit rounded-xl text-emerald-600 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-4xl font-extrabold text-gray-800">{{ $stats['terakreditasi'] }}</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Terakreditasi</p>
            </div>
        </div>

        <!-- Ditolak -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
            <div class="p-3 bg-rose-50 w-fit rounded-xl text-rose-600 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-4xl font-extrabold text-gray-800">{{ $stats['ditolak'] }}</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Ditolak</p>
            </div>
        </div>
    </div>

    <!-- Monthly Chart -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mt-8">
        <div class="flex justify-between items-start mb-12">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Pengajuan Akreditasi per Bulan</h3>
                <p class="text-xs text-gray-400 mt-1 uppercase font-bold tracking-wider">Tiap bulan</p>
            </div>
            <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100 italic text-xs text-gray-600">
                <span>{{ date('Y') }}</span>
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 17.5l-6-6h12l-6 6z" />
                </svg>
            </div>
        </div>
        <div class="h-[300px] w-full relative">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    <!-- Bottom Row -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 mt-8">
        <!-- Monitoring Asesor -->
        <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <h3 class="text-lg font-bold text-gray-800">Monitoring Asesor</h3>
            <p class="text-xs text-gray-400 mt-1 uppercase font-bold tracking-wider">Ringkasan distribusi dan beban tugas asesor aktif</p>

            <div class="mt-8 space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-gray-50 border-dashed">
                    <div class="flex items-center gap-4">
                        <div class="p-2.5 bg-emerald-500 rounded-lg text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <span class="font-bold text-gray-700">Total Asesor Aktif</span>
                    </div>
                    <span class="font-black text-gray-800">{{ $totalAsesor }}</span>
                </div>

                <div class="flex items-center justify-between pb-4 border-b border-gray-50 border-dashed">
                    <div class="flex items-center gap-4">
                        <div class="p-2.5 bg-blue-500 rounded-lg text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <span class="font-bold text-gray-700">Total Tugas Aktif</span>
                    </div>
                    <span class="font-black text-gray-800">{{ $totalTugasAktif }}</span>
                </div>

                <div class="flex items-center justify-between pb-4 border-b border-gray-50 border-dashed">
                    <div class="flex items-center gap-4">
                        <div class="p-2.5 bg-rose-500 rounded-lg text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="font-bold text-gray-700">Asesor Tanpa Tugas</span>
                    </div>
                    <span class="font-black text-gray-800">{{ $asesorTanpaTugas }}</span>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-2.5 bg-amber-500 rounded-lg text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <span class="font-bold text-gray-700">Rata-rata Beban Tugas</span>
                    </div>
                    <span class="font-black text-gray-800">{{ $avgBeban }} <span class="text-[10px] text-gray-400 font-medium">tugas/asesor</span></span>
                </div>
            </div>
        </div>

        <!-- Status Summary Chart -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <h3 class="text-lg font-bold text-gray-800">Ringkasan Status Akreditasi Pesantren</h3>
            <p class="text-xs text-gray-400 mt-1 uppercase font-bold tracking-wider">Distribusi tahapan proses akreditasi saat ini</p>

            <div class="mt-8 flex flex-col items-center justify-center">
                <div class="h-64 w-64 relative flex items-center justify-center">
                    <canvas id="statusChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-4xl font-black text-gray-800">{{ $stats['terakreditasi'] + $stats['ditolak'] }}</span>
                        <span class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-1">Pesantren</span>
                    </div>
                </div>

                <div class="mt-8 grid grid-cols-2 gap-4 w-full">
                    <div class="flex items-center gap-2 justify-center">
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                        <span class="text-xs text-gray-500 font-medium">Terakreditasi</span>
                    </div>
                    <div class="flex items-center gap-2 justify-center">
                        <div class="w-2.5 h-2.5 rounded-full bg-rose-500"></div>
                        <span class="text-xs text-gray-500 font-medium">Ditolak</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <!-- Stats Grid Non-Admin -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Proses Pengajuan -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 group hover:-translate-y-1 transition-all duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-blue-500 uppercase tracking-widest mb-1">Pengajuan</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $prosesPengajuan }}</h3>
                    <p class="text-xs text-gray-400 font-medium mt-1">Sedang Diproses</p>
                </div>
                <div class="p-3 bg-blue-50 rounded-xl text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Di Tolak -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 group hover:-translate-y-1 transition-all duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-rose-500 uppercase tracking-widest mb-1">Ditolak</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $ditolak }}</h3>
                    <p class="text-xs text-gray-400 font-medium mt-1">Perlu Revisi</p>
                </div>
                <div class="p-3 bg-rose-50 rounded-xl text-rose-600 group-hover:bg-rose-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Selesai -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 group hover:-translate-y-1 transition-all duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-emerald-500 uppercase tracking-widest mb-1">Selesai</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $selesai }}</h3>
                    <p class="text-xs text-gray-400 font-medium mt-1">Terakreditasi</p>
                </div>
                <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>