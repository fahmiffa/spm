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
    <body class="font-sans antialiased text-gray-900 bg-gray-50" x-data>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('sidebar', {
                    open: false,
                })
            })
        </script>
        <div class="flex h-screen overflow-hidden">
            <!-- Sidebar -->
            <livewire:layout.navigation />

            <!-- Main Content Area -->
            <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
                <!-- Top Header -->
                <header class="sticky top-0 z-30 flex items-center justify-between h-16 bg-white border-b border-gray-200 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center">
                        <!-- Mobile Sidebar Toggle -->
                        <button @click="$store.sidebar.open = !$store.sidebar.open" class="text-gray-500 lg:hidden focus:outline-none focus:text-gray-700">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        
                        <!-- Page Title/Breadcrumbs -->
                        <div class="ml-4 lg:ml-0 flex items-center h-16">
                            <h2 class="font-bold text-lg text-gray-800 tracking-tight leading-none uppercase">@isset($header){{ $header }}@else Dashboard @endisset</h2>
                        </div>
                    </div>

                    <!-- Right Header Actions -->
                    <div class="flex items-center space-x-4">
                        <livewire:layout.notification-menu />
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-grow p-4 md:p-6 lg:p-8">
                    {{ $slot }}
                </main>
                
                <footer class="bg-white border-t border-gray-200 py-4 px-6 text-center text-sm text-gray-500">
                    &copy; {{ date('Y') }} Sistem Penjaminan Mutu (SPM) Muhammadiyah
                </footer>
            </div>
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
                    });
                }
            }" 
            x-show="show" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-y-2 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            class="fixed bottom-0 right-0 p-6 z-[100]"
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
            </div>
        </div>
    </body>
</html>
