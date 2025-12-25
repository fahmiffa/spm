<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- Notification Toast -->
        <div x-data="{ 
                show: false, 
                title: '', 
                message: '',
                timeout: null,
                init() {
                    window.addEventListener('notification-received', (event) => {
                        this.title = event.detail.title;
                        this.message = event.detail.message;
                        this.show = true;
                        
                        if (this.timeout) clearTimeout(this.timeout);
                        this.timeout = setTimeout(() => { this.show = false }, 5000);

                        if (Notification.permission === 'granted') {
                            new Notification(this.title, { body: this.message });
                        } else if (Notification.permission !== 'denied') {
                            Notification.requestPermission();
                        }
                    });
                }
            }" 
            x-show="show" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed bottom-0 right-0 p-6 z-50 w-full max-w-sm"
            style="display: none;">
            <div class="bg-white border-l-4 border-indigo-600 rounded-lg shadow-xl p-4 flex items-start gap-4">
                <div class="flex-shrink-0 pt-0.5">
                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-gray-900" x-text="title"></p>
                    <p class="mt-1 text-sm text-gray-500" x-text="message"></p>
                </div>
                <div class="flex-shrink-0">
                    <button @click="show = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </body>
</html>
