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
    public $userId;
    
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
        $this->users = User::with('role')->orderBy('id', 'desc')->get();
    }

    public function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role_id = '';
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
        $this->role_id = $user->role_id;
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
        ];

        if (!$this->isEditing) {
            $rules['password'] = ['required', 'string', Rules\Password::defaults()];
        } else {
            $rules['password'] = ['nullable', 'string', Rules\Password::defaults()];
        }

        $this->validate($rules);

        if ($this->isEditing) {
            $user = User::find($this->userId);
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'role_id' => $this->role_id,
            ];
            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }
            $user->update($data);
            session()->flash('status', 'Account updated successfully.');
        } else {
            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role_id' => $this->role_id,
            ]);
            session()->flash('status', 'Account created successfully.');
        }

        $this->loadUsers();
        $this->dispatch('close-modal', 'account-modal');
        $this->resetForm();
    }

    public function deleteUser($id)
    {
        if ($id == auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }
        User::find($id)->delete();
        $this->loadUsers();
        session()->flash('status', 'Account deleted successfully.');
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Account Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />
                @if (session('error'))
                    <div class="mb-4 font-medium text-sm text-red-600">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="mb-4 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Users</h3>
                    <x-primary-button wire:click="createUser">
                        {{ __('Add Account') }}
                    </x-primary-button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 border">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($users as $user)
                                <tr wire:key="{{ $user->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $loop->iteration }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ $user->role?->name ?? 'No Role' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button wire:click="editUser({{ $user->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            {{ __('Edit') }}
                                        </button>
                                        @if ($user->id !== auth()->id())
                                            <button 
                                                wire:click="deleteUser({{ $user->id }})" 
                                                wire:confirm="Are you sure you want to delete this account?"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                {{ __('Delete') }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
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
                    <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" value="{{ __('Email') }}" />
                    <x-text-input wire:model="email" id="email" type="email" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="role_id" value="{{ __('Role') }}" />
                    <select wire:model="role_id" id="role_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" value="{{ __('Password') }}" />
                    <x-text-input wire:model="password" id="password" type="password" class="mt-1 block w-full" :required="!$isEditing" />
                    @if($isEditing)
                        <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password.</p>
                    @endif
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
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
</div>
