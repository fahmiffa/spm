<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

\Livewire\Volt\Volt::route('roles', 'pages.roles.index')
    ->middleware(['auth', 'verified'])
    ->name('roles.index');

\Livewire\Volt\Volt::route('accounts', 'pages.accounts.index')
    ->middleware(['auth', 'verified'])
    ->name('accounts.index');

\Livewire\Volt\Volt::route('pesantren/profile', 'pages.pesantren.profile')
    ->middleware(['auth', 'verified'])
    ->name('pesantren.profile');

\Livewire\Volt\Volt::route('pesantren/ipm', 'pages.pesantren.ipm')
    ->middleware(['auth', 'verified'])
    ->name('pesantren.ipm');

\Livewire\Volt\Volt::route('pesantren/sdm', 'pages.pesantren.sdm')
    ->middleware(['auth', 'verified'])
    ->name('pesantren.sdm');

\Livewire\Volt\Volt::route('pesantren/edpm', 'pages.pesantren.edpm')
    ->middleware(['auth', 'verified'])
    ->name('pesantren.edpm');

\Livewire\Volt\Volt::route('pesantren/akreditasi', 'pages.pesantren.akreditasi')
    ->middleware(['auth', 'verified'])
    ->name('pesantren.akreditasi');

\Livewire\Volt\Volt::route('asesor/profile', 'pages.asesor.profile')
    ->middleware(['auth', 'verified'])
    ->name('asesor.profile');

\Livewire\Volt\Volt::route('asesor/akreditasi', 'pages.asesor.akreditasi')
    ->middleware(['auth', 'verified'])
    ->name('asesor.akreditasi');

\Livewire\Volt\Volt::route('asesor/akreditasi/{uuid}', 'pages.asesor.akreditasi-detail')
    ->middleware(['auth', 'verified'])
    ->name('asesor.akreditasi-detail');

\Livewire\Volt\Volt::route('admin/master-edpm', 'pages.admin.master-edpm')
    ->middleware(['auth', 'verified'])
    ->name('admin.master-edpm');

\Livewire\Volt\Volt::route('admin/akreditasi', 'pages.admin.akreditasi')
    ->middleware(['auth', 'verified'])
    ->name('admin.akreditasi');

\Livewire\Volt\Volt::route('admin/akreditasi/{uuid}', 'pages.admin.akreditasi-detail')
    ->middleware(['auth', 'verified'])
    ->name('admin.akreditasi-detail');

require __DIR__.'/auth.php';
