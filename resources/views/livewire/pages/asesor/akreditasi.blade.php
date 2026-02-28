<?php

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\AkreditasiCatatan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    use \Livewire\WithPagination;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'id';
    public $sortAsc = false;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortAsc = true;
        }

        $this->sortField = $field;
    }

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

        return Assessment::with(['akreditasi.user.pesantren', 'akreditasi.catatans'])
            ->where('asesor_id', $asesor->id)
            ->when($this->search, function ($query) {
                $query->whereHas('akreditasi.user.pesantren', function ($q) {
                    $q->where('nama_pesantren', 'like', '%' . $this->search . '%');
                })->orWhereHas('akreditasi.user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
            ->paginate($this->perPage);
    }


    public $visitasi_akreditasi_id;
    public $visitasi_tanggal;
    public $visitasi_tanggal_akhir;
    public $visitasi_catatan;
    public $visitasi_action = 'terima';

    public function openVisitasiModal($id)
    {
        $this->visitasi_akreditasi_id = $id;
        // Reset fields
        $this->visitasi_tanggal = date('Y-m-d');
        $this->visitasi_tanggal_akhir = date('Y-m-d');
        $this->visitasi_catatan = '';
        $this->visitasi_action = 'terima';
        $this->resetErrorBag();

        $this->dispatch('open-modal', 'visitasi-modal');
    }

    public function submitVisitasi()
    {
        $akreditasi = Akreditasi::with('assessments')->find($this->visitasi_akreditasi_id);
        $assessment = $akreditasi->assessments->first(); // Assuming all assessments share the same range

        if ($this->visitasi_action == 'terima') {
            $this->validate([
                'visitasi_action' => 'required',
                'visitasi_tanggal' => [
                    'required',
                    'date',
                    function ($attribute, $value, $fail) use ($assessment) {
                        if ($assessment && ($value < $assessment->tanggal_mulai || $value > $assessment->tanggal_berakhir)) {
                            $fail('Tanggal visitasi harus berada dalam rentang assessment (' . \Carbon\Carbon::parse($assessment->tanggal_mulai)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($assessment->tanggal_berakhir)->format('d/m/Y') . ').');
                        }
                    },
                ],
                'visitasi_tanggal_akhir' => [
                    'required',
                    'date',
                    'after_or_equal:visitasi_tanggal',
                    function ($attribute, $value, $fail) use ($assessment) {
                        if ($assessment && ($value < $assessment->tanggal_mulai || $value > $assessment->tanggal_berakhir)) {
                            $fail('Tanggal visitasi akhir harus berada dalam rentang assessment (' . \Carbon\Carbon::parse($assessment->tanggal_mulai)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($assessment->tanggal_berakhir)->format('d/m/Y') . ').');
                        }

                        $start = \Carbon\Carbon::parse($this->visitasi_tanggal);
                        $end = \Carbon\Carbon::parse($value);
                        if ($start->diffInDays($end) >= 4) {
                            $fail('Rentang visitasi maksimal adalah 4 hari.');
                        }
                    },
                ],
            ]);
        } else {
            $this->validate([
                'visitasi_action' => 'required',
                'visitasi_catatan' => 'required|min:10',
            ], [
                'visitasi_catatan.required' => 'Catatan penolakan wajib diisi.',
                'visitasi_catatan.min' => 'Catatan penolakan minimal 10 karakter.',
            ]);
        }

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
                'tgl_visitasi_akhir' => $this->visitasi_tanggal_akhir,
            ]);

            $rangeStr = \Carbon\Carbon::parse($this->visitasi_tanggal)->format('d/m/Y');
            if ($this->visitasi_tanggal != $this->visitasi_tanggal_akhir) {
                $rangeStr .= ' s/d ' . \Carbon\Carbon::parse($this->visitasi_tanggal_akhir)->format('d/m/Y');
            }

            // Notify Pesantren: Visitasi Scheduled
            $akreditasi->user->notify(new \App\Notifications\AkreditasiNotification(
                'visitasi_diterima',
                'Jadwal Visitasi Ditetapkan',
                'Asesor ' . $asesorName . ' telah menjadwalkan visitasi pada tanggal ' . $rangeStr . '.',
                route('pesantren.akreditasi')
            ));

            // Notify Admin: Visitasi Scheduled
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AkreditasiNotification(
                'visitasi_diterima',
                'Jadwal Visitasi Ditetapkan',
                'Asesor ' . $asesorName . ' telah menetapkan jadwal visitasi untuk pesantren ' . ($akreditasi->user->pesantren->nama_pesantren ?? $akreditasi->user->name) . ' pada tanggal ' . $rangeStr . '.',
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
    <x-slot name="header">
        <h2 class="font-semibold text-gray-800 leading-tight">
            {{ __('Akreditasi') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <x-datatable.layout title="Akreditasi" :records="$this->assessments">
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari Pesantren..." />
            </x-slot>

            <x-slot name="thead">
                <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest pl-6">NO</th>
                <x-datatable.th field="id" :sortField="$sortField" :sortAsc="$sortAsc">
                    PESANTREN
                </x-datatable.th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">STATUS</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">AKREDITASI</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">JADWAL</th>
                <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest">CATATAN</th>
                <th class="py-3 px-4 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest pr-8">AKSI</th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->assessments as $index => $item)
                @if($item->akreditasi)
                <tr class="hover:bg-gray-50/50 transition-colors duration-150 group border-b border-gray-50 last:border-0" wire:key="ass-{{ $item->id }}">
                    <td class="py-5 px-4 pl-6">
                        <span class="text-xs font-bold text-gray-400">{{ ($this->assessments->currentPage() - 1) * $this->assessments->perPage() + $index + 1 }}</span>
                    </td>
                    <td class="py-5 px-4">
                        <span class="text-sm font-bold text-[#374151]">{{ $item->akreditasi->user?->pesantren?->nama_pesantren ?? $item->akreditasi->user?->name ?? 'N/A' }}</span>
                    </td>
                    <td class="py-5 px-4 text-center">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight {{ Akreditasi::getStatusBadgeClass($item->akreditasi->status) }}">
                            {{ Akreditasi::getStatusLabel($item->akreditasi->status) }}
                        </span>
                    </td>
                    <td class="py-5 px-4 text-center">
                        @if ($item->akreditasi->status == 1)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-600 uppercase tracking-tight border border-green-100">{{ $item->akreditasi->peringkat ?? 'Berhasil' }}</span>
                        @elseif (in_array($item->akreditasi->status, [3, 4, 5]))
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-600 uppercase tracking-tight border border-amber-100">Proses</span>
                        @else
                        <span class="text-xs font-bold text-gray-300">-</span>
                        @endif
                    </td>
                    <td class="py-5 px-4">
                        <div class="flex flex-col items-center gap-1">
                            <div class="flex items-center gap-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-tight" title="Tanggal Pengajuan">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>Pengajuan: {{ $item->akreditasi->created_at->format('d/m/y') }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-[10px] font-bold text-purple-600 uppercase tracking-tight" title="Jadwal Assessment">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <span>Assesment: {{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d/m/y') }}</span>
                            </div>
                            @if($item->akreditasi->tgl_visitasi)
                            <div class="flex items-center gap-1.5 text-[10px] font-bold text-indigo-600 uppercase tracking-tight" title="Tanggal Visitasi">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                                <span>Visitasi: {{ \Carbon\Carbon::parse($item->akreditasi->tgl_visitasi)->format('d/m/y') }}
                                    @if($item->akreditasi->tgl_visitasi_akhir && $item->akreditasi->tgl_visitasi != $item->akreditasi->tgl_visitasi_akhir)
                                    - {{ \Carbon\Carbon::parse($item->akreditasi->tgl_visitasi_akhir)->format('d/m/y') }}
                                    @endif
                                </span>
                            </div>
                            @endif
                        </div>
                    </td>
                    <td class="py-5 px-4 max-w-xs">
                        <div class="space-y-1.5">
                            @php
                            $visitasiCatatans = $item->akreditasi->catatans->where('tipe', 'visitasi');
                            @endphp
                            @foreach($visitasiCatatans as $catatan)
                            <div class="text-[10px] font-medium p-1.5 rounded-lg border bg-amber-50/50 border-amber-100 text-amber-800 leading-tight">
                                <span class="font-bold uppercase opacity-75">{{ $catatan->tipe }}:</span> {{ $catatan->catatan }}
                            </div>
                            @endforeach
                            @if($visitasiCatatans->isEmpty())
                            <span class="text-xs font-bold text-gray-300">-</span>
                            @endif
                        </div>
                    </td>
                    <td class="py-5 px-4 text-right pr-6">
                        @if ($item->akreditasi->status == 5)
                        <div class="flex gap-2 justify-end">
                            <a href="{{ route('asesor.akreditasi-detail', $item->akreditasi->uuid) }}" wire:navigate
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-[11px] font-bold text-gray-500 hover:text-gray-800 transition-colors bg-gray-50/80 rounded-lg group-hover:bg-gray-100">
                                Detail
                            </a>
                            @if($item->tipe == 1)
                            <button wire:click="openVisitasiModal({{ $item->akreditasi->id }})"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-[11px] font-bold text-amber-600 hover:text-amber-800 transition-colors bg-amber-50/50 rounded-lg hover:bg-amber-100">
                                Visitasi
                            </button>
                            @endif
                        </div>
                        @elseif ($item->akreditasi->status == 4)
                        <a href="{{ route('asesor.akreditasi-detail', $item->akreditasi->uuid) }}" wire:navigate
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-[11px] font-bold text-indigo-600 hover:text-indigo-800 transition-colors bg-indigo-50/50 rounded-lg hover:bg-indigo-100">
                            Input Nilai
                        </a>
                        @else
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest bg-gray-50 px-3 py-1.5 rounded-lg">Selesai</span>
                        @endif
                    </td>
                </tr>
                @else
                <tr>
                    <td colspan="7" class="py-10 text-center text-gray-400 italic">
                        Data Akreditasi Tidak Tersedia
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="7" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-10 h-10 text-gray-400/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-xs text-gray-400 font-bold">Belum ada tugas akreditasi ditugaskan.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="visitasi_tanggal" value="Tanggal Mulai Visitasi" />
                            <x-text-input wire:model="visitasi_tanggal" id="visitasi_tanggal" type="date"
                                class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('visitasi_tanggal')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="visitasi_tanggal_akhir" value="Tanggal Akhir Visitasi" />
                            <x-text-input wire:model="visitasi_tanggal_akhir" id="visitasi_tanggal_akhir" type="date"
                                class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('visitasi_tanggal_akhir')" class="mt-2" />
                        </div>
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