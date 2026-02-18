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

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public $kartu_kendali_file;
    public $selected_akreditasi_id;
    public function mount()
    {
        if (!auth()->user()->isPesantren()) {
            abort(403);
        }
    }

    public function getAkreditasisProperty()
    {
        return Akreditasi::with('assessments')->where('user_id', auth()->id())
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
            'status' => 6, // 6. Pengajuan
        ]);

        // Lock Pesantren Data
        auth()->user()->pesantren->update(['is_locked' => true]);

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
                'status' => 3, // 3. Validasi
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

    public function selectForUpload($id)
    {
        $this->selected_akreditasi_id = $id;
        $this->dispatch('open-modal', 'upload-kartu-kendali');
    }

    public function uploadKartuKendali()
    {
        $this->validate([
            'kartu_kendali_file' => 'required|file|mimes:pdf,docx|max:5120',
        ], [
            'kartu_kendali_file.required' => 'File Kartu Kendali wajib diunggah.',
            'kartu_kendali_file.mimes' => 'Format file harus PDF atau DOCX.',
            'kartu_kendali_file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        $akreditasi = Akreditasi::where('user_id', auth()->id())->findOrFail($this->selected_akreditasi_id);

        $path = $this->kartu_kendali_file->store('akreditasi/kartu_kendali', 'public');

        $akreditasi->update([
            'kartu_kendali' => $path
        ]);

        $this->dispatch('close-modal', 'upload-kartu-kendali');
        $this->reset(['kartu_kendali_file', 'selected_akreditasi_id']);

        $this->dispatch(
            'notification-received',
            type: 'success',
            title: 'Berhasil!',
            message: 'Kartu Kendali berhasil diunggah.'
        );
    }
}; ?>

<div class="py-12" x-data="akreditasiPesantren">
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
                                    <div class="space-y-1">
                                        @foreach($item->catatans as $catatan)
                                        <div class="text-xs p-1 rounded border {{ $catatan->tipe == 'visitasi' ? 'bg-orange-50 border-orange-200 text-orange-800' : 'bg-red-50 border-red-200 text-red-800' }}">
                                            <span class="font-bold uppercase">{{ $catatan->tipe }}:</span> {{ $catatan->catatan }}
                                            <div class="text-[10px] text-gray-500 mt-0.5">{{ $catatan->created_at->format('d M Y H:i') }}</div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @if($item->catatan)
                                    <div class="text-xs mt-1 text-gray-600 border-t pt-1">
                                        Logs Lama: {{ $item->catatan }}
                                    </div>
                                    @endif
                                    @if($item->catatans->isEmpty() && !$item->catatan)
                                    <span class="text-gray-400 italic">-</span>
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
                                    <div class="flex items-center justify-center gap-4">
                                        <a href="{{ route('pesantren.akreditasi-detail', $item->uuid) }}"
                                            class="text-indigo-600 hover:text-indigo-900 font-medium">
                                            Detail
                                        </a>

                                        @if ($item->status == 3)
                                        @if($item->kartu_kendali)
                                        <a href="{{ Storage::url($item->kartu_kendali) }}" target="_blank"
                                            class="text-green-600 hover:text-green-900 font-medium">
                                            Lihat Kartu
                                        </a>
                                        <button wire:click="selectForUpload({{ $item->id }})"
                                            class="text-amber-600 hover:text-amber-900 font-medium">
                                            Ganti
                                        </button>
                                        @else
                                        <button wire:click="selectForUpload({{ $item->id }})"
                                            class="text-indigo-600 hover:text-indigo-900 font-medium">
                                            Upload Kartu
                                        </button>
                                        @endif
                                        @endif

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

                <!-- Modal Upload Kartu Kendali -->
                <x-modal name="upload-kartu-kendali" :show="false" focusable>
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">
                            Unggah Kartu Kendali
                        </h2>
                        <p class="text-sm text-gray-600 mb-4">
                            Kartu Kendali adalah dokumen wajib yang harus diunggah pesantren setelah proses visitasi selesai untuk keperluan validasi admin.
                        </p>

                        <form wire:submit.prevent="uploadKartuKendali">
                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="kartu_kendali_file" value="File Kartu Kendali (PDF/DOCX)" />
                                    <input wire:model="kartu_kendali_file" type="file" id="kartu_kendali_file" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none" accept=".pdf,.docx">
                                    <div wire:loading wire:target="kartu_kendali_file" class="text-xs text-indigo-600 mt-1 font-bold">Mengunggah...</div>
                                    <x-input-error :messages="$errors->get('kartu_kendali_file')" class="mt-2" />
                                </div>

                                <div class="flex justify-end gap-3 mt-6">
                                    <x-secondary-button x-on:click="$dispatch('close')">
                                        Batal
                                    </x-secondary-button>
                                    <x-primary-button>
                                        Simpan
                                    </x-primary-button>
                                </div>
                            </div>
                        </form>
                    </div>
                </x-modal>
            </div>
        </div>
    </div>
</div>