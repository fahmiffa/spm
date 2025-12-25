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
    
    // Assessor's EDPM evaluation (read only for admin)
    public $asesorEvaluasis = []; 
    public $asesorCatatans = [];

    public $nomor_sk;
    public $catatan_admin;

    public $activeTab = 'profil';


    public function mount($uuid)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $this->akreditasi = Akreditasi::with(['user.pesantren', 'assessment'])->where('uuid', $uuid)->firstOrFail();
        
        $userId = $this->akreditasi->user_id;
        $this->pesantren = Pesantren::where('user_id', $userId)->first();
        $this->ipm = Ipm::where('user_id', $userId)->first();
        $this->sdm = SdmPesantren::where('user_id', $userId)->get()->keyBy('tingkat');
        $this->komponens = MasterEdpmKomponen::with('butirs')->get();

        // Load Pesantren EDPM
        $pEvaluasis = Edpm::where('user_id', $userId)->get()->pluck('isian', 'butir_id');
        $pCatatans = EdpmCatatan::where('user_id', $userId)->get()->pluck('catatan', 'komponen_id');

        // Load Assessor EDPM
        $aEvaluasis = AkreditasiEdpm::where('akreditasi_id', $this->akreditasi->id)->get()->pluck('isian', 'butir_id');
        $aCatatans = AkreditasiEdpmCatatan::where('akreditasi_id', $this->akreditasi->id)->get()->pluck('catatan', 'komponen_id');

        foreach ($this->komponens as $komponen) {
            $this->pesantrenCatatans[$komponen->id] = $pCatatans[$komponen->id] ?? '-';
            $this->asesorCatatans[$komponen->id] = $aCatatans[$komponen->id] ?? '-';
            
            foreach ($komponen->butirs as $butir) {
                $this->pesantrenEvaluasis[$butir->id] = $pEvaluasis[$butir->id] ?? '-';
                $this->asesorEvaluasis[$butir->id] = $aEvaluasis[$butir->id] ?? '-';
            }
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
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
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Detail Akreditasi (Admin): {{ $pesantren->nama_pesantren ?? $akreditasi->user->name }}</h2>
                        <p class="text-sm text-gray-500">Status Saat Ini: <span class="font-semibold {{ Akreditasi::getStatusBadgeClass($akreditasi->status) }} px-2 py-0.5 rounded text-[10px]">{{ Akreditasi::getStatusLabel($akreditasi->status) }}</span></p>
                    </div>
                    <a href="{{ route('admin.akreditasi') }}" class="text-indigo-600 hover:text-indigo-900 font-medium">&larr; Kembali ke Daftar</a>
                </div>

                <!-- Tabs -->
                <div class="mb-4 border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500">
                        <li class="me-2">
                            <button wire:click="setTab('profil')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'profil' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">Profil</button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab('ipm')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'ipm' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">IPM</button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab('sdm')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'sdm' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">SDM</button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab('edpm_pesantren')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'edpm_pesantren' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">EDPM Pesantren</button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab('instrumen')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'instrumen' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">Instrumen Asesor</button>
                        </li>
                    </ul>
                </div>

                <!-- Tab Contents -->
                <div class="mt-6">
                    @if($activeTab === 'profil')
                        <div class="space-y-6">
                            <h3 class="text-lg font-bold text-gray-800 border-l-4 border-indigo-500 pl-3">PROFIL PESANTREN</h3>
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
                        </div>
                    @endif

                    @if($activeTab === 'ipm')
                        <div class="space-y-6">
                            <h3 class="text-lg font-bold text-gray-800 border-l-4 border-indigo-500 pl-3">INDEKS PEMENUHAN MUTLAK (IPM)</h3>
                            <div class="space-y-4">
                                @php
                                    $ipmItems = [
                                        'nsp_file' => '1. Izin operasional Kementerian Agama (NSP)',
                                        'lulus_santri_file' => '2. Pernah meluluskan santri / memiliki santri kelas akhir',
                                        'kurikulum_file' => '3. Menyelenggarakan kurikulum Dirasah Islamiyah',
                                        'buku_ajar_file' => '4. Menggunakan buku ajar terbitan LP2 PPM',
                                    ];
                                @endphp
                                @foreach($ipmItems as $field => $label)
                                    <div class="p-4 border rounded-lg bg-gray-50 flex justify-between items-center">
                                        <span class="text-sm text-gray-700 font-medium">{{ $label }}</span>
                                        <div>
                                            @if($ipm && $ipm->$field)
                                                <a href="{{ Storage::url($ipm->$field) }}" target="_blank" class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded text-xs font-bold hover:bg-indigo-200">Lihat Dokumen</a>
                                            @else
                                                <span class="text-red-500 text-xs italic">Belum diunggah</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($activeTab === 'sdm')
                        <div class="space-y-6">
                            <h3 class="text-lg font-bold text-gray-800 border-l-4 border-indigo-500 pl-3">REKAPITULASI DATA SDM</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full border-collapse border border-gray-300 text-xs">
                                    <thead class="bg-gray-100 uppercase font-bold text-[10px]">
                                        <tr>
                                            <th rowspan="2" class="border border-gray-300 px-2 py-2">NO.</th>
                                             <th rowspan="2" class="border border-gray-300 px-2 py-2">BENTUK</th>
                                            <th colspan="2" class="border border-gray-300 px-2 py-1 bg-green-50">SANTRI</th>
                                            <th colspan="2" class="border border-gray-300 px-2 py-1 bg-blue-50">USTADZ DIROSAH</th>
                                            <th colspan="2" class="border border-gray-300 px-2 py-1 bg-yellow-50">PAMONG</th>
                                            <th colspan="2" class="border border-gray-300 px-2 py-1 bg-purple-50">TENAGA KEPENDIDIKAN</th>
                                        </tr>
                                        <tr class="bg-gray-50">
                                            <th class="border border-gray-300">L</th><th class="border border-gray-300">P</th>
                                            <th class="border border-gray-300">L</th><th class="border border-gray-300">P</th>
                                            <th class="border border-gray-300">L</th><th class="border border-gray-300">P</th>
                                            <th class="border border-gray-300">L</th><th class="border border-gray-300">P</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(['SD', 'MI', 'SMP', 'MTs', 'SMA', 'MA', 'SMK', 'MAK', 'Satuan Pesantren Muadalah (SPM)'] as $level)
                                            <tr>
                                                <td class="border border-gray-300 px-2 py-1 font-bold">{{ $loop->iteration }}</td>
                                                         <td class="border border-gray-300 px-2 py-1 font-bold">{{ $level }}</td>
                                                <td class="border border-gray-300 px-2 py-1 text-center">{{ $sdm[$level]->santri_l ?? 0 }}</td>
                                                <td class="border border-gray-300 px-2 py-1 text-center">{{ $sdm[$level]->santri_p ?? 0 }}</td>
                                                <td class="border border-gray-300 px-2 py-1 text-center">{{ $sdm[$level]->ustadz_dirosah_l ?? 0 }}</td>
                                                <td class="border border-gray-300 px-2 py-1 text-center">{{ $sdm[$level]->ustadz_dirosah_p ?? 0 }}</td>
                                                <td class="border border-gray-300 px-2 py-1 text-center">{{ $sdm[$level]->pamong_l ?? 0 }}</td>
                                                <td class="border border-gray-300 px-2 py-1 text-center">{{ $sdm[$level]->pamong_p ?? 0 }}</td>
                                                <td class="border border-gray-300 px-2 py-1 text-center">{{ $sdm[$level]->tendik_l ?? 0 }}</td>
                                                <td class="border border-gray-300 px-2 py-1 text-center">{{ $sdm[$level]->tendik_p ?? 0 }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if($activeTab === 'edpm_pesantren')
                        <div class="space-y-6">
                            <h3 class="text-lg font-bold text-gray-800 border-l-4 border-indigo-500 pl-3">EDPM OLEH PESANTREN (Read Only)</h3>
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
                                        @foreach($komponens as $komponen)
                                            @php $butirsCount = count($komponen->butirs); @endphp
                                            @foreach($komponen->butirs as $idx => $butir)
                                                <tr>
                                                    <td class="border border-gray-300 px-2 py-2 text-center font-bold">{{ $butir->nomor_butir }}</td>
                                                    <td class="border border-gray-300 px-4 py-2">{{ $butir->butir_pernyataan }}</td>
                                                    <td class="border border-gray-300 px-4 py-2 font-medium bg-yellow-50 text-indigo-700">{{ $pesantrenEvaluasis[$butir->id] }}</td>
                                                    @if($idx === 0)
                                                        <td rowspan="{{ $butirsCount }}" class="border border-gray-300 px-4 py-2 text-[10px] bg-gray-50 align-top">
                                                            <span class="font-bold text-gray-500">KOMPONEN: {{ $komponen->nama }}</span><br>
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

                    @if($activeTab === 'instrumen')
                        <div class="space-y-6">
                            <div class="flex justify-between items-center bg-gray-100 p-4 rounded-lg border border-gray-200">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 border-l-4 border-gray-500 pl-3 uppercase">Instrumen Akreditasi (Evaluasi Asesor)</h3>
                                    <p class="text-xs text-gray-700 mt-1">Pratinjau hasil penilaian asesor.</p>
                                </div>
                            </div>

                            <div class="overflow-x-auto mt-4">
                                <table class="min-w-full border-collapse border border-gray-300 text-xs">
                                    <thead class="bg-gray-100 font-bold uppercase">
                                        <tr>
                                            <th class="border border-gray-300 px-4 py-3 w-32">Komponen</th>
                                            <th class="border border-gray-300 px-2 py-3 w-16 text-center">No SK</th>
                                            <th class="border border-gray-300 px-2 py-3 w-16 text-center">No Butir</th>
                                            <th class="border border-gray-300 px-4 py-3 text-left">Butir Pernyataan</th>
                                            <th class="border border-gray-300 px-4 py-3 text-center w-48">Hasil Evaluasi Asesor</th>
                                            <th class="border border-gray-300 px-4 py-3 text-center w-64">Catatan Perbaikan/Kinerja</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($komponens as $komponen)
                                            @php $butirsCount = count($komponen->butirs); @endphp
                                            @foreach($komponen->butirs as $index => $butir)
                                                <tr class="hover:bg-gray-50">
                                                    @if($index === 0)
                                                        <td rowspan="{{ $butirsCount }}" class="border border-gray-300 px-4 py-2 font-bold text-center bg-gray-50 align-middle uppercase text-indigo-700">
                                                            {{ $komponen->nama }}
                                                        </td>
                                                    @endif
                                                    <td class="border border-gray-300 px-2 py-2 text-center text-gray-500">{{ $butir->no_sk }}</td>
                                                    <td class="border border-gray-300 px-2 py-2 text-center font-bold">{{ $butir->nomor_butir }}</td>
                                                    <td class="border border-gray-300 px-4 py-2">{{ $butir->butir_pernyataan }}</td>
                                                    <td class="border border-gray-300 px-4 py-2 bg-gray-50">
                                                        {{ $asesorEvaluasis[$butir->id] }}
                                                    </td>
                                                    @if($index === 0)
                                                        <td rowspan="{{ $butirsCount }}" class="border border-gray-300 px-4 py-2 bg-gray-50 align-top text-[10px]">
                                                            {{ $asesorCatatans[$komponen->id] }}
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
                </div>

                @if($akreditasi->status == 4)
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Approve Form -->
                            <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                                <h4 class="text-sm font-bold text-green-900 mb-4 uppercase">Setujui Akreditasi</h4>
                                <form wire:submit="approve">
                                    <div class="space-y-4">
                                        <div>
                                            <x-input-label for="nomor_sk" value="Nomor SK" />
                                            <x-text-input wire:model="nomor_sk" id="nomor_sk" type="text" class="mt-1 block w-full border-green-300 focus:border-green-500 focus:ring-green-500" required placeholder="Masukkan nomor SK resmi..." />
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
                                            <textarea wire:model="catatan_admin" id="catatan_admin" class="mt-1 block w-full border-red-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm text-sm" rows="3" required placeholder="Masukkan alasan penolakan..."></textarea>
                                            <x-input-error :messages="$errors->get('catatan_admin')" class="mt-2" />
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
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
