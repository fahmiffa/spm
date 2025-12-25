<?php

use App\Models\Akreditasi;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Str;

new #[Layout('layouts.app')] class extends Component {
    public function mount()
    {
        if (!auth()->user()->isPesantren()) {
            abort(403);
        }
    }

    public function getAkreditasisProperty()
    {
        return Akreditasi::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create()
    {
        Akreditasi::create([
            'user_id' => auth()->id(),
            'status' => 6, // pengajuan
        ]);

        session()->flash('status', 'Pengajuan akreditasi berhasil dibuat.');
    }

    public function delete($id)
    {
        $akreditasi = Akreditasi::where('user_id', auth()->id())->findOrFail($id);
        $akreditasi->delete();

        session()->flash('status', 'Pengajuan akreditasi berhasil dihapus.');
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Daftar Akreditasi</h2>
                    <button wire:click="create"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                        Buat Pengajuan Baru
                    </button>
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
                                <th class="py-3 px-6 text-center">Status</th>
                                <th class="py-3 px-6 text-center">Catatan</th>
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
                                    <td class="py-3 px-6 text-center">
                                        <span
                                            class="{{ Akreditasi::getStatusBadgeClass($item->status) }} py-1 px-3 rounded-full text-xs font-semibold">
                                            {{ Akreditasi::getStatusLabel($item->status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-6 text-left font-medium">
                                        {{ $item->catatan }}
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        {{ $item->created_at->format('d M Y H:i') }}
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <button wire:click="delete({{ $item->id }})"
                                            wire:confirm="Apakah Anda yakin ingin menghapus pengajuan ini?"
                                            class="text-red-600 hover:text-red-900 font-medium">
                                            Hapus
                                        </button>
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
</div>
