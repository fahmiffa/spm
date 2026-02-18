<?php

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public $users;
    public $roles;

    public $name;
    public $email;
    public $password;
    public $role_id;
    public $status = true; // Default to true (active)
    public $userId;
    public $search = '';
    public $activeTab = 1; // Default to Admin role (ID 1)

    public $isEditing = false;

    public function mount()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
        $this->loadUsers();
        $this->roles = Role::all();
    }

    public function loadUsers()
    {
        $this->users = User::with('role')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function updatedSearch()
    {
        $this->loadUsers();
    }

    public function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role_id = '';
        $this->status = true;
        $this->userId = null;
        $this->isEditing = false;
        $this->resetErrorBag();
    }

    public function createUser()
    {
        $this->resetForm();
        $this->dispatch('open-modal', 'account-modal');
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->email = $user->email;
        $this->role_id = $user->role_id;
        $this->status = $user->status == 1; // 1 is active, 0 is inactive
        $this->password = '';
        $this->isEditing = true;
        $this->dispatch('open-modal', 'account-modal');
    }

    public function saveAccount()
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . ($this->userId ?? 'NULL')],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['boolean'] // Add validation for status
        ];

        // $rules['password'] = ['nullable', 'string', Rules\Password::defaults()];
        $rules['password'] = ['nullable', 'string'];

        $this->validate($rules);

        if ($this->isEditing) {
            $user = User::find($this->userId);
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'role_id' => $this->role_id,
                'status' => $this->status ? 1 : 0
            ];
            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }
            $user->update($data);
            $this->dispatch('swal:success', title: 'Berhasil!', text: 'Data Akun berhasil diperbarui.');
        } else {
            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role_id' => $this->role_id,
                'status' => $this->status ? 1 : 0
            ]);
            $this->dispatch('swal:success', title: 'Berhasil!', text: 'Data Akun berhasil ditambahkan.');
        }

        $this->loadUsers();
        $this->dispatch('close-modal', 'account-modal');
        $this->resetForm();
    }

    public function deleteUser($id)
    {
        if ($id == auth()->id()) {
            $this->dispatch('swal:error', title: 'Gagal!', text: 'Anda tidak dapat menghapus akun Anda sendiri.');
            return;
        }
        User::find($id)->delete();
        $this->loadUsers();
        $this->dispatch('swal:success', title: 'Berhasil!', text: 'Data Akun berhasil dihapus.');
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->status = $user->status == 1 ? 0 : 1;
        $user->save();

        $this->loadUsers();
        $this->dispatch('swal:success', title: 'Berhasil!', text: 'Status akun berhasil diubah.');
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }
}; ?>

<div>
    <x-slot name="header">{{ __('Account Management') }}</x-slot>

    <div class="py-12" x-data="deleteConfirmation">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />
                @if (session('error'))
                <div class="mb-4 font-medium text-sm text-red-600">
                    {{ session('error') }}
                </div>
                @endif

                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">Manajemen Akun</h3>
                    </div>
                    <x-primary-button wire:click="createUser">
                        {{ __('Add Account') }}
                    </x-primary-button>
                </div>

                <!-- Search Input -->
                <div class="mb-6">
                    <div class="relative max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-150 ease-in-out"
                            placeholder="Cari berdasarkan nama atau email...">
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <div class="mb-6 border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                        <li class="me-2">
                            <button wire:click="setTab(1)"
                                class="inline-block p-4 border-b-2 rounded-t-lg transition-colors {{ $activeTab == 1 ? 'text-indigo-600 border-indigo-600 bg-indigo-50/50' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    Admin
                                    <span class="ml-2 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-[10px] font-bold">{{ $users->where('role_id', 1)->count() }}</span>
                                </div>
                            </button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab(2)"
                                class="inline-block p-4 border-b-2 rounded-t-lg transition-colors {{ $activeTab == 2 ? 'text-indigo-600 border-indigo-600 bg-indigo-50/50' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    Asesor
                                    <span class="ml-2 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-[10px] font-bold">{{ $users->where('role_id', 2)->count() }}</span>
                                </div>
                            </button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab(3)"
                                class="inline-block p-4 border-b-2 rounded-t-lg transition-colors {{ $activeTab == 3 ? 'text-indigo-600 border-indigo-600 bg-indigo-50/50' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    Pesantren
                                    <span class="ml-2 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-[10px] font-bold">{{ $users->where('role_id', 3)->count() }}</span>
                                </div>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 border">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    No.</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nama</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Email</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Role</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($users->where('role_id', $activeTab) as $index => $user)
                            <tr wire:key="user-{{ $user->id }}" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->email }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ $user->role?->name ?? 'No Role' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $user->status ? 'Aktif' : 'Tidak Aktif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="editUser({{ $user->id }})"
                                        class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        {{ __('Edit') }}
                                    </button>
                                    @if ($user->id !== auth()->id())
                                    <button @click="confirmAction({{ $user->id }}, 'toggleStatus', 'Ubah status akun menjadi {{ $user->status ? 'Tidak Aktif' : 'Aktif' }}?', 'Ya, Ubah!')"
                                        class="{{ $user->status ? 'text-amber-600 hover:text-amber-900' : 'text-emerald-600 hover:text-emerald-900' }} mr-3">
                                        {{ $user->status ? __('Non Aktifkan') : __('Aktifkan') }}
                                    </button>
                                    <!-- <button @click="confirmDelete({{ $user->id }}, 'deleteUser', 'Hapus akun ini?')"
                                        class="text-red-600 hover:text-red-900">
                                        {{ __('Delete') }}
                                    </button> -->
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5"
                                    class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                    No users found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <x-modal name="account-modal" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="saveAccount" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ $isEditing ? __('Edit Account') : __('Create Account') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ $isEditing ? __('Update account details.') : __('Add a new user account to the system.') }}
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="name" value="{{ __('Name') }}" />
                    <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" required
                        autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" value="{{ __('Email') }}" />
                    <x-text-input wire:model="email" id="email" type="email" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="role_id" value="{{ __('Role') }}" />
                    <select wire:model="role_id" id="role_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        required>
                        <option value="">Select Role</option>
                        @foreach ($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" value="{{ __('Password') }}" />
                    <x-text-input wire:model="password" id="password" type="password" class="mt-1 block w-full"
                        :required="!$isEditing" />
                    @if ($isEditing)
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password.</p>
                    @endif
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="block mt-4">
                    <label for="status" class="inline-flex items-center">
                        <input id="status" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="status" wire:model="status">
                        <span class="ms-2 text-sm text-gray-600">{{ __('Status Aktif') }}</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ms-3">
                    {{ $isEditing ? __('Update') : __('Save') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('swal:success', (data) => {
                Swal.fire({
                    title: data[0].title,
                    text: data[0].text,
                    icon: 'success',
                    confirmButtonColor: '#10b981', // emerald-500 matching the theme
                    confirmButtonText: 'OK',
                    timer: 3000,
                    timerProgressBar: true
                });
            });

            Livewire.on('swal:error', (data) => {
                Swal.fire({
                    title: data[0].title,
                    text: data[0].text,
                    icon: 'error',
                    confirmButtonColor: '#ef4444', // red-500
                    confirmButtonText: 'OK'
                });
            });
        });
    </script>
</div>