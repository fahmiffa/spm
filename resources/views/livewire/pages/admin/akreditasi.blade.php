<?php

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\AkreditasiCatatan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.app')] class extends Component {
    use \Livewire\WithPagination;
    public $akreditasi_id;
    public $asesor_id1;
    public $asesor_id2;
    public $tanggal_mulai;
    public $tanggal_berakhir;
    public $catatan_penolakan;
    public $action_type = 'approve'; // 'approve' or 'reject'
    public $statusFilter = 'pengajuan';
    public $search = '';
    public $perPage = 10;
    public $sortField = 'created_at';
    public $sortAsc = false;
    public $selectedAkreditasiNotes;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
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

    public function openCatatanModal($id)
    {
        $this->selectedAkreditasiNotes = Akreditasi::with(['catatans.user'])->find($id);
        $this->dispatch('open-modal', 'catatan-modal');
    }

    public function mount()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403);
        }
    }

    public function getAkreditasisProperty()
    {
        $query = Akreditasi::with(['user.pesantren', 'assessments', 'catatans.user']);

        if ($this->statusFilter === 'pengajuan') {
            $query->where('status', 6);
        } elseif ($this->statusFilter === 'assessment') {
            $query->where('status', 5);
        } elseif ($this->statusFilter === 'visitasi') {
            $query->where('status', '<=', 4);
        }

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('pesantren', function ($q2) {
                        $q2->where('nama_pesantren', 'like', '%' . $this->search . '%');
                    });
            });
        }

        return $query->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
            ->paginate($this->perPage);
    }

    public function getCountPengajuanProperty()
    {
        return Akreditasi::where('status', 6)->count();
    }

    public function getCountAssessmentProperty()
    {
        return Akreditasi::where('status', 5)->count();
    }

    public function getCountVisitasiProperty()
    {
        return Akreditasi::where('status', '<=', 4)->count();
    }

    public function getAsesorsProperty()
    {
        return Asesor::with('user')
            ->whereDoesntHave('assessments', function ($query) {
                $query->whereHas('akreditasi', function ($q) {
                    $q->whereNotIn('status', [1, 2]);
                });
            })
            ->get();
    }

    public function delete($id)
    {
        Akreditasi::findOrFail($id)->delete();
        session()->flash('status', 'Pengajuan akreditasi berhasil dihapus.');
    }

    public function openVerifikasiModal($id)
    {
        $this->akreditasi_id = $id;
        $this->asesor_id1 = '';
        $this->asesor_id2 = '';
        $this->tanggal_mulai = '';
        $this->tanggal_berakhir = '';
        $this->catatan_penolakan = '';
        $this->action_type = 'approve';
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'verifikasi-modal');
    }

    public function verifikasi()
    {
        // Validasi berdasarkan action_type
        if ($this->action_type === 'approve') {
            $this->validate([
                'asesor_id1' => 'required|exists:asesors,id',
                'asesor_id2' => 'nullable|exists:asesors,id|different:asesor_id1',
                'tanggal_mulai' => 'required|date',
                'tanggal_berakhir' => 'required|date|after_or_equal:tanggal_mulai',
            ], [
                'asesor_id1.required' => 'Asesor 1 wajib dipilih.',
                'asesor_id1.exists' => 'Asesor 1 tidak valid.',
                'asesor_id2.exists' => 'Asesor 2 tidak valid.',
                'asesor_id2.different' => 'Asesor 1 dan Asesor 2 harus berbeda.',
                'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
                'tanggal_mulai.date' => 'Format tanggal mulai salah.',
                'tanggal_berakhir.required' => 'Tanggal berakhir wajib diisi.',
                'tanggal_berakhir.date' => 'Format tanggal berakhir salah.',
                'tanggal_berakhir.after_or_equal' => 'Tanggal berakhir harus sama atau setelah tanggal mulai.',
            ]);

            // Clear existing assessments first
            Assessment::where('akreditasi_id', $this->akreditasi_id)->delete();

            // Create Asesor 1
            Assessment::create([
                'akreditasi_id' => $this->akreditasi_id,
                'asesor_id' => $this->asesor_id1,
                'tipe' => 1,
                'tanggal_mulai' => $this->tanggal_mulai,
                'tanggal_berakhir' => $this->tanggal_berakhir,
            ]);

            // Create Asesor 2 if selected
            if ($this->asesor_id2) {
                Assessment::create([
                    'akreditasi_id' => $this->akreditasi_id,
                    'asesor_id' => $this->asesor_id2,
                    'tipe' => 2,
                    'tanggal_mulai' => $this->tanggal_mulai,
                    'tanggal_berakhir' => $this->tanggal_berakhir,
                ]);
            }

            $akreditasi = Akreditasi::findOrFail($this->akreditasi_id);
            $akreditasi->update(['status' => 5]); // 5. Assessment

            // Notify Pesantren
            $akreditasi->user->notify(new \App\Notifications\AkreditasiNotification('assessment', 'Update Status: Assessment', 'Pengajuan akreditasi Anda telah diverifikasi dan masuk tahap Assessment.', route('pesantren.akreditasi')));

            // Notify Asesor 1
            $asesor1 = Asesor::with('user')->find($this->asesor_id1);
            if ($asesor1 && $asesor1->user) {
                $asesor1->user->notify(new \App\Notifications\AkreditasiNotification('tugas_baru', 'Tugas Assessment Baru', 'Anda telah ditugaskan sebagai asesor 1 untuk pesantren ' . ($akreditasi->user->pesantren->nama_pesantren ?? $akreditasi->user->name), route('asesor.akreditasi')));
            }

            // Notify Asesor 2
            if ($this->asesor_id2) {
                $asesor2 = Asesor::with('user')->find($this->asesor_id2);
                if ($asesor2 && $asesor2->user) {
                    $asesor2->user->notify(new \App\Notifications\AkreditasiNotification('tugas_baru', 'Tugas Assessment Baru', 'Anda telah ditugaskan sebagai asesor 2 untuk pesantren ' . ($akreditasi->user->pesantren->nama_pesantren ?? $akreditasi->user->name), route('asesor.akreditasi')));
                }
            }

            session()->flash('status', 'Pengajuan berhasil diverifikasi. Status berubah menjadi Assesment.');
        } else {
            // Reject action
            $this->validate([
                'catatan_penolakan' => 'required|string|min:10',
            ], [
                'catatan_penolakan.required' => 'Catatan penolakan wajib diisi.',
                'catatan_penolakan.min' => 'Catatan penolakan minimal 10 karakter.',
            ]);

            /** @var Akreditasi $akreditasi */
            $akreditasi = Akreditasi::findOrFail($this->akreditasi_id);
            $akreditasi->update([
                'status' => 6, // 6. Pengajuan (Perbaikan)
            ]);

            AkreditasiCatatan::create([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => Auth::id(), // Admin
                'tipe' => 'pengajuan',
                'catatan' => $this->catatan_penolakan,
            ]);

            // Notify Pesantren
            $akreditasi->user->notify(new \App\Notifications\AkreditasiNotification('di stop', 'Pengajuan Perlu Perbaikan', 'Pengajuan akreditasi Anda ditolak oleh admin. Catatan: ' . $this->catatan_penolakan . '. Silahkan perbaiki dokumen dan ajukan kembali.', route('pesantren.akreditasi')));

            session()->flash('status', 'Pengajuan berhasil di stop dan status tetap Menunggu Verifikasi untuk perbaikan.');
        }

        $this->dispatch('close-modal', 'verifikasi-modal');
    }
}; ?>

