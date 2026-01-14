<?php

use App\Models\Asesor;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public $asesor;

    // Identitas Asesor
    public $nama_dengan_gelar;
    public $nama_tanpa_gelar;
    public $nbm_nia;
    public $whatsapp;
    public $nik;
    public $tempat_lahir;
    public $tanggal_lahir;
    public $unit_kerja;
    public $jabatan_utama;
    public $jenis_kelamin;
    public $alamat_kantor;
    public $alamat_rumah;
    public $email_pribadi;

    // Data Pesantren
    public $layanan_satuan_pendidikan = [];
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

    // Dokumen (Uploaded files)
    public $ktp_file_upload;
    public $ijazah_file_upload;
    public $kartu_nbm_file_upload;

    // Existing file paths
    public $existing_files = [];

    public function mount()
    {
        if (!auth()->user()->isAsesor()) {
            abort(403);
        }

        $this->asesor = Asesor::firstOrCreate(
            ['user_id' => auth()->id()],
            [
                'nama_dengan_gelar' => auth()->user()->name,
                'nama_tanpa_gelar' => auth()->user()->name,
            ]
        );

        $this->nama_dengan_gelar = $this->asesor->nama_dengan_gelar;
        $this->nama_tanpa_gelar = $this->asesor->nama_tanpa_gelar;
        $this->nbm_nia = $this->asesor->nbm_nia;
        $this->whatsapp = $this->asesor->whatsapp;
        $this->nik = $this->asesor->nik;
        $this->tempat_lahir = $this->asesor->tempat_lahir;
        $this->tanggal_lahir = $this->asesor->tanggal_lahir;
        $this->unit_kerja = $this->asesor->unit_kerja;
        $this->jabatan_utama = $this->asesor->jabatan_utama;
        $this->jenis_kelamin = $this->asesor->jenis_kelamin;
        $this->alamat_kantor = $this->asesor->alamat_kantor;
        $this->alamat_rumah = $this->asesor->alamat_rumah;
        $this->email_pribadi = $this->asesor->email_pribadi;

        $this->layanan_satuan_pendidikan = is_array($this->asesor->layanan_satuan_pendidikan) ? $this->asesor->layanan_satuan_pendidikan : [];
        $this->rombel_sd = $this->asesor->rombel_sd;
        $this->rombel_mi = $this->asesor->rombel_mi;
        $this->rombel_smp = $this->asesor->rombel_smp;
        $this->rombel_mts = $this->asesor->rombel_mts;
        $this->rombel_sma = $this->asesor->rombel_sma;
        $this->rombel_ma = $this->asesor->rombel_ma;
        $this->rombel_smk = $this->asesor->rombel_smk;
        $this->rombel_spm = $this->asesor->rombel_spm;
        $this->luas_tanah = $this->asesor->luas_tanah;
        $this->luas_bangunan = $this->asesor->luas_bangunan;

        $this->existing_files = [
            'ktp_file' => $this->asesor->ktp_file,
            'ijazah_file' => $this->asesor->ijazah_file,
            'kartu_nbm_file' => $this->asesor->kartu_nbm_file,
        ];
    }

    public function save()
    {
        $this->validate([
            'nama_dengan_gelar' => 'required|string|max:255',
            'nama_tanpa_gelar' => 'required|string|max:255',
            'email_pribadi' => 'nullable|email',
        ]);

        $data = [
            'nama_dengan_gelar' => $this->nama_dengan_gelar,
            'nama_tanpa_gelar' => $this->nama_tanpa_gelar,
            'nbm_nia' => $this->nbm_nia,
            'whatsapp' => $this->whatsapp,
            'nik' => $this->nik,
            'tempat_lahir' => $this->tempat_lahir,
            'tanggal_lahir' => $this->tanggal_lahir,
            'unit_kerja' => $this->unit_kerja,
            'jabatan_utama' => $this->jabatan_utama,
            'jenis_kelamin' => $this->jenis_kelamin,
            'alamat_kantor' => $this->alamat_kantor,
            'alamat_rumah' => $this->alamat_rumah,
            'email_pribadi' => $this->email_pribadi,
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

        $fileFields = [
            'ktp_file' => 'ktp_file_upload',
            'ijazah_file' => 'ijazah_file_upload',
            'kartu_nbm_file' => 'kartu_nbm_file_upload',
        ];

        foreach ($fileFields as $dbField => $property) {
            if ($this->$property) {
                if ($this->asesor->$dbField) {
                    Storage::disk('public')->delete($this->asesor->$dbField);
                }
                $data[$dbField] = $this->$property->store('asesor_docs', 'public');
                $this->existing_files[$dbField] = $data[$dbField];
            }
        }

        $this->asesor->update($data);

        session()->flash('status', 'Profil asesor berhasil diperbarui.');
    }
}; ?>


