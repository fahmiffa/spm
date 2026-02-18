<?php

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\AkreditasiCatatan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public function mount()
    {
        if (!auth()->user()->isAsesor()) {
            abort(403);
        }
    }

    public function getAssessmentsProperty()
    {
        $asesor = auth()->user()->asesor;
        if (!$asesor) return collect();

        return Assessment::with('akreditasi.user.pesantren')->where('asesor_id', $asesor->id)->get();
    }


    public $visitasi_akreditasi_id;
    public $visitasi_tanggal;
    public $visitasi_catatan;
    public $visitasi_action = 'terima';

    public function openVisitasiModal($id)
    {
        $this->visitasi_akreditasi_id = $id;
        // Reset fields
        $this->visitasi_tanggal = date('Y-m-d');
        $this->visitasi_catatan = '';
        $this->visitasi_action = 'terima';
        $this->resetErrorBag();

        $this->dispatch('open-modal', 'visitasi-modal');
    }

    public function submitVisitasi()
    {
        $this->validate([
            'visitasi_action' => 'required',
            'visitasi_tanggal' => 'required_if:visitasi_action,terima',
            'visitasi_catatan' => 'required_if:visitasi_action,tolak',
        ]);

        $akreditasi = Akreditasi::find($this->visitasi_akreditasi_id);

        // Identify active user (Asesor)
        $asesorName = auth()->user()->name;
        // Fetch admins
        $admins = \App\Models\User::whereHas('role', function ($q) {
            $q->where('id', 1);
        })->get();

        if ($this->visitasi_action == 'terima') {
            $akreditasi->update([
                'status' => 4, // 4. Visitasi
                'tgl_visitasi' => $this->visitasi_tanggal,
            ]);

            // Notify Pesantren: Visitasi Scheduled
            $akreditasi->user->notify(new \App\Notifications\AkreditasiNotification(
                'visitasi_diterima',
                'Jadwal Visitasi Ditetapkan',
                'Asesor ' . $asesorName . ' telah menjadwalkan visitasi pada tanggal ' . $this->visitasi_tanggal . '.',
                route('pesantren.akreditasi')
            ));

            // Notify Admin: Visitasi Scheduled
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AkreditasiNotification(
                'visitasi_diterima',
                'Jadwal Visitasi Ditetapkan',
                'Asesor ' . $asesorName . ' telah menetapkan jadwal visitasi untuk pesantren ' . ($akreditasi->user->pesantren->nama_pesantren ?? $akreditasi->user->name) . ' pada tanggal ' . $this->visitasi_tanggal . '.',
                route('admin.akreditasi')
            ));
        } else {
            $akreditasi->status = 5; // 5. Assessment (kembali ke tahap penjadwalan)
            AkreditasiCatatan::create([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => auth()->id(),
                'tipe' => 'visitasi',
                'catatan' => $this->visitasi_catatan,
            ]);
            $akreditasi->save();

            // Notify Pesantren: Visitasi Rejected
            $akreditasi->user->notify(new \App\Notifications\AkreditasiNotification(
                'visitasi_ditolak',
                'Pengajuan Visitasi Ditolak',
                'Asesor ' . $asesorName . ' menolak jadwal visitasi dengan catatan: ' . $this->visitasi_catatan . '. Silahkan periksa catatan perbaikan.',
                route('pesantren.akreditasi')
            ));

            // Notify Admin: Visitasi Rejected
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AkreditasiNotification(
                'visitasi_ditolak',
                'Visitasi Ditolak Asesor',
                'Asesor ' . $asesorName . ' menolak visitasi untuk pesantren ' . ($akreditasi->user->pesantren->nama_pesantren ?? $akreditasi->user->name) . '.',
                route('admin.akreditasi')
            ));
        }

        $this->dispatch('close-modal', 'visitasi-modal');
        $this->js('window.location.reload()');
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Akreditasi</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">No</th>
                                <th class="py-3 px-6 text-left">Pesantren</th>
                                <th class="py-3 px-6 text-center">Status</th>
                                <th class="py-3 px-6 text-center">Akreditasi</th>
                                <th class="py-3 px-6 text-center">Tanggal</th>
                                <th class="py-3 px-6 text-center">Catatan</th>
                                <th class="py-3 px-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-xs md:text-sm font-light">
                            @forelse ($this->assessments as $index => $item)
                            <tr class="border-b border-gray-200 hover:bg-gray-100">
                                <td class="py-3 px-6 text-left whitespace-nowrap">
                                    {{ $index + 1 }}
                                </td>
                                @if($item->akreditasi)
                                <td class="py-3 px-6 text-left font-medium">
                                    {{ $item->akreditasi->user?->pesantren?->nama_pesantren ?? $item->akreditasi->user?->name ?? 'N/A' }}
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <span
                                        class="{{ Akreditasi::getStatusBadgeClass($item->akreditasi->status) }} py-1 px-3 rounded-full text-xs font-semibold">
                                        {{ Akreditasi::getStatusLabel($item->akreditasi->status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-6 text-center">
                                    @if ($item->akreditasi->status == 1)
                                    <span class="bg-indigo-100 text-indigo-700 py-1 px-3 rounded-full text-xs font-bold uppercase">{{ $item->akreditasi->peringkat ?? 'Berhasil' }}</span>
                                    @elseif (in_array($item->akreditasi->status, [3, 4, 5]))
                                    <span class="bg-amber-100 text-amber-700 py-1 px-3 rounded-full text-xs font-bold uppercase tracking-wider">Proses</span>
                                    @else
                                    <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        {{-- 1. Pengajuan --}}
                                        <div class="flex items-center gap-1 text-[11px] text-gray-500 whitespace-nowrap" title="Tanggal Pengajuan">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="font-medium uppercase tracking-tighter">Pengajuan:</span>
                                            <span>{{ $item->akreditasi->created_at->format('d/m/y') }}</span>
                                        </div>

                                        {{-- 2. Assessment --}}
                                        <div class="flex items-center gap-1 text-[11px] text-purple-600 font-bold whitespace-nowrap" title="Jadwal Assessment">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                            </svg>
                                            <span class="uppercase tracking-tighter">Assessment:</span>
                                            <span>{{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d/m/y') }} - {{ \Carbon\Carbon::parse($item->tanggal_berakhir)->format('d/m/y') }}</span>
                                        </div>

                                        {{-- 3. Visitasi --}}
                                        @if($item->akreditasi->tgl_visitasi)
                                        <div class="flex items-center gap-1 text-[11px] text-indigo-600 font-bold whitespace-nowrap" title="Tanggal Visitasi">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="uppercase tracking-tighter">Visitasi:</span>
                                            <span>{{ \Carbon\Carbon::parse($item->akreditasi->tgl_visitasi)->format('d/m/y') }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-6 text-left font-medium">
                                    <div class="space-y-1">
                                        @php
                                        $visitasiCatatans = $item->akreditasi->catatans->where('tipe', 'visitasi');
                                        @endphp
                                        @foreach($visitasiCatatans as $catatan)
                                        <div class="text-xs p-1 rounded border bg-orange-50 border-orange-200 text-orange-800">
                                            <span class="font-bold uppercase">{{ $catatan->tipe }}:</span> {{ $catatan->catatan }}
                                            <div class="text-[10px] text-gray-500 mt-0.5">{{ $catatan->created_at->format('d M Y H:i') }}</div>
                                        </div>
                                        @endforeach
                                        @if($visitasiCatatans->isEmpty())
                                        <span class="text-gray-400 italic font-normal text-xs">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-6 text-center">
                                    @if ($item->akreditasi->status == 5)
                                    <div class="flex gap-2 justify-center">
                                        <a href="{{ route('asesor.akreditasi-detail', $item->akreditasi->uuid) }}"
                                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-4 rounded text-xs transition duration-150 ease-in-out">
                                            Detail
                                        </a>
                                        @if($item->tipe == 1)
                                        <button wire:click="openVisitasiModal({{ $item->akreditasi->id }})"
                                            class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-1 px-4 rounded text-xs transition duration-150 ease-in-out">
                                            Visitasi
                                        </button>
                                        @endif
                                    </div>
                                    @elseif ($item->akreditasi->status == 4)
                                    <a href="{{ route('asesor.akreditasi-detail', $item->akreditasi->uuid) }}"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-1 px-4 rounded text-xs transition duration-150 ease-in-out">
                                        Input Nilai
                                    </a>
                                    @else
                                    <span class="text-gray-400 italic">Selesai</span>
                                    @endif
                                </td>
                                @else
                                <td colspan="6" class="py-3 px-6 text-center text-gray-400 italic">
                                    Data Akreditasi Tidak Tersedia
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-gray-500">
                                    Belum ada tugas akreditasi yang ditugaskan kepada Anda.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Visitasi -->
    <x-modal name="visitasi-modal" focusable>
        <form wire:submit="submitVisitasi" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Verifikasi Visitasi</h2>
            <p class="mt-1 text-sm text-gray-600">
                Tentukan status visitasi untuk pesantren ini.
            </p>

            <div class="mt-6 space-y-4">
                <div class="flex gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <label class="flex items-center cursor-pointer flex-1">
                        <input type="radio" wire:model.live="visitasi_action" value="terima"
                            class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900">Terima Visitasi</span>
                            <span class="block text-xs text-gray-500">Jadwalkan tanggal visitasi</span>
                        </div>
                    </label>
                    <label class="flex items-center cursor-pointer flex-1">
                        <input type="radio" wire:model.live="visitasi_action" value="tolak"
                            class="w-4 h-4 text-red-600 border-gray-300 focus:ring-red-500">
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900">Tolak Visitasi</span>
                            <span class="block text-xs text-gray-500">Berikan alasan penolakan</span>
                        </div>
                    </label>
                </div>

                <div x-show="$wire.visitasi_action === 'terima'" class="space-y-4">
                    <div>
                        <x-input-label for="visitasi_tanggal" value="Tanggal Visitasi" />
                        <x-text-input wire:model="visitasi_tanggal" id="visitasi_tanggal" type="date"
                            class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('visitasi_tanggal')" class="mt-2" />
                    </div>
                </div>

                <div x-show="$wire.visitasi_action === 'tolak'" class="space-y-4">
                    <div>
                        <x-input-label for="visitasi_catatan" value="Catatan Penolakan" />
                        <textarea wire:model="visitasi_catatan" id="visitasi_catatan" rows="4"
                            class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm"
                            placeholder="Jelaskan alasan penolakan visitasi..."></textarea>
                        <x-input-error :messages="$errors->get('visitasi_catatan')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-primary-button>
                    Simpan
                </x-primary-button>
            </div>
        </form>
    </x-modal>
    @script
    <script>
        $wire.on('open-modal', (name) => {
            // Handle modal opening if using dispatch
        });
    </script>
    @endscript
</div>