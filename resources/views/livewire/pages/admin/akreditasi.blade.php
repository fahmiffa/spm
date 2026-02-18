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

    public function mount()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    public function getAkreditasisProperty()
    {
        return Akreditasi::with(['user.pesantren', 'assessments'])->orderBy('created_at', 'desc')->get();
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
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Akreditasi</h2>
                </div>

                @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                    {{ session('status') }}
                </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">No</th>
                                <th class="py-3 px-6 text-left">Pesantren</th>
                                <th class="py-3 px-6 text-center">Catatan</th>
                                <th class="py-3 px-6 text-center">Status</th>
                                <th class="py-3 px-6 text-center">Nilai</th>
                                <th class="py-3 px-6 text-center">Peringkat</th>
                                <th class="py-3 px-6 text-center">Tanggal</th>
                                <th class="py-3 px-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-xs md:text-sm font-light">
                            @forelse ($this->akreditasis as $index => $item)
                            <tr class="border-b border-gray-200 hover:bg-gray-100">
                                <td class="py-3 px-6 text-left whitespace-nowrap">
                                    {{ $index + 1 }}
                                </td>
                                <td class="py-3 px-6 text-left font-medium">
                                    {{ $item->user->pesantren->nama_pesantren ?? $item->user->name }}
                                </td>
                                <td class="py-3 px-6 text-left font-medium">
                                    <div class="space-y-1">
                                        @foreach($item->catatans as $catatan)
                                        <div class="text-xs p-1 rounded border {{ $catatan->tipe == 'visitasi' ? 'bg-orange-50 border-orange-200 text-orange-800' : ($catatan->tipe == 'pengajuan' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-gray-50 border-gray-200 text-gray-800') }}">
                                            <span class="font-bold uppercase">{{ $catatan->tipe }}:</span> {{ $catatan->catatan }}
                                            <div class="text-[10px] text-gray-500 mt-0.5">{{ $catatan->created_at->format('d M Y H:i') }}</div>
                                        </div>
                                        @endforeach
                                        @if($item->catatan && $item->catatans->isEmpty())
                                        <div class="text-xs mt-1 text-gray-600 border-t pt-1">Logs Lama: {{ $item->catatan }}</div>
                                        @endif
                                        @if($item->catatans->isEmpty() && !$item->catatan)
                                        <span class="text-gray-400 italic">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <span
                                        class="{{ Akreditasi::getStatusBadgeClass($item->status) }} py-1 px-3 rounded-full text-xs font-semibold text-nowrap">
                                        {{ Akreditasi::getStatusLabel($item->status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-6 text-center font-bold text-indigo-600">
                                    {{ $item->nilai ?? '-' }}
                                </td>
                                <td class="py-3 px-6 text-center">
                                    @if($item->peringkat)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold 
                                        {{ $item->peringkat == 'Unggul' ? 'bg-green-100 text-green-700' : 
                                           ($item->peringkat == 'Baik' ? 'bg-blue-100 text-blue-700' : 
                                           'bg-yellow-100 text-yellow-700') }}">
                                        {{ $item->peringkat }}
                                    </span>
                                    @else
                                    -
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
                                            <span>{{ $item->created_at->format('d/m/y') }}</span>
                                        </div>

                                        {{-- 2. Assessment --}}
                                        @php $firstAss = $item->assessments->first(); @endphp
                                        @if($firstAss)
                                        <div class="flex items-center gap-1 text-[11px] text-purple-600 font-bold whitespace-nowrap" title="Jadwal Assessment">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                            </svg>
                                            <span class="uppercase tracking-tighter">Assessment:</span>
                                            <span>{{ \Carbon\Carbon::parse($firstAss->tanggal_mulai)->format('d/m/y') }} - {{ \Carbon\Carbon::parse($firstAss->tanggal_berakhir)->format('d/m/y') }}</span>
                                        </div>
                                        @endif

                                        {{-- 3. Visitasi --}}
                                        @if($item->tgl_visitasi)
                                        <div class="flex items-center gap-1 text-[11px] text-indigo-600 font-bold whitespace-nowrap" title="Tanggal Visitasi">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="uppercase tracking-tighter">Visitasi:</span>
                                            <span>{{ \Carbon\Carbon::parse($item->tgl_visitasi)->format('d/m/y') }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <div class="flex item-center justify-center gap-4">
                                        @if ($item->status == 6)
                                        <button wire:click="openVerifikasiModal({{ $item->id }})"
                                            class="text-blue-600 hover:text-blue-900 font-medium">
                                            Verifikasi
                                        </button>
                                        @endif
                                        <a href="{{ route('admin.akreditasi-detail', $item->uuid) }}"
                                            class="text-indigo-600 hover:text-indigo-900 font-medium"
                                            wire:navigate>
                                            Detail
                                        </a>

                                        <button @click="confirmDelete({{ $item->id }}, 'delete', 'Pengajuan akreditasi yang dihapus tidak dapat dikembalikan!')"
                                            class="text-red-600 hover:text-red-900 font-medium">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-gray-500">
                                    Belum ada data pengajuan akreditasi.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
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