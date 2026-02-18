<?php

use App\Models\MasterEdpmKomponen;
use App\Models\Edpm;
use App\Models\EdpmCatatan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Str;

new #[Layout('layouts.app')] class extends Component {
    public $komponens;
    public $evaluasis = []; // butir_id => isian
    public $catatans = [];  // komponen_id => catatan
    public $activeStep = 0;

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

    public function nextStep()
    {
        // Validation for current step
        if (isset($this->komponens[$this->activeStep])) {
            $currentKomponen = $this->komponens[$this->activeStep];
            $rules = [];
            $messages = [];

            foreach ($currentKomponen->butirs as $butir) {
                $rules['evaluasis.' . $butir->id] = 'required|numeric|min:1|max:4';
                $messages['evaluasis.' . $butir->id . '.required'] = 'Harap pilih nilai evaluasi untuk butir ' . $butir->nomor_butir;
                $messages['evaluasis.' . $butir->id . '.numeric'] = 'Nilai harus berupa angka.';
                $messages['evaluasis.' . $butir->id . '.min'] = 'Nilai minimal adalah 1.';
                $messages['evaluasis.' . $butir->id . '.max'] = 'Nilai maksimal adalah 4.';
            }

            try {
                $this->validate($rules, $messages);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $errorMessages = collect($e->errors())->flatten()->toArray();
                session()->flash('validation_errors', $errorMessages);
                $this->dispatch('show-validation-error');
                return;
            }
        }

        if ($this->activeStep < count($this->komponens) - 1) {
            $this->activeStep++;
        }
    }

    public function prevStep()
    {
        if ($this->activeStep > 0) {
            $this->activeStep--;
        }
    }

    public function setStep($step)
    {
        if ($step >= 0 && $step < count($this->komponens)) {
            $this->activeStep = $step;
        }
    }

    public function save()
    {
        if (auth()->user()->pesantren->is_locked) {
            $this->js("Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak',
                text: 'Data terkunci karena sedang dalam proses akreditasi.',
                confirmButtonColor: '#d33'
            })");
            return;
        }

        $this->validate([
            'evaluasis.*' => 'required|numeric|min:1|max:4',
            'catatans.*' => 'nullable|string',
        ], [
            'evaluasis.*.required' => 'Harap pilih nilai evaluasi.',
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

    public function isStepComplete($index)
    {
        if (!isset($this->komponens[$index])) return false;

        foreach ($this->komponens[$index]->butirs as $butir) {
            // Check if evaluation value exists and is not empty or null
            if (!isset($this->evaluasis[$butir->id]) || $this->evaluasis[$butir->id] === '') {
                return false;
            }
        }
        return true;
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 overflow-x-auto">
                <header class="mb-6">
                    <h2 class="text-lg md:text-xl font-bold text-gray-800 border-l-4 border-indigo-600 pl-3">
                        {{ __('Evaluasi Data Pesantren Muhammadiyah (EDPM)') }}
                    </h2>
                </header>

                @if(auth()->user()->pesantren->is_locked)
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <span class="font-bold">DATA TERKUNCI!</span> Data EDPM tidak dapat diubah karena sedang dalam proses akreditasi.
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                @if($komponens && count($komponens) > 0)
                <!-- Stepper Headers -->
                <div class="mb-8 overflow-x-auto md:overflow-hidden pb-16 md:pb-20">
                    <div class="flex items-center justify-between md:justify-center md:flex-wrap gap-y-16 md:gap-y-20 relative min-w-[600px] md:min-w-full px-2">
                        <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-gray-200 -z-10 md:hidden"></div>
                        @foreach($komponens as $index => $komponen)
                        @php
                        $isActive = $activeStep === $index;
                        $isComplete = $this->isStepComplete($index);

                        // Determain classes based on state
                        if ($isActive) {
                        $circleClasses = 'bg-indigo-600 text-white border-indigo-600 ring-2 ring-indigo-200';
                        $textClasses = 'text-indigo-600';
                        } elseif ($isComplete) {
                        $circleClasses = 'bg-green-100 text-green-600 border-green-500';
                        $textClasses = 'text-green-600';
                        } else {
                        $circleClasses = 'bg-white text-gray-400 border-gray-300';
                        $textClasses = 'text-gray-400';
                        }
                        @endphp
                        <div class="relative flex flex-col items-center group cursor-default bg-white px-2 md:w-1/4 lg:w-1/6">
                            <div class="w-8 h-8 md:w-10 md:h-10 rounded-full flex items-center justify-center font-bold text-xs md:text-sm border-2 transition-colors {{ $circleClasses }} z-10 mb-2">
                                {{ $index + 1 }}
                            </div>
                            <span class="text-[10px] md:text-xs font-semibold text-center w-full px-1 {{ $textClasses }} leading-tight">
                                {{ $komponen->nama }}
                            </span>
                        </div>
                        @endforeach
                    </div>

                </div>

                <div class="mt-4 mb-4">
                    <h3 class="text-base md:text-lg font-semibold text-gray-800 text-center border-b pb-2">
                        {{ $komponens[$activeStep]->nama ?? '' }}
                    </h3>
                </div>

                <form wire:submit="save">
                    <div class="space-y-6">
                        @if(isset($komponens[$activeStep]))
                        @php
                        $currentKomponen = $komponens[$activeStep];
                        @endphp

                        <!-- List Butir as Cards -->
                        <div class="grid gap-6">
                            @forelse($currentKomponen->butirs as $butir)
                            <div class="bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow p-4 md:p-6">
                                <div class="flex flex-col md:flex-row md:items-start gap-4">
                                    <!-- Badges -->
                                    <div class="flex flex-row md:flex-col gap-2 shrink-0">
                                        <div class="bg-gray-100 text-gray-600 text-xs font-bold px-3 py-1 rounded text-center whitespace-nowrap">
                                            SK: {{ $butir->no_sk }}
                                        </div>
                                        <div class="bg-indigo-50 text-indigo-700 text-xs font-bold px-3 py-1 rounded text-center whitespace-nowrap">
                                            No. {{ $butir->nomor_butir }}
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div class="grow">
                                        <p class="text-sm md:text-base text-gray-800 leading-relaxed mb-4">
                                            {{ $butir->butir_pernyataan }}
                                        </p>

                                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Pilih Nilai Evaluasi:</label>
                                            <select wire:model.live="evaluasis.{{ $butir->id }}"
                                                class="w-full md:w-auto min-w-[200px] border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm @error('evaluasis.'.$butir->id) border-red-300 ring-red-200 @enderror">
                                                <option value="">-- Pilih Nilai --</option>
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                            </select>
                                            @error('evaluasis.'.$butir->id)
                                            <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-10 text-gray-500 italic border-2 border-dashed rounded-lg">
                                Belum ada butir pernyataan untuk komponen ini.
                            </div>
                            @endforelse
                        </div>

                        <!-- Catatan Section (Only on Last Step) -->
                        @if($activeStep === count($komponens) - 1)
                        <div class="mt-12 space-y-4">
                            <h3 class="text-lg font-bold text-gray-900 border-l-4 border-blue-500 pl-3">
                                Catatan & Deskripsi Kinerja
                            </h3>
                            <p class="text-sm text-gray-600 mb-4">
                                Mohon lengkapi catatan evaluasi untuk setiap komponen berikut sebelum menyimpan.
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach($komponens as $komponen)
                                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                                    <label class="block text-sm font-bold text-gray-800 mb-2">
                                        {{ $komponen->nama }}
                                    </label>
                                    <textarea wire:model.live="catatans.{{ $komponen->id }}"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm min-h-[100px]"
                                        placeholder="Catatan untuk {{ strtolower($komponen->nama) }}..."></textarea>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @endif
                    </div>

                    <div class="mt-8 flex items-center justify-between">
                        <!-- Prev Button -->
                        <button type="button" wire:click="prevStep"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded transition-colors {{ $activeStep === 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ $activeStep === 0 ? 'disabled' : '' }}>
                            &laquo; Sebelumnya
                        </button>

                        <!-- Next / Save Button -->
                        @if ($activeStep === count($komponens) - 1)
                        <div class="flex items-center gap-4">
                            @if (session('status'))
                            <p class="text-sm text-green-600 font-medium">{{ session('status') }}</p>
                            @endif
                            <x-primary-button>
                                {{ __('Simpan Evaluasi EDPM') }}
                            </x-primary-button>
                        </div>
                        @else
                        <button type="button" onclick="validateAndNext()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition-colors">
                            Selanjutnya &raquo;
                        </button>
                        @endif
                    </div>
                </form>
                @else
                <div class="text-center py-10">
                    <p class="text-gray-500 italic">Data komponen EDPM belum tersedia.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    function validateAndNext() {
        // Get all select elements in current step
        const selects = document.querySelectorAll('select[wire\\:model\\.live^="evaluasis"]');
        const emptySelects = [];

        selects.forEach(select => {
            if (!select.value || select.value === '') {
                // Find the butir number from the card
                const card = select.closest('.bg-white.border.rounded-lg');
                const butirBadge = card?.querySelector('.bg-indigo-50');
                const butirText = butirBadge?.textContent.trim() || 'Unknown';
                emptySelects.push(butirText);
            }
        });

        if (emptySelects.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Nilai',
                html: 'Harap pilih nilai evaluasi untuk:<br><br>' + emptySelects.map(b => '• ' + b).join('<br>'),
                confirmButtonColor: '#4f46e5',
                confirmButtonText: 'OK'
            });
            return;
        }

        // If validation passes, call Livewire method
        Livewire.find('{{ $_instance->getId() }}').call('nextStep');
    }
</script>