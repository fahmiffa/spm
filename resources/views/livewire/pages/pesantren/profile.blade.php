<?php

use App\Models\Pesantren;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public $pesantren;
    
    // Form fields
    public $nama_pesantren;
    public $ns_pesantren;
    public $alamat;
    public $kota_kabupaten;
    public $provinsi;
    public $tahun_pendirian;
    public $nama_mudir;
    public $jenjang_pendidikan_mudir;
    public $telp_pesantren;
    public $hp_wa;
    public $email_pesantren;
    public $persyarikatan;
    public $visi;
    public $misi;

    // DATA PESANTREN
    public $layanan_satuan_pendidikan;
    public $rombel_sd = 0;
    public $rombel_mi = 0;
    public $rombel_smp = 0;
    public $rombel_mts = 0;
    public $rombel_sma = 0;
    public $rombel_ma = 0;
    public $rombel_smk = 0;
    public $rombel_spm = 0;
    public $luas_tanah;
    public $luas_bangunan;

    // DOKUMEN (Uploaded files)
    public $status_kepemilikan_tanah_file;
    public $sertifikat_nsp_file;
    public $rk_anggaran_file;
    public $silabus_rpp_file;
    public $peraturan_kepegawaian_file;
    public $file_lk_iapm_file;
    public $laporan_tahunan_file;
    
    // DOKUMEN SEKUNDER
    public $dok_profil_file;
    public $dok_nsp_file;
    public $dok_renstra_file;
    public $dok_rk_anggaran_file;
    public $dok_kurikulum_file;
    public $dok_silabus_rpp_file;
    public $dok_kepengasuhan_file;
    public $dok_peraturan_kepegawaian_file;
    public $dok_sarpras_file;
    public $dok_laporan_tahunan_file;
    public $dok_sop_file;

    // Existing file paths
    public $existing_files = [];

    public function mount()
    {
        if (!auth()->user()->isPesantren()) {
            abort(403);
        }

        $this->pesantren = Pesantren::firstOrCreate(['user_id' => auth()->id()], ['nama_pesantren' => auth()->user()->name]);
        
        $this->nama_pesantren = $this->pesantren->nama_pesantren;
        $this->ns_pesantren = $this->pesantren->ns_pesantren;
        $this->alamat = $this->pesantren->alamat;
        $this->kota_kabupaten = $this->pesantren->kota_kabupaten;
        $this->provinsi = $this->pesantren->provinsi;
        $this->tahun_pendirian = $this->pesantren->tahun_pendirian;
        $this->nama_mudir = $this->pesantren->nama_mudir;
        $this->jenjang_pendidikan_mudir = $this->pesantren->jenjang_pendidikan_mudir;
        $this->telp_pesantren = $this->pesantren->telp_pesantren;
        $this->hp_wa = $this->pesantren->hp_wa;
        $this->email_pesantren = $this->pesantren->email_pesantren;
        $this->persyarikatan = $this->pesantren->persyarikatan;
        $this->visi = $this->pesantren->visi;
        $this->misi = $this->pesantren->misi;

        $this->layanan_satuan_pendidikan = $this->pesantren->layanan_satuan_pendidikan;
        $this->rombel_sd = $this->pesantren->rombel_sd;
        $this->rombel_mi = $this->pesantren->rombel_mi;
        $this->rombel_smp = $this->pesantren->rombel_smp;
        $this->rombel_mts = $this->pesantren->rombel_mts;
        $this->rombel_sma = $this->pesantren->rombel_sma;
        $this->rombel_ma = $this->pesantren->rombel_ma;
        $this->rombel_smk = $this->pesantren->rombel_smk;
        $this->rombel_spm = $this->pesantren->rombel_spm;
        $this->luas_tanah = $this->pesantren->luas_tanah;
        $this->luas_bangunan = $this->pesantren->luas_bangunan;

        // Store existing file paths
        $fileFields = [
            'status_kepemilikan_tanah', 'sertifikat_nsp', 'rk_anggaran', 'silabus_rpp', 
            'peraturan_kepegawaian', 'file_lk_iapm', 'laporan_tahunan', 'dok_profil', 
            'dok_nsp', 'dok_renstra', 'dok_rk_anggaran', 'dok_kurikulum', 
            'dok_silabus_rpp', 'dok_kepengasuhan', 'dok_peraturan_kepegawaian', 
            'dok_sarpras', 'dok_laporan_tahunan', 'dok_sop'
        ];

        foreach ($fileFields as $field) {
            $this->existing_files[$field] = $this->pesantren->$field;
        }
    }

    public function save()
    {
        $this->validate([
            'nama_pesantren' => 'required|string|max:255',
            'email_pesantren' => 'nullable|email',
            // Files validation can be added here
        ]);

        $data = [
            'nama_pesantren' => $this->nama_pesantren,
            'ns_pesantren' => $this->ns_pesantren,
            'alamat' => $this->alamat,
            'kota_kabupaten' => $this->kota_kabupaten,
            'provinsi' => $this->provinsi,
            'tahun_pendirian' => $this->tahun_pendirian,
            'nama_mudir' => $this->nama_mudir,
            'jenjang_pendidikan_mudir' => $this->jenjang_pendidikan_mudir,
            'telp_pesantren' => $this->telp_pesantren,
            'hp_wa' => $this->hp_wa,
            'email_pesantren' => $this->email_pesantren,
            'persyarikatan' => $this->persyarikatan,
            'visi' => $this->visi,
            'misi' => $this->misi,
            'layanan_satuan_pendidikan' => $this->layanan_satuan_pendidikan,
            'rombel_sd' => $this->rombel_sd,
            'rombel_mi' => $this->rombel_mi,
            'rombel_smp' => $this->rombel_smp,
            'rombel_mts' => $this->rombel_mts,
            'rombel_sma' => $this->rombel_sma,
            'rombel_ma' => $this->rombel_ma,
            'rombel_smk' => $this->rombel_smk,
            'rombel_spm' => $this->rombel_spm,
            'luas_tanah' => $this->luas_tanah,
            'luas_bangunan' => $this->luas_bangunan,
        ];

        // Handle file uploads
        $fileFields = [
            'status_kepemilikan_tanah' => 'status_kepemilikan_tanah_file',
            'sertifikat_nsp' => 'sertifikat_nsp_file',
            'rk_anggaran' => 'rk_anggaran_file',
            'silabus_rpp' => 'silabus_rpp_file',
            'peraturan_kepegawaian' => 'peraturan_kepegawaian_file',
            'file_lk_iapm' => 'file_lk_iapm_file',
            'laporan_tahunan' => 'laporan_tahunan_file',
            'dok_profil' => 'dok_profil_file',
            'dok_nsp' => 'dok_nsp_file',
            'dok_renstra' => 'dok_renstra_file',
            'dok_rk_anggaran' => 'dok_rk_anggaran_file',
            'dok_kurikulum' => 'dok_kurikulum_file',
            'dok_silabus_rpp' => 'dok_silabus_rpp_file',
            'dok_kepengasuhan' => 'dok_kepengasuhan_file',
            'dok_peraturan_kepegawaian' => 'dok_peraturan_kepegawaian_file',
            'dok_sarpras' => 'dok_sarpras_file',
            'dok_laporan_tahunan' => 'dok_laporan_tahunan_file',
            'dok_sop' => 'dok_sop_file',
        ];

        foreach ($fileFields as $dbField => $property) {
            if ($this->$property) {
                // Delete old file if exists
                if ($this->pesantren->$dbField) {
                    Storage::disk('public')->delete($this->pesantren->$dbField);
                }
                $data[$dbField] = $this->$property->store('pesantren_docs', 'public');
                $this->existing_files[$dbField] = $data[$dbField];
            }
        }

        $this->pesantren->update($data);

        session()->flash('status', 'Profil pesantren berhasil diperbarui.');
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <form wire:submit="save">
                    <!-- Section: Profil -->
                    <div class="mb-8 border-b pb-4">
                        <h3 class="text-lg font-bold mb-4 text-indigo-600">PROFIL PESANTREN</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="nama_pesantren" value="Nama Pesantren" />
                                <x-text-input wire:model="nama_pesantren" id="nama_pesantren" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="ns_pesantren" value="Nomor Statistik Pesantren" />
                                <x-text-input wire:model="ns_pesantren" id="ns_pesantren" type="text" class="mt-1 block w-full" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="alamat" value="Alamat" />
                                <textarea wire:model="alamat" id="alamat" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            </div>
                            <div>
                                <x-input-label for="kota_kabupaten" value="Kota/Kabupaten" />
                                <x-text-input wire:model="kota_kabupaten" id="kota_kabupaten" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="provinsi" value="Provinsi" />
                                <x-text-input wire:model="provinsi" id="provinsi" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="tahun_pendirian" value="Tahun Pendirian" />
                                <x-text-input wire:model="tahun_pendirian" id="tahun_pendirian" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="nama_mudir" value="Nama Mudir Pesantren" />
                                <x-text-input wire:model="nama_mudir" id="nama_mudir" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="jenjang_pendidikan_mudir" value="Jenjang Pendidikan Terakhir Mudir" />
                                <x-text-input wire:model="jenjang_pendidikan_mudir" id="jenjang_pendidikan_mudir" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="telp_pesantren" value="No. Telp Pesantren" />
                                <x-text-input wire:model="telp_pesantren" id="telp_pesantren" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="hp_wa" value="No. HP/WA" />
                                <x-text-input wire:model="hp_wa" id="hp_wa" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="email_pesantren" value="Email Pesantren (G-Mail)" />
                                <x-text-input wire:model="email_pesantren" id="email_pesantren" type="email" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="persyarikatan" value="Persyarikatan Penyelenggara" />
                                <x-text-input wire:model="persyarikatan" id="persyarikatan" type="text" class="mt-1 block w-full" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="visi" value="Visi Pesantren" />
                                <textarea wire:model="visi" id="visi" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="misi" value="Misi Pesantren" />
                                <textarea wire:model="misi" id="misi" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section: DATA PESANTREN -->
                    <div class="mb-8 border-b pb-4">
                        <h3 class="text-lg font-bold mb-4 text-indigo-600">DATA PESANTREN</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-3">
                                <x-input-label for="layanan_satuan_pendidikan" value="Layanan Satuan Pendidikan yang Dimiliki" />
                                <x-text-input wire:model="layanan_satuan_pendidikan" id="layanan_satuan_pendidikan" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="rombel_sd" value="Rombel SD" />
                                <x-text-input wire:model="rombel_sd" id="rombel_sd" type="number" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="rombel_mi" value="Rombel MI" />
                                <x-text-input wire:model="rombel_mi" id="rombel_mi" type="number" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="rombel_smp" value="Rombel SMP" />
                                <x-text-input wire:model="rombel_smp" id="rombel_smp" type="number" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="rombel_mts" value="Rombel MTs" />
                                <x-text-input wire:model="rombel_mts" id="rombel_mts" type="number" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="rombel_sma" value="Rombel SMA" />
                                <x-text-input wire:model="rombel_sma" id="rombel_sma" type="number" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="rombel_ma" value="Rombel MA" />
                                <x-text-input wire:model="rombel_ma" id="rombel_ma" type="number" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="rombel_smk" value="Rombel SMK" />
                                <x-text-input wire:model="rombel_smk" id="rombel_smk" type="number" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="rombel_spm" value="Rombel SPM" />
                                <x-text-input wire:model="rombel_spm" id="rombel_spm" type="number" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="luas_tanah" value="Luas Tanah" />
                                <x-text-input wire:model="luas_tanah" id="luas_tanah" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="luas_bangunan" value="Luas Bangunan" />
                                <x-text-input wire:model="luas_bangunan" id="luas_bangunan" type="text" class="mt-1 block w-full" />
                            </div>
                        </div>
                    </div>

                    <!-- Section: DOKUMEN UTAMA -->
                    <div class="mb-8 border-b pb-4">
                        <h3 class="text-lg font-bold mb-4 text-indigo-600">DOKUMEN UTAMA</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @php
                                $mainDocs = [
                                    'status_kepemilikan_tanah_file' => 'Status Kepemilikan Tanah',
                                    'sertifikat_nsp_file' => 'Sertifikat Nomor Statistik Pesantren (NSP)',
                                    'rk_anggaran_file' => 'Rencana Kerja Anggaran Pesantren',
                                    'silabus_rpp_file' => 'Silabus dan RPP (Dirosah Islamiyah)',
                                    'peraturan_kepegawaian_file' => 'Peraturan Kepegawaian',
                                    'file_lk_iapm_file' => 'File Lembar Kerja (LK) Penilaian IAPM2025',
                                    'laporan_tahunan_file' => 'Laporan Tahunan Pesantren',
                                ];
                            @endphp
                            @foreach($mainDocs as $prop => $label)
                                <div>
                                    <x-input-label for="{{ $prop }}" value="{{ $label }}" />
                                    <input type="file" wire:model="{{ $prop }}" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                    @php $dbField = str_replace('_file', '', $prop); @endphp
                                    @if($existing_files[$dbField])
                                        <div class="mt-1 text-xs text-green-600">
                                            File terunggah: <a href="{{ Storage::url($existing_files[$dbField]) }}" target="_blank" class="underline">Lihat File</a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Section: DOKUMEN SEKUNDER -->
                    <div class="mb-8 border-b pb-4">
                        <h3 class="text-lg font-bold mb-4 text-indigo-600">DOKUMEN SEKUNDER</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @php
                                $secondaryDocs = [
                                    'dok_profil_file' => 'Dokumen Profil Pesantren',
                                    'dok_nsp_file' => 'Dokumen Sertifikat NSP',
                                    'dok_renstra_file' => 'Dokumen Renstra Pesantren',
                                    'dok_rk_anggaran_file' => 'Dokumen Rencana Kerja Anggaran Pesantren',
                                    'dok_kurikulum_file' => 'Dokumen Kurikulum Pesantren',
                                    'dok_silabus_rpp_file' => 'Dokumen Silabus dan RPP',
                                    'dok_kepengasuhan_file' => 'Dokumen Panduan Kepengasuhan Pesantren',
                                    'dok_peraturan_kepegawaian_file' => 'Dokumen Peraturan Kepegawaian',
                                    'dok_sarpras_file' => 'Dokumen Sarana dan Prasarana Pesantren',
                                    'dok_laporan_tahunan_file' => 'Dokumen Laporan Tahunan Pesantren',
                                    'dok_sop_file' => 'Dokumen SOP Pesantren',
                                ];
                            @endphp
                            @foreach($secondaryDocs as $prop => $label)
                                <div>
                                    <x-input-label for="{{ $prop }}" value="{{ $label }}" />
                                    <input type="file" wire:model="{{ $prop }}" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                    @php $dbField = str_replace('_file', '', $prop); @endphp
                                    @if($existing_files[$dbField])
                                        <div class="mt-1 text-xs text-green-600">
                                            File terunggah: <a href="{{ Storage::url($existing_files[$dbField]) }}" target="_blank" class="underline">Lihat File</a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button>
                            {{ __('Simpan Perubahan') }}
                        </x-primary-button>
                    </div>

                    @if (session('status'))
                        <div class="mt-4 text-sm text-green-600 font-medium">
                            {{ session('status') }}
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
