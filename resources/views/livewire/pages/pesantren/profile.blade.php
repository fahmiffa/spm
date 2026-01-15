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
    public $layanan_satuan_pendidikan = [];


    // Dynamic Units Data
    public $units_data = [];

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

        $this->layanan_satuan_pendidikan = is_array($this->pesantren->layanan_satuan_pendidikan) ? $this->pesantren->layanan_satuan_pendidikan : [];
        // Initialize units_data
        foreach (['sd', 'mi','smp','mts', 'sma', 'ma', 'smk'] as $unit) {
            $this->units_data[$unit] = [
                'luas_tanah' => '',
                'luas_bangunan' => '',
                'jumlah_rombel' => 0
            ];
        }

        // Load existing units data
        foreach ($this->pesantren->units as $unit) {
            if (isset($this->units_data[$unit->unit])) {
                $this->units_data[$unit->unit] = [
                    'luas_tanah' => $unit->luas_tanah,
                    'luas_bangunan' => $unit->luas_bangunan,
                    'jumlah_rombel' => $unit->jumlah_rombel,
                ];
            }
        }

        // Store existing file paths
        $fileFields = [
            'status_kepemilikan_tanah',
            'sertifikat_nsp',
            'rk_anggaran',
            'silabus_rpp',
            'peraturan_kepegawaian',
            'file_lk_iapm',
            'laporan_tahunan',
            'dok_profil',
            'dok_nsp',
            'dok_renstra',
            'dok_rk_anggaran',
            'dok_kurikulum',
            'dok_silabus_rpp',
            'dok_kepengasuhan',
            'dok_peraturan_kepegawaian',
            'dok_sarpras',
            'dok_laporan_tahunan',
            'dok_sop'
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
            'layanan_satuan_pendidikan' => 'array',

            // Dynamic units validation
            'units_data' => 'array',
            'units_data.*.jumlah_rombel' => 'required_with:units_data|integer|min:0',
            'units_data.*.luas_tanah' => 'nullable|string',
            'units_data.*.luas_bangunan' => 'nullable|string',
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
            'layanan_satuan_pendidikan' => $this->layanan_satuan_pendidikan,
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

        // Save Units Data
        $currentUnits = $this->layanan_satuan_pendidikan;

        // delete units not in selected list
        $this->pesantren->units()->whereNotIn('unit', $currentUnits)->delete();

        // update or create selected units
        foreach ($currentUnits as $unitName) {
            $this->pesantren->units()->updateOrCreate(
                ['unit' => $unitName],
                [
                    'luas_tanah' => $this->units_data[$unitName]['luas_tanah'] ?? null,
                    'luas_bangunan' => $this->units_data[$unitName]['luas_bangunan'] ?? null,
                    'jumlah_rombel' => $this->units_data[$unitName]['jumlah_rombel'] ?? 0,
                ]
            );
        }

        $this->dispatch(
            'notification-received',
            type: 'success',
            title: 'Berhasil!',
            message: 'Profil pesantren berhasil diperbarui.'
        );
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
                                <div class="mt-2 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-4">
                                    @foreach(['sd', 'mi', 'smp', 'mts', 'sma', 'ma', 'smk'] as $item)
                                    <label class="inline-flex items-center p-2 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors {{ in_array($item, (array)$layanan_satuan_pendidikan) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200' }}">
                                        <input type="checkbox" wire:model.live="layanan_satuan_pendidikan" value="{{ $item }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 uppercase font-medium text-gray-700">{{ $item }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>


                            @if(count($layanan_satuan_pendidikan) > 0)
                            <div class="md:col-span-3 mt-4 space-y-4">
                                <h4 class="font-bold text-gray-700 border-b pb-2">Detail Luas Tanah & Bangunan per Unit</h4>
                                @foreach($layanan_satuan_pendidikan as $unit)
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 border p-4 rounded-lg bg-gray-50 relative">
                                    <div class="absolute -top-3 left-4 bg-indigo-100 text-indigo-800 text-xs font-bold px-2 py-1 rounded border border-indigo-200 uppercase">
                                        UNIT {{ $unit }}
                                    </div>
                                    <div class="mt-2">
                                        <x-input-label for="units_data.{{ $unit }}.jumlah_rombel" value="Jumlah Rombel" />
                                        <x-text-input wire:model="units_data.{{ $unit }}.jumlah_rombel" type="number" class="mt-1 block w-full" placeholder="0" />
                                    </div>
                                    <div class="mt-2">
                                        <x-input-label for="units_data.{{ $unit }}.luas_tanah" value="Luas Tanah (m²)" />
                                        <x-text-input wire:model="units_data.{{ $unit }}.luas_tanah" type="text" class="mt-1 block w-full" placeholder="0" />
                                    </div>
                                    <div class="mt-2">
                                        <x-input-label for="units_data.{{ $unit }}.luas_bangunan" value="Luas Bangunan (m²)" />
                                        <x-text-input wire:model="units_data.{{ $unit }}.luas_bangunan" type="text" class="mt-1 block w-full" placeholder="0" />
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
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
                        <x-primary-button wire:loading.attr="disabled">
                            <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="save">{{ __('Simpan Perubahan') }}</span>
                            <span wire:loading wire:target="save">{{ __('Memproses...') }}</span>
                        </x-primary-button>
                    </div>


                </form>
            </div>
        </div>
    </div>
</div>