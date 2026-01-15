<?php

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\Ipm;
use App\Models\SdmPesantren;
use App\Models\MasterEdpmKomponen;
use App\Models\Edpm;
use App\Models\EdpmCatatan;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public $akreditasi;
    public $pesantren;
    public $ipm;
    public $sdm;
    public $komponens;

    // Pesantren's EDPM data (read only)
    public $pesantrenEvaluasis = [];
    public $pesantrenCatatans = [];

    // Assessor's EDPM evaluation (editable)
    public $asesorEvaluasis = [];
    public $asesorCatatans = [];
    public $asesorNks = [];
    public $asesorCatatanNks = [];
    public $asesorButirCatatans = [];

    // Values from the other assessor (for preview)
    public $otherAsesorEvaluasis = [];
    public $otherAsesorCatatans = [];
    public $otherAsesorButirCatatans = [];

    public $asesorTipe;
    public $activeTab = 'profil';

    public function mount($uuid)
    {
        if (!auth()->user()->isAsesor()) {
            abort(403);
        }

        $this->akreditasi = Akreditasi::with(['user.pesantren', 'assessments'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        // Security check: only assigned assessor can see this
        $currentAssessment = $this->akreditasi->assessments->where('asesor_id', auth()->user()->asesor->id)->first();
        if (!$currentAssessment) {
            abort(403);
        }
        $this->asesorTipe = $currentAssessment->tipe;

        $userId = $this->akreditasi->user_id;
        $this->pesantren = Pesantren::where('user_id', $userId)->first();
        $this->ipm = Ipm::where('user_id', $userId)->first();
        $this->sdm = SdmPesantren::where('user_id', $userId)->get()->keyBy('tingkat');
        $this->komponens = MasterEdpmKomponen::with('butirs')->get();

        // Load Pesantren EDPM
        $pEvaluasis = Edpm::where('user_id', $userId)->get()->pluck('isian', 'butir_id');
        $pCatatans = EdpmCatatan::where('user_id', $userId)->get()->pluck('catatan', 'komponen_id');

        // Load Assessor EDPM (filtered by current assessor)
        $asesorId = auth()->user()->asesor->id;
        $aEdpms = AkreditasiEdpm::where('akreditasi_id', $this->akreditasi->id)->where('asesor_id', $asesorId)->get();
        $aEvaluasis = $aEdpms->pluck('isian', 'butir_id');
        $aNks = $aEdpms->pluck('nk', 'butir_id');
        $aButirCatatans = $aEdpms->pluck('catatan', 'butir_id');

        $aCatatansModels = AkreditasiEdpmCatatan::where('akreditasi_id', $this->akreditasi->id)->where('asesor_id', $asesorId)->get();
        $aCatatans = $aCatatansModels->pluck('catatan', 'komponen_id');
        $aCatatanNks = $aCatatansModels->pluck('nk', 'komponen_id');

        // Load the other assessor's data if current is Asesor 1
        $otherEvaluasis = collect();
        $otherCatatans = collect();
        if ($this->asesorTipe == 1) {
            $otherAssessment = $this->akreditasi->assessments->where('tipe', 2)->first();
            if ($otherAssessment) {
                $oEdpms = AkreditasiEdpm::where('akreditasi_id', $this->akreditasi->id)->where('asesor_id', $otherAssessment->asesor_id)->get();
                $otherEvaluasis = $oEdpms->pluck('isian', 'butir_id');
                $otherButirCatatans = $oEdpms->pluck('catatan', 'butir_id');
                $otherCatatans = AkreditasiEdpmCatatan::where('akreditasi_id', $this->akreditasi->id)->where('asesor_id', $otherAssessment->asesor_id)->get()->pluck('catatan', 'komponen_id');
            }
        }

        foreach ($this->komponens as $komponen) {
            $this->pesantrenCatatans[$komponen->id] = $pCatatans[$komponen->id] ?? '-';
            $this->asesorCatatans[$komponen->id] = $aCatatans[$komponen->id] ?? '';
            $this->asesorCatatanNks[$komponen->id] = $aCatatanNks[$komponen->id] ?? '';

            foreach ($komponen->butirs as $butir) {
                $this->pesantrenEvaluasis[$butir->id] = $pEvaluasis[$butir->id] ?? '-';
                $this->asesorEvaluasis[$butir->id] = $aEvaluasis[$butir->id] ?? '';
                $this->asesorNks[$butir->id] = $aNks[$butir->id] ?? '';
                $this->asesorButirCatatans[$butir->id] = $aButirCatatans[$butir->id] ?? '';
                $this->otherAsesorEvaluasis[$butir->id] = $otherEvaluasis[$butir->id] ?? '';
                $this->otherAsesorButirCatatans[$butir->id] = $otherButirCatatans[$butir->id] ?? '';
            }
            $this->otherAsesorCatatans[$komponen->id] = $otherCatatans[$komponen->id] ?? '';
        }
    }

    public function saveAsesorEdpm()
    {
        if ($this->akreditasi->status != 5) {
            session()->flash('error', 'Data tidak dapat diubah karena status sudah bukan Assesment.');
            return;
        }

        $this->validate([
            'asesorEvaluasis.*' => 'nullable|integer|between:1,4',
            'asesorCatatans.*' => 'nullable|string',
            'asesorButirCatatans.*' => 'nullable|string',
            'asesorNks.*' => 'nullable|integer|between:1,4',
            'asesorCatatanNks.*' => 'nullable|integer|between:1,4',
        ]);

        $asesorId = auth()->user()->asesor->id;
        foreach ($this->asesorEvaluasis as $butirId => $isian) {
            $data = [
                'pesantren_id' => $this->akreditasi->user_id,
                'isian' => $isian,
                'catatan' => $this->asesorButirCatatans[$butirId] ?? null
            ];
            if ($this->asesorTipe == 1) {
                $data['nk'] = !empty($this->asesorNks[$butirId]) ? $this->asesorNks[$butirId] : null;
            }
            AkreditasiEdpm::updateOrCreate(['akreditasi_id' => $this->akreditasi->id, 'butir_id' => $butirId, 'asesor_id' => $asesorId], $data);
        }

        foreach ($this->asesorCatatans as $komponenId => $catatan) {
            $data = ['pesantren_id' => $this->akreditasi->user_id, 'catatan' => $catatan];
            if ($this->asesorTipe == 1) {
                $data['nk'] = !empty($this->asesorCatatanNks[$komponenId]) ? $this->asesorCatatanNks[$komponenId] : null;
            }
            AkreditasiEdpmCatatan::updateOrCreate(['akreditasi_id' => $this->akreditasi->id, 'komponen_id' => $komponenId, 'asesor_id' => $asesorId], $data);
        }

        session()->flash('status', 'Instrumen Akreditasi (Evaluasi Asesor) berhasil disimpan.');
    }

    public function finalizeVerification()
    {
        $this->saveAsesorEdpm();

        $this->akreditasi->update(['status' => 4]); // 4. Visitasi

        // Notify Admin
        $admins = \App\Models\User::whereHas('role', function ($q) {
            $q->where('id', 1);
        })->get();
        \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AkreditasiNotification('assessment_selesai', 'Assessment Selesai', 'Asesor ' . auth()->user()->name . ' telah menyelesaikan assessment untuk ' . ($this->pesantren->nama_pesantren ?? $this->akreditasi->user->name), route('admin.akreditasi')));

        // Notify Pesantren
        $this->akreditasi->user->notify(new \App\Notifications\AkreditasiNotification('visitasi', 'Update Status: Visitasi', 'Assessment telah selesai. Status pengajuan Anda kini adalah Visitasi.', route('pesantren.akreditasi')));

        session()->flash('status', 'Verifikasi berhasil diselesaikan. Status berubah menjadi Visitasi.');
        return redirect()->route('asesor.akreditasi');
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Visitasi Akreditasi:
                            {{ $pesantren->nama_pesantren ?? $akreditasi->user->name }}
                        </h2>
                        <p class="text-sm text-gray-500">Status Saat Ini: <span
                                class="font-semibold {{ Akreditasi::getStatusBadgeClass($akreditasi->status) }} px-2 py-0.5 rounded text-[10px]">{{ Akreditasi::getStatusLabel($akreditasi->status) }}</span>
                        </p>
                    </div>
                    <a href="{{ route('asesor.akreditasi') }}"
                        class="text-indigo-600 hover:text-indigo-900 font-medium">&larr; Kembali ke Daftar</a>
                </div>

                @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                    {{ session('status') }}
                </div>
                @endif

                <!-- Tabs -->
                <div class="mb-4 border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500">
                        <li class="me-2">
                            <button wire:click="setTab('profil')"
                                class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'profil' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">Profil</button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab('ipm')"
                                class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'ipm' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">IPM</button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab('sdm')"
                                class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'sdm' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">SDM</button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab('edpm_pesantren')"
                                class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'edpm_pesantren' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">EDPM</button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab('instrumen')"
                                class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'instrumen' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">NA</button>
                        </li>
                    </ul>
                </div>

                <!-- Tab Contents -->
                <div class="mt-6">
                    @if ($activeTab === 'profil')
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-gray-800 border-l-4 border-indigo-500 pl-3">PROFIL
                            PESANTREN</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-6 rounded-lg">
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Nama Pesantren</p>
                                <p class="text-gray-900">{{ $pesantren->nama_pesantren ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">NSP</p>
                                <p class="text-gray-900">{{ $pesantren->ns_pesantren ?? '-' }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-xs font-bold text-gray-500 uppercase">Alamat</p>
                                <p class="text-gray-900">{{ $pesantren->alamat ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Kota/Kabupaten</p>
                                <p class="text-gray-900">{{ $pesantren->kota_kabupaten ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Provinsi</p>
                                <p class="text-gray-900">{{ $pesantren->provinsi ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Nama Mudir</p>
                                <p class="text-gray-900">{{ $pesantren->nama_mudir ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Tahun Pendirian</p>
                                <p class="text-gray-900">{{ $pesantren->tahun_pendirian ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="md:col-span-2 mt-4">
                            <p class="text-xs font-bold text-gray-500 uppercase mb-2">Layanan Satuan Pendidikan</p>
                            @if($pesantren->units && $pesantren->units->count() > 0)
                            <div class="overflow-x-auto border rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Unit</th>
                                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Jml Rombel</th>
                                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Luas Tanah (m²)</th>
                                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Luas Bangunan (m²)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($pesantren->units as $unit)
                                        <tr>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm font-bold text-gray-900 uppercase">{{ $unit->unit }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-700">{{ $unit->jumlah_rombel }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-700">{{ $unit->luas_tanah ?? '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-700">{{ $unit->luas_bangunan ?? '-' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <p class="text-gray-900 italic text-sm">Belum ada data unit pendidikan.</p>
                            @endif
                        </div>
                        <!-- Dokumen Section -->
                        <div class="mt-6">
                            @php
                            $dokumenUtama = [
                            'status_kepemilikan_tanah' => 'Status Kepemilikan Tanah',
                            'sertifikat_nsp' => 'Sertifikat NSP',
                            'rk_anggaran' => 'Rencana Kerja Anggaran',
                            'silabus_rpp' => 'Silabus dan RPP',
                            'peraturan_kepegawaian' => 'Peraturan Kepegawaian',
                            'file_lk_iapm' => 'File LK Penilaian IAPM',
                            'laporan_tahunan' => 'Laporan Tahunan',
                            ];

                            $dokumenSekunder = [
                            'dok_profil' => 'Dokumen Profil',
                            'dok_nsp' => 'Dokumen NSP',
                            'dok_renstra' => 'Dokumen Renstra',
                            'dok_rk_anggaran' => 'Dokumen RK Anggaran',
                            'dok_kurikulum' => 'Dokumen Kurikulum',
                            'dok_silabus_rpp' => 'Dokumen Silabus & RPP',
                            'dok_kepengasuhan' => 'Dokumen Kepengasuhan',
                            'dok_peraturan_kepegawaian' => 'Dokumen Peraturan Kepegawaian',
                            'dok_sarpras' => 'Dokumen Sarpras',
                            'dok_laporan_tahunan' => 'Dokumen Laporan Tahunan',
                            'dok_sop' => 'Dokumen SOP',
                            ];
                            @endphp

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-6 rounded-lg mt-4">
                                <!-- Dokumen Utama -->
                                <div>
                                    <h4 class="text-sm font-bold text-gray-500 uppercase mb-3 border-b pb-1">Dokumen Utama</h4>
                                    <div class="space-y-2">
                                        @foreach($dokumenUtama as $field => $label)
                                        <div class="flex justify-between items-center bg-white p-2 rounded border border-gray-100">
                                            <span class="text-xs font-medium text-gray-700">{{ $label }}</span>
                                            @if($pesantren->$field)
                                            <a href="{{ Storage::url($pesantren->$field) }}" target="_blank" class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-1 rounded hover:bg-indigo-100 font-bold uppercase">Lihat</a>
                                            @else
                                            <span class="text-[10px] text-gray-400 italic"> - </span>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Dokumen Sekunder -->
                                <div>
                                    <h4 class="text-sm font-bold text-gray-500 uppercase mb-3 border-b pb-1">Dokumen Sekunder</h4>
                                    <div class="space-y-2">
                                        @foreach($dokumenSekunder as $field => $label)
                                        <div class="flex justify-between items-center bg-white p-2 rounded border border-gray-100">
                                            <span class="text-xs font-medium text-gray-700">{{ $label }}</span>
                                            @if($pesantren->$field)
                                            <a href="{{ Storage::url($pesantren->$field) }}" target="_blank" class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-1 rounded hover:bg-indigo-100 font-bold uppercase">Lihat</a>
                                            @else
                                            <span class="text-[10px] text-gray-400 italic"> - </span>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if ($activeTab === 'ipm')
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-gray-800 border-l-4 border-indigo-500 pl-3">INDEKS
                            PEMENUHAN MUTLAK (IPM)</h3>
                        <div class="space-y-4">
                            @php
                            $ipmItems = [
                            'nsp_file' => '1. Izin operasional Kementerian Agama (NSP)',
                            'lulus_santri_file' =>
                            '2. Pernah meluluskan santri / memiliki santri kelas akhir',
                            'kurikulum_file' => '3. Menyelenggarakan kurikulum Dirasah Islamiyah',
                            'buku_ajar_file' => '4. Menggunakan buku ajar terbitan LP2 PPM',
                            ];
                            @endphp
                            @foreach ($ipmItems as $field => $label)
                            <div class="p-4 border rounded-lg bg-gray-50 flex justify-between items-center">
                                <span class="text-sm text-gray-700 font-medium">{{ $label }}</span>
                                <div>
                                    @if ($ipm && $ipm->$field)
                                    <a href="{{ Storage::url($ipm->$field) }}" target="_blank"
                                        class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded text-xs font-bold hover:bg-indigo-200">Lihat
                                        Dokumen</a>
                                    @else
                                    <span class="text-red-500 text-xs italic">Belum diunggah</span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if ($activeTab === 'sdm')
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-gray-800 border-l-4 border-indigo-500 pl-3">REKAPITULASI
                            DATA SDM</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse border border-gray-300 text-xs">
                                <thead class="bg-gray-100 uppercase font-bold text-[10px]">
                                    <tr>
                                        <th rowspan="2" class="border border-gray-300 px-2 py-2">BENTUK</th>
                                        <th colspan="2" class="border border-gray-300 px-2 py-1 bg-green-50">
                                            SANTRI</th>
                                        <th colspan="2" class="border border-gray-300 px-2 py-1 bg-blue-50">
                                            USTADZ DIROSAH</th>
                                        <th colspan="2" class="border border-gray-300 px-2 py-1 bg-yellow-50">
                                            PAMONG</th>
                                        <th colspan="2" class="border border-gray-300 px-2 py-1 bg-purple-50">
                                            TENAGA KEPENDIDIKAN</th>
                                    </tr>
                                    <tr class="bg-gray-50">
                                        <th class="border border-gray-300">L</th>
                                        <th class="border border-gray-300">P</th>
                                        <th class="border border-gray-300">L</th>
                                        <th class="border border-gray-300">P</th>
                                        <th class="border border-gray-300">L</th>
                                        <th class="border border-gray-300">P</th>
                                        <th class="border border-gray-300">L</th>
                                        <th class="border border-gray-300">P</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (['SD', 'MI', 'SMP', 'MTs', 'SMA', 'MA', 'SMK', 'MAK', 'Satuan Pesantren Muadalah (SPM)'] as $level)
                                    <tr>
                                        <td class="border border-gray-300 px-2 py-1 font-bold">
                                            {{ $level }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-1 text-center">
                                            {{ $sdm[$level]->santri_l ?? 0 }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-1 text-center">
                                            {{ $sdm[$level]->santri_p ?? 0 }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-1 text-center">
                                            {{ $sdm[$level]->ustadz_dirosah_l ?? 0 }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-1 text-center">
                                            {{ $sdm[$level]->ustadz_dirosah_p ?? 0 }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-1 text-center">
                                            {{ $sdm[$level]->pamong_l ?? 0 }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-1 text-center">
                                            {{ $sdm[$level]->pamong_p ?? 0 }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-1 text-center">
                                            {{ $sdm[$level]->tendik_l ?? 0 }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-1 text-center">
                                            {{ $sdm[$level]->tendik_p ?? 0 }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if ($activeTab === 'edpm_pesantren')
                    <div class="space-y-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse border border-gray-300 text-xs">
                                <thead class="bg-gray-100 uppercase">
                                    <tr>
                                        <th class="border border-gray-300 px-2 py-2">No Butir</th>
                                        <th class="border border-gray-300 px-4 py-2 text-left">Pernyataan</th>
                                        <th class="border border-gray-300 px-4 py-2">Isian Pesantren</th>
                                        <th class="border border-gray-300 px-4 py-2">Catatan Komponen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($komponens as $komponen)
                                    @php $butirsCount = count($komponen->butirs); @endphp
                                    @foreach ($komponen->butirs as $idx => $butir)
                                    <tr>
                                        <td class="border border-gray-300 px-2 py-2 text-center font-bold">
                                            {{ $butir->nomor_butir }}
                                        </td>
                                        <td class="border border-gray-300 px-4 py-2">
                                            {{ $butir->butir_pernyataan }}
                                        </td>
                                        <td
                                            class="border border-gray-300 px-4 py-2 font-medium bg-yellow-50 text-indigo-700">
                                            {{ $pesantrenEvaluasis[$butir->id] }}
                                        </td>
                                        @if ($idx === 0)
                                        <td rowspan="{{ $butirsCount }}"
                                            class="border border-gray-300 px-4 py-2 text-[10px] bg-gray-50 align-top">
                                            <span class="font-bold text-gray-500">KOMPONEN:
                                                {{ $komponen->nama }}</span><br>
                                            {{ $pesantrenCatatans[$komponen->id] }}
                                        </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if ($activeTab === 'instrumen')
                    <div class="space-y-6">
                        <div
                            class="flex justify-between items-center bg-indigo-50 p-4 rounded-lg border border-indigo-100">
                            <div>
                                <h3
                                    class="text-lg font-bold text-indigo-900 border-l-4 border-indigo-500 pl-3 uppercase">
                                    Instrumen Akreditasi (Evaluasi Asesor)</h3>
                                <p class="text-xs text-indigo-700 mt-1">Silakan isi evaluasi dan catatan kinerja
                                    berdasarkan hasil tinjauan Anda.</p>
                            </div>
                            @if ($akreditasi->status == 5)
                            <div class="flex gap-2">
                                @if ($this->asesorTipe == 1)
                                <x-secondary-button wire:click="saveAsesorEdpm" wire:loading.attr="disabled" wire:target="saveAsesorEdpm">
                                    <span wire:loading.remove wire:target="saveAsesorEdpm">Simpan Draft</span>
                                    <span wire:loading wire:target="saveAsesorEdpm">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Menyimpan...
                                    </span>
                                </x-secondary-button>
                                <x-primary-button wire:click="finalizeVerification" wire:confirm="Selesaikan verifikasi? Status akan berubah menjadi Visitasi." wire:loading.attr="disabled" wire:target="finalizeVerification">
                                    <span wire:loading.remove wire:target="finalizeVerification">Selesaikan & Verifikasi</span>
                                    <span wire:loading wire:target="finalizeVerification">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Memproses...
                                    </span>
                                </x-primary-button>
                                @else
                                <x-primary-button wire:click="saveAsesorEdpm" wire:loading.attr="disabled" wire:target="saveAsesorEdpm">
                                    <span wire:loading.remove wire:target="saveAsesorEdpm">Simpan</span>
                                    <span wire:loading wire:target="saveAsesorEdpm">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Menyimpan...
                                    </span>
                                </x-primary-button>
                                @endif
                            </div>
                            @endif
                        </div>

                        @if ($akreditasi->status == 4)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <h4 class="text-sm font-bold text-yellow-900">Data Sedang Diverifikasi</h4>
                                    <p class="text-xs text-yellow-700 mt-1">Assessment telah diselesaikan dan saat ini sedang dalam proses verifikasi oleh admin. Data tidak dapat diubah.</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <form wire:submit="saveAsesorEdpm">
                            <div class="overflow-x-auto mt-4">
                                <table class="min-w-full border-collapse border border-gray-300 text-xs">
                                    <thead class="bg-gray-100 font-bold uppercase">
                                        <tr>
                                            <th class="border border-gray-300 px-4 py-3 w-32">Komponen</th>
                                            <th class="border border-gray-300 px-2 py-3 w-16 text-center">No SK
                                            </th>
                                            <th class="border border-gray-300 px-2 py-3 w-16 text-center">No Butir
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-left">Butir Pernyataan
                                            </th>
                                            @if ($this->asesorTipe == 1)
                                            <th class="border border-gray-300 px-4 py-3 text-center w-20">NA 1</th>
                                            <th class="border border-gray-300 px-4 py-3 text-center w-20 bg-green-50">NA 2</th>
                                            <th class="border border-gray-300 px-4 py-3 text-center w-20 bg-amber-50">NK</th>
                                            <th class="border border-gray-300 px-4 py-3 text-center w-56 bg-blue-50 text-[10px]">CATATAN BUTIR (NK)</th>
                                            <th class="border border-gray-300 px-4 py-3 text-center w-64 bg-blue-50 text-[10px]">CATATAN REKOMENDASI KOMPONEN (NK)</th>
                                            @else
                                            <th class="border border-gray-300 px-4 py-3 text-center w-24">NA</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($komponens as $komponen)
                                        @php $butirsCount = count($komponen->butirs); @endphp
                                        @foreach ($komponen->butirs as $index => $butir)
                                        <tr class="hover:bg-gray-50">
                                            @if ($index === 0)
                                            <td rowspan="{{ $butirsCount }}"
                                                class="border border-gray-300 px-4 py-2 font-bold text-center bg-gray-50 align-middle uppercase text-indigo-700">
                                                {{ $komponen->nama }}
                                            </td>
                                            @endif
                                            <td
                                                class="border border-gray-300 px-2 py-2 text-center text-gray-500">
                                                {{ $butir->no_sk }}
                                            </td>
                                            <td
                                                class="border border-gray-300 px-2 py-2 text-center font-bold">
                                                {{ $butir->nomor_butir }}
                                            </td>
                                            <td class="border border-gray-300 px-4 py-2">
                                                {{ $butir->butir_pernyataan }}
                                            </td>
                                            <td class="border border-gray-300 p-0">
                                                <select
                                                    wire:model.live="asesorEvaluasis.{{ $butir->id }}"
                                                    class="w-full border-0 p-2 text-xs focus:ring-2 focus:ring-indigo-500 {{ $akreditasi->status == 5 ? 'bg-white' : 'bg-gray-100 cursor-not-allowed' }}"
                                                    {{ $akreditasi->status == 5 ? '' : 'disabled' }}>
                                                    <option value="">Pilih...</option>
                                                    <option value="1">1</option>
                                                    <option value="2">2</option>
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                </select>
                                                @error('asesorEvaluasis.' . $butir->id)
                                                <span class="text-red-500 text-[10px] px-2 pb-1 block">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            @if ($this->asesorTipe == 1)
                                            <td class="border border-gray-300 px-2 py-2 text-center font-bold bg-green-50/20 text-green-800">
                                                {{ $otherAsesorEvaluasis[$butir->id] ?? '' }}
                                            </td>
                                            <td class="border border-gray-300 p-0 bg-amber-50/10">
                                                <select
                                                    wire:model.live="asesorNks.{{ $butir->id }}"
                                                    class="w-full border-0 p-2 text-xs focus:ring-2 focus:ring-amber-500 {{ $akreditasi->status == 5 && !empty($asesorEvaluasis[$butir->id]) ? 'bg-white' : 'bg-gray-50 cursor-not-allowed' }}"
                                                    {{ $akreditasi->status == 5 && !empty($asesorEvaluasis[$butir->id]) ? '' : 'disabled' }}>
                                                    <option value="">Pilih...</option>
                                                    <option value="1">1</option>
                                                    <option value="2">2</option>
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                </select>
                                                @error('asesorNks.' . $butir->id)
                                                <span class="text-red-500 text-[10px] px-2 pb-1 block">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="border border-gray-300 p-0 bg-blue-50/10">
                                                <textarea wire:model.live="asesorButirCatatans.{{ $butir->id }}"
                                                    class="w-full border-0 p-2 text-[10px] focus:ring-2 focus:ring-blue-500 min-h-[60px] {{ $akreditasi->status == 5 ? 'bg-white' : 'bg-gray-50 cursor-not-allowed' }}"
                                                    placeholder="Catatan butir..."
                                                    {{ $akreditasi->status == 5 ? '' : 'disabled' }}></textarea>
                                            </td>
                                            @if ($index === 0)
                                            <td rowspan="{{ $butirsCount }}"
                                                class="border border-gray-300 p-0 align-top h-px bg-blue-50/20">
                                                <textarea wire:model.live="asesorCatatans.{{ $komponen->id }}"
                                                    class="w-full h-full min-h-[150px] border-0 p-2 text-xs focus:ring-2 focus:ring-indigo-500 {{ $akreditasi->status == 5 ? 'bg-white' : 'bg-gray-100 cursor-not-allowed' }}"
                                                    placeholder="Masukkan catatan perbaikan..." {{ $akreditasi->status == 5 ? '' : 'disabled' }}></textarea>
                                            </td>
                                            @endif
                                            @endif
                                        </tr>
                                        @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>