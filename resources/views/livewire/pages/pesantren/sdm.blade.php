<?php

use App\Models\SdmPesantren;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public $data = [];
    public $levels = [
        'SD',
        'MI',
        'SMP',
        'MTs',
        'SMA',
        'MA',
        'SMK',
        'MAK',
        'Satuan Pesantren Muadalah (SPM)'
    ];
    public $fields = [
        'santri_l',
        'santri_p',
        'ustadz_dirosah_l',
        'ustadz_dirosah_p',
        'ustadz_non_dirosah_l',
        'ustadz_non_dirosah_p',
        'pamong_l',
        'pamong_p',
        'musyrif_l',
        'musyrif_p',
        'tendik_l',
        'tendik_p'
    ];

    public function mount()
    {
        if (!auth()->user()->isPesantren()) {
            abort(403);
        }

        $existingData = SdmPesantren::where('user_id', auth()->id())->get()->keyBy('tingkat');

        foreach ($this->levels as $level) {
            foreach ($this->fields as $field) {
                $this->data[$level][$field] = $existingData->has($level) ? $existingData[$level]->$field : 0;
            }
        }
    }

    public function save()
    {
        foreach ($this->levels as $level) {
            SdmPesantren::updateOrCreate(
                ['user_id' => auth()->id(), 'tingkat' => $level],
                $this->data[$level]
            );
        }

        $this->dispatch(
            'notification-received',
            type: 'success',
            title: 'Berhasil!',
            message: 'Data SDM berhasil disimpan.'
        );
    }

    public function getTotal($field)
    {
        $total = 0;
        foreach ($this->levels as $level) {
            $total += (int)($this->data[$level][$field] ?? 0);
        }
        return $total;
    }
}; ?>

<div class="py-12">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 overflow-x-auto">
                <header class="mb-6">
                    <h2 class="text-xl font-bold text-gray-900">
                        {{ __('REKAPITULASI DATA SDM PESANTREN') }}
                    </h2>
                </header>

                <form wire:submit="save">
                    <table class="min-w-full border-collapse border border-gray-300 text-sm">
                        <thead class="bg-gray-100 text-nowrap">
                            <tr>
                                <th rowspan="2" class="border border-gray-300 px-2 py-2 text-center">NO</th>
                                <th rowspan="2" class="border border-gray-300 px-4 py-2 text-center">BENTUK</th>
                                <th colspan="2" class="border border-gray-300 px-2 py-1 text-center bg-green-50">SANTRI</th>
                                <th colspan="2" class="border border-gray-300 px-2 py-1 text-center bg-green-50">USTADZ DIROSAH</th>
                                <th colspan="2" class="border border-gray-300 px-2 py-1 text-center bg-green-50">USTADZ NON DIROSAH</th>
                                <th colspan="2" class="border border-gray-300 px-2 py-1 text-center bg-green-50">PAMONG</th>
                                <th colspan="2" class="border border-gray-300 px-2 py-1 text-center bg-green-50">MUSYRIF/MUSYRIFAH</th>
                                <th colspan="2" class="border border-gray-300 px-2 py-1 text-center bg-green-50">TENAGA KEPENDIDIKAN</th>
                            </tr>
                            <tr class="bg-gray-50">
                                <th class="border border-gray-300 px-2 py-1 text-center text-xs">Laki-Laki</th>
                                <th class="border border-gray-300 px-2 py-1 text-center text-xs">Perempuan</th>
                                <th class="border border-gray-300 px-2 py-1 text-center text-xs">Laki-Laki</th>
                                <th class="border border-gray-300 px-2 py-1 text-center text-xs">Perempuan</th>
                                <th class="border border-gray-300 px-2 py-1 text-center text-xs">Laki-Laki</th>
                                <th class="border border-gray-300 px-2 py-1 text-center text-xs">Perempuan</th>
                                <th class="border border-gray-300 px-2 py-1 text-center text-xs">Laki-Laki</th>
                                <th class="border border-gray-300 px-2 py-1 text-center text-xs">Perempuan</th>
                                <th class="border border-gray-300 px-2 py-1 text-center text-xs">Laki-Laki</th>
                                <th class="border border-gray-300 px-2 py-1 text-center text-xs">Perempuan</th>
                                <th class="border border-gray-300 px-2 py-1 text-center text-xs">Laki-Laki</th>
                                <th class="border border-gray-300 px-2 py-1 text-center text-xs">Perempuan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($levels as $index => $level)
                            <tr class="hover:bg-gray-50">
                                <td class="border border-gray-300 px-2 py-1 text-center">{{ $index + 1 }}</td>
                                <td class="border border-gray-300 px-4 py-1 font-medium bg-yellow-50 whitespace-nowrap">{{ $level }}</td>
                                @foreach($fields as $field)
                                <td class="border border-gray-300 p-0">
                                    <input type="number"
                                        wire:model.live="data.{{ $level }}.{{ $field }}"
                                        class="w-full border-0 p-1 text-center focus:ring-2 focus:ring-indigo-500"
                                        min="0">
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-blue-50 font-bold">
                            <tr>
                                <td colspan="2" class="border border-gray-300 px-4 py-2 text-center uppercase">JUMLAH</td>
                                @foreach($fields as $field)
                                <td class="border border-gray-300 px-2 py-2 text-center">
                                    {{ $this->getTotal($field) }}
                                </td>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>

                    <div class="mt-6 flex items-center gap-4">
                        <x-primary-button wire:loading.attr="disabled">
                            <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="save">{{ __('Simpan Data SDM') }}</span>
                            <span wire:loading wire:target="save">{{ __('Memproses...') }}</span>
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>