<div class="py-12" x-data="deleteConfirmation">
    <x-slot name="header">{{ __('Akreditasi') }}</x-slot>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <x-datatable.layout title="Akreditasi" :records="$this->akreditasis">
            <x-slot name="filters">
                <div class="flex flex-wrap items-center gap-1 bg-gray-50/50 p-1 rounded-xl border border-gray-100 mr-2">
                    <button wire:click="$set('statusFilter', 'pengajuan')"
                        class="px-4 py-1.5 text-[11px] font-bold rounded-lg transition-all
                        {{ $statusFilter === 'pengajuan' ? 'bg-[#1e3a5f] text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                        Pengajuan ({{ $this->countPengajuan }})
                    </button>
                    <button wire:click="$set('statusFilter', 'assessment')"
                        class="px-4 py-1.5 text-[11px] font-bold rounded-lg transition-all
                        {{ $statusFilter === 'assessment' ? 'bg-[#1e3a5f] text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                        Assessment ({{ $this->countAssessment }})
                    </button>
                    <button wire:click="$set('statusFilter', 'visitasi')"
                        class="px-4 py-1.5 text-[11px] font-bold rounded-lg transition-all
                        {{ $statusFilter === 'visitasi' ? 'bg-[#1e3a5f] text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                        Visitasi ({{ $this->countVisitasi }})
                    </button>
                </div>

                <x-datatable.search placeholder="Cari Pesantren..." />

                <button class="bg-[#1e3a5f] text-white px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-[#162d4a] transition-all shadow-sm active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Ekspor Data
                </button>
            </x-slot>

            <x-slot name="thead">
                <th class="w-12 py-3 px-4">
                    <input type="checkbox" class="rounded border-gray-300 text-green-600 focus:ring-green-500 bg-gray-100 h-4 w-4">
                </th>
                <x-datatable.th field="user_id" :sortField="$sortField" :sortAsc="$sortAsc">
                    PESANTREN
                </x-datatable.th>
                <x-datatable.th field="created_at" :sortField="$sortField" :sortAsc="$sortAsc">
                    TAHAP AKREDITASI
                </x-datatable.th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">NILAI</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">PERINGKAT</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">STATUS</th>
                <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest">CATATAN</th>
                <th class="py-3 px-4 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest pr-8">AKSI</th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->akreditasis as $index => $item)
                <tr class="hover:bg-gray-50/50 transition-colors duration-150 group border-b border-gray-50 last:border-0" wire:key="akred-{{ $item->id }}">
                    <td class="py-5 px-4">
                        <input type="checkbox" class="rounded border-gray-300 text-green-600 focus:ring-green-500 bg-gray-100 h-4 w-4">
                    </td>
                    <td class="py-5 px-4">
                        <span class="text-sm font-bold text-[#374151]">{{ $item->user->pesantren->nama_pesantren ?? $item->user->name }}</span>
                    </td>
                    <td class="py-5 px-4 text-xs font-bold text-gray-500">
                        @if($item->status == 6)
                        <span class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                            Pengajuan: {{ $item->created_at->format('d/m/y') }}
                        </span>
                        @elseif($item->status == 5)
                        <span class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                            Assessment: {{ $item->assessment1 ? \Carbon\Carbon::parse($item->assessment1->tanggal_mulai)->format('d/m/y') : '-' }}
                        </span>
                        @else
                        <span class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                            Visitasi: {{ $item->tgl_visitasi ? \Carbon\Carbon::parse($item->tgl_visitasi)->format('d/m/y') : '-' }}
                            @if($item->tgl_visitasi_akhir && $item->tgl_visitasi != $item->tgl_visitasi_akhir)
                            - {{ \Carbon\Carbon::parse($item->tgl_visitasi_akhir)->format('d/m/y') }}
                            @endif
                        </span>
                        @endif
                    </td>
                    <td class="py-5 px-4 text-center">
                        <span class="text-sm font-bold text-gray-300">{{ $item->nilai ?? '-' }}</span>
                    </td>
                    <td class="py-5 px-4 text-center">
                        <span class="text-sm font-bold text-gray-300">{{ $item->peringkat ?? '-' }}</span>
                    </td>
                    <td class="py-5 px-4 text-center">
                        @if($item->status >= 3)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-600 uppercase tracking-tight border border-amber-100">Proses</span>
                        @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight {{ Akreditasi::getStatusBadgeClass($item->status) }}">
                            {{ Akreditasi::getStatusLabel($item->status) }}
                        </span>
                        @endif
                    </td>
                    <td class="py-5 px-4">
                        <button wire:click="openCatatanModal({{ $item->id }})" class="flex items-center gap-2 text-[10px] font-extrabold text-[#111827] hover:text-blue-600 transition-colors uppercase tracking-tight bg-gray-50 py-1 px-2.5 rounded-lg border border-gray-100">
                            <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ $item->catatans->count() }} Catatan
                        </button>
                    </td>
                    <td class="py-5 px-4 text-right pr-6 overflow-visible">
                        <div class="relative inline-block text-left" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-gray-400 hover:text-gray-700 transition-colors bg-gray-50/50 rounded-lg group-hover:bg-gray-100">
                                Aksi
                                <svg class="w-3 h-3 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 z-[100] mt-1 w-44 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 origin-top-right overflow-hidden shadow-slate-200/50" x-cloak>
                                @if ($item->status == 6)
                                <button wire:click="openVerifikasiModal({{ $item->id }})" @click="open = false"
                                    class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-blue-700 hover:bg-blue-50 transition-colors gap-3">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Verifikasi
                                </button>
                                @endif
                                <a href="{{ route('admin.akreditasi-detail', $item->uuid) }}" wire:navigate
                                    class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition-colors gap-3 border-t border-gray-50/50">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Lihat Detail
                                </a>
                                <button @click="open = false; confirmDelete({{ $item->id }}, 'delete', 'Pengajuan akreditasi yang dihapus tidak dapat dikembalikan!')"
                                    class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-rose-600 hover:bg-rose-50 transition-colors gap-3 border-t border-gray-50/50">
                                    <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-10 h-10 text-gray-400/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-xs text-gray-400 font-bold">Data tidak ditemukan.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </div>

    <!-- Modal Verifikasi -->
    <x-modal name="verifikasi-modal" focusable>
        <form wire:submit="verifikasi" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Verifikasi Pengajuan Akreditasi</h2>
            <p class="mt-1 text-sm text-gray-600">
                Pilih tindakan yang akan dilakukan untuk pengajuan akreditasi ini.
            </p>

            <!-- Action Type Selection -->
            <div class="mt-6 space-y-4">
                <div class="flex gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <label class="flex items-center cursor-pointer flex-1">
                        <input type="radio" wire:model.live="action_type" value="approve"
                            class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900">Lanjutkan Proses</span>
                            <span class="block text-xs text-gray-500">Pilih asesor dan jadwal assessment</span>
                        </div>
                    </label>
                    <label class="flex items-center cursor-pointer flex-1">
                        <input type="radio" wire:model.live="action_type" value="reject"
                            class="w-4 h-4 text-red-600 border-gray-300 focus:ring-red-500">
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900">Stop Pengajuan</span>
                            <span class="block text-xs text-gray-500">Berikan catatan penolakan</span>
                        </div>
                    </label>
                </div>

                <!-- Form Approve: Pilih Asesor -->
                <div x-show="$wire.action_type === 'approve'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="asesor_id1" value="Ketua Asesor" />
                            <select wire:model="asesor_id1" id="asesor_id1"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Pilih Ketua Asesor</option>
                                @foreach ($this->asesors as $asesor)
                                <option value="{{ $asesor->id }}">{{ $asesor->user->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('asesor_id1')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="asesor_id2" value="Anggota Asesor" />
                            <select wire:model="asesor_id2" id="asesor_id2"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Pilih Anggota Asesor</option>
                                @foreach ($this->asesors as $asesor)
                                <option value="{{ $asesor->id }}">{{ $asesor->user->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('asesor_id2')" class="mt-2" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="tanggal_mulai" value="Tanggal Mulai Assessment" />
                            <x-text-input wire:model="tanggal_mulai" id="tanggal_mulai" type="date"
                                class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('tanggal_mulai')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="tanggal_berakhir" value="Tanggal Berakhir Assessment" />
                            <x-text-input wire:model="tanggal_berakhir" id="tanggal_berakhir" type="date"
                                class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('tanggal_berakhir')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Form Reject: Catatan Penolakan -->
                <div x-show="$wire.action_type === 'reject'" x-transition class="space-y-4">
                    <div>
                        <x-input-label for="catatan_penolakan" value="Catatan Penolakan" />
                        <textarea wire:model="catatan_penolakan" id="catatan_penolakan" rows="4"
                            class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm"
                            placeholder="Jelaskan alasan penolakan pengajuan akreditasi ini..."></textarea>
                        <x-input-error :messages="$errors->get('catatan_penolakan')" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-500">Minimal 10 karakter</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-primary-button
                    x-bind:class="$wire.action_type === 'reject' ? 'bg-red-600 hover:bg-red-700 focus:bg-red-700 active:bg-red-900' : ''">
                    <span x-show="$wire.action_type === 'approve'">Lanjutkan</span>
                    <span x-show="$wire.action_type === 'reject'">Stop</span>
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- Modal Catatan (View Only) -->
    <x-modal name="catatan-modal" focusable>
        <div class="p-6">
            <div class="flex justify-between items-center mb-6 border-b pb-4">
                <h2 class="text-xl font-bold text-gray-800">Catatan</h2>
                <button x-on:click="$dispatch('close')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @if($selectedAkreditasiNotes)
            <div class="space-y-6 max-h-[70vh] overflow-y-auto pr-2 custom-scrollbar">
                @forelse($selectedAkreditasiNotes->catatans as $catatan)
                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($catatan->user->name) }}&color=7F9CF5&background=EBF4FF" class="w-10 h-10 rounded-full border-2 border-white shadow-sm" alt="Avatar">
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-bold text-gray-800 mb-2">{{ $catatan->user->name }}</div>
                        <div class="bg-yellow-50 border border-yellow-100 p-4 rounded-xl text-xs text-gray-700 leading-relaxed shadow-sm">
                            {!! nl2br(e($catatan->catatan)) !!}
                        </div>
                        <div class="mt-2 text-[10px] text-gray-400 font-medium tracking-wide">{{ $catatan->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
                @empty
                <div class="text-center py-12">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-gray-400 font-medium">Tidak ada catatan untuk akreditasi ini.</p>
                </div>
                @endforelse
            </div>
            @endif
        </div>
    </x-modal>
</div>