<x-slot name="header">{{ __('Profil') }}</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 border-b border-gray-200">

                <form wire:submit="save">
                    <!-- Section: IDENTITAS ASESOR -->
                    <div class="mb-8 p-4 border rounded-lg bg-gray-50">
                        <h3 class="text-lg font-bold mb-4 text-indigo-600 border-b pb-2">IDENTITAS ASESOR</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="nama_dengan_gelar" value="Nama Lengkap Asesor (dengan Gelar)" />
                                <x-text-input wire:model="nama_dengan_gelar" id="nama_dengan_gelar" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="nama_tanpa_gelar" value="Nama Lengkap Asesor (Tanpa Gelar)" />
                                <x-text-input wire:model="nama_tanpa_gelar" id="nama_tanpa_gelar" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="nbm_nia" value="NBM / NIA-PM" />
                                <x-text-input wire:model="nbm_nia" id="nbm_nia" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="whatsapp" value="Nomor WhatsApp" />
                                <x-text-input wire:model="whatsapp" id="whatsapp" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="nik" value="NIK / Nomor KTP" />
                                <x-text-input wire:model="nik" id="nik" type="text" class="mt-1 block w-full" />
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <x-input-label for="tempat_lahir" value="Tempat Lahir" />
                                    <x-text-input wire:model="tempat_lahir" id="tempat_lahir" type="text" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <x-input-label for="tanggal_lahir" value="Tanggal Lahir" />
                                    <x-text-input wire:model="tanggal_lahir" id="tanggal_lahir" type="date" class="mt-1 block w-full" />
                                </div>
                            </div>
                            <div>
                                <x-input-label for="unit_kerja" value="Unit Tempat Kerja" />
                                <x-text-input wire:model="unit_kerja" id="unit_kerja" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="jabatan_utama" value="Jabatan Utama" />
                                <x-text-input wire:model="jabatan_utama" id="jabatan_utama" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="jenis_kelamin" value="Jenis Kelamin" />
                                <select wire:model="jenis_kelamin" id="jenis_kelamin" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="Laki-Laki">Laki-Laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="email_pribadi" value="Email Pribadi" />
                                <x-text-input wire:model="email_pribadi" id="email_pribadi" type="email" class="mt-1 block w-full" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="alamat_kantor" value="Alamat Kantor" />
                                <textarea wire:model="alamat_kantor" id="alamat_kantor" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="alamat_rumah" value="Alamat Rumah" />
                                <textarea wire:model="alamat_rumah" id="alamat_rumah" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section: DATA PESANTREN -->
                    <div class="mb-8 p-4 border rounded-lg bg-gray-50">
                        <h3 class="text-lg font-bold mb-4 text-indigo-600 border-b pb-2">DATA PESANTREN</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-3">
                                <x-input-label for="layanan_satuan_pendidikan" value="Layanan Satuan Pendidikan yang Dimiliki" />
                                <div class="mt-2 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                    @foreach(['sd', 'smp', 'mi', 'sma', 'ma', 'smk'] as $item)
                                    <label class="inline-flex items-center p-2 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors {{ in_array($item, (array)$layanan_satuan_pendidikan) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200' }}">
                                        <input type="checkbox" wire:model="layanan_satuan_pendidikan" value="{{ $item }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 uppercase font-medium text-gray-700">{{ $item }}</span>
                                    </label>
                                    @endforeach
                                </div>
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
                    <div class="mb-8 p-4 border rounded-lg bg-gray-50">
                        <h3 class="text-lg font-bold mb-4 text-indigo-600 border-b pb-2">DOKUMEN UTAMA</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="ktp_file_upload" value="Unggahan KTP" />
                                <input type="file" wire:model="ktp_file_upload" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                @if($existing_files['ktp_file'])
                                <div class="mt-1 text-xs text-green-600">
                                    File terunggah: <a href="{{ Storage::url($existing_files['ktp_file']) }}" target="_blank" class="underline">Lihat Dokumen</a>
                                </div>
                                @endif
                            </div>
                            <div>
                                <x-input-label for="ijazah_file_upload" value="Unggahan Ijazah Terakhir" />
                                <input type="file" wire:model="ijazah_file_upload" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                @if($existing_files['ijazah_file'])
                                <div class="mt-1 text-xs text-green-600">
                                    File terunggah: <a href="{{ Storage::url($existing_files['ijazah_file']) }}" target="_blank" class="underline">Lihat Dokumen</a>
                                </div>
                                @endif
                            </div>
                            <div>
                                <x-input-label for="kartu_nbm_file_upload" value="Unggahan Kartu NBM" />
                                <input type="file" wire:model="kartu_nbm_file_upload" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                @if($existing_files['kartu_nbm_file'])
                                <div class="mt-1 text-xs text-green-600">
                                    File terunggah: <a href="{{ Storage::url($existing_files['kartu_nbm_file']) }}" target="_blank" class="underline">Lihat Dokumen</a>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        @if (session('status'))
                        <p
                            x-data="{ show: true }"
                            x-show="show"
                            x-transition
                            x-init="setTimeout(() => show = false, 2000)"
                            class="text-sm text-green-600 font-medium">{{ session('status') }}</p>
                        @endif

                        <x-primary-button>
                            {{ __('Simpan Profil') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>