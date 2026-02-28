<?php

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\Ipm;
use App\Models\SdmPesantren;
use App\Models\Edpm;
use App\Models\MasterEdpmButir;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;
    use \Livewire\WithPagination;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'created_at';
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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isPesantren()) {
            abort(403);
        }
    }

    public function getAkreditasisProperty()
    {
        return Akreditasi::with(['assessments', 'catatans'])
            ->where('user_id', Auth::id())
            ->when($this->search, function ($query) {
                // Search in catatan or rankings jika perlu, tapi biasanya di sini search kurang relevan 
                // karena user hanya melihat datanya sendiri. Tapi kita tambahkan untuk konsistensi.
                $query->where('peringkat', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
            ->paginate($this->perPage);
    }

    public function create()
    {
        $userId = Auth::id();
        $missingData = [];

        // 1. Check Profil Pesantren
        $pesantren = Pesantren::where('user_id', $userId)->first();
        if (!$pesantren) {
            $missingData[] = 'Profil Pesantren belum diisi';
        } else {
            // Check critical fields or documents if needed. For now, just existence.
            if (empty($pesantren->nama_pesantren)) {
                $missingData[] = 'Nama Pesantren di Profil belum diisi';
            }
        }

        // 2. Check IPM
        $ipm = Ipm::where('user_id', $userId)->first();
        if (!$ipm) {
            $missingData[] = 'Data IPM belum diisi';
        } else {
            if (!$ipm->nsp_file) $missingData[] = 'Dokumen NSP di IPM belum diunggah';
            if (!$ipm->lulus_santri_file) $missingData[] = 'Dokumen Kelulusan Santri di IPM belum diunggah';
            if (!$ipm->kurikulum_file) $missingData[] = 'Dokumen Kurikulum di IPM belum diunggah';
            if (!$ipm->buku_ajar_file) $missingData[] = 'Dokumen Buku Ajar di IPM belum diunggah';
        }

        // 3. Check SDM
        // Check if there is at least one SDM record
        $sdmCount = SdmPesantren::where('user_id', $userId)->count();
        if ($sdmCount === 0) {
            $missingData[] = 'Data SDM belum diisi';
        }

        // 4. Check EDPM
        // Compare total evaluated butirs vs total master butirs
        $totalButirs = MasterEdpmButir::count();
        $evaluatedButirs = Edpm::where('user_id', $userId)->count();

        if ($evaluatedButirs < $totalButirs) {
            $missingData[] = 'Evaluasi Diri (EDPM) belum lengkap (' . $evaluatedButirs . '/' . $totalButirs . ' butir terisi)';
        }

        if (!empty($missingData)) {
            $errorMessage = "<ul class='text-left list-disc pl-5 mt-2'><li>" . implode("</li><li>", $missingData) . "</li></ul>";

            $this->dispatch(
                'show-validation-alert',
                title: 'Data Belum Lengkap!',
                html: "Mohon lengkapi data berikut sebelum mengajukan akreditasi:<br>" . $errorMessage
            );
            return;
        }

        $akreditasi = Akreditasi::create([
            'user_id' => $userId,
            'status' => 6, // 6. Pengajuan
        ]);

        // Lock Pesantren Data
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->pesantren->update(['is_locked' => true]);

        // Notify Admin
        $admins = \App\Models\User::whereHas('role', function ($q) {
            $q->where('id', 1);
        })->get();
        \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AkreditasiNotification(
            'pengajuan',
            'Pengajuan Akreditasi Baru',
            'Pesantren ' . ($user->pesantren->nama_pesantren ?? $user->name) . ' telah membuat pengajuan akreditasi baru.',
            route('admin.akreditasi')
        ));

        session()->flash('status', 'Pengajuan akreditasi berhasil dibuat.');
    }

    public function delete($id)
    {
        $akreditasi = Akreditasi::where('user_id', Auth::id())->findOrFail($id);
        $akreditasi->delete();

        // Unlock Pesantren Data
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->pesantren->update(['is_locked' => false]);

        session()->flash('status', 'Pengajuan akreditasi berhasil dihapus. Data profil telah dibuka kunci.');
    }

    public function banding($id, $alasan)
    {
        $akreditasi = Akreditasi::where('user_id', Auth::id())->findOrFail($id);

        // Ensure status is 2 (Rejected) and has assessments
        if ($akreditasi->status == 2 && $akreditasi->assessments()->exists()) {
            $akreditasi->update([
                'status' => 3, // 3. Validasi
                'catatan' => $alasan,
            ]);

            // Notify Admin
            $admins = \App\Models\User::whereHas('role', function ($q) {
                $q->where('id', 1);
            })->get();
            /** @var \App\Models\User $user */
            $user = Auth::user();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AkreditasiNotification(
                'banding',
                'Pengajuan Banding Baru',
                'Pesantren ' . ($user->pesantren->nama_pesantren ?? $user->name) . ' telah mengajukan banding akreditasi.',
                route('admin.akreditasi')
            ));

            session()->flash('status', 'Pengajuan banding berhasil dikirim. Status berubah menjadi Validasi.');
        } else {
            session()->flash('error', 'Gagal mengajukan banding. Pastikan status pengajuan adalah Ditolak dan sudah melalui tahap Assessment.');
        }
    }
}; ?>

