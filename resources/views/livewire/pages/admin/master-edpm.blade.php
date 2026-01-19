<?php

use App\Models\MasterEdpmKomponen;
use App\Models\MasterEdpmButir;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public $komponens;

    // Form fields for Komponen
    public $komponen_nama;
    public $komponen_id;
    public $komponen_ipr;

    // Form fields for Butir
    public $butir_id;
    public $butir_komponen_id;
    public $butir_no_sk;
    public $butir_nomor_butir;
    public $butir_pernyataan;

    public $modalTitle = '';
    public $activeModal = ''; // 'komponen' or 'butir'

    public function mount()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
        $this->loadData();
    }

    public function loadData()
    {
        $this->komponens = MasterEdpmKomponen::with('butirs')->get();
    }

    public function resetKomponenForm()
    {
        $this->komponen_nama = '';
        $this->komponen_id = null;
        $this->komponen_ipr = false;
        $this->resetErrorBag();
    }

    public function resetButirForm()
    {
        $this->butir_id = null;
        $this->butir_no_sk = '';
        $this->butir_nomor_butir = '';
        $this->butir_pernyataan = '';
        $this->resetErrorBag();
    }

    public function openKomponenModal($id = null)
    {
        $this->resetKomponenForm();
        if ($id) {
            $komponen = MasterEdpmKomponen::findOrFail($id);
            $this->komponen_id = $komponen->id;
            $this->komponen_nama = $komponen->nama;
            $this->komponen_ipr = $komponen->ipr == 1;
            $this->modalTitle = 'Edit Komponen';
        } else {
            $this->modalTitle = 'Tambah Komponen';
        }
        $this->activeModal = 'komponen';
        $this->dispatch('open-modal', 'edpm-komponen-modal');
    }

    public function saveKomponen()
    {
        $this->validate(['komponen_nama' => 'required|string|max:255']);

        MasterEdpmKomponen::updateOrCreate(
            ['id' => $this->komponen_id],
            [
                'nama' => $this->komponen_nama,
                'ipr' => $this->komponen_ipr ? 1 : 0
            ]
        );

        session()->flash('status', 'Komponen berhasil disimpan.');
        $this->loadData();
        $this->dispatch('close-modal', 'edpm-komponen-modal');
    }

    public function deleteKomponen($id)
    {
        MasterEdpmKomponen::findOrFail($id)->delete();
        $this->loadData();
        session()->flash('status', 'Komponen berhasil dihapus.');
    }

    public function openButirModal($komponenId, $butirId = null)
    {
        $this->resetButirForm();
        $this->butir_komponen_id = $komponenId;

        if ($butirId) {
            $butir = MasterEdpmButir::findOrFail($butirId);
            $this->butir_id = $butir->id;
            $this->butir_no_sk = $butir->no_sk;
            $this->butir_nomor_butir = $butir->nomor_butir;
            $this->butir_pernyataan = $butir->butir_pernyataan;
            $this->modalTitle = 'Edit Butir Pernyataan';
        } else {
            $this->modalTitle = 'Tambah Butir Pernyataan';
        }
        $this->activeModal = 'butir';
        $this->dispatch('open-modal', 'edpm-butir-modal');
    }

    public function saveButir()
    {
        $this->validate([
            // 'butir_no_sk' => 'required|string',
            'butir_nomor_butir' => 'required|string',
            'butir_pernyataan' => 'required|string',
        ]);

        MasterEdpmButir::updateOrCreate(
            ['id' => $this->butir_id],
            [
                'komponen_id' => $this->butir_komponen_id,
                'no_sk' => $this->butir_no_sk,
                'nomor_butir' => $this->butir_nomor_butir,
                'butir_pernyataan' => $this->butir_pernyataan,
            ]
        );

        session()->flash('status', 'Butir pernyataan berhasil disimpan.');
        $this->loadData();
        $this->dispatch('close-modal', 'edpm-butir-modal');
    }

    public function deleteButir($id)
    {
        MasterEdpmButir::findOrFail($id)->delete();
        $this->loadData();
        session()->flash('status', 'Butir pernyataan berhasil dihapus.');
    }
}; ?>

