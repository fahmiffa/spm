<?php

use App\Models\Role;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public $roles;
    public $name;
    public $parameter;
    public $roleId;
    public $isEditing = false;

    public function mount()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
        $this->loadRoles();
    }

    public function loadRoles()
    {
        $this->roles = Role::orderBy('id', 'desc')->get();
    }

    public function resetForm()
    {
        $this->name = '';
        $this->parameter = '';
        $this->roleId = null;
        $this->isEditing = false;
        $this->resetErrorBag();
    }

    public function createRole()
    {
        $this->resetForm();
        $this->dispatch('open-modal', 'role-modal');
    }

    public function editRole($id)
    {
        $role = Role::findOrFail($id);
        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->parameter = $role->parameter;
        $this->isEditing = true;
        $this->dispatch('open-modal', 'role-modal');
    }

    public function saveRole()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . ($this->roleId ?? 'NULL'),
            'parameter' => 'required|string|max:255|unique:roles,parameter,' . ($this->roleId ?? 'NULL'),
        ]);

        if ($this->isEditing) {
            Role::find($this->roleId)->update(['name' => $this->name, 'name' => $this->name]);
            session()->flash('status', 'Role updated successfully.');
        } else {
            Role::create(['name' => $this->name]);
            session()->flash('status', 'Role created successfully.');
        }

        $this->loadRoles();
        $this->dispatch('close-modal', 'role-modal');
        $this->resetForm();
    }

    public function deleteRole($id)
    {
        Role::find($id)->delete();
        $this->loadRoles();
        session()->flash('status', 'Role deleted successfully.');
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Roles') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <div class="mb-4 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Manage Roles</h3>
                    <x-primary-button wire:click="createRole">
                        {{ __('Add Role') }}
                    </x-primary-button>
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
                                    Parameter</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($roles as $role)
                                <tr wire:key="{{ $role->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $loop->iteration }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $role->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $role->parameter }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button wire:click="editRole({{ $role->id }})"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            {{ __('Edit') }}
                                        </button>
                                        <button wire:click="deleteRole({{ $role->id }})"
                                            wire:confirm="Are you sure you want to delete this role?"
                                            class="text-red-600 hover:text-red-900">
                                            {{ __('Delete') }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3"
                                        class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                        No roles found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <x-modal name="role-modal" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="saveRole" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ $isEditing ? __('Edit Role') : __('Create Role') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ $isEditing ? __('Update the name of the role.') : __('Add a new role to the system.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="name" value="{{ __('Name') }}" />

                <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full"
                    placeholder="{{ __('e.g. Administrator') }}" required autofocus />

                <x-input-error :messages="$errors->get('name')" class="mt-2" />

                <x-text-input wire:model="parameter" id="parameter" name="parameter" type="text"
                    class="mt-1 block w-full" placeholder="{{ __('e.g. administrator') }}" required autofocus />
                <x-input-error :messages="$errors->get('parameter')" class="mt-2" />
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
