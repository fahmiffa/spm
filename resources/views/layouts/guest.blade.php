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

<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col justify-center items-center p-6 bg-gradient-to-r from-[#2c506d] to-[#427c95]">
        
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <div class="mx-auto flex justify-center my-6">
                <a href="/" wire:navigate>
                    <svg class="h-12 w-auto text-white lg:h-16 lg:text-[#FF2D20]" xmlns="http://www.w3.org/2000/svg"
                        width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0072beff" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-medal-icon lucide-medal">
                        <path
                            d="M7.21 15 2.66 7.14a2 2 0 0 1 .13-2.2L4.4 2.8A2 2 0 0 1 6 2h12a2 2 0 0 1 1.6.8l1.6 2.14a2 2 0 0 1 .14 2.2L16.79 15" />
                        <path d="M11 12 5.12 2.2" />
                        <path d="m13 12 5.88-9.8" />
                        <path d="M8 7h8" />
                        <circle cx="12" cy="17" r="5" />
                        <path d="M12 18v-2h-.5" />
                    </svg>
                </a>
            </div>
            {{ $slot }}
        </div>
    </div>
</body>

</html>
