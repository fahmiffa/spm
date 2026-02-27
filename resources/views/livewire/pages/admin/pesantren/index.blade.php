<?php

use App\Models\User;
use App\Models\Pesantren;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $filterAkreditasi = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterAkreditasi()
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
            ->when($this->filterStatus !== '', function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterAkreditasi, function ($query) {
                if ($this->filterAkreditasi === 'belum') {
                    $query->whereDoesntHave('akreditasis');
                } elseif ($this->filterAkreditasi === 'proses') {
                    $query->whereHas('akreditasis', function ($q) {
                        $q->whereNotIn('status', [1, 2]);
                    });
                } elseif ($this->filterAkreditasi === 'terakreditasi') {
                    $query->whereHas('akreditasis', function ($q) {
                        $q->where('status', 1);
                    });
                } elseif ($this->filterAkreditasi === 'ditolak') {
                    $query->whereHas('akreditasis', function ($q) {
                        $q->where('status', 2);
                    });
                }
            })
            ->with(['pesantren', 'akreditasis'])
            ->orderBy('name', 'asc')
            ->paginate(10);
    }
}; ?>

<div class="py-12">
    <x-slot name="header">
        <h2 class="font-semibold text-gray-800 leading-tight">
            {{ __('Pesantren') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
            <div class="p-6 text-gray-900">
                <!-- Header Actions -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                    <h2 class="text-xl font-extrabold text-[#111827]">Pesantren</h2>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari Pesantren"
                                class="pl-9 pr-4 py-2 text-xs border border-gray-100 rounded-lg focus:ring-1 focus:ring-green-500 focus:border-green-500 w-48 bg-gray-50/50 placeholder-gray-400">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <select wire:model.live="filterAkreditasi" class="text-xs border border-gray-100 rounded-lg bg-gray-50/50 py-2 pl-3 pr-8 focus:ring-1 focus:ring-green-500 focus:border-green-500 text-gray-500">
                            <option value="">Semua Akreditasi</option>
                            <option value="terakreditasi">Unggul</option>
                            <option value="proses">Proses Akreditasi</option>
                            <option value="belum">Belum Terakreditasi</option>
                            <option value="ditolak">Tidak Terakreditasi</option>
                        </select>
                        <select wire:model.live="filterStatus" class="text-xs border border-gray-100 rounded-lg bg-gray-50/50 py-2 pl-3 pr-8 focus:ring-1 focus:ring-green-500 focus:border-green-500 text-gray-500">
                            <option value="">Semua Status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                        <button class="bg-[#1e3a5f] text-white px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-[#162d4a] transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export Data
                        </button>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead>
                            <tr class="bg-white">
                                <th class="w-12 py-3 px-4">
                                    <input type="checkbox" class="rounded border-gray-300 text-green-600 focus:ring-green-500 bg-gray-100 h-4 w-4">
                                </th>
                                <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">NAMA PESANTREN</th>
                                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">AKREDITASI</th>
                                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">STATUS</th>
                                <th class="py-3 px-4 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider pr-8">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($this->pesantrens as $index => $user)
                            <tr class="hover:bg-gray-50/30 transition-colors duration-150 group">
                                <td class="py-5 px-4">
                                    <input type="checkbox" class="rounded border-gray-300 text-green-600 focus:ring-green-500 bg-gray-100 h-4 w-4">
                                </td>
                                <td class="py-5 px-4">
                                    <span class="text-sm font-bold text-[#374151]">{{ $user->pesantren->nama_pesantren ?? $user->name }}</span>
                                </td>
                                <td class="py-5 px-4 text-center">
                                    @php
                                    $latestAkreditasi = $user->akreditasis->sortByDesc('created_at')->first();
                                    @endphp
                                    @if (!$latestAkreditasi)
                                    <span class="text-[11px] font-bold text-gray-400">Belum Teakreditasi</span>
                                    @elseif ($latestAkreditasi->status == 1)
                                    <span class="text-[11px] font-bold text-green-500">{{ $latestAkreditasi->peringkat ?? 'Unggul' }}</span>
                                    @elseif ($latestAkreditasi->status == 2)
                                    <span class="text-[11px] font-bold text-rose-500">Tidak Terakreditasi</span>
                                    @else
                                    <span class="bg-yellow-50 text-yellow-600 px-3 py-1 rounded text-[11px] font-bold">Proses Akreditasi</span>
                                    @endif
                                </td>
                                <td class="py-5 px-4 text-center">
                                    @if($user->status == 1)
                                    <span class="text-[11px] font-bold text-green-500">Aktif</span>
                                    @else
                                    <span class="text-[11px] font-bold text-rose-500">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td class="py-5 px-4 text-right pr-6">
                                    <a href="{{ route('admin.pesantren.detail', $user->uuid) }}" wire:navigate
                                        class="inline-flex items-center gap-2 px-3 py-1.5 text-[11px] font-bold text-gray-500 hover:text-gray-800 transition-colors bg-gray-50/80 rounded-lg group-hover:bg-gray-100">
                                        <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Detail
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-16 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-10 h-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <p class="text-sm text-gray-400 font-medium">Belum ada data pesantren.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($this->pesantrens instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-8">
                    {{ $this->pesantrens->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>