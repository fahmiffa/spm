<?php

use App\Models\User;
use App\Models\Asesor;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public $user;
    public $asesor;

    public function mount($uuid)
    {
        $this->user = User::where('uuid', $uuid)->with('asesor')->firstOrFail();
        $this->asesor = $this->user->asesor;
    }
}; ?>

<div class="py-12">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detail Asesor') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('admin.asesor.index') }}" wire:navigate class="text-indigo-600 hover:text-indigo-900 font-medium flex items-center transition duration-150">
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
                    <div class="h-24 w-24 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-4xl mx-auto mb-4 border-4 border-indigo-100 shadow-md">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">{{ $user->name }}</h3>
                    <p class="text-sm text-gray-500 mb-4">{{ $user->email }}</p>
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
                        <span class="text-xs text-gray-400 uppercase font-bold block mb-1">Informasi Kontak</span>
                        <div class="flex items-center text-sm text-gray-700">
                            <svg class="h-4 w-4 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            {{ $asesor->no_hp ?? '-' }}
                        </div>
                    </div>
                    @if($asesor && $asesor->nomor_induk_asesor_pm)
                    <div>
                        <span class="text-xs text-gray-400 uppercase font-bold block mb-1">NIA PM</span>
                        <div class="text-sm font-semibold text-indigo-700">
                            {{ $asesor->nomor_induk_asesor_pm }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Detailed Info Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg md:col-span-2">
                <div class="border-b border-gray-100 p-6">
                    <h4 class="text-lg font-bold text-gray-800">Data Lengkap Asesor</h4>
                </div>
                <div class="p-6">
                    @if($asesor)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <span class="text-xs text-gray-400 uppercase font-bold block mb-1">Tempat, Tanggal Lahir</span>
                            <div class="text-sm text-gray-800">
                                {{ $asesor->tempat_lahir ?? '-' }}, {{ $asesor->tanggal_lahir ? \Carbon\Carbon::parse($asesor->tanggal_lahir)->format('d F Y') : '-' }}
                            </div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 uppercase font-bold block mb-1">Jenis Kelamin</span>
                            <div class="text-sm text-gray-800">
                                {{ $asesor->jenis_kelamin ?? '-' }}
                            </div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 uppercase font-bold block mb-1">Pendidikan Terakhir</span>
                            <div class="text-sm text-gray-800">
                                {{ $asesor->pendidikan_terakhir ?? '-' }}
                            </div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 uppercase font-bold block mb-1">Profesi</span>
                            <div class="text-sm text-gray-800">
                                {{ $asesor->profesi ?? '-' }}
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <span class="text-xs text-gray-400 uppercase font-bold block mb-1">Alamat</span>
                            <div class="text-sm text-gray-800">
                                {{ $asesor->alamat ?? '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        @if($asesor->riwayat_pendidikan)
                        <div>
                            <h5 class="text-sm font-bold text-indigo-600 border-b border-indigo-50 pb-2 mb-3">Riwayat Pendidikan</h5>
                            <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
                                @foreach($asesor->riwayat_pendidikan as $item)
                                <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        @if($asesor->pengalaman_bekerja)
                        <div>
                            <h5 class="text-sm font-bold text-indigo-600 border-b border-indigo-50 pb-2 mb-3">Pengalaman Bekerja</h5>
                            <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
                                @foreach($asesor->pengalaman_bekerja as $item)
                                <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <!-- Add more fields as needed -->
                    </div>
                    @else
                    <div class="text-center py-10 bg-gray-50 rounded-lg">
                        <svg class="h-12 w-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="text-gray-500 italic">Profil lengkap belum diisi oleh asesor.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>