<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public $title = '';
    public $status = 1;
    public $is_pesantren = false;
    public $is_asesor = false;
    public $file;
    public $documentId = null;
    public $currentFile = null;


    public $searchUser = '';

    public function mount()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    public function getDocumentsProperty()
    {
        return Document::latest()->get();
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['title', 'status', 'is_pesantren', 'is_asesor', 'file', 'documentId', 'currentFile']);
        $this->status = 1;
        $this->dispatch('open-modal', 'document-modal');
    }

    public function edit($id)
    {
        $this->resetValidation();
        $doc = Document::findOrFail($id);
        $this->documentId = $doc->id;
        $this->title = $doc->title;
        $this->status = $doc->status;
        $this->is_pesantren = (bool) $doc->is_pesantren;
        $this->is_asesor = (bool) $doc->is_asesor;
        $this->currentFile = $doc->file_path;
        $this->dispatch('open-modal', 'document-modal');
    }

    public function save()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'status' => 'required|integer',
            'is_pesantren' => 'boolean',
            'is_asesor' => 'boolean',
        ];

        if (!$this->documentId) {
            $rules['file'] = 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:10240';
        } else {
            $rules['file'] = 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240';
        }

        $this->validate($rules);

        $data = [
            'title' => $this->title,
            'status' => $this->status,
            'is_pesantren' => $this->is_pesantren,
            'is_asesor' => $this->is_asesor,
        ];

        if ($this->file) {
            if ($this->documentId) {
                $doc = Document::findOrFail($this->documentId);
                if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                    Storage::disk('public')->delete($doc->file_path);
                }
            }
            $path = $this->file->store('documents', 'public');
            $data['file_path'] = $path;
        }

        if ($this->documentId) {
            Document::where('id', $this->documentId)->update($data);
        } else {
            Document::create($data);
        }

        $this->dispatch('close-modal', 'document-modal');
        $this->dispatch('notification-received', type: 'success', title: 'Berhasil', message: 'Dokumen berhasil disimpan.');
        $this->reset(['title', 'status', 'is_pesantren', 'is_asesor', 'file', 'documentId', 'currentFile']);
    }

    public function delete($id)
    {
        $doc = Document::findOrFail($id);
        if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }
        $doc->delete();
        $this->dispatch('notification-received', type: 'success', title: 'Berhasil', message: 'Dokumen berhasil dihapus.');
    }
}; ?>

