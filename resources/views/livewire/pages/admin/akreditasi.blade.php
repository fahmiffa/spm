<?php

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\AkreditasiCatatan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public $akreditasi_id;
    public $asesor_id1;
    public $asesor_id2;
    public $tanggal_mulai;
    public $tanggal_berakhir;
    public $catatan_penolakan;
    public $action_type = 'approve'; // 'approve' or 'reject'
    public $statusFilter = 'pengajuan';
    public $search = '';

    public function mount()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    public function getAkreditasisProperty()
    {
        $query = Akreditasi::with(['user.pesantren', 'assessments'])->orderBy('created_at', 'desc');

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

        return $query->paginate(10);
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

            $akreditasi = Akreditasi::findOrFail($this->akreditasi_id);
            $akreditasi->update([
                'status' => 6, // 6. Pengajuan (Perbaikan)
            ]);

            AkreditasiCatatan::create([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => auth()->id(), // Admin
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
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
            <div class="p-6 text-gray-900">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Akreditasi</h2>
                </div>

                @if (session('status'))
                <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-xl border border-green-100 text-sm font-medium">
                    {{ session('status') }}
                </div>
                @endif

                <!-- Filter Tabs -->
                <div class="flex flex-wrap items-center gap-2 mb-6 border-b border-gray-100 pb-4">
                    <button wire:click="$set('statusFilter', 'pengajuan')"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all duration-200
                        {{ $statusFilter === 'pengajuan' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' }}">
                        Pengajuan <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'pengajuan' ? 'bg-indigo-500' : 'bg-gray-200 text-gray-500' }}">{{ $this->countPengajuan }}</span>
                    </button>
                    <button wire:click="$set('statusFilter', 'assessment')"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all duration-200
                        {{ $statusFilter === 'assessment' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' }}">
                        Assessment <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'assessment' ? 'bg-indigo-500' : 'bg-gray-200 text-gray-500' }}">{{ $this->countAssessment }}</span>
                    </button>
                    <button wire:click="$set('statusFilter', 'visitasi')"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all duration-200
                        {{ $statusFilter === 'visitasi' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' }}">
                        Visitasi <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'visitasi' ? 'bg-indigo-500' : 'bg-gray-200 text-gray-500' }}">{{ $this->countVisitasi }}</span>
                    </button>

                    <div class="ml-auto relative">
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari pesantren..."
                            class="pl-9 pr-4 py-2 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-48 sm:w-64 bg-gray-50">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto rounded-xl border border-gray-100">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Pesantren</th>
                                <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Catatan</th>
                                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-500 uppercase tracking-wider">Nilai</th>
                                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-500 uppercase tracking-wider">Peringkat</th>
                                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($this->akreditasis as $index => $item)
                            <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                                <td class="py-3.5 px-4">
                                    <span class="text-sm font-semibold text-gray-800">{{ $item->user->pesantren->nama_pesantren ?? $item->user->name }}</span>
                                </td>
                                <td class="py-3.5 px-4 max-w-[200px]">
                                    <div class="space-y-1">
                                        @foreach($item->catatans as $catatan)
                                        <div class="text-[11px] p-1.5 rounded-md border {{ $catatan->tipe == 'visitasi' ? 'bg-orange-50 border-orange-100 text-orange-700' : ($catatan->tipe == 'pengajuan' ? 'bg-red-50 border-red-100 text-red-700' : 'bg-gray-50 border-gray-100 text-gray-700') }}">
                                            <span class="font-bold uppercase">{{ $catatan->tipe }}:</span> {{ Str::limit($catatan->catatan, 50) }}
                                        </div>
                                        @endforeach
                                        @if($item->catatans->isEmpty() && !$item->catatan)
                                        <span class="text-gray-300 text-xs">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="{{ Akreditasi::getStatusBadgeClass($item->status) }} py-1 px-3 rounded-full text-[11px] font-bold text-nowrap">
                                        {{ Akreditasi::getStatusLabel($item->status) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-bold text-sm {{ $item->nilai ? 'text-indigo-600' : 'text-gray-300' }}">{{ $item->nilai ?? '-' }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($item->peringkat)
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold 
                                        {{ $item->peringkat == 'Unggul' ? 'bg-green-50 text-green-700 border border-green-200' : 
                                           ($item->peringkat == 'Baik' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 
                                           'bg-yellow-50 text-yellow-700 border border-yellow-200') }}">
                                        {{ $item->peringkat }}
                                    </span>
                                    @else
                                    <span class="text-gray-300 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex flex-col items-center gap-0.5">
                                        <div class="text-[11px] text-gray-500 whitespace-nowrap">{{ $item->created_at->format('d/m/Y') }}</div>
                                        @php $firstAss = $item->assessments->first(); @endphp
                                        @if($firstAss)
                                        <div class="text-[10px] text-purple-600 font-semibold whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($firstAss->tanggal_mulai)->format('d/m') }} - {{ \Carbon\Carbon::parse($firstAss->tanggal_berakhir)->format('d/m/y') }}
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="relative" x-data="{ open: false }">
                                        <button @click="open = !open" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors border border-gray-200">
                                            Aksi
                                            <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                            class="absolute right-0 z-20 mt-1 w-36 bg-white rounded-lg shadow-lg border border-gray-100 py-1" x-cloak>
                                            @if ($item->status == 6)
                                            <button wire:click="openVerifikasiModal({{ $item->id }})" @click="open = false"
                                                class="flex items-center w-full px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-50 transition-colors gap-2">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Verifikasi
                                            </button>
                                            @endif
                                            <a href="{{ route('admin.akreditasi-detail', $item->uuid) }}" wire:navigate
                                                class="flex items-center w-full px-3 py-2 text-xs font-medium text-indigo-700 hover:bg-indigo-50 transition-colors gap-2">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Detail
                                            </a>
                                            <button @click="open = false; confirmDelete({{ $item->id }}, 'delete', 'Pengajuan akreditasi yang dihapus tidak dapat dikembalikan!')"
                                                class="flex items-center w-full px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-50 transition-colors gap-2">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                <td colspan="7" class="py-16 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-10 h-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-sm text-gray-400 font-medium">Belum ada data pengajuan akreditasi</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $this->akreditasis->links() }}
                </div>
            </div>
        </div>
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
</div>