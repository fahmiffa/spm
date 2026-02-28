<?php

use App\Models\Ipm;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public $ipm;

    // Form fields (files)
    public $nsp_file_upload;
    public $lulus_santri_file_upload;
    public $kurikulum_file_upload;
    public $buku_ajar_file_upload;

    // Existing file paths
    public $existing_files = [];

    public function mount()
    {
        if (!auth()->user()->isPesantren()) {
            abort(403);
        }

        $this->ipm = Ipm::firstOrCreate(['user_id' => auth()->id()]);

        $this->existing_files = [
            'nsp_file' => $this->ipm->nsp_file,
            'lulus_santri_file' => $this->ipm->lulus_santri_file,
            'kurikulum_file' => $this->ipm->kurikulum_file,
            'buku_ajar_file' => $this->ipm->buku_ajar_file,
        ];
    }

    protected function messages()
    {
        return [
            'required' => ':attribute wajib diisi.',
            'mimes' => ':attribute harus berformat PDF.',
            'max' => 'Ukuran :attribute tidak boleh lebih dari :max KB (2MB).',
            'uploaded' => ':attribute gagal diunggah. Kemungkinan file terlalu besar (Max 2MB) atau koneksi terputus.',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'nsp_file_upload' => 'File NSP',
            'lulus_santri_file_upload' => 'File Lulus Santri',
            'kurikulum_file_upload' => 'File Kurikulum',
            'buku_ajar_file_upload' => 'File Buku Ajar',
        ];
    }

    public function save()
    {
        if (auth()->user()->pesantren->is_locked) {
            $this->js("Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak',
                text: 'Data terkunci karena sedang dalam proses akreditasi.',
                confirmButtonColor: '#d33'
            })");
            return;
        }
        $this->validate([
            'nsp_file_upload' => 'nullable|mimes:pdf|max:2048',
            'lulus_santri_file_upload' => 'nullable|mimes:pdf|max:2048',
            'kurikulum_file_upload' => 'nullable|mimes:pdf|max:2048',
            'buku_ajar_file_upload' => 'nullable|mimes:pdf|max:2048',
        ]);

        $data = [];
        $fileFields = [
            'nsp_file' => 'nsp_file_upload',
            'lulus_santri_file' => 'lulus_santri_file_upload',
            'kurikulum_file' => 'kurikulum_file_upload',
            'buku_ajar_file' => 'buku_ajar_file_upload',
        ];

        foreach ($fileFields as $dbField => $property) {
            if ($this->$property) {
                // Delete old file if exists
                if ($this->ipm->$dbField) {
                    Storage::disk('public')->delete($this->ipm->$dbField);
                }
                $data[$dbField] = $this->$property->store('ipm_docs', 'public');
                $this->existing_files[$dbField] = $data[$dbField];
            }
        }

        if (!empty($data)) {
            $this->ipm->update($data);
        }

        $this->dispatch(
            'notification-received',
            type: 'success',
            title: 'Berhasil!',
            message: 'Data IPM berhasil diperbarui.'
        );
    }
}; ?>

