<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Document;

new #[Layout('layouts.app')] class extends Component {
    public function getDocumentsProperty()
    {
        return auth()->user()->documents()->latest()->get();
    }
}; ?>

<div class="py-12">
    <x-slot name="header">{{ __('Dokumen Saya') }}</x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Daftar Dokumen</h2>
                    <p class="text-sm text-gray-500 mt-1">Berikut adalah dokumen yang dibagikan oleh Admin kepada Anda.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse ($this->documents as $doc)
                    <div class="group bg-white border border-gray-100 rounded-2xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-[0_20px_40px_rgba(0,0,0,0.08)] transition-all duration-300 transform hover:-translate-y-1 flex flex-col h-full relative overflow-hidden">
                        <!-- Background Accent -->
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-indigo-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-500"></div>

                        <div class="relative z-10 flex flex-col h-full">
                            <div class="mb-4 flex items-start justify-between">
                                <div class="h-12 w-12 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-200 group-hover:bg-indigo-700 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-500 bg-indigo-50 px-2 py-1 rounded-md">
                                    {{ strtoupper(pathinfo($doc->file_path, PATHINFO_EXTENSION)) }}
                                </span>
                            </div>

                            <h3 class="text-lg font-bold text-gray-900 mb-6 leading-snug group-hover:text-indigo-600 transition-colors">
                                {{ $doc->title }}
                            </h3>

                            <div class="mt-auto flex items-center justify-between border-t border-gray-50 pt-4">
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Diunggah pada</span>
                                    <span class="text-xs font-semibold text-gray-600">{{ $doc->created_at->format('d M Y') }}</span>
                                </div>

                                <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="inline-flex items-center gap-2 bg-gray-900 hover:bg-black text-white px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-lg hover:shadow-xl active:scale-95">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Buka Berkas
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-full py-20 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200">
                        <div class="mx-auto h-20 w-20 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Belum Ada Dokumen</h3>
                        <p class="text-sm text-gray-500 mt-2">Admin belum membagikan dokumen apa pun kepada Anda.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>