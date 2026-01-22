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
    public $levels = [];
    public $fields = [
        'santri_l',
        'santri_p',
        'ustadz_dirosah_l',
        'ustadz_dirosah_p',
        'ustadz_non_dirosah_l',
        'ustadz_non_dirosah_p',
        'pamong_l',
        'pamong_p',
        'musyrif_l',
        'musyrif_p',
        'tendik_l',
        'tendik_p',
    ];

    public function mount($uuid)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $this->akreditasi = Akreditasi::with(['user.pesantren', 'assessments.asesor.user', 'assessment1', 'assessment2'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $userId = $this->akreditasi->user_id;
        $this->pesantren = Pesantren::with('units')->where('user_id', $userId)->first();
        $this->ipm = Ipm::where('user_id', $userId)->first();
        $this->sdm = SdmPesantren::where('user_id', $userId)->get()->keyBy('tingkat');
        if ($this->pesantren && $this->pesantren->relationLoaded('units')) {
            $this->levels = $this->pesantren->units->pluck('unit')->toArray();
        }
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

    protected function messages()
    {
        return [
            'adminNvs.*.required' => 'Nilai NV wajib diisi.',
            'adminNvs.*.integer' => 'Nilai NV harus berupa angka.',
            'adminNvs.*.between' => 'Nilai NV harus antara 1 sampai 4.',
        ];
    }

    protected function validationAttributes()
    {
        $attributes = [];
        foreach ($this->komponens as $k) {
            foreach ($k->butirs as $b) {
                $attributes["adminNvs.{$b->id}"] = "Nilai NV Butir {$b->nomor_butir}";
            }
        }
        return $attributes;
    }

    public function saveAdminNv()
    {
        if ($this->akreditasi->status != 4) {
            session()->flash('error', 'Data tidak dapat diubah karena status bukan Validasi.');
            return;
        }

        try {
            $this->validate([
                'adminNvs.*' => 'required|integer|between:1,4',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $missingItems = [];
            $errors = $e->validator->errors()->messages();

            foreach ($errors as $key => $messages) {
                if (preg_match('/adminNvs\.(\d+)/', $key, $matches)) {
                    $butirId = $matches[1];

                    // Find butir info from our komponens collection
                    foreach ($this->komponens as $komponen) {
                        $butir = $komponen->butirs->firstWhere('id', $butirId);
                        if ($butir) {
                            $missingItems[] = "<li><b>NV</b>: Butir {$butir->nomor_butir} ({$komponen->nama})</li>";
                            break;
                        }
                    }
                }
            }

            $htmlList = '<ul class="text-left list-disc pl-5 mt-2 space-y-1 text-[11px]">' . implode('', array_unique($missingItems)) . '</ul>';

            $this->dispatch(
                'validation-failed',
                title: 'Nilai NV Belum Lengkap',
                html: "Mohon lengkapi nilai verifikasi berikut sebelum menyimpan:<br>" . $htmlList
            );
            throw $e;
        }

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

        $this->dispatch(
            'notification-received',
            type: 'success',
            title: 'Berhasil!',
            message: 'Nilai Verifikasi berhasil disimpan.'
        );
    }

    public function approve()
    {
        if (!$this->checkScores()) {
            return;
        }

        $this->validate([
            'nomor_sk' => 'required|string|max:255',
        ], [
            'nomor_sk.required' => 'Nomor SK wajib diisi.',
            'nomor_sk.max' => 'Nomor SK maksimal 255 karakter.',
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
        if (!$this->checkScores()) {
            return;
        }

        $this->validate([
            'catatan_admin' => 'required|string',
        ], [
            'catatan_admin.required' => 'Catatan penolakan wajib diisi.',
        ]);

        $this->akreditasi->update([
            'status' => 2,
            'catatan' => $this->catatan_admin,
        ]);

        session()->flash('status', 'Akreditasi telah ditolak.');
        return redirect()->route('admin.akreditasi');
    }

    public function getTotal($field)
    {
        $total = 0;
        foreach ($this->levels as $level) {
            $total += (int)($this->sdm[$level]->$field ?? 0);
        }
        return $total;
    }

    private function checkScores()
    {
        $isMissing = false;
        foreach ($this->komponens as $komponen) {
            foreach ($komponen->butirs as $butir) {
                if (empty($this->asesor1Nks[$butir->id]) || empty($this->adminNvs[$butir->id])) {
                    $isMissing = true;
                    break 2;
                }
            }
        }

        if ($isMissing) {
            $this->dispatch(
                'notification-received',
                type: 'error',
                title: 'Data Belum Lengkap',
                message: 'Tidak dapat memproses akreditasi. Pastikan nilai NK (Asesor) dan NV (Admin) telah diisi untuk semua butir.'
            );
            return false;
        }

        return true;
    }
}; ?>


<div class="py-12" x-data="akreditasiManagement">
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
                        class="text-indigo-600 hover:text-indigo-900 font-medium">&larr; Kembali</a>
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
                                <p class="text-xs font-bold text-gray-500 uppercase mb-2">Layanan Satuan Pendidikan</p>
                                @if($pesantren->units && $pesantren->units->count() > 0)
                                <div class="overflow-x-auto border rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Unit</th>
                                                <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Jml Rombel</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($pesantren->units as $unit)
                                            <tr>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm font-bold text-gray-900 uppercase">{{ $unit->unit }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-700">{{ $unit->jumlah_rombel }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-4">
                                    <div>
                                        <p class="text-xs font-bold text-gray-500 uppercase">Total Luas Tanah (m²)</p>
                                        <p class="text-gray-900 font-bold">{{ $pesantren->luas_tanah ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-gray-500 uppercase">Total Luas Bangunan (m²)</p>
                                        <p class="text-gray-900 font-bold">{{ $pesantren->luas_bangunan ?? '-' }}</p>
                                    </div>
                                </div>
                                @else
                                <p class="text-gray-900 italic text-sm">Belum ada data unit pendidikan.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Dokumen Section -->
                    <div class="space-y-6">
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

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-6 rounded-lg">
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
                                            <th colspan="2" class="border border-gray-300 px-2 py-1 bg-green-50">SANTRI</th>
                                            <th colspan="2" class="border border-gray-300 px-2 py-1 bg-blue-50">USTADZ DIROSAH</th>
                                            <th colspan="2" class="border border-gray-300 px-2 py-1 bg-indigo-50">USTADZ NON DIROSAH</th>
                                            <th colspan="2" class="border border-gray-300 px-2 py-1 bg-yellow-50">PAMONG</th>
                                            <th colspan="2" class="border border-gray-300 px-2 py-1 bg-orange-50">MUSYRIF/AH</th>
                                            <th colspan="2" class="border border-gray-300 px-2 py-1 bg-purple-50">TENAGA KEPENDIDIKAN</th>
                                        </tr>
                                        <tr class="bg-gray-50 text-center">
                                            <th class="border border-gray-300">L</th>
                                            <th class="border border-gray-300">P</th>
                                            <th class="border border-gray-300">L</th>
                                            <th class="border border-gray-300">P</th>
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
                                        @foreach ($levels as $index => $level)
                                        <tr class="text-center">
                                            <td class="border border-gray-300 px-2 py-1 font-bold">
                                                {{ $index + 1 }}
                                            </td>
                                            <td class="border border-gray-300 px-2 py-1 font-bold text-left uppercase">
                                                {{ $level }}
                                            </td>
                                            @foreach($fields as $field)
                                            <td class="border border-gray-300 px-2 py-1">
                                                {{ $sdm[$level]->$field ?? 0 }}
                                            </td>
                                            @endforeach
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-blue-50 font-bold text-center">
                                        <tr>
                                            <td colspan="2" class="border border-gray-300 px-4 py-2 uppercase">JUMLAH</td>
                                            @foreach($fields as $field)
                                            <td class="border border-gray-300 px-2 py-2">
                                                {{ $this->getTotal($field) }}
                                            </td>
                                            @endforeach
                                        </tr>
                                    </tfoot>
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
                                                @error('adminNvs.' . $butir->id)
                                                <span class="text-red-500 text-[10px] px-2 pb-1 block whitespace-nowrap">{{ $message }}</span>
                                                @enderror
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

                            @if ($akreditasi->status == 4)
                            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h3 class="text-sm font-bold text-purple-900 mb-1">Nilai Verifikasi (NV)
                                        </h3>
                                        <p class="text-xs text-purple-700">Silakan input nilai verifikasi untuk
                                            setiap butir penilaian.</p>
                                    </div>
                                    <x-primary-button wire:click="saveAdminNv" wire:loading.attr="disabled"
                                        class="bg-purple-600 hover:bg-purple-700">
                                        <svg wire:loading wire:target="saveAdminNv" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Simpan NV</span>
                                    </x-primary-button>
                                </div>
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
                                                    <x-primary-button wire:loading.attr="disabled" class="bg-green-600 hover:bg-green-700">
                                                        <svg wire:loading wire:target="approve" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        <span>Setujui & Simpan</span>
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
                                                    <button type="submit" wire:loading.attr="disabled"
                                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                        <svg wire:loading wire:target="reject" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        <span>Tolak Pengajuan</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Floating Navigation Buttons for NA Tab -->
                        <div class="fixed bottom-8 right-8 flex flex-col gap-3 z-50">
                            <button type="button"
                                onclick="document.getElementById('main-content-scroll').scrollTo({top: 0, behavior: 'smooth'})"
                                class="flex items-center justify-center w-12 h-12 bg-indigo-600 text-white rounded-full shadow-xl hover:bg-indigo-700 hover:scale-110 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-indigo-300"
                                title="Scroll Ke Atas">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                </svg>
                            </button>
                            <button type="button"
                                onclick="const el = document.getElementById('main-content-scroll'); el.scrollTo({top: el.scrollHeight, behavior: 'smooth'})"
                                class="flex items-center justify-center w-12 h-12 bg-indigo-600 text-white rounded-full shadow-xl hover:bg-indigo-700 hover:scale-110 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-indigo-300"
                                title="Scroll Ke Bawah">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>