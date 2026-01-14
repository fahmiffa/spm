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

    // Assessor 1 EDPM evaluation
    public $asesor1Evaluasis = [];
    public $asesor1Catatans = [];
    public $asesor1Nks = [];
    public $asesor1CatatanNks = [];
    public $asesor1ButirCatatans = [];

    // Assessor 2 EDPM evaluation
    public $asesor2Evaluasis = [];
    public $asesor2Catatans = [];
    public $asesor2ButirCatatans = [];

    public $nomor_sk;
    public $catatan_admin;

    // Admin NV (Nilai Verifikasi)
    public $adminNvs = [];

    public $activeTab = 'profil';

    public function mount($uuid)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $this->akreditasi = Akreditasi::with(['user.pesantren', 'assessments.asesor.user', 'assessment1', 'assessment2'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $userId = $this->akreditasi->user_id;
        $this->pesantren = Pesantren::where('user_id', $userId)->first();
        $this->ipm = Ipm::where('user_id', $userId)->first();
        $this->sdm = SdmPesantren::where('user_id', $userId)->get()->keyBy('tingkat');
        $this->komponens = MasterEdpmKomponen::with('butirs')->get();

        // Load Pesantren EDPM
        $pEvaluasis = Edpm::where('user_id', $userId)->get()->pluck('isian', 'butir_id');
        $pCatatans = EdpmCatatan::where('user_id', $userId)->get()->pluck('catatan', 'komponen_id');

        // Load Assessor 1 EDPM
        $asesor1Id = $this->akreditasi->assessment1->asesor_id ?? null;
        if ($asesor1Id) {
            $a1Edpms = AkreditasiEdpm::where('akreditasi_id', $this->akreditasi->id)->where('asesor_id', $asesor1Id)->get();
            $a1Evaluasis = $a1Edpms->pluck('isian', 'butir_id');
            $a1Nks = $a1Edpms->pluck('nk', 'butir_id');
            $a1Nvs = $a1Edpms->pluck('nv', 'butir_id');
            $a1ButirCatatans = $a1Edpms->pluck('catatan', 'butir_id');

            $a1CatatansModels = AkreditasiEdpmCatatan::where('akreditasi_id', $this->akreditasi->id)->where('asesor_id', $asesor1Id)->get();
            $a1Catatans = $a1CatatansModels->pluck('catatan', 'komponen_id');
            $a1CatatanNks = $a1CatatansModels->pluck('nk', 'komponen_id');
        }

        // Load Assessor 2 EDPM
        $asesor2Id = $this->akreditasi->assessment2->asesor_id ?? null;
        if ($asesor2Id) {
            $a2Edpms = AkreditasiEdpm::where('akreditasi_id', $this->akreditasi->id)->where('asesor_id', $asesor2Id)->get();
            $a2Evaluasis = $a2Edpms->pluck('isian', 'butir_id');
            $a2ButirCatatans = $a2Edpms->pluck('catatan', 'butir_id');
            $a2Catatans = AkreditasiEdpmCatatan::where('akreditasi_id', $this->akreditasi->id)->where('asesor_id', $asesor2Id)->get()->pluck('catatan', 'komponen_id');
        }

        foreach ($this->komponens as $komponen) {
            $this->pesantrenCatatans[$komponen->id] = $pCatatans[$komponen->id] ?? '';
            $this->asesor1Catatans[$komponen->id] = $a1Catatans[$komponen->id] ?? '';
            $this->asesor2Catatans[$komponen->id] = $a2Catatans[$komponen->id] ?? '';

            foreach ($komponen->butirs as $butir) {
                $this->pesantrenEvaluasis[$butir->id] = $pEvaluasis[$butir->id] ?? '';
                $this->asesor1Evaluasis[$butir->id] = $a1Evaluasis[$butir->id] ?? '';
                $this->asesor1Nks[$butir->id] = $a1Nks[$butir->id] ?? '';
                $this->adminNvs[$butir->id] = $a1Nvs[$butir->id] ?? '';
                $this->asesor1ButirCatatans[$butir->id] = $a1ButirCatatans[$butir->id] ?? '';
                $this->asesor2Evaluasis[$butir->id] = $a2Evaluasis[$butir->id] ?? '';
                $this->asesor2ButirCatatans[$butir->id] = $a2ButirCatatans[$butir->id] ?? '';
            }
            $this->asesor1CatatanNks[$komponen->id] = $a1CatatanNks[$komponen->id] ?? '';
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function saveAdminNv()
    {
        if ($this->akreditasi->status != 4) {
            session()->flash('error', 'Data tidak dapat diubah karena status bukan Validasi.');
            return;
        }

        $this->validate([
            'adminNvs.*' => 'nullable|integer|between:1,4',
        ]);

        $asesor1Id = $this->akreditasi->assessment1->asesor_id ?? null;
        if (!$asesor1Id) {
            session()->flash('error', 'Asesor 1 tidak ditemukan.');
            return;
        }

        foreach ($this->adminNvs as $butirId => $nv) {
            if (!empty($nv)) {
                AkreditasiEdpm::where('akreditasi_id', $this->akreditasi->id)
                    ->where('butir_id', $butirId)
                    ->where('asesor_id', $asesor1Id)
                    ->update(['nv' => $nv]);
            }
        }

        session()->flash('success', 'Nilai Verifikasi berhasil disimpan.');
    }

    public function approve()
    {
        $this->validate([
            'nomor_sk' => 'required|string|max:255',
        ]);

        $this->akreditasi->update([
            'status' => 1,
            'nomor_sk' => $this->nomor_sk,
        ]);

        // Notify Pesantren
        $this->akreditasi->user->notify(new \App\Notifications\AkreditasiNotification('validasi', 'Akreditasi Disetujui', 'Selamat! Pengajuan akreditasi Anda telah disetujui dengan nomor SK: ' . $this->nomor_sk, route('pesantren.akreditasi')));

        // Notify Asesor 1
        $asesor1User = $this->akreditasi->assessment1->asesor->user ?? null;
        if ($asesor1User) {
            $asesor1User->notify(new \App\Notifications\AkreditasiNotification('validasi', 'Akreditasi Divalidasi', 'Hasil assessment untuk ' . ($this->pesantren->nama_pesantren ?? $this->akreditasi->user->name) . ' telah divalidasi oleh Admin.', route('asesor.akreditasi')));
        }

        // Notify Asesor 2
        $asesor2User = $this->akreditasi->assessment2->asesor->user ?? null;
        if ($asesor2User) {
            $asesor2User->notify(new \App\Notifications\AkreditasiNotification('validasi', 'Akreditasi Divalidasi', 'Hasil assessment untuk ' . ($this->pesantren->nama_pesantren ?? $this->akreditasi->user->name) . ' telah divalidasi oleh Admin.', route('asesor.akreditasi')));
        }

        session()->flash('status', 'Akreditasi berhasil disetujui.');
        return redirect()->route('admin.akreditasi');
    }

    public function reject()
    {
        $this->validate([
            'catatan_admin' => 'required|string',
        ]);

        $this->akreditasi->update([
            'status' => 2,
            'catatan' => $this->catatan_admin,
        ]);

        session()->flash('status', 'Akreditasi telah ditolak.');
        return redirect()->route('admin.akreditasi');
    }
}; ?>


<div class="py-12">
    <x-slot name="header">{{ __('Detail Akreditasi') }}</x-slot>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Detail Akreditasi (Admin):
                            {{ $pesantren->nama_pesantren ?? $akreditasi->user->name }}
                        </h2>
                        <p class="text-sm text-gray-500">Status Saat Ini: <span
                                class="font-semibold {{ Akreditasi::getStatusBadgeClass($akreditasi->status) }} px-2 py-0.5 rounded text-[10px]">{{ Akreditasi::getStatusLabel($akreditasi->status) }}</span>
                        </p>
                    </div>
                    <a href="{{ route('admin.akreditasi') }}"
                        class="text-indigo-600 hover:text-indigo-900 font-medium">&larr; Kembali ke Daftar</a>
                </div>

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
                    <div class="space-y-6 mb-3">
                        <h3 class="text-lg font-bold text-gray-800 border-l-4 border-indigo-500 pl-3">PESANTREN</h3>
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
                            <div class="md:col-span-2">
                                <p class="text-xs font-bold text-gray-500 uppercase">Layanan Satuan Pendidikan</p>
                                <p class="text-gray-900">
                                    @if($pesantren->layanan_satuan_pendidikan && is_array($pesantren->layanan_satuan_pendidikan))
                                    {{ implode(', ', array_map('strtoupper', $pesantren->layanan_satuan_pendidikan)) }}
                                    @else
                                    -
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-gray-800 border-l-4 border-indigo-500 pl-3">ASESOR</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-6 rounded-lg">
                            @forelse ($akreditasi->assessments as $assessment)
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">
                                    {{ $assessment->tipe == 1 ? 'Ketua' : 'Anggota' }}
                                </p>
                                <p class="text-gray-900 font-medium">
                                    {{ $assessment->asesor->user->name ?? '-' }}
                                </p>
                            </div>
                            @empty
                            <div class="md:col-span-2">
                                <p class="text-gray-500 italic text-sm">Belum ada asesor ditugaskan</p>
                            </div>
                            @endforelse

                            @if ($akreditasi->assessments->isNotEmpty())
                            @php $mainAssessment = $akreditasi->assessments->first(); @endphp
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Tanggal Mulai</p>
                                <p class="text-gray-900">
                                    {{ \Carbon\Carbon::parse($mainAssessment->tanggal_mulai)->format('d M Y') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Tanggal Berakhir</p>
                                <p class="text-gray-900">
                                    {{ \Carbon\Carbon::parse($mainAssessment->tanggal_berakhir)->format('d M Y') }}
                                </p>
                            </div>
                            @endif
                        </div>
                        @endif

                        @if ($activeTab === 'ipm')
                        <div class="space-y-6">
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
                            <div class="overflow-x-auto">
                                <table class="min-w-full border-collapse border border-gray-300 text-xs">
                                    <thead class="bg-gray-100 uppercase font-bold text-[10px]">
                                        <tr>
                                            <th rowspan="2" class="border border-gray-300 px-2 py-2">NO.</th>
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
                                                {{ $loop->iteration }}
                                            </td>
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
                            @if ($akreditasi->status == 4)
                            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h3 class="text-sm font-bold text-purple-900 mb-1">Nilai Verifikasi (NV)
                                        </h3>
                                        <p class="text-xs text-purple-700">Silakan input nilai verifikasi untuk
                                            setiap butir penilaian.</p>
                                    </div>
                                    <x-primary-button wire:click="saveAdminNv"
                                        class="bg-purple-600 hover:bg-purple-700">
                                        Simpan NV
                                    </x-primary-button>
                                </div>
                            </div>
                            @endif
                            <div class="overflow-x-auto mt-4">
                                <table class="min-w-full border-collapse border border-gray-300 text-[10px]">
                                    <thead class="bg-gray-100 font-bold uppercase">
                                        <tr>
                                            <th class="border border-gray-300 px-2 py-3 w-24">Komponen</th>
                                            <th class="border border-gray-300 px-1 py-3 w-12 text-center">No SK</th>
                                            <th class="border border-gray-300 px-1 py-3 w-12 text-center">No Butir</th>
                                            <th class="border border-gray-300 px-2 py-3 text-left">Pernyataan</th>
                                            <th class="border border-gray-300 px-2 py-3 text-center w-20">NA 1</th>
                                            <th class="border border-gray-300 px-2 py-3 text-center w-20 bg-green-50">
                                                NA 2</th>
                                            <th class="border border-gray-300 px-2 py-3 text-center w-20 bg-amber-50">
                                                NK</th>
                                            <th class="border border-gray-300 px-2 py-3 text-center w-20 bg-purple-50">
                                                NV</th>
                                            <th
                                                class="border border-gray-300 px-2 py-3 text-center w-48 bg-blue-50 text-[10px]">
                                                CATATAN BUTIR (NK)</th>
                                            <th class="border border-gray-300 px-2 py-3 text-left w-64 bg-indigo-50">
                                                RINGKASAN REKOMENDASI KOMPONEN</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($komponens as $komponen)
                                        @php $butirsCount = count($komponen->butirs); @endphp
                                        @foreach ($komponen->butirs as $index => $butir)
                                        <tr class="hover:bg-gray-50">
                                            @if ($index === 0)
                                            <td rowspan="{{ $butirsCount }}"
                                                class="border border-gray-300 px-2 py-2 font-bold text-center bg-gray-50 align-middle uppercase text-indigo-700">
                                                {{ $komponen->nama }}
                                            </td>
                                            @endif
                                            <td
                                                class="border border-gray-300 px-1 py-2 text-center text-gray-500">
                                                {{ $butir->no_sk }}
                                            </td>
                                            <td class="border border-gray-300 px-1 py-2 text-center font-bold">
                                                {{ $butir->nomor_butir }}
                                            </td>
                                            <td class="border border-gray-300 px-2 py-2">
                                                {{ $butir->butir_pernyataan }}
                                            </td>
                                            <td class="border border-gray-300 px-2 py-2 text-center font-bold">
                                                {{ $asesor1Evaluasis[$butir->id] ?? '' }}
                                            </td>
                                            <td
                                                class="border border-gray-300 px-2 py-2 text-center font-bold bg-green-50/30">
                                                {{ $asesor2Evaluasis[$butir->id] ?? '' }}
                                            </td>
                                            <td
                                                class="border border-gray-300 px-2 py-2 text-center font-bold bg-amber-50/30 text-amber-900">
                                                {{ $asesor1Nks[$butir->id] ?? '' }}
                                            </td>
                                            <td class="border border-gray-300 p-0 bg-purple-50/10">
                                                @if ($akreditasi->status == 4)
                                                <select wire:model.live="adminNvs.{{ $butir->id }}"
                                                    class="w-full border-0 p-2 text-xs focus:ring-2 focus:ring-purple-500 bg-white">
                                                    <option value="">Pilih...</option>
                                                    <option value="1">1</option>
                                                    <option value="2">2</option>
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                </select>
                                                @else
                                                <div
                                                    class="px-2 py-2 text-center font-bold text-purple-900">
                                                    {{ $adminNvs[$butir->id] ?? '' }}
                                                </div>
                                                @endif
                                            </td>
                                            <td
                                                class="border border-gray-300 px-2 py-2 text-[9px] italic bg-blue-50/20 text-blue-900">
                                                {{ $asesor1ButirCatatans[$butir->id] ?? '' }}
                                            </td>
                                            @if ($index === 0)
                                            <td rowspan="{{ $butirsCount }}"
                                                class="border border-gray-300 px-2 py-2 bg-indigo-50/20 align-top text-[10px] text-gray-700">
                                                {{ $asesor1Catatans[$komponen->id] ?? '-' }}
                                            </td>
                                            @endif
                                        </tr>
                                        @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Ringkasan Data --}}
                            <div
                                class="mt-8 bg-gradient-to-r from-indigo-50 to-purple-50 p-6 rounded-lg border border-indigo-200">
                                <h3 class="text-lg font-bold text-indigo-900 mb-4 border-b-2 border-indigo-300 pb-2">
                                    📊 RINGKASAN DATA PENILAIAN
                                </h3>

                                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="border border-gray-300 px-3 py-2 text-left font-bold">Komponen</th>
                                                <th class="border border-gray-300 px-3 py-2 text-center font-bold">Skor Maksimum<br>(Cmaks)</th>
                                                <th class="border border-gray-300 px-3 py-2 text-center font-bold">Capaian Indikator<br>(CI)</th>
                                                <th class="border border-gray-300 px-3 py-2 text-center font-bold">Bobot Komponen<br>(BK)</th>
                                                <th class="border border-gray-300 px-3 py-2 text-center font-bold">Skor Komponen</th>
                                                <th class="border border-gray-300 px-3 py-2 text-center font-bold">Total Skor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                            $totalCmaks = 0;
                                            $totalCI = 0;
                                            $totalBK = 0;
                                            $totalSkorKomponen = 0;
                                            $grandTotalSkor = 0;

                                            // Hardcoded bobot komponen
                                            $bobotKomponen = [
                                            'MUTU LULUSAN' => 35,
                                            'PROSES PEMBELAJARAN' => 29,
                                            'MUTU USTAZ' => 18,
                                            'MANAJEMEN PESANTREN' => 18,
                                            'B. INDIKATOR PEMENUHAN RELATIF' => 97,
                                            ];
                                            @endphp

                                            @php
                                            $iprNullComponents = $komponens->filter(function($k) { return is_null($k->ipr); });
                                            $iprNotNullComponents = $komponens->filter(function($k) { return !is_null($k->ipr); });

                                            // Calculate total for null IPR components
                                            $totalSkorIprNull = 0;
                                            foreach ($iprNullComponents as $k) {
                                            $b = $bobotKomponen[$k->nama] ?? 0;
                                            $c_total = count($k->butirs) * 4;
                                            $c_ci = 0;
                                            foreach ($k->butirs as $butir) {
                                            $c_ci += (int)($adminNvs[$butir->id] ?? 0);
                                            }
                                            if ($c_total > 0) {
                                            $totalSkorIprNull += round(($c_ci / $c_total) * $b);
                                            }
                                            }
                                            @endphp

                                            @foreach ($komponens as $index => $komponen)
                                            @php
                                            // Hitung Cmaks
                                            $totalButir = count($komponen->butirs);
                                            $nilaiMaksimalNk = 4;
                                            $cmaksKomponen = $totalButir * $nilaiMaksimalNk;

                                            // Hitung CI (sum NV)
                                            $sumNvKomponen = 0;
                                            foreach ($komponen->butirs as $butir) {
                                            $nvValue = $adminNvs[$butir->id] ?? 0;
                                            $sumNvKomponen += (int) $nvValue;
                                            }

                                            // Ambil BK
                                            $bkValue = $bobotKomponen[$komponen->nama] ?? 0;

                                            // Hitung Skor Komponen
                                            $isIpr = !is_null($komponen->ipr);
                                            $faktor = $isIpr ? 100 : $bkValue;

                                            $skorKomponen = 0;
                                            if ($cmaksKomponen > 0) {
                                            $skorKomponen = round(($sumNvKomponen / $cmaksKomponen) * $faktor);
                                            }
                                            @endphp

                                            <tr class="hover:bg-gray-50">
                                                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">
                                                    {{ $komponen->nama }}
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2 text-center text-indigo-700 font-bold">
                                                    {{ $cmaksKomponen }}
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2 text-center text-purple-700 font-bold">
                                                    {{ $sumNvKomponen }}
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2 text-center text-orange-700 font-bold">
                                                    {{ $bkValue }}
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2 text-center text-blue-700 font-mono text-[10px]">
                                                    {{ $skorKomponen }}
                                                </td>

                                                @if ($index === 0)
                                                <td rowspan="{{ $iprNullComponents->count() }}" class="border border-gray-300 px-3 py-2 text-center text-green-900 font-bold text-lg bg-green-50 align-middle">
                                                    {{ $totalSkorIprNull }}
                                                </td>
                                                @elseif ($index === $iprNullComponents->count())
                                                <td class="border border-gray-300 px-3 py-2 text-center text-green-900 font-bold text-lg bg-green-100 align-middle">
                                                    {{ $skorKomponen }}
                                                </td>
                                                @endif
                                            </tr>
                                            @endforeach

                                            @php
                                            // Calculate total for not-null IPR components
                                            $totalSkorIprNotNull = 0;
                                            foreach ($iprNotNullComponents as $k) {
                                            // For IPR not null, factor is 100
                                            $c_total = count($k->butirs) * 4;
                                            $c_ci = 0;
                                            foreach ($k->butirs as $butir) {
                                            $c_ci += (int)($adminNvs[$butir->id] ?? 0);
                                            }
                                            if ($c_total > 0) {
                                            $totalSkorIprNotNull += round(($c_ci / $c_total) * 100);
                                            }
                                            }
                                            @endphp
                                            {{-- Total Row Removed as requested by specific layout --}}
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Hasil Akhir & Peringkat --}}
                                @php
                                $nilaiAkreditasi = round((0.7 * $totalSkorIprNull) + (0.3 * $totalSkorIprNotNull));

                                $peringkat = 'NA';
                                if ($nilaiAkreditasi >= 86) {
                                $peringkat = 'Unggul';
                                } elseif ($nilaiAkreditasi >= 70) {
                                $peringkat = 'Baik';
                                } elseif ($nilaiAkreditasi >= 0) {
                                $peringkat = 'Cukup';
                                }

                                // Set color based on peringkat
                                $peringkatColor = match($peringkat) {
                                'Unggul' => 'text-green-600 bg-green-50 border-green-200',
                                'Baik' => 'text-blue-600 bg-blue-50 border-blue-200',
                                'Cukup' => 'text-yellow-600 bg-yellow-50 border-yellow-200',
                                default => 'text-gray-600 bg-gray-50 border-gray-200',
                                };
                                @endphp

                                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-200">
                                        <h4 class="text-xs font-bold text-indigo-800 uppercase mb-1">Nilai Akreditasi</h4>
                                        <div class="text-2xl font-bold text-indigo-900">
                                            {{ $nilaiAkreditasi }}
                                        </div>
                                    </div>

                                    <div class="{{ $peringkatColor }} rounded-lg p-4 border">
                                        <h4 class="text-xs font-bold uppercase mb-1 opacity-80">Peringkat Akreditasi</h4>
                                        <div class="text-2xl font-bold">
                                            {{ $peringkat }}
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>

                            @if ($akreditasi->status == 4)
                            <div class="mt-8 pt-6 border-t border-gray-200">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <!-- Approve Form -->
                                    <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                                        <h4 class="text-sm font-bold text-green-900 mb-4 uppercase">Setujui Akreditasi</h4>
                                        <form wire:submit="approve">
                                            <div class="space-y-4">
                                                <div>
                                                    <x-input-label for="nomor_sk" value="Nomor SK" />
                                                    <x-text-input wire:model="nomor_sk" id="nomor_sk" type="text"
                                                        class="mt-1 block w-full border-green-300 focus:border-green-500 focus:ring-green-500"
                                                        required placeholder="Masukkan nomor SK resmi..." />
                                                    <x-input-error :messages="$errors->get('nomor_sk')" class="mt-2" />
                                                </div>
                                                <div class="flex justify-end">
                                                    <x-primary-button class="bg-green-600 hover:bg-green-700">
                                                        Setujui & Simpan
                                                    </x-primary-button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Reject Form -->
                                    <div class="bg-red-50 p-6 rounded-lg border border-red-200">
                                        <h4 class="text-sm font-bold text-red-900 mb-4 uppercase">Tolak Akreditasi</h4>
                                        <form wire:submit="reject">
                                            <div class="space-y-4">
                                                <div>
                                                    <x-input-label for="catatan_admin" value="Catatan Penolakan" />
                                                    <textarea wire:model="catatan_admin" id="catatan_admin"
                                                        class="mt-1 block w-full border-red-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm text-sm"
                                                        rows="3" required placeholder="Masukkan alasan penolakan..."></textarea>
                                                    <x-input-error :messages="$errors->get('catatan_admin')" class="mt-2" />
                                                </div>
                                                <div class="flex justify-end">
                                                    <button type="submit"
                                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                        Tolak Pengajuan
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>