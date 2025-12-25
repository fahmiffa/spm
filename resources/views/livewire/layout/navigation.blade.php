<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 sticky top-0 z-40 shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 relative">
            <!-- Left Side: Hamburger (Mobile) & Desktop Links (Start) -->
            <div class="flex items-center">
                <!-- Hamburger (Mobile Only) -->
                <div class="flex items-center sm:hidden">
                    <button @click="open = ! open"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 12h16M4 6h16M4 18h16" />
                            <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                                stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Logo (Desktop: Left, Mobile: Hidden/Placeholder) -->
                <div class="hidden sm:shrink-0 sm:flex sm:items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @if (auth()->user()->isAdmin())
                        <x-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')" wire:navigate>
                            {{ __('Roles') }}
                        </x-nav-link>
                        <x-nav-link :href="route('accounts.index')" :active="request()->routeIs('accounts.*')" wire:navigate>
                            {{ __('Accounts') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.master-edpm')" :active="request()->routeIs('admin.master-edpm')" wire:navigate>
                            {{ __('Master EDPM') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.akreditasi')" :active="request()->routeIs('admin.akreditasi')" wire:navigate>
                            {{ __('Akreditasi') }}
                        </x-nav-link>
                    @endif

                    @if (auth()->user()->isPesantren())
                        <x-nav-link :href="route('pesantren.profile')" :active="request()->routeIs('pesantren.profile')" wire:navigate>
                            {{ __('Profil Pesantren') }}
                        </x-nav-link>
                        <x-nav-link :href="route('pesantren.ipm')" :active="request()->routeIs('pesantren.ipm')" wire:navigate>
                            {{ __('IPM') }}
                        </x-nav-link>
                        <x-nav-link :href="route('pesantren.sdm')" :active="request()->routeIs('pesantren.sdm')" wire:navigate>
                            {{ __('REKAPITULASI DATA SDM') }}
                        </x-nav-link>
                        <x-nav-link :href="route('pesantren.edpm')" :active="request()->routeIs('pesantren.edpm')" wire:navigate>
                            {{ __('EDPM') }}
                        </x-nav-link>
                        <x-nav-link :href="route('pesantren.akreditasi')" :active="request()->routeIs('pesantren.akreditasi')" wire:navigate>
                            {{ __('Akreditasi') }}
                        </x-nav-link>
                    @endif

                    @if (auth()->user()->isAsesor())
                        <x-nav-link :href="route('asesor.profile')" :active="request()->routeIs('asesor.profile')" wire:navigate>
                            {{ __('Profil') }}
                        </x-nav-link>
                        <x-nav-link :href="route('asesor.akreditasi')" :active="request()->routeIs('asesor.akreditasi*')" wire:navigate>
                            {{ __('Akreditasi') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Center: Logo (Mobile Only) -->
            <div class="flex items-center sm:hidden absolute left-1/2 -translate-x-1/2 h-full">
                <a href="{{ route('dashboard') }}" wire:navigate>
                    <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                </a>
            </div>

            <!-- Right Side: Settings Dropdown (Desktop) & Profile Trigger (Mobile) -->
            <div class="flex items-center sm:ms-6">
                <!-- Desktop Settings Dropdown -->
                <div class="hidden sm:flex sm:items-center">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <div x-data='@json(['name' => auth()->user()->name,'role' => auth()->user()->role->name,])' x-text="`${name} (${role})`"
                                    x-on:profile-updated.window="name = $event.detail.name;role = $event.detail.role;"
                                    class="whitespace-nowrap">
                                </div>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile')" wire:navigate>
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <button wire:click="logout" class="w-full text-start">
                                <x-dropdown-link>
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                </div>

                <!-- Mobile Profile Link -->
                <div class="flex items-center sm:hidden">
                    <a href="{{ route('profile') }}" wire:navigate class="p-2 text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Sidebar Menu -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-0 z-50 overflow-hidden sm:hidden" 
         style="display: none;">
        
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false"></div>

        <!-- Sidebar -->
        <div class="absolute inset-y-0 left-0 max-w-xs w-full bg-white shadow-xl flex flex-col">
            <div class="flex items-center justify-between p-4 border-b">
                <div class="flex items-center gap-2">
                    <x-application-logo class="block h-8 w-auto fill-current text-gray-800" />
                    <span class="font-bold text-gray-800">SPM</span>
                </div>
                <button @click="open = false" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto pt-2 pb-4">
                <div class="space-y-1 px-2">
                    <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </x-responsive-nav-link>
                    
                    @if (auth()->user()->isAdmin())
                        <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Admin</div>
                        <x-responsive-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')" wire:navigate>
                            {{ __('Roles') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('accounts.index')" :active="request()->routeIs('accounts.*')" wire:navigate>
                            {{ __('Accounts') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('admin.master-edpm')" :active="request()->routeIs('admin.master-edpm')" wire:navigate>
                            {{ __('Master EDPM') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('admin.akreditasi')" :active="request()->routeIs('admin.akreditasi')" wire:navigate>
                            {{ __('Akreditasi') }}
                        </x-responsive-nav-link>
                    @endif

                    @if (auth()->user()->isPesantren())
                        <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Pesantren</div>
                        <x-responsive-nav-link :href="route('pesantren.profile')" :active="request()->routeIs('pesantren.profile')" wire:navigate>
                            {{ __('Profil Pesantren') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('pesantren.ipm')" :active="request()->routeIs('pesantren.ipm')" wire:navigate>
                            {{ __('IPM') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('pesantren.sdm')" :active="request()->routeIs('pesantren.sdm')" wire:navigate>
                            {{ __('REKAPITULASI DATA SDM') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('pesantren.edpm')" :active="request()->routeIs('pesantren.edpm')" wire:navigate>
                            {{ __('EDPM') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('pesantren.akreditasi')" :active="request()->routeIs('pesantren.akreditasi')" wire:navigate>
                            {{ __('Akreditasi') }}
                        </x-responsive-nav-link>
                    @endif

                    @if (auth()->user()->isAsesor())
                        <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Asesor</div>
                        <x-responsive-nav-link :href="route('asesor.profile')" :active="request()->routeIs('asesor.profile')" wire:navigate>
                            {{ __('Profil') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('asesor.akreditasi')" :active="request()->routeIs('asesor.akreditasi*')" wire:navigate>
                            {{ __('Akreditasi') }}
                        </x-responsive-nav-link>
                    @endif
                </div>
            </div>

            <!-- Sidebar Footer -->
            <div class="p-4 border-t bg-gray-50">
                <div class="flex items-center gap-3 mb-4 px-2">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <div class="space-y-1">
                    <x-responsive-nav-link :href="route('profile')" wire:navigate>
                        {{ __('Profile Settings') }}
                    </x-responsive-nav-link>
                    <button wire:click="logout" class="w-full text-start">
                        <x-responsive-nav-link class="text-red-600">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </button>
                </div>
            </div>
        </div>
    </div>
</nav>
