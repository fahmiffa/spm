<?php

use App\Models\MasterEdpmKomponen;
use App\Models\Edpm;
use App\Models\EdpmCatatan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public $komponens;
    public $evaluasis = []; // butir_id => isian
    public $catatans = [];  // komponen_id => catatan

    public function mount()
    {
        if (!auth()->user()->isPesantren()) {
            abort(403);
        }

        $this->loadData();
    }

    public function loadData()
    {
        $this->komponens = MasterEdpmKomponen::with('butirs')->get();
        
        $existingEvaluasis = Edpm::where('user_id', auth()->id())->get()->pluck('isian', 'butir_id');
        $existingCatatans = EdpmCatatan::where('user_id', auth()->id())->get()->pluck('catatan', 'komponen_id');

        foreach ($this->komponens as $komponen) {
            $this->catatans[$komponen->id] = $existingCatatans[$komponen->id] ?? '';
            foreach ($komponen->butirs as $butir) {
                $this->evaluasis[$butir->id] = $existingEvaluasis[$butir->id] ?? '';
            }
        }
    }

    public function save()
    {
        $this->validate([
            'evaluasis.*' => 'required|numeric|min:1|max:4',
            'catatans.*' => 'nullable|string',
        ], [
            'evaluasis.*.numeric' => 'Nilai harus berupa angka.',
            'evaluasis.*.min' => 'Nilai minimal adalah 1.',
            'evaluasis.*.max' => 'Nilai maksimal adalah 4.',
        ]);

        foreach ($this->evaluasis as $butirId => $isian) {
            Edpm::updateOrCreate(
                ['user_id' => auth()->id(), 'butir_id' => $butirId],
                ['isian' => $isian]
            );
        }

        foreach ($this->catatans as $komponenId => $catatan) {
            EdpmCatatan::updateOrCreate(
                ['user_id' => auth()->id(), 'komponen_id' => $komponenId],
                ['catatan' => $catatan]
            );
        }

        session()->flash('status', 'Evaluasi EDPM berhasil disimpan.');
        $this->dispatch('notification-received', title: 'Berhasil', message: 'Evaluasi EDPM berhasil disimpan.');
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 overflow-x-auto">
                <header class="mb-6">
                    <h2 class="text-xl font-bold text-gray-800 border-l-4 border-indigo-600 pl-3">
                        {{ __('Evaluasi Data Pesantren Muhammadiyah (EDPM)') }}
                    </h2>
                </header>

                <form wire:submit="save">
                    <table class="min-w-full border-collapse border border-gray-300 text-sm">
                        <thead class="bg-gray-100 uppercase font-bold text-xs">
                            <tr>
                                <th class="border border-gray-300 px-4 py-3 text-center w-32">KOMPONEN</th>
                                <th class="border border-gray-300 px-2 py-3 text-center w-16">No SK</th>
                                <th class="border border-gray-300 px-2 py-3 text-center w-16">Nomor Butir</th>
                                <th class="border border-gray-300 px-4 py-3 text-left">Butir Pernyataan</th>
                                <th class="border border-gray-300 px-4 py-3 text-center w-48">Isian Evaluasi EDPM</th>
                                <th class="border border-gray-300 px-4 py-3 text-center w-64">CATATAN KOMPONEN (Deskripsi Kinerja)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($komponens as $komponen)
                                @php $butirsCount = count($komponen->butirs); @endphp
                                @forelse($komponen->butirs as $index => $butir)
                                    <tr class="hover:bg-gray-50">
                                        @if($index === 0)
                                            <td rowspan="{{ $butirsCount }}" class="border border-gray-300 px-4 py-2 font-bold text-center bg-gray-50 align-middle">
                                                {{ $komponen->nama }}
                                            </td>
                                        @endif
                                        <td class="border border-gray-300 px-2 py-2 text-center">{{ $butir->no_sk }}</td>
                                        <td class="border border-gray-300 px-2 py-2 text-center font-bold">{{ $butir->nomor_butir }}</td>
                                        <td class="border border-gray-300 px-4 py-2">{{ $butir->butir_pernyataan }}</td>
                                        <td class="border border-gray-300 p-0">
                                            <input type="number" min="1" max="4" wire:model.live="evaluasis.{{ $butir->id }}" class="w-full border-0 p-2 text-sm focus:ring-2 focus:ring-indigo-500 @error('evaluasis.'.$butir->id) bg-red-50 @enderror">
                                            @error('evaluasis.'.$butir->id)
                                                <div class="px-2 pb-1 text-[10px] text-red-600 font-medium">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        @if($index === 0)
                                            <td rowspan="{{ $butirsCount }}" class="border border-gray-300 p-0 align-top">
                                                <textarea wire:model.live="catatans.{{ $komponen->id }}" class="w-full border-0 p-2 text-xs focus:ring-2 focus:ring-indigo-500 min-h-[150px]" placeholder="Masukkan catatan komponen..."></textarea>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="border border-gray-300 px-4 py-2 font-bold text-center bg-gray-50">
                                            {{ $komponen->nama }}
                                        </td>
                                        <td colspan="5" class="border border-gray-300 px-4 py-2 italic text-gray-500 text-center">
                                            Belum ada butir pernyataan untuk komponen ini.
                                        </td>
                                    </tr>
                                @endforelse
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-8 flex items-center gap-4">
                        <x-primary-button>
                            {{ __('Simpan Evaluasi EDPM') }}
                        </x-primary-button>

                        @if (session('status'))
                            <p
                                x-data="{ show: true }"
                                x-show="show"
                                x-transition
                                x-init="setTimeout(() => show = false, 2000)"
                                class="text-sm text-green-600 font-medium"
                            >{{ session('status') }}</p>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
