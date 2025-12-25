<?php

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
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
        $asesorId = auth()->user()->asesor->id;
        return Assessment::with('akreditasi.user.pesantren')->where('asesor_id', $asesorId)->get();
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
                                <th class="py-3 px-6 text-left">Nama Pesantren</th>
                                <th class="py-3 px-6 text-center">Tanggal Assesment</th>
                                <th class="py-3 px-6 text-center">Status</th>
                                <th class="py-3 px-6 text-center">Catatan</th>
                                <th class="py-3 px-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            @forelse ($this->assessments as $index => $item)
                                <tr class="border-b border-gray-200 hover:bg-gray-100">
                                    <td class="py-3 px-6 text-left whitespace-nowrap">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="py-3 px-6 text-left font-medium">
                                        {{ $item->akreditasi->user->pesantren->nama_pesantren ?? $item->akreditasi->user->name }}
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        {{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d M Y') }} s/d
                                        {{ \Carbon\Carbon::parse($item->tanggal_berakhir)->format('d M Y') }}
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <span
                                            class="{{ Akreditasi::getStatusBadgeClass($item->akreditasi->status) }} py-1 px-3 rounded-full text-xs font-semibold">
                                            {{ Akreditasi::getStatusLabel($item->akreditasi->status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-6 text-left font-medium">
                                        {{ $item->akreditasi->catatan }}
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        @if ($item->akreditasi->status == 5)
                                            <a href="{{ route('asesor.akreditasi-detail', $item->akreditasi->uuid) }}"
                                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-4 rounded text-xs transition duration-150 ease-in-out">
                                                Verifikasi
                                            </a>
                                        @elseif ($item->akreditasi->status == 4)
                                            <a href="{{ route('asesor.akreditasi-detail', $item->akreditasi->uuid) }}"
                                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-1 px-4 rounded text-xs transition duration-150 ease-in-out">
                                                Data
                                            </a>
                                        @else
                                            <span class="text-gray-400 italic">Selesai</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-gray-500">
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
</div>
