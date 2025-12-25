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

    public function save()
    {
        $this->validate([
            'nsp_file_upload' => 'nullable|file|max:2048',
            'lulus_santri_file_upload' => 'nullable|file|max:2048',
            'kurikulum_file_upload' => 'nullable|file|max:2048',
            'buku_ajar_file_upload' => 'nullable|file|max:2048',
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

        session()->flash('status', 'Data IPM berhasil diperbarui.');
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

                <form wire:submit="save" class="space-y-6">
                    <!-- Kriteria 1 -->
                    <div class="p-4 border rounded-lg bg-gray-50">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            1. Pesantren telah memiliki izin operasional Kementerian Agama (Nomor Statistik Pesantren – NSP) yang dibuktikan dengan mengunggah dalam SPM-PesantrenMu.
                        </label>
                        <input type="file" wire:model="nsp_file_upload" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
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
                        <input type="file" wire:model="lulus_santri_file_upload" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
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
                        <input type="file" wire:model="kurikulum_file_upload" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
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
                        <input type="file" wire:model="buku_ajar_file_upload" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        <x-input-error :messages="$errors->get('buku_ajar_file_upload')" class="mt-2" />
                        @if($existing_files['buku_ajar_file'])
                            <div class="mt-2 text-xs text-green-600">
                                Berkas terunggah: <a href="{{ Storage::url($existing_files['buku_ajar_file']) }}" target="_blank" class="underline font-bold">Lihat Dokumen</a>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>
                            {{ __('Simpan Perubahan') }}
                        </x-primary-button>

                        @if (session('status'))
                            <p
                                x-data="{ show: true }"
                                x-show="show"
                                x-transition
                                x-init="setTimeout(() => show = false, 2000)"
                                class="text-sm text-green-600 font-medium"
                            >{{ session('status') }}</p>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
