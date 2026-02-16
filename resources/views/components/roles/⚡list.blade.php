<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

new class extends Component {
    use \Livewire\WithPagination;

    public ?int $editingId = null;
    #[\Livewire\Attributes\Url(except: '')]
    public string $sortBy = 'created_at';
    #[\Livewire\Attributes\Url(except: '')]
    public string $sortDirection = 'desc';
    #[\Livewire\Attributes\Url(except: '')]
    public string $search = '';
    public bool $showModal = false;

    public string $name = '';
    public array $permissionIds = [];

    protected array $rules = [
        'name' => ['required', 'max:50'],
        'permissionIds' => ['array'],
        'permissionIds.*' => ['exists:permissions,id'],
    ];

    public function sort($column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function edit($id): void
    {
        $existingModel = \Spatie\Permission\Models\Role::query()->findOrFail($id);
        $existingModel->load('permissions');
        $this->editingId = $existingModel->id;
        $this->name = $existingModel->name;
        $this->permissionIds = $existingModel->permissions()->pluck('id')->toArray();
        $this->modal('add-modal')->show();
    }

    public function save(): void
    {
        $this->validate();
        $isNewUser = !$this->editingId;

        $model = \Spatie\Permission\Models\Role::updateOrCreate(['id' => $this->editingId], [
            'name' => $this->name,
        ]);
        $model->permissions()->sync($this->permissionIds);
        $this->modal('add-modal')->close();
        $this->resetFormData();
        $this->resetPage();
        session()->flash("success", "Record saved successfully");
    }

    public function resetFormData(): void
    {
        $this->reset(['name', 'permissionIds', 'editingId']);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[\Livewire\Attributes\Computed]
    public function roles(): LengthAwarePaginator
    {
        return \Spatie\Permission\Models\Role::query()
            ->withCount('permissions')
            ->when($this->search, function (Builder $query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('permissions', function (Builder $query) {
                        $query->where('description', 'like', '%' . $this->search . '%');
                    });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    public function permissions(): \Illuminate\Database\Eloquent\Collection
    {
        return Permission::query()->latest()->get();
    }

};
?>

<div>
    <div class="mb-4">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item wire:navigate href="{{ route('admin.dashboard') }}" separator="slash">
                Dashboard
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item separator="slash">Users</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>
    <div class="relative w-full">
        <div class="flex justify-between">
            <div>
                <flux:heading size="xl" level="1">
                    Roles List
                </flux:heading>
                <flux:subheading size="md" class="mb-6">
                    Manage system roles here.
                </flux:subheading>
            </div>
            <div>
                <flux:modal.trigger name="add-modal">
                    <flux:button type="button" variant="primary" icon="plus">
                        Add New
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    </div>
    <x-app-flash/>

    <div>
        {{-- TABLE --}}
        <flux:card class="space-y-6">
            <div class="flex items-center gap-4 justify-between">
                <div>

                    <flux:input type="text" placeholder="Search ..." :loading="false"
                                wire:model.live.debounce="search"
                                icon="magnifying-glass">
                        <x-slot name="iconTrailing">
                            <flux:button size="sm" wire:loading variant="subtle" icon="loading" class="-mr-1"/>
                        </x-slot>
                    </flux:input>
                </div>
                <div class="flex gap-2">

                </div>
            </div>
            {{--            <flux:input  placeholder="Search orders" />--}}

            <div wire:loading.class="opacity-50 pointer-events-none">
                <flux:table :paginate="$this->roles">
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                                           wire:click="sort('created_at')">
                            Date
                        </flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                                           wire:click="sort('name')">
                            Name
                        </flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'description'" :direction="$sortDirection"
                                           wire:click="sort('description')">
                            Permissions
                        </flux:table.column>
                        <flux:table.column>
                            Actions
                        </flux:table.column>

                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->roles as $order)
                            <flux:table.row :key="$order->id">
                                <flux:table.cell class="flex items-center gap-3">
                                    {{ $order->created_at->format("Y-m-d h:i:s") }}
                                </flux:table.cell>

                                <flux:table.cell class="whitespace-nowrap">{{ $order->name }}</flux:table.cell>
                                <flux:table.cell
                                    class="whitespace-nowrap">{{ $order->permissions_count }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:button icon="pencil" square size="xs" wire:click="edit({{$order->id}})"/>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="text-center py-4">
                                    No records found.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:card>

        {{-- MODAL --}}

        <flux:modal name="add-modal" class="md:w-7xl" @cancel="resetFormData">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        {{ $editingId ? 'Edit Role' : 'Create Role' }}
                    </flux:heading>
                    <flux:text class="mt-2">
                        Please fill the details below.
                    </flux:text>
                </div>
                <form wire:submit="save" class="space-y-6">
                    <div class="space-y-6">

                        <flux:input label="Name" wire:model="name" placeholder="Name"/>
                        <div class="space-y-2 grid grid-cols-1 gap-2 md:grid-cols-2 ">
                            @foreach($this->permissions() as $permission)
                                <flux:field variant="inline">
                                    <flux:checkbox wire:model="permissionIds" value="{{ $permission->id }}"/>
                                    <flux:label>{{ $permission->description }}</flux:label>
                                    <flux:error name="permissionIds"/>
                                </flux:field>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex gap-2 ">
                        <flux:spacer/>
                        <flux:button type="submit" variant="primary">Save changes</flux:button>
                        <flux:modal.close>
                            <flux:button>Cancel</flux:button>
                        </flux:modal.close>
                    </div>
                </form>

            </div>
        </flux:modal>



    </div>
</div>
