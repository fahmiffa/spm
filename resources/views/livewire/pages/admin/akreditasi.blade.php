<?php

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public $akreditasi_id;
    public $asesor_id;
    public $tanggal_mulai;
    public $tanggal_berakhir;

    public function mount()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    public function getAkreditasisProperty()
    {
        return Akreditasi::with('user.pesantren')->orderBy('created_at', 'desc')->get();
    }

    public function getAsesorsProperty()
    {
        return Asesor::with('user')->get();
    }

    public function delete($id)
    {
        Akreditasi::findOrFail($id)->delete();
        session()->flash('status', 'Pengajuan akreditasi berhasil dihapus.');
    }

    public function openVerifikasiModal($id)
    {
        $this->akreditasi_id = $id;
        $this->asesor_id = '';
        $this->tanggal_mulai = '';
        $this->tanggal_berakhir = '';
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'verifikasi-modal');
    }

    public function verifikasi()
    {
        $this->validate([
            'asesor_id' => 'required|exists:asesors,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_berakhir' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        Assessment::create([
            'akreditasi_id' => $this->akreditasi_id,
            'asesor_id' => $this->asesor_id,
            'tanggal_mulai' => $this->tanggal_mulai,
            'tanggal_berakhir' => $this->tanggal_berakhir,
        ]);

        $akreditasi = Akreditasi::findOrFail($this->akreditasi_id);
        $akreditasi->update(['status' => 5]); // 5. assesment

        // Notify Pesantren
        $akreditasi->user->notify(new \App\Notifications\AkreditasiNotification(
            'assessment',
            'Update Status: Assessment',
            'Pengajuan akreditasi Anda telah diverifikasi dan masuk tahap Assessment.',
            route('pesantren.akreditasi')
        ));

        // Notify Asesor
        $asesor = Asesor::with('user')->find($this->asesor_id);
        if ($asesor && $asesor->user) {
            $asesor->user->notify(new \App\Notifications\AkreditasiNotification(
                'tugas_baru',
                'Tugas Assessment Baru',
                'Anda telah ditugaskan sebagai asesor untuk pesantren ' . ($akreditasi->user->pesantren->nama_pesantren ?? $akreditasi->user->name),
                route('asesor.akreditasi')
            ));
        }

        session()->flash('status', 'Pengajuan berhasil diverifikasi. Status berubah menjadi Assesment.');
        $this->dispatch('close-modal', 'verifikasi-modal');
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Manajemen Akreditasi (Admin)</h2>
                </div>

                @if (session('status'))
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">No</th>
                                <th class="py-3 px-6 text-left">Nama Pesantren</th>
                                <th class="py-3 px-6 text-center">Catatan</th>
                                <th class="py-3 px-6 text-center">Status</th>
                                <th class="py-3 px-6 text-center">Tanggal Pengajuan</th>
                                <th class="py-3 px-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            @forelse ($this->akreditasis as $index => $item)
                                <tr class="border-b border-gray-200 hover:bg-gray-100">
                                    <td class="py-3 px-6 text-left whitespace-nowrap">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="py-3 px-6 text-left font-medium">
                                        {{ $item->user->pesantren->nama_pesantren ?? $item->user->name }}
                                    </td>
                                    <td class="py-3 px-6 text-left font-medium">
                                        {{ $item->catatan }}
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <span
                                            class="{{ Akreditasi::getStatusBadgeClass($item->status) }} py-1 px-3 rounded-full text-xs font-semibold">
                                            {{ Akreditasi::getStatusLabel($item->status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        {{ $item->created_at->format('d M Y H:i') }}
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <div class="flex item-center justify-center gap-4">
                                            @if ($item->status == 6)
                                                <button wire:click="openVerifikasiModal({{ $item->id }})"
                                                    class="text-blue-600 hover:text-blue-900 font-medium">
                                                    Verifikasi
                                                </button>
                                            @endif
                                            @if ($item->status == 4)
                                                <a href="{{ route('admin.akreditasi-detail', $item->uuid) }}"
                                                    class="text-indigo-600 hover:text-indigo-900 font-medium"
                                                    wire:navigate>
                                                    Detail
                                                </a>
                                            @endif
                                            <a href="{{ route('admin.akreditasi-detail', $item->uuid) }}"
                                                class="text-indigo-600 hover:text-indigo-900 font-medium" wire:navigate>
                                                Detail
                                            </a>
                                            <button wire:click="delete({{ $item->id }})"
                                                wire:confirm="Apakah Anda yakin ingin menghapus pengajuan ini?"
                                                class="text-red-600 hover:text-red-900 font-medium">
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-gray-500">
                                        Belum ada data pengajuan akreditasi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Verifikasi -->
    <x-modal name="verifikasi-modal" focusable>
        <form wire:submit="verifikasi" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Konfirmasi Pengajuan dan Sosialisasi IPM</h2>
            <p class="mt-1 text-sm text-gray-600">
                Silakan pilih asesor dan tentukan jadwal assesment untuk melanjutkan proses pengajuan.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="asesor_id" value="Pilih Asesor" />
                    <select wire:model="asesor_id" id="asesor_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">-- Pilih Asesor --</option>
                        @foreach ($this->asesors as $asesor)
                            <option value="{{ $asesor->id }}">{{ $asesor->user->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('asesor_id')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="tanggal_mulai" value="Tanggal Mulai Assesment" />
                        <x-text-input wire:model="tanggal_mulai" id="tanggal_mulai" type="date"
                            class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('tanggal_mulai')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="tanggal_berakhir" value="Tanggal Berakhir Assesment" />
                        <x-text-input wire:model="tanggal_berakhir" id="tanggal_berakhir" type="date"
                            class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('tanggal_berakhir')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-primary-button class="ms-3">
                    Simpan & Verifikasi
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
