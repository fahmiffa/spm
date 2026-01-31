<?php

use App\Models\User;
use App\Models\Pesantren;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public $user;
    public $pesantren;

    public function mount($uuid)
    {
        $this->user = User::where('uuid', $uuid)->with(['pesantren', 'pesantren.units'])->firstOrFail();
        $this->pesantren = $this->user->pesantren;
    }
}; ?>

<div class="py-12">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detail Pesantren') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('admin.pesantren.index') }}" wire:navigate class="text-indigo-600 hover:text-indigo-900 font-medium flex items-center transition duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Basic Info Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg md:col-span-1">
                <div class="p-6 text-center">
                    <div class="h-24 w-24 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-4xl mx-auto mb-4 border-4 border-indigo-100 shadow-md">
                        {{ substr($pesantren->nama_pesantren ?? $user->name, 0, 1) }}
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">{{ $pesantren->nama_pesantren ?? $user->name }}</h3>
                    <p class="text-sm text-gray-500 mb-2">{{ $user->email }}</p>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest mb-4 font-bold">NSPP: {{ $pesantren->nspp ?? '-' }}</p>
                    <div class="flex justify-center">
                        @if($user->status == 1)
                        <span class="bg-green-100 text-green-800 py-1 px-4 rounded-full text-xs font-bold uppercase tracking-wider">Aktif</span>
                        @else
                        <span class="bg-red-100 text-red-800 py-1 px-4 rounded-full text-xs font-bold uppercase tracking-wider">Tidak Aktif</span>
                        @endif
                    </div>
                </div>
                <div class="border-t border-gray-100 p-6 space-y-4">
                    <div>
                        <span class="text-xs text-gray-400 uppercase font-bold block mb-1">Kontak Pesantren</span>
                        <div class="flex items-center text-sm text-gray-700 mb-1">
                            <svg class="h-4 w-4 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            {{ $pesantren->no_hp_pesantren ?? '-' }}
                        </div>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 uppercase font-bold block mb-1">Lokasi</span>
                        <div class="text-sm text-gray-700 font-medium">
                            {{ $pesantren->kota_kabupaten ?? '-' }}, {{ $pesantren->provinsi ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Info Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg md:col-span-2">
                <div class="border-b border-gray-100 p-6 flex justify-between items-center">
                    <h4 class="text-lg font-bold text-gray-800">Data Lengkap Pesantren</h4>
                    <span class="text-xs font-bold px-2 py-1 bg-indigo-50 text-indigo-600 rounded">Tipe: {{ $pesantren->tipe_pesantren ?? '-' }}</span>
                </div>
                <div class="p-6">
                    @if($pesantren)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <span class="text-xs text-gray-400 uppercase font-bold block mb-1">Tahun Berdiri</span>
                            <div class="text-sm text-gray-800">
                                {{ $pesantren->tahun_berdiri ?? '-' }}
                            </div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 uppercase font-bold block mb-1">Nama Pimpinan</span>
                            <div class="text-sm text-gray-800">
                                {{ $pesantren->nama_pimpinan ?? '-' }}
                            </div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 uppercase font-bold block mb-1">No. Telp Pimpinan</span>
                            <div class="text-sm text-gray-800 font-medium">
                                {{ $pesantren->no_hp_pimpinan ?? '-' }}
                            </div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 uppercase font-bold block mb-1">Email Pesantren</span>
                            <div class="text-sm text-gray-800 italic border-b border-gray-50 pb-2">
                                {{ $pesantren->email_pesantren ?? '-' }}
                            </div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 uppercase font-bold block mb-1">Akreditasi</span>
                            <div class="text-sm text-gray-800 border-b border-gray-50 pb-2">
                                @php
                                $latestAkreditasi = $user->akreditasis()->latest()->first();
                                @endphp
                                @if (!$latestAkreditasi)
                                -
                                @elseif ($latestAkreditasi->status == 1)
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-700 border border-indigo-200 uppercase">
                                    {{ $latestAkreditasi->peringkat ?? 'Berhasil' }}
                                </span>
                                @else
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200 uppercase">
                                    Proses
                                </span>
                                @endif
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <span class="text-xs text-gray-400 uppercase font-bold block mb-1">Alamat Lengkap</span>
                            <div class="text-sm text-gray-800 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                {{ $pesantren->alamat_lengkap ?? '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        @if($pesantren->units && $pesantren->units->count() > 0)
                        <div>
                            <h5 class="text-sm font-bold text-indigo-600 border-b border-indigo-50 pb-2 mb-3">Unit Satuan Pendidikan</h5>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach($pesantren->units as $unit)
                                <div class="bg-indigo-50/30 p-3 rounded-lg border border-indigo-100 flex items-center">
                                    <svg class="h-4 w-4 mr-2 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="text-sm text-gray-700 font-semibold">{{ $unit->nama_unit }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($pesantren->layanan_satuan_pendidikan)
                        <div>
                            <h5 class="text-sm font-bold text-indigo-600 border-b border-indigo-50 pb-2 mb-3">Layanan Pendidikan</h5>
                            <div class="flex flex-wrap gap-2">
                                @foreach($pesantren->layanan_satuan_pendidikan as $layanan)
                                <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-medium border border-gray-200">{{ $layanan }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @else
                    <div class="text-center py-10 bg-gray-50 rounded-lg">
                        <svg class="h-12 w-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="text-gray-500 italic">Profil lengkap belum diisi oleh pesantren.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>