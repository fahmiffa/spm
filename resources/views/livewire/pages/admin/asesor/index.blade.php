<?php

use App\Models\User;
use App\Models\Asesor;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public $search = '';
    public $filterPeran = '';
    public $filterPenugasan = '';
    public $filterStatus = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }
    public function updatedFilterPeran()
    {
        $this->resetPage();
    }
    public function updatedFilterPenugasan()
    {
        $this->resetPage();
    }
    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function toggleStatus($userId)
    {
        $user = User::findOrFail($userId);
        $user->status = $user->status == 1 ? 0 : 1;
        $user->save();
        session()->flash('status', 'Status asesor berhasil diperbarui.');
    }

    public function getAsesorsProperty()
    {
        $query = User::where('role_id', 2)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterStatus !== '', function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterPeran, function ($query) {
                $query->whereHas('asesor.assessments', function ($q) {
                    $q->where('tipe', $this->filterPeran);
                });
            })
            ->when($this->filterPenugasan, function ($query) {
                if ($this->filterPenugasan === 'bertugas') {
                    $query->whereHas('asesor.assessments.akreditasi', function ($q) {
                        $q->whereNotIn('status', [1, 2]);
                    });
                } elseif ($this->filterPenugasan === 'bebas') {
                    $query->whereDoesntHave('asesor.assessments', function ($q) {
                        $q->whereHas('akreditasi', function ($sq) {
                            $sq->whereNotIn('status', [1, 2]);
                        });
                    });
                }
            });

        return $query->with(['asesor.assessments.akreditasi.user.pesantren'])
            ->orderBy('name', 'asc')
            ->paginate(10);
    }
}; ?>

<div class="py-12">
    <x-slot name="header">
        <h2 class="font-semibold text-gray-800 leading-tight">
            {{ __('Asesor') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
            <div class="p-6 text-gray-900">
                <!-- Header Actions -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                    <h2 class="text-xl font-extrabold text-[#111827]">Asesor</h2>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari Asesor"
                                class="pl-9 pr-4 py-2 text-xs border border-gray-100 rounded-lg focus:ring-1 focus:ring-green-500 focus:border-green-500 w-48 bg-gray-50/50 placeholder-gray-400">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <select wire:model.live="filterPeran" class="text-xs border border-gray-100 rounded-lg bg-gray-50/50 py-2 pl-3 pr-8 focus:ring-1 focus:ring-green-500 focus:border-green-500 text-gray-500">
                            <option value="">Peran</option>
                            <option value="1">Ketua Asesor</option>
                            <option value="2">Anggota Asesor</option>
                        </select>
                        <select wire:model.live="filterPenugasan" class="text-xs border border-gray-100 rounded-lg bg-gray-50/50 py-2 pl-3 pr-8 focus:ring-1 focus:ring-green-500 focus:border-green-500 text-gray-500">
                            <option value="">Penugasan</option>
                            <option value="bertugas">Sedang Bertugas</option>
                            <option value="bebas">Bebas Tugas</option>
                        </select>
                        <select wire:model.live="filterStatus" class="text-xs border border-gray-100 rounded-lg bg-gray-50/50 py-2 pl-3 pr-8 focus:ring-1 focus:ring-green-500 focus:border-green-500 text-gray-500">
                            <option value="">Status</option>
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

                @if (session('status'))
                <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl border border-green-100 text-xs font-medium">
                    {{ session('status') }}
                </div>
                @endif

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead>
                            <tr class="bg-white">
                                <th class="w-12 py-3 px-4">
                                    <input type="checkbox" class="rounded border-gray-300 text-green-600 focus:ring-green-500 bg-gray-100 h-4 w-4">
                                </th>
                                <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">ASESOR</th>
                                <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">PESANTREN DITANGANI</th>
                                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">PERAN ASESOR</th>
                                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">STATUS PENUGASAN</th>
                                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">STATUS</th>
                                <th class="py-3 px-4 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider pr-8">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($this->asesors as $user)
                            <tr class="hover:bg-gray-50/30 transition-colors duration-150 group">
                                <td class="py-5 px-4">
                                    <input type="checkbox" class="rounded border-gray-300 text-green-600 focus:ring-green-500 bg-gray-100 h-4 w-4">
                                </td>
                                <td class="py-5 px-4">
                                    <span class="text-sm font-bold text-[#374151]">{{ $user->name }}</span>
                                </td>
                                <td class="py-5 px-4">
                                    @php
                                    $latestTask = $user->asesor?->assessments->sortByDesc('created_at')->first();
                                    $pesantrenName = $latestTask?->akreditasi?->user?->pesantren?->nama_pesantren ?? '-';
                                    @endphp
                                    <span class="text-[11px] font-bold text-gray-500">{{ $pesantrenName }}</span>
                                </td>
                                <td class="py-5 px-4 text-center">
                                    @if ($latestTask)
                                    <span class="px-2 py-1 rounded text-[10px] font-bold {{ $latestTask->tipe == 1 ? 'bg-blue-100 text-blue-600' : 'bg-sky-50 text-sky-600' }}">
                                        {{ $latestTask->tipe == 1 ? 'Ketua Asesor' : 'Anggota Asesor' }}
                                    </span>
                                    @else
                                    <span class="text-[11px] font-bold text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-5 px-4 text-center">
                                    @if ($latestTask)
                                    @php
                                    $statusAkreditasi = $latestTask->akreditasi?->status;
                                    $penugasanText = 'Sedang Bertugas';
                                    $penugasanColor = 'text-green-500';
                                    if ($statusAkreditasi == 4) { $penugasanText = 'Visitasi'; $penugasanColor = 'text-indigo-500'; }
                                    elseif (in_array($statusAkreditasi, [1, 2])) { $penugasanText = 'Selesai'; $penugasanColor = 'text-gray-400'; }
                                    @endphp
                                    <span class="text-[11px] font-bold {{ $penugasanColor }}">{{ $penugasanText }}</span>
                                    @else
                                    <span class="text-[11px] font-bold text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-5 px-4 text-center">
                                    @if($user->status == 1)
                                    <span class="text-[11px] font-bold text-green-500">Aktif</span>
                                    @else
                                    <span class="text-[11px] font-bold text-gray-400">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td class="py-5 px-4 text-right pr-6 overflow-visible">
                                    <div class="relative inline-block text-left" x-data="{ open: false }">
                                        <button @click="open = !open" @click.away="open = false" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-gray-400 hover:text-gray-700 transition-colors">
                                            Aksi
                                            <svg class="w-3 h-3 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                            class="absolute right-0 z-[100] mt-1 w-44 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 origin-top-right overflow-hidden" x-cloak>
                                            <a href="{{ route('admin.asesor.detail', $user->uuid) }}" wire:navigate
                                                class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition-colors gap-3">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Lihat Detail
                                            </a>
                                            <button wire:click="toggleStatus({{ $user->id }})" @click="open = false"
                                                class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold {{ $user->status == 1 ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50' }} transition-colors gap-3 border-t border-gray-50 mt-1">
                                                @if($user->status == 1)
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                                Nonaktifkan
                                                @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Aktifkan Kembali
                                                @endif
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-16 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-10 h-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <p class="text-sm text-gray-400 font-medium">Belum ada data asesor.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $this->asesors->links() }}
                </div>
            </div>
        </div>
    </div>
</div>