<div class="py-12" x-data="{
    confirmCreate() {
        Swal.fire({
            title: 'Konfirmasi Pengajuan',
            text: 'Apakah Anda yakin ingin membuat pengajuan akreditasi baru? Data profil dan administrasi akan dikunci selama proses berlangsung.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1e3a5f',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Ajukan Sekarang',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $wire.create();
            }
        });
    },
    confirmDelete(id) {
        Swal.fire({
            title: 'Hapus Pengajuan?',
            text: 'Data pengajuan ini akan dihapus secara permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $wire.delete(id);
            }
        });
    },
    confirmBanding(id) {
        Swal.fire({
            title: 'Ajukan Banding',
            text: 'Tuliskan alasan atau catatan banding Anda:',
            input: 'textarea',
            inputPlaceholder: 'Masukkan catatan di sini...',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Kirim Banding',
            cancelButtonText: 'Batal',
            inputValidator: (value) => {
                if (!value) {
                    return 'Alasan banding wajib diisi!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $wire.banding(id, result.value);
            }
        });
    }
}" x-init="
    window.addEventListener('show-validation-alert', event => {
        Swal.fire({
            icon: 'error',
            title: event.detail.title,
            html: event.detail.html,
            confirmButtonColor: '#ef4444'
        });
    });
