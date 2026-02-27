<?php

use App\Models\User;
use App\Models\Pesantren;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function getPesantrensProperty()
    {
        return User::where('role_id', 3)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhereHas('pesantren', function ($pq) {
                            $pq->where('nama_pesantren', 'like', '%' . $this->search . '%')
                                ->orWhere('ns_pesantren', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->with(['pesantren', 'akreditasis'])
            ->orderBy('name', 'asc')
            ->get();
    }
}; ?>

<div class="py-12">
    <x-slot name="header">
        <h2 class="font-semibold text-gray-800 leading-tight">
            {{ __('Pesantren') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <h2 class="text-2xl font-semibold text-gray-800">Pesantren</h2>

                    <div class="relative w-full md:w-72">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-150 ease-in-out"
                            placeholder="Cari pesantren...">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">No</th>
                                <th class="py-3 px-6 text-left">Nama Pesantren</th>
                                <th class="py-3 px-6 text-left">Email</th>
                                <th class="py-3 px-6 text-center">Akreditasi</th>
                                <th class="py-3 px-6 text-center">Status</th>
                                <th class="py-3 px-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-xs md:text-sm font-light">
                            @forelse ($this->pesantrens as $index => $user)
                            <tr class="border-b border-gray-200 hover:bg-gray-100 transition duration-150">
                                <td class="py-3 px-6 text-left whitespace-nowrap">
                                    {{ $index + 1 }}
                                </td>
                                <td class="py-3 px-6 text-left font-medium">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-gray-900 font-bold">{{ $user->pesantren->nama_pesantren ?? $user->name }}</div>
                                            <div class="text-[10px] text-gray-400 uppercase tracking-widest">{{ $user->pesantren->ns_pesantren ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-6 text-left">
                                    {{ $user->email }}
                                </td>
                                <td class="py-3 px-6 text-center">
                                    @php
                                    $latestAkreditasi = $user->akreditasis->sortByDesc('created_at')->first();
                                    @endphp
                                    @if (!$latestAkreditasi)
                                    <span class="text-gray-400">-</span>
                                    @elseif ($latestAkreditasi->status == 1)
                                    <span class="bg-indigo-100 text-indigo-700 py-1 px-3 rounded-full text-xs font-bold">{{ $latestAkreditasi->peringkat ?? 'Berhasil' }}</span>
                                    @else
                                    <span class="bg-amber-100 text-amber-700 py-1 px-3 rounded-full text-xs font-bold uppercase tracking-wider">Proses</span>
                                    @endif
                                </td>
                                <td class="py-3 px-6 text-center">
                                    @if($user->status == 1)
                                    <span class="bg-green-100 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">Aktif</span>
                                    @else
                                    <span class="bg-red-100 text-red-800 py-1 px-3 rounded-full text-xs font-semibold">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <a href="{{ route('admin.pesantren.detail', $user->uuid) }}"
                                        class="text-indigo-600 hover:text-indigo-900 font-medium inline-flex items-center"
                                        wire:navigate>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Detail
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-gray-500 italic">
                                    Belum ada data pesantren.
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