<div class="py-12">
    <x-slot name="header">{{ __('Master Dokumen') }}</x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Daftar Dokumen</h2>
                    <button wire:click="openModal" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Tambah Dokumen
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left w-10">No</th>
                                <th class="py-3 px-6 text-left">Dokumen</th>
                                <th class="py-3 px-6 text-center">Akses</th>
                                <th class="py-3 px-6 text-center">Status Dokumen</th>
                                <th class="py-3 px-6 text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            @forelse ($this->documents as $index => $doc)
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-3 px-6 text-left whitespace-nowrap">{{ $index + 1 }}</td>
                                <td class="py-3 px-6 text-left font-medium text-gray-800">
                                    <div>{{ $doc->title }}</div>
                                </td>
                                <td class="py-5 px-6 text-center">
                                    <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                        @if($doc->is_pesantren)
                                        <span class="bg-blue-500 text-white px-2.5 py-1 rounded-md text-[9px] font-black uppercase tracking-wider shadow-sm">Pesantren</span>
                                        @endif
                                        @if($doc->is_asesor)
                                        <span class="bg-indigo-600 text-white px-2.5 py-1 rounded-md text-[9px] font-black uppercase tracking-wider shadow-sm">Asesor</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-5 px-4 text-center">
                                    @if($doc->status == 1)
                                    <span class="bg-emerald-50 text-emerald-600 px-2.5 py-0.5 rounded text-[10px] font-bold border border-emerald-100 uppercase">Aktif</span>
                                    @else
                                    <span class="bg-slate-50 text-slate-400 px-2.5 py-0.5 rounded text-[10px] font-bold border border-slate-100 uppercase">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <div class="flex item-center justify-center gap-2">
                                        <button wire:click="edit({{ $doc->id }})" class="w-8 h-8 rounded bg-yellow-100 text-yellow-600 hover:bg-yellow-200 flex items-center justify-center transition" title="Edit">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button x-on:click="
                                            Swal.fire({
                                                title: 'Apakah Anda yakin?',
                                                text: 'Dokumen ini akan dihapus secara permanen!',
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#ef4444',
                                                cancelButtonColor: '#6b7280',
                                                confirmButtonText: 'Ya, Hapus!',
                                                cancelButtonText: 'Batal'
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    $wire.delete({{ $doc->id }})
                                                }
                                            })
                                        "
                                            class="w-8 h-8 rounded bg-red-100 text-red-600 hover:bg-red-200 flex items-center justify-center transition" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-16 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-10 h-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-sm text-gray-400 font-medium">Belum ada dokumen ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <x-modal name="document-modal" maxWidth="lg">
        <form wire:submit="save" class="p-8">
            <h2 class="text-xl font-extrabold text-gray-900 mb-6">
                {{ $documentId ? 'Edit Dokumen' : 'Tambah Dokumen Baru' }}
            </h2>

            <div class="space-y-6">
                <!-- Nama Dokumen -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Nama Dokumen</label>
                    <input type="text" wire:model="title" required placeholder="Contoh: Panduan Assessment"
                        class="w-full text-xs border-gray-100 rounded-lg bg-gray-50/50 py-2.5 focus:ring-1 focus:ring-green-500 focus:border-green-500 placeholder-gray-400">
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <!-- Dokumen Saat Ini (If Editing) -->
                @if($documentId && $currentFile)
                <div>
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Dokumen Saat Ini</label>
                    <div class="flex items-center gap-3 p-2.5 bg-gray-50/50 border border-gray-100 rounded-lg">
                        <div class="p-1.5 bg-white rounded border border-gray-100 shadow-sm">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m1 3h1m1 3h-2" />
                            </svg>
                        </div>
                        <span class="text-[11px] font-bold text-gray-500 truncate flex-1 uppercase tracking-tight">{{ basename($currentFile) }}</span>
                    </div>
                </div>
                @endif

                <!-- Upload File -->
                <div x-data="{ isUploading: false, progress: 0 }"
                    x-on:livewire-upload-start="isUploading = true"
                    x-on:livewire-upload-finish="isUploading = false"
                    x-on:livewire-upload-error="isUploading = false"
                    x-on:livewire-upload-progress="progress = $event.detail.progress">

                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Upload File</label>
                    <div class="relative group">
                        <input type="file" wire:model="file" id="file" accept=".pdf,.doc,.docx,.xls,.xlsx" class="hidden">
                        <label for="file" class="flex items-center gap-3 p-2.5 bg-white border border-gray-100 rounded-lg hover:border-indigo-200 hover:bg-indigo-50/30 transition-all cursor-pointer shadow-sm group">
                            <div class="p-1.5 bg-slate-50 rounded border border-gray-100 group-hover:bg-white group-hover:text-indigo-600 transition-colors">
                                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            <span class="text-[11px] font-bold text-gray-400 group-hover:text-indigo-700 transition-colors">{{ $file ? $file->getClientOriginalName() : 'No File Choosen' }}</span>
                        </label>
                    </div>
                    <div class="mt-2 text-[10px] text-gray-400 font-medium tracking-tight">Format yang diizinkan: .doc, .docx (Max 10MB)</div>

                    <div x-show="isUploading" class="mt-2 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500 transition-all duration-300" :style="'width: ' + progress + '%'"></div>
                    </div>

                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-8 pt-4">
                    <!-- Hak Akses -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-4">Hak Akses</label>
                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <div class="relative flex items-center">
                                    <input type="checkbox" wire:model="is_pesantren" class="peer h-5 w-5 cursor-pointer appearance-none rounded border border-gray-200 bg-white checked:bg-[#1e3a5f] transition-all shadow-sm">
                                    <svg class="absolute w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 pointer-events-none left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-[11px] font-bold text-gray-600 transition-colors group-hover:text-[#1e3a5f]">Pesantren</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <div class="relative flex items-center">
                                    <input type="checkbox" wire:model="is_asesor" class="peer h-5 w-5 cursor-pointer appearance-none rounded border border-gray-200 bg-white checked:bg-[#1e3a5f] transition-all shadow-sm">
                                    <svg class="absolute w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 pointer-events-none left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-[11px] font-bold text-gray-600 transition-colors group-hover:text-[#1e3a5f]">Asesor</span>
                            </label>
                        </div>
                    </div>

                    <!-- Status Dokumen -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-4">Status Dokuemn</label>
                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <div class="relative flex items-center">
                                    <input type="radio" wire:model="status" value="1" class="peer h-5 w-5 cursor-pointer appearance-none rounded-full border border-gray-200 bg-white checked:bg-[#1e3a5f] transition-all shadow-sm border-2">
                                    <div class="absolute w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 pointer-events-none left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2"></div>
                                </div>
                                <span class="text-[11px] font-bold text-gray-600 group-hover:text-[#1e3a5f] transition-colors">Aktif</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <div class="relative flex items-center">
                                    <input type="radio" wire:model="status" value="0" class="peer h-5 w-5 cursor-pointer appearance-none rounded-full border border-gray-200 bg-white checked:bg-slate-300 transition-all shadow-sm border-2">
                                    <div class="absolute w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 pointer-events-none left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2"></div>
                                </div>
                                <span class="text-[11px] font-bold text-gray-600 group-hover:text-slate-500 transition-colors">Non Aktif</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-10 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')" class="px-6 py-2.5 rounded-lg text-[11px] font-bold text-gray-400 bg-gray-50 hover:bg-gray-100 transition-colors">
                    Batal
                </button>
                <button type="submit" wire:loading.attr="disabled" class="bg-[#1e3a5f] text-white px-8 py-2.5 rounded-lg text-[11px] font-bold hover:bg-[#162d4a] transition-colors shadow-sm disabled:opacity-50">
                    <span wire:loading.remove>Simpan</span>
                    <span wire:loading>Menyimpan...</span>
                </button>
            </div>
        </form>
    </x-modal>
</div>