">
    <x-slot name="header">
        <h2 class="font-semibold text-gray-800 leading-tight">
            {{ __('Akreditasi') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <x-datatable.layout title="Daftar Akreditasi" :records="$this->akreditasis">
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari Peringkat..." />

                <button @click="confirmCreate"
                    class="bg-[#1e3a5f] text-white px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-[#162d4a] transition-all shadow-sm active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Buat Pengajuan
                </button>
            </x-slot>

            <x-slot name="thead">
                <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest pl-6">NO</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">STATUS</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">NILAI</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">PERINGKAT</th>
                <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest">CATATAN</th>
                <x-datatable.th field="created_at" :sortField="$sortField" :sortAsc="$sortAsc">
                    JADWAL
                </x-datatable.th>
                <th class="py-3 px-4 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest pr-8">AKSI</th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->akreditasis as $index => $item)
                <tr class="hover:bg-gray-50/50 transition-colors duration-150 group border-b border-gray-50 last:border-0" wire:key="akred-{{ $item->id }}">
                    <td class="py-5 px-4 pl-6">
                        <span class="text-xs font-bold text-gray-400">{{ ($this->akreditasis->currentPage() - 1) * $this->akreditasis->perPage() + $index + 1 }}</span>
                    </td>
                    <td class="py-5 px-4 text-center">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight {{ Akreditasi::getStatusBadgeClass($item->status) }}">
                            {{ Akreditasi::getStatusLabel($item->status) }}
                        </span>
                    </td>
                    <td class="py-5 px-4 text-center">
                        <span class="text-sm font-bold text-indigo-600">{{ $item->nilai ?? '-' }}</span>
                    </td>
                    <td class="py-5 px-4 text-center">
                        @if($item->peringkat)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight 
                            {{ $item->peringkat == 'Unggul' ? 'bg-green-50 text-green-600 border border-green-100' : 
                               ($item->peringkat == 'Baik' ? 'bg-blue-50 text-blue-600 border border-blue-100' : 
                               'bg-yellow-50 text-yellow-600 border border-yellow-100') }}">
                            {{ $item->peringkat }}
                        </span>
                        @else
                        <span class="text-xs font-bold text-gray-300">-</span>
                        @endif
                    </td>
                    <td class="py-5 px-4 max-w-xs">
                        <div class="space-y-1.5">
                            @foreach($item->catatans as $catatan)
                            <div class="text-[10px] font-medium p-1.5 rounded-lg border {{ $catatan->tipe == 'visitasi' ? 'bg-orange-50/50 border-orange-100 text-orange-800' : 'bg-red-50/50 border-red-100 text-red-800' }} leading-tight">
                                <span class="font-bold uppercase opacity-75">{{ $catatan->tipe }}:</span> {{ $catatan->catatan }}
                            </div>
                            @endforeach
                            @if($item->catatan)
                            <div class="text-[10px] italic text-gray-500 mt-1 border-t border-gray-100 pt-1">
                                Logs: {{ $item->catatan }}
                            </div>
                            @endif
                            @if($item->catatans->isEmpty() && !$item->catatan)
                            <span class="text-xs font-bold text-gray-300">-</span>
                            @endif
                        </div>
                    </td>
                    <td class="py-5 px-4">
                        <div class="flex flex-col items-center gap-1">
                            <div class="flex items-center gap-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-tight" title="Tanggal Pengajuan">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>Pengajuan: {{ $item->created_at->format('d/m/y') }}</span>
                            </div>
                            @php $firstAss = $item->assessments->first(); @endphp
                            @if($firstAss)
                            <div class="flex items-center gap-1.5 text-[10px] font-bold text-purple-600 uppercase tracking-tight" title="Jadwal Assessment">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <span>Assessment: {{ \Carbon\Carbon::parse($firstAss->tanggal_mulai)->format('d/m/y') }}</span>
                            </div>
                            @endif
                            @if($item->tgl_visitasi)
                            <div class="flex items-center gap-1.5 text-[10px] font-bold text-indigo-600 uppercase tracking-tight" title="Tanggal Visitasi">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                                <span>Visitasi: {{ \Carbon\Carbon::parse($item->tgl_visitasi)->format('d/m/y') }}
                                    @if($item->tgl_visitasi_akhir && $item->tgl_visitasi != $item->tgl_visitasi_akhir)
                                    - {{ \Carbon\Carbon::parse($item->tgl_visitasi_akhir)->format('d/m/y') }}
                                    @endif
                                </span>
                            </div>
                            @endif
                        </div>
                    </td>
                    <td class="py-5 px-4 text-right pr-6">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('pesantren.akreditasi-detail', $item->uuid) }}" wire:navigate
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-[11px] font-bold text-gray-500 hover:text-gray-800 transition-colors bg-gray-50/80 rounded-lg group-hover:bg-gray-100">
                                Detail
                            </a>

                            @if ($item->status == 3)
                            @if(!$item->kartu_kendali)
                            <a href="{{ route('pesantren.akreditasi-detail', ['uuid' => $item->uuid, 'activeTab' => 'kartu']) }}" wire:navigate
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-[11px] font-bold text-indigo-600 hover:text-indigo-800 transition-colors bg-indigo-50/50 rounded-lg hover:bg-indigo-100 uppercase">
                                Upload Kartu
                            </a>
                            @else
                            <a href="{{ Storage::url($item->kartu_kendali) }}" target="_blank"
                                class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Lihat Kartu">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            @endif
                            @endif

                            @if ($item->status == 2 && $item->assessments()->exists())
                            <button @click="confirmBanding({{ $item->id }})"
                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Ajukan Banding">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            @endif

                            <button @click="confirmDelete({{ $item->id }})"
                                class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-10 h-10 text-gray-400/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-xs text-gray-400 font-bold">Belum ada data pengajuan akreditasi.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </div>

</div>