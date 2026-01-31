<?php

use App\Models\User;
use App\Models\Asesor;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public function getAsesorsProperty()
    {
        return User::where('role_id', 2)
            ->with(['asesor', 'asesor.assessments.akreditasi'])
            ->orderBy('name', 'asc')
            ->get();
    }
}; ?>

<div class="py-12">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Asesor') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Asesor</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">No</th>
                                <th class="py-3 px-6 text-left">Nama</th>
                                <th class="py-3 px-6 text-left">Email</th>
                                <th class="py-3 px-6 text-center">No. HP</th>
                                <th class="py-3 px-6 text-center">Akreditasi</th>
                                <th class="py-3 px-6 text-center">Status</th>
                                <th class="py-3 px-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-xs md:text-sm font-light">
                            @forelse ($this->asesors as $index => $user)
                            <tr class="border-b border-gray-200 hover:bg-gray-100 transition duration-150">
                                <td class="py-3 px-6 text-left whitespace-nowrap">
                                    {{ $index + 1 }}
                                </td>
                                <td class="py-3 px-6 text-left font-medium">
                                    <div class="flex items-center">
                                        {{ $user->name }}
                                    </div>
                                </td>
                                <td class="py-3 px-6 text-left">
                                    {{ $user->email }}
                                </td>
                                <td class="py-3 px-6 text-center">
                                    {{ $user->asesor->no_hp ?? '-' }}
                                </td>
                                <td class="py-3 px-6 text-center">
                                    @php
                                    $assessments = $user->asesor->assessments ?? collect();
                                    $activeProcess = $assessments->contains(function ($a) {
                                    return !in_array($a->akreditasi->status, [1, 2]);
                                    });
                                    @endphp
                                    @if ($assessments->isEmpty())
                                    <span class="text-gray-400">-</span>
                                    @elseif ($activeProcess)
                                    <span class="bg-amber-100 text-amber-700 py-1 px-3 rounded-full text-xs font-bold uppercase tracking-wider">Proses</span>
                                    @else
                                    <span class="bg-indigo-100 text-indigo-700 py-1 px-3 rounded-full text-xs font-bold uppercase tracking-wider">Selesai</span>
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
                                    <a href="{{ route('admin.asesor.detail', $user->uuid) }}"
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
                                    Belum ada data asesor.
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