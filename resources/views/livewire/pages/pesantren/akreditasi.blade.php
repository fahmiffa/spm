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

new #[Layout('layouts.app')] class extends Component {
    public function mount()
    {
        if (!auth()->user()->isPesantren()) {
            abort(403);
        }
    }

    public function getAkreditasisProperty()
    {
        return Akreditasi::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create()
    {
        $userId = auth()->id();
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
            'status' => 6, // pengajuan
        ]);

        // Notify Admin
        $admins = \App\Models\User::whereHas('role', function ($q) {
            $q->where('id', 1);
        })->get();
        \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AkreditasiNotification(
            'pengajuan',
            'Pengajuan Akreditasi Baru',
            'Pesantren ' . (auth()->user()->pesantren->nama_pesantren ?? auth()->user()->name) . ' telah membuat pengajuan akreditasi baru.',
            route('admin.akreditasi')
        ));

        session()->flash('status', 'Pengajuan akreditasi berhasil dibuat.');
    }

    public function delete($id)
    {
        $akreditasi = Akreditasi::where('user_id', auth()->id())->findOrFail($id);
        $akreditasi->delete();

        session()->flash('status', 'Pengajuan akreditasi berhasil dihapus.');
    }

    public function banding($id, $alasan)
    {
        $akreditasi = Akreditasi::where('user_id', auth()->id())->findOrFail($id);

        // Ensure status is 2 (Rejected) and has assessments
        if ($akreditasi->status == 2 && $akreditasi->assessments()->exists()) {
            $akreditasi->update([
                'status' => 4, // Validasi
                'catatan' => $alasan,
            ]);

            // Notify Admin
            $admins = \App\Models\User::whereHas('role', function ($q) {
                $q->where('id', 1);
            })->get();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AkreditasiNotification(
                'banding',
                'Pengajuan Banding Baru',
                'Pesantren ' . (auth()->user()->pesantren->nama_pesantren ?? auth()->user()->name) . ' telah mengajukan banding akreditasi.',
                route('admin.akreditasi')
            ));

            session()->flash('status', 'Pengajuan banding berhasil dikirim. Status berubah menjadi Validasi.');
        } else {
            session()->flash('error', 'Gagal mengajukan banding. Pastikan status pengajuan adalah Ditolak dan sudah melalui tahap Assessment.');
        }
    }
}; ?>

<div class="py-12" x-data="akreditasiPesantren">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Daftar Akreditasi</h2>
                    <button wire:click="create"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                        Buat Pengajuan
                    </button>
                </div>

                @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                    {{ session('status') }}
                </div>
                @endif

                @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                    {{ session('error') }}
                </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">No</th>
                                <th class="py-3 px-6 text-center">Status</th>
                                <th class="py-3 px-6 text-center">Nilai</th>
                                <th class="py-3 px-6 text-center">Peringkat</th>
                                <th class="py-3 px-6 text-center">Catatan</th>
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
                                <td class="py-3 px-6 text-center">
                                    <span
                                        class="{{ Akreditasi::getStatusBadgeClass($item->status) }} py-1 px-3 rounded-full text-xs font-semibold">
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
                                <td class="py-3 px-6 text-left font-medium">
                                    {{ $item->catatan }}
                                </td>
                                <td class="py-3 px-6 text-center">
                                    {{ $item->created_at->format('d M Y H:i') }}
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <div class="flex items-center justify-center gap-4">
                                        @if ($item->status == 2 && $item->assessments()->exists())
                                        <button @click="confirmBanding({{ $item->id }})"
                                            class="text-blue-600 hover:text-blue-900 font-medium">
                                            Banding
                                        </button>
                                        @endif
                                        <button @click="confirmDelete({{ $item->id }})"
                                            class="text-red-600 hover:text-red-900 font-medium">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-gray-500">
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
</div>