<div>
    <x-slot name="header">{{ __('Master Komponen') }}</x-slot>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <div class="py-12" x-data="{
        confirmDelete(id, type) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: type === 'komponen' ? 'Hapus seluruh komponen dan butir di dalamnya?' : 'Hapus butir ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (type === 'komponen') {
                        $wire.deleteKomponen(id);
                    } else {
                        $wire.deleteButir(id);
                    }
                }
            })
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <div class="mb-6 flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 border-l-4 border-indigo-500 pl-3">DAFTAR KOMPONEN & BUTIR EDPM</h3>
                    <x-primary-button wire:click="openKomponenModal()">
                        {{ __('Tambah Komponen') }}
                    </x-primary-button>
                </div>

                <div class="space-y-8">
                    @forelse ($komponens as $komponen)
                    <div class="border rounded-lg overflow-hidden shadow-sm">
                        <div class="bg-gray-100 px-4 py-3 flex justify-between items-center border-b">
                            <div class="flex items-center gap-3">
                                <h4 class="font-bold text-indigo-700 uppercase tracking-wide">{{ $komponen->nama }}</h4>
                                @if ($komponen->ipr)
                                <span class="bg-amber-100 text-amber-700 text-[10px] font-bold px-2 py-0.5 rounded border border-amber-200 uppercase tracking-tighter">IPR</span>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <x-secondary-button wire:click="openButirModal({{ $komponen->id }})" class="text-xs">
                                    {{ __('+ Tambah Butir') }}
                                </x-secondary-button>
                                <button wire:click="openKomponenModal({{ $komponen->id }})" class="text-indigo-600 hover:text-indigo-900 mx-2">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button @click="confirmDelete({{ $komponen->id }}, 'komponen')" class="text-red-600 hover:text-red-900">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase w-16">No SK</th>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase w-20">No Butir</th>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Butir Pernyataan</th>
                                    <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase w-24">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($komponen->butirs as $butir)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm text-gray-900 border-r">{{ $butir->no_sk }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 border-r font-bold">{{ $butir->nomor_butir }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $butir->butir_pernyataan }}</td>
                                    <td class="px-4 py-2 text-right text-sm font-medium whitespace-nowrap">
                                        <button wire:click="openButirModal({{ $komponen->id }}, {{ $butir->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button @click="confirmDelete({{ $butir->id }}, 'butir')" class="text-red-600 hover:text-red-900">Hapus</button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500 italic">Belum ada butir pernyataan untuk komponen ini.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @empty
                    <div class="text-center py-10 text-gray-500 border-2 border-dashed rounded-lg">
                        Belum ada master data EDPM. Klik "Tambah Komponen" untuk memulai.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Komponen -->
    <x-modal name="edpm-komponen-modal" focusable>
        <form wire:submit="saveKomponen" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">{{ $modalTitle }}</h2>
            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="komponen_nama" value="Nama Komponen" />
                    <x-text-input wire:model="komponen_nama" id="komponen_nama" class="mt-1 block w-full" placeholder="Contoh: MUTU LULUSAN" required />
                    <x-input-error :messages="$errors->get('komponen_nama')" class="mt-2" />
                </div>

                <div class="flex items-center gap-3 bg-gray-50 border rounded-lg p-3">
                    <input type="checkbox" wire:model="komponen_ipr" id="komponen_ipr" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-5 h-5">
                    <div>
                        <x-input-label for="komponen_ipr" value="Komponen IPR" class="font-bold text-gray-800" />
                        <p class="text-[10px] text-gray-500">Centang jika komponen ini termasuk dalam Indikator Pemenuhan Relatif (IPR)</p>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-primary-button class="ms-3">Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- Modal Butir -->
    <x-modal name="edpm-butir-modal" focusable>
        <form wire:submit="saveButir" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">{{ $modalTitle }}</h2>
            <div class="mt-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="butir_no_sk" value="No SK" />
                        <x-text-input wire:model="butir_no_sk" id="butir_no_sk" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('butir_no_sk')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="butir_nomor_butir" value="Nomor Butir" />
                        <x-text-input wire:model="butir_nomor_butir" id="butir_nomor_butir" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('butir_nomor_butir')" class="mt-2" />
                    </div>
                </div>
                <div>
                    <x-input-label for="butir_pernyataan" value="Butir Pernyataan" />
                    <textarea wire:model="butir_pernyataan" id="butir_pernyataan" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required></textarea>
                    <x-input-error :messages="$errors->get('butir_pernyataan')" class="mt-2" />
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-primary-button class="ms-3">Simpan Butir</x-primary-button>
            </div>
        </form>
    </x-modal>
</div>