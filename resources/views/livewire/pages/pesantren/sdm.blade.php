<?php

use App\Models\SdmPesantren;
use App\Models\Pesantren;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Str;

new #[Layout('layouts.app')] class extends Component {
    public $data = [];
    public $levels = [];
    public $unitIds = [];
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

    public $categories = [
        ['key' => 'santri', 'label' => 'Santri', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ['key' => 'ustadz_dirosah', 'label' => 'Ustadz Dirosah', 'icon' => 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z'],
        ['key' => 'ustadz_non_dirosah', 'label' => 'Ustadz Non Dirosah', 'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
        ['key' => 'pamong', 'label' => 'Pamong', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-7.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
        ['key' => 'musyrif', 'label' => 'Musyrif/Musyrifah', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
        ['key' => 'tendik', 'label' => 'Tenaga Kependidikan', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
    ];

    public function mount()
    {
        if (!auth()->user()->isPesantren()) {
            abort(403);
        }

        $pesantren = Pesantren::with('units')->where('user_id', auth()->id())->first();

        if ($pesantren) {
            $this->levels = $pesantren->units->pluck('unit')->toArray();
            $this->unitIds = $pesantren->units->pluck('id', 'unit')->toArray();
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
        if (auth()->user()->pesantren->is_locked) {
            $this->js("Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak',
                text: 'Data terkunci karena sedang dalam proses akreditasi.',
                confirmButtonColor: '#d33'
            })");
            return;
        }

        foreach ($this->levels as $level) {
            $unitId = $this->unitIds[$level] ?? null;

            SdmPesantren::updateOrCreate(
                ['user_id' => auth()->id(), 'tingkat' => $level],
                array_merge($this->data[$level], ['pesantren_unit_id' => $unitId])
            );
        }

        $this->dispatch(
            'notification-received',
            type: 'success',
            title: 'Berhasil!',
            message: 'Data SDM berhasil disimpan.'
        );
    }

    public function getCategoryTotal($categoryKey, $fieldSuffix)
    {
        $field = $categoryKey . '_' . $fieldSuffix;
        $total = 0;
        foreach ($this->levels as $level) {
            $total += (int)($this->data[$level][$field] ?? 0);
        }
        return $total;
    }

    public function getGrandTotal($categoryKey)
    {
        return $this->getCategoryTotal($categoryKey, 'l') + $this->getCategoryTotal($categoryKey, 'p');
    }
}; ?>

<div class="py-12" x-data="{ 
    confirmSave() {
        Swal.fire({
            title: 'Apakah anda yakin ingin menyimpan perubahan data SDM ini?',
            text: 'Pastikan seluruh informasi telah diperiksa dan sesuai sebelum melanjutkan.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1e3a5f',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Ya, Simpan Perubahan',
            cancelButtonText: 'Batal',
            customClass: {
                title: 'text-xl font-bold text-slate-800',
                htmlContainer: 'text-sm text-slate-500',
                confirmButton: 'px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest',
                cancelButton: 'px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $wire.save();
            }
        })
    }
}">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-[#1e3a5f] shadow-sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight uppercase tracking-tight">{{ __('Data SDM Pesantren') }}</h2>
                <p class="text-xs text-slate-400 font-medium">Rekapitulasi data santri dan tenaga pendidik</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        @if(auth()->user()->pesantren->is_locked)
        <div class="mb-8 bg-rose-50 border border-rose-100 rounded-2xl p-5 flex gap-4 items-center">
            <div class="w-12 h-12 rounded-xl bg-white border border-rose-100 flex items-center justify-center text-rose-500 shadow-sm animate-pulse">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-bold text-rose-800 uppercase tracking-tight">Data Terkunci!</h4>
                <p class="text-xs text-rose-600/80 font-medium leading-relaxed">Status data sedang dalam proses akreditasi dan tidak dapat diubah untuk sementara waktu.</p>
            </div>
        </div>
        @endif

        <div class="mb-8">
            <div class="flex items-center justify-between mb-2 pl-1">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">{{ __('Rekapitulasi Data SDM') }}</h3>
                <div class="h-0.5 flex-1 bg-gradient-to-r from-slate-100 to-transparent ml-4"></div>
            </div>
        </div>

        <div class="space-y-6">
            @foreach($categories as $category)
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden transition-all duration-300" x-data="{ expanded: true }">
                <!-- Header -->
                <div class="px-6 py-5 bg-slate-50/50 flex items-center justify-between border-b border-slate-100 group cursor-pointer" @click="expanded = !expanded">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 rounded-2xl bg-white border border-slate-200 flex items-center justify-center text-[#1e3a5f] shadow-sm transform group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $category['icon'] }}" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 uppercase tracking-widest text-sm">{{ $category['label'] }}</h4>
                        </div>
                    </div>
                    <button class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-400 transition-colors" :class="{ 'rotate-180': expanded }">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div x-show="expanded" x-collapse>
                    <div class="p-6">
                        <div class="space-y-5">
                            @foreach($levels as $level)
                            <div class="bg-slate-50/30 rounded-2xl p-5 border border-slate-100/80 group">
                                <div class="flex items-center gap-2 mb-4">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $category['icon'] }}" />
                                    </svg>
                                    <span class="text-[11px] font-black text-slate-500 uppercase tracking-[0.2em]">{{ Str::of($level)->replace('_', ' ')->upper() }}</span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="space-y-1.5">
                                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest pl-1">Laki-laki</label>
                                        <div class="relative group/input">
                                            <input type="number"
                                                wire:model.live="data.{{ $level }}.{{ $category['key'] }}_l"
                                                class="w-full bg-white border border-slate-200 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-[#1e3a5f]/20 focus:border-[#1e3a5f] transition-all"
                                                placeholder="0"
                                                {{ auth()->user()->pesantren->is_locked ? 'disabled' : '' }}>
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest pl-1">Perempuan</label>
                                        <div class="relative group/input">
                                            <input type="number"
                                                wire:model.live="data.{{ $level }}.{{ $category['key'] }}_p"
                                                class="w-full bg-white border border-slate-200 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-[#1e3a5f]/20 focus:border-[#1e3a5f] transition-all"
                                                placeholder="0"
                                                {{ auth()->user()->pesantren->is_locked ? 'disabled' : '' }}>
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest pl-1">Total</label>
                                        <div class="w-full bg-slate-100/50 border border-slate-200/50 rounded-xl py-2.5 px-4 text-sm font-black text-[#1e3a5f] text-center shadow-inner">
                                            {{ (int)($data[$level][$category['key'].'_l'] ?? 0) + (int)($data[$level][$category['key'].'_p'] ?? 0) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach

                            <!-- Summary Box -->
                            <div class="mt-8 bg-blue-50/50 rounded-[2.5rem] p-4 border border-slate-100 shadow-inner">
                                <div class="bg-white rounded-[2rem] p-6 border border-slate-100 shadow-sm flex lg:flex-row lg:items-center justify-between gap-6">
                                    <div class="flex items-center gap-5">
                                        <div>
                                            <h5 class="text-[13px] font-black text-[#1e3a5f] uppercase tracking-widest leading-none">Total Keseluruhan Santri</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-12 flex items-center justify-end gap-3 pb-20">
            <button type="button"
                class="px-8 py-3 rounded-2xl bg-white border border-slate-200 text-slate-500 text-[11px] font-black uppercase tracking-[0.2em] hover:bg-slate-50 hover:text-slate-700 transition-all active:scale-95 shadow-sm">
                Batal
            </button>
            <button type="button"
                @click="confirmSave"
                class="px-10 py-3 rounded-2xl bg-[#1e3a5f] text-white text-[11px] font-black uppercase tracking-[0.2em] hover:bg-[#162d4a] shadow-xl shadow-[#1e3a5f]/30 transition-all flex items-center gap-3 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                {{ auth()->user()->pesantren->is_locked ? 'disabled' : '' }}>
                <span>Simpan Perubahan</span>
                <svg wire:loading wire:target="save" class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </div>
    </div>
</div>