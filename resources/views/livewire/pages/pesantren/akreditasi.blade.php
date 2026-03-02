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
                    <svg width="15" height="18" viewBox="0 0 15 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14.4084 7.68333C14.4029 7.14283 14.2563 6.61315 13.9829 6.14683C13.7096 5.68052 13.319 5.29378 12.8501 5.025L12.6917 4.93333V3.075C12.6917 2.67119 12.6122 2.27132 12.4577 1.89825C12.3031 1.52517 12.0766 1.18619 11.7911 0.900647C11.5055 0.615107 11.1665 0.388604 10.7935 0.23407C10.4204 0.0795372 10.0205 0 9.61672 0H4.83339C4.01785 0 3.23571 0.323972 2.65903 0.900647C2.08236 1.47732 1.75839 2.25946 1.75839 3.075V4.975L1.51672 5.11667C1.05186 5.39282 0.667311 5.78576 0.401255 6.25648C0.135199 6.72719 -0.00311189 7.25931 5.31235e-05 7.8V12.5583C0.00549846 13.0988 0.152176 13.6285 0.425532 14.0948C0.698889 14.5611 1.08942 14.9479 1.55839 15.2167L5.72505 17.5667C6.19532 17.8372 6.72835 17.9796 7.27089 17.9796C7.81342 17.9796 8.34645 17.8372 8.81672 17.5667L12.9084 15.15C13.3754 14.8755 13.762 14.4832 14.0297 14.0122C14.2973 13.5413 14.4365 13.0083 14.4334 12.4667L14.4084 7.68333ZM3.00839 3.075C3.00839 2.59098 3.20066 2.12678 3.54292 1.78453C3.88517 1.44228 4.34937 1.25 4.83339 1.25H9.61672C10.1007 1.25 10.5649 1.44228 10.9072 1.78453C11.2494 2.12678 11.4417 2.59098 11.4417 3.075V4.225L8.70839 2.675C8.5895 2.60992 8.46709 2.5515 8.34172 2.5C7.96316 2.36172 7.56105 2.29943 7.15839 2.31667C6.89071 2.3152 6.62417 2.35167 6.36672 2.425C6.10322 2.49518 5.85079 2.60176 5.61672 2.74167L3.00839 4.24167V3.075ZM12.2917 14.05L8.19172 16.4833C7.91728 16.6389 7.60719 16.7207 7.29172 16.7207C6.97625 16.7207 6.66616 16.6389 6.39172 16.4833L2.22505 14.1333C1.946 13.9732 1.71377 13.7428 1.55148 13.465C1.38919 13.1872 1.3025 12.8717 1.30005 12.55V7.79167C1.29271 7.47354 1.36791 7.15895 1.51834 6.87854C1.66877 6.59813 1.88928 6.36148 2.15839 6.19167L6.25005 3.78333C6.53057 3.62331 6.84794 3.53915 7.17089 3.53915C7.49384 3.53915 7.81121 3.62331 8.09172 3.78333L12.2584 6.125C12.5374 6.28513 12.7697 6.51556 12.932 6.79336C13.0942 7.07115 13.1809 7.38662 13.1834 7.70833L13.2251 12.4667C13.2226 12.7894 13.135 13.1057 12.9711 13.3837C12.8072 13.6618 12.5729 13.8916 12.2917 14.05Z" fill="white" />
                        <path d="M8.384 13.3496C8.15053 13.3488 7.9211 13.2886 7.71734 13.1746L7.284 12.9496C7.23328 12.9288 7.17639 12.9288 7.12567 12.9496L6.69234 13.1829C6.45634 13.3031 6.19154 13.3551 5.92764 13.3331C5.66373 13.3112 5.41116 13.2161 5.19828 13.0586C4.98539 12.9011 4.8206 12.6874 4.7224 12.4415C4.6242 12.1955 4.59648 11.9271 4.64234 11.6663L4.72567 11.1829C4.73807 11.1599 4.74456 11.1341 4.74456 11.1079C4.74456 11.0818 4.73807 11.056 4.72567 11.0329L4.334 10.6496C4.14321 10.4666 4.00885 10.2328 3.94682 9.97581C3.88479 9.71884 3.8977 9.44947 3.984 9.1996C4.06622 8.94791 4.21772 8.72449 4.42113 8.55498C4.62454 8.38548 4.87161 8.27675 5.134 8.24126L5.62567 8.1746C5.65215 8.16995 5.6772 8.15926 5.69887 8.14336C5.72055 8.12746 5.73828 8.10678 5.75067 8.08293L5.96734 7.64126C6.08849 7.4138 6.27009 7.22419 6.49212 7.09332C6.71414 6.96246 6.96798 6.89542 7.22567 6.8996C7.49022 6.89751 7.74988 6.97088 7.97422 7.11109C8.19856 7.2513 8.37829 7.45256 8.49234 7.69126L8.709 8.13293C8.72092 8.15797 8.73836 8.17997 8.76001 8.1973C8.78166 8.21462 8.80696 8.2268 8.834 8.23293L9.31734 8.2996C9.57919 8.33923 9.82469 8.45146 10.026 8.62357C10.2273 8.79567 10.3763 9.02075 10.4561 9.27327C10.5359 9.52578 10.5434 9.79561 10.4776 10.0521C10.4118 10.3087 10.2755 10.5416 10.084 10.7246L9.72567 11.0663C9.70557 11.0852 9.69048 11.1088 9.68175 11.135C9.67302 11.1611 9.67093 11.1891 9.67567 11.2163L9.759 11.6996C9.79415 11.9038 9.78433 12.1132 9.73023 12.3133C9.67613 12.5133 9.57906 12.6991 9.44578 12.8578C9.31249 13.0165 9.14621 13.1442 8.95851 13.232C8.77082 13.3198 8.56622 13.3656 8.359 13.3663L8.384 13.3496ZM7.20067 11.6829C7.43312 11.6836 7.66192 11.7408 7.86734 11.8496L8.30067 12.0829C8.32824 12.0999 8.35997 12.1089 8.39234 12.1089C8.4247 12.1089 8.45643 12.0999 8.484 12.0829C8.50847 12.0632 8.52746 12.0375 8.53913 12.0083C8.55081 11.9791 8.55478 11.9474 8.55067 11.9163L8.46734 11.4329C8.43105 11.2064 8.44998 10.9745 8.52252 10.7569C8.59506 10.5393 8.71907 10.3424 8.884 10.1829L9.24234 9.84126C9.26374 9.81813 9.27881 9.78988 9.28611 9.75923C9.29341 9.72857 9.29268 9.69656 9.284 9.66626C9.27456 9.63729 9.25696 9.61166 9.23331 9.59244C9.20966 9.57323 9.18096 9.56124 9.15067 9.55793L8.659 9.48293C8.43284 9.44644 8.21871 9.35627 8.03457 9.21998C7.85043 9.08369 7.70164 8.90524 7.60067 8.6996L7.384 8.25793C7.384 8.1746 7.26734 8.13293 7.234 8.16626C7.20254 8.16382 7.1711 8.17136 7.14417 8.18782C7.11724 8.20428 7.09618 8.22881 7.084 8.25793L6.86734 8.69126C6.76504 8.89762 6.61341 9.07555 6.4259 9.20929C6.23838 9.34302 6.02076 9.42843 5.79234 9.45793L5.309 9.5246C5.2769 9.52902 5.24666 9.54227 5.22165 9.56287C5.19664 9.58347 5.17783 9.61061 5.16734 9.64126C5.15943 9.67162 5.1591 9.70345 5.16636 9.73396C5.17363 9.76447 5.18827 9.79273 5.209 9.81626L5.559 10.1579C5.72284 10.3194 5.84487 10.5184 5.91457 10.7376C5.98426 10.9569 5.99951 11.1898 5.959 11.4163L5.87567 11.8996C5.87059 11.9302 5.87414 11.9616 5.88591 11.9902C5.89769 12.0189 5.91723 12.0437 5.94233 12.0619C5.96744 12.0801 5.99711 12.0909 6.02803 12.0931C6.05894 12.0954 6.08987 12.089 6.11734 12.0746L6.55067 11.8496C6.75833 11.7378 6.98987 11.6778 7.22567 11.6746L7.20067 11.6829Z" fill="white" />
                    </svg>

                    Buat Pengajuan
                </button>
            </x-slot>

            <x-slot name="thead">
                <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest pl-6 w-16">NO</th>
                <x-datatable.th field="status" :sortField="$sortField" :sortAsc="$sortAsc" class="text-center">
                    STATUS
                </x-datatable.th>
                <x-datatable.th field="nilai" :sortField="$sortField" :sortAsc="$sortAsc" class="text-center">
                    NILAI
                </x-datatable.th>
                <x-datatable.th field="peringkat" :sortField="$sortField" :sortAsc="$sortAsc" class="text-center">
                    PERINGKAT
                </x-datatable.th>
                <x-datatable.th field="catatan" :sortField="$sortField" :sortAsc="$sortAsc">
                    CATATAN
                </x-datatable.th>
                <x-datatable.th field="created_at" :sortField="$sortField" :sortAsc="$sortAsc">
                    JADWAL
                </x-datatable.th>
                <th class="py-3 px-4 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest pr-8">AKSI</th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->akreditasis as $index => $item)
                <tr class="hover:bg-gray-50/50 transition-colors duration-150 group border-b border-gray-50 last:border-0" wire:key="akred-{{ $item->id }}">
                    <td class="py-5 px-4 pl-6 w-16">
                        <span class="text-xs font-bold text-gray-400">{{ $this->akreditasis->firstItem() + $index }}</span>
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