<div class="py-12">
    <x-slot name="header">{{ __('Indek Pemenuhan Mutlak (IPM)') }}</x-slot>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if(auth()->user()->pesantren->is_locked)
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        <span class="font-bold">DATA TERKUNCI!</span> Data IPM tidak dapat diubah karena sedang dalam proses akreditasi.
                    </p>
                </div>
            </div>
        </div>
        @endif
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <header class="mb-6">
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ __('Indek Pemenuhan Mutlak (IPM)') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('Silakan unggah dokumen pendukung untuk setiap kriteria pemenuhan mutlak di bawah ini.') }}
                    </p>
                </header>

                <form x-on:submit.prevent="
                    Swal.fire({
                        title: 'Apakah Anda yakin data yang Anda input sudah benar dan lengkap?',
                        showCancelButton: true,
                        confirmButtonColor: '#22c55e',
                        cancelButtonColor: '#ef4444',
                        confirmButtonText: 'Ya, Simpan',
                        cancelButtonText: 'Periksa Lagi',
                        icon: 'question',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $wire.save();
                        }
                    })
                " class="space-y-6" x-data="fileManagement">
                    <p class="text-xs text-red-600 italic font-medium">* Format Berkas wajib PDF dan Ukuran Maksimal 2MB</p>
                    <!-- Kriteria 1 -->
                    <div class="p-4 border rounded-lg bg-gray-50">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            1. Pesantren telah memiliki izin operasional Kementerian Agama (Nomor Statistik Pesantren – NSP) yang dibuktikan dengan mengunggah dalam SPM-PesantrenMu.
                        </label>
                        <input type="file"
                            accept="application/pdf"
                            x-on:change="if(validate($event)) { $wire.upload('nsp_file_upload', $event.target.files[0]) }"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        <x-input-error :messages="$errors->get('nsp_file_upload')" class="mt-2" />
                        @if($existing_files['nsp_file'])
                        <div class="mt-2 text-xs text-green-600">
                            Berkas terunggah: <a href="{{ Storage::url($existing_files['nsp_file']) }}" target="_blank" class="underline font-bold">Lihat Dokumen</a>
                        </div>
                        @endif
                    </div>

                    <!-- Kriteria 2 -->
                    <div class="p-4 border rounded-lg bg-gray-50">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            2. Pesantren pernah meluluskan santri dan/atau memiliki santri kelas akhir.
                        </label>
                        <input type="file"
                            accept="application/pdf"
                            x-on:change="if(validate($event)) { $wire.upload('lulus_santri_file_upload', $event.target.files[0]) }"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        <x-input-error :messages="$errors->get('lulus_santri_file_upload')" class="mt-2" />
                        @if($existing_files['lulus_santri_file'])
                        <div class="mt-2 text-xs text-green-600">
                            Berkas terunggah: <a href="{{ Storage::url($existing_files['lulus_santri_file']) }}" target="_blank" class="underline font-bold">Lihat Dokumen</a>
                        </div>
                        @endif
                    </div>

                    <!-- Kriteria 3 -->
                    <div class="p-4 border rounded-lg bg-gray-50">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            3. Pesantren memiliki dan menyelenggarakan kurikulum Dirasah Islamiyah sesuai standar kurikulum LP2 PPM di seluruh kelas.
                        </label>
                        <input type="file"
                            accept="application/pdf"
                            x-on:change="if(validate($event)) { $wire.upload('kurikulum_file_upload', $event.target.files[0]) }"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        <x-input-error :messages="$errors->get('kurikulum_file_upload')" class="mt-2" />
                        @if($existing_files['kurikulum_file'])
                        <div class="mt-2 text-xs text-green-600">
                            Berkas terunggah: <a href="{{ Storage::url($existing_files['kurikulum_file']) }}" target="_blank" class="underline font-bold">Lihat Dokumen</a>
                        </div>
                        @endif
                    </div>

                    <!-- Kriteria 4 -->
                    <div class="p-4 border rounded-lg bg-gray-50">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            4. Pesantren menggunakan buku ajar Dirasah Islamiyah terbitan LP2 PPM.
                        </label>
                        <input type="file"
                            accept="application/pdf"
                            x-on:change="if(validate($event)) { $wire.upload('buku_ajar_file_upload', $event.target.files[0]) }"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        <x-input-error :messages="$errors->get('buku_ajar_file_upload')" class="mt-2" />
                        @if($existing_files['buku_ajar_file'])
                        <div class="mt-2 text-xs text-green-600">
                            Berkas terunggah: <a href="{{ Storage::url($existing_files['buku_ajar_file']) }}" target="_blank" class="underline font-bold">Lihat Dokumen</a>
                        </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-4">
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