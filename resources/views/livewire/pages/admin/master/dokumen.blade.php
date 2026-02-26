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
    public $type = '';
    public $file;
    public $selectedUsers = [];
    public $documentId = null;

    // Filter options
    public $roleFilter = ''; // Filter users by role in modal
    public $searchUser = '';

    public function mount()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    public function getDocumentsProperty()
    {
        return Document::with('users')->latest()->get();
    }

    public function getUsersProperty()
    {
        $query = User::where('role_id', '!=', 1); // Exclude admin

        if ($this->roleFilter) {
            $query->where('role_id', $this->roleFilter);
        }

        if ($this->searchUser) {
            $query->where('name', 'like', '%' . $this->searchUser . '%');
        }

        return $query->get();
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['title', 'type', 'file', 'selectedUsers', 'documentId', 'roleFilter', 'searchUser']);
        $this->dispatch('open-modal', 'document-modal');
    }

    public function edit($id)
    {
        $this->resetValidation();
        $doc = Document::with('users')->findOrFail($id);
        $this->documentId = $doc->id;
        $this->title = $doc->title;
        $this->type = $doc->type;
        $this->selectedUsers = $doc->users->pluck('id')->map(fn($id) => (string) $id)->toArray(); // Ensure string for checkboxes
        $this->roleFilter = '';
        $this->searchUser = '';
        $this->dispatch('open-modal', 'document-modal');
    }

    public function save()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'selectedUsers' => 'required|array|min:1',
        ];

        if (!$this->documentId) {
            $rules['file'] = 'required|file|mimes:pdf,doc,docx|max:10240';
        } else {
            $rules['file'] = 'nullable|file|mimes:pdf,doc,docx|max:10240';
        }

        $this->validate($rules);

        if ($this->documentId) {
            $doc = Document::findOrFail($this->documentId);
            $updateData = [
                'title' => $this->title,
                'type' => $this->type,
            ];
            if ($this->file) {
                if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                    Storage::disk('public')->delete($doc->file_path);
                }
                $path = $this->file->store('documents', 'public');
                $updateData['file_path'] = $path;
            }
            $doc->update($updateData);
        } else {
            $path = $this->file->store('documents', 'public');
            $doc = Document::create([
                'title' => $this->title,
                'type' => $this->type,
                'file_path' => $path,
            ]);
        }

        $doc->users()->sync($this->selectedUsers);

        $this->dispatch('close-modal', 'document-modal');
        $this->dispatch('notification-received', type: 'success', title: 'Berhasil', message: 'Dokumen berhasil disimpan.');
        $this->reset(['title', 'type', 'file', 'selectedUsers', 'documentId']);
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

    public function toggleSelectAll()
    {
        // Toggle selection of currently filtered users
        $filteredIds = $this->users->pluck('id')->map(fn($id) => (string) $id)->toArray();

        // If all filtered are selected, unselect them. Else, select them.
        $allSelected = !array_diff($filteredIds, $this->selectedUsers);

        if ($allSelected) {
            $this->selectedUsers = array_diff($this->selectedUsers, $filteredIds);
        } else {
            $this->selectedUsers = array_unique(array_merge($this->selectedUsers, $filteredIds));
        }
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
                                <th class="py-3 px-6 text-center">Berkas</th>
                                <th class="py-3 px-6 text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            @forelse ($this->documents as $index => $doc)
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-3 px-6 text-left whitespace-nowrap">{{ $index + 1 }}</td>
                                <td class="py-3 px-6 text-left font-medium text-gray-800">
                                    <div>{{ $doc->title }}</div>
                                    <div class="mt-1">
                                        <span class="text-[10px] px-1.5 py-0.5 rounded font-bold uppercase {{ $doc->type == 'ipam' ? 'bg-purple-100 text-purple-700' : 'bg-orange-100 text-orange-700' }}">
                                            {{ str_replace('_', ' ', $doc->type) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <div class="flex flex-wrap justify-center gap-1 min-w-[150px]">
                                        @foreach($doc->users as $u)
                                        <span class="bg-blue-50 text-blue-700 text-[10px] font-bold px-1.5 py-0.5 rounded border border-blue-100 whitespace-nowrap">
                                            {{ $u->name }}
                                        </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 font-bold flex justify-center items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        {{ strtoupper(pathinfo($doc->file_path, PATHINFO_EXTENSION)) }}
                                    </a>
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
                                <td colspan="6" class="py-8 text-center text-gray-500 italic">Belum ada dokumen yang diunggah.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <x-modal name="document-modal" focusable>
        <form wire:submit="save" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                {{ $documentId ? 'Edit Dokumen' : 'Tambah Dokumen Baru' }}
            </h2>

            <div class="space-y-4">
                <!-- Title -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="title" value="Judul Dokumen" />
                        <x-text-input wire:model="title" id="title" class="block w-full mt-1" type="text" required placeholder="Contoh: Panduan V2" />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="type" value="Tipe Dokumen" />
                        <select wire:model="type" id="type" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">Pilih Tipe</option>
                            <option value="ipam">IPAM</option>
                            <option value="kartu_kendali">Kartu Kendali</option>
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-2" />
                    </div>
                </div>

                <!-- File Upload -->
                <div>
                    <x-input-label for="file" value="File (PDF, DOC, DOCX)" />
                    <input type="file" wire:model="file" id="file" accept=".pdf,.doc,.docx" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer mt-1 border border-gray-300 rounded-md" />
                    <div class="mt-1 text-xs text-gray-500">Maksimal 10MB. Format .pdf, .doc, .docx</div>
                    <div wire:loading wire:target="file" class="text-xs text-indigo-600 mt-1 font-bold">Sedang mengupload...</div>
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    @if($documentId && !$file)
                    <div class="text-xs text-green-600 mt-1">File saat ini tersimpan. Upload baru untuk mengganti.</div>
                    @endif
                </div>

                <!-- User Selection -->
                <div class="border-t pt-4 mt-4">
                    <div class="flex justify-between items-center mb-2">
                        <x-input-label value="Berikan Akses Kepada" />
                        <div class="flex gap-2">
                            <select wire:model.live="roleFilter" class="text-xs border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-1">
                                <option value="">Semua Role</option>
                                <option value="3">Pesantren</option>
                                <option value="2">Asesor</option>
                            </select>
                            <input type="text" wire:model.live.debounce.300ms="searchUser" placeholder="Cari nama..." class="text-xs border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-1 w-32">
                        </div>
                    </div>

                    <div class="border rounded-md max-h-48 overflow-y-auto p-2 bg-gray-50 text-sm">
                        <div class="flex items-center mb-2 px-2 pb-2 border-b">
                            <input type="checkbox" wire:click="toggleSelectAll" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-xs font-bold text-gray-700">Pilih Semua (sesuai filter)</span>
                        </div>

                        <div class="space-y-1">
                            @foreach($this->users as $user)
                            <label class="flex items-center px-2 py-1 hover:bg-gray-100 rounded cursor-pointer">
                                <input type="checkbox" wire:model="selectedUsers" value="{{ $user->id }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 flex-1">
                                    <span class="font-medium text-gray-800">{{ $user->name }}</span>
                                    <span class="text-xs text-gray-500 ml-1">({{ $user->role->name ?? '-' }})</span>
                                </span>
                            </label>
                            @endforeach
                            @if($this->users->isEmpty())
                            <div class="text-center py-4 text-gray-400 text-xs">Tidak ada user ditemukan.</div>
                            @endif
                        </div>
                    </div>
                    <div class="mt-1 text-xs text-gray-500 text-right">
                        {{ count($selectedUsers) }} user dipilih
                    </div>
                    <x-input-error :messages="$errors->get('selectedUsers')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-primary-button wire:loading.attr="disabled" wire:target="save, file">
                    <span wire:loading.remove wire:target="save, file">Simpan</span>
                    <span wire:loading wire:target="save, file">Menyimpan...</span>
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>