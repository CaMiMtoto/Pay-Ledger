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

    public string $description='';
    public ?string $category='';

    protected array $rules = [
        'description' => ['required', 'max:50'],
        'category' => ['nullable', 'max:50'],
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
        $existingModel = Permission::query()->findOrFail($id);
        $this->editingId = $existingModel->id;
        $this->description = $existingModel->description;
        $this->category = $existingModel->category;
        $this->modal('add-modal')->show();
    }

    public function save(): void
    {
        $this->validate();
        $isNewUser = !$this->editingId;

        $user = Permission::updateOrCreate(['id' => $this->editingId], [
                'description' => $this->description,
                'category' => $this->category,
            ]
        );


        $this->modal('add-modal')->close();
        $this->reset(['description', 'category', 'editingId']);
        $this->resetPage();
        session()->flash("success", "Record saved successfully");
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[\Livewire\Attributes\Computed]
    public function permissions(): LengthAwarePaginator
    {
        return Permission::query()
            ->when($this->search, function (Builder $query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('category', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
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
                    Permissions List
                </flux:heading>
                <flux:subheading size="md" class="mb-6">
                    Manage system permissions here.
                </flux:subheading>
            </div>
            <div>

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
                <flux:table :paginate="$this->permissions">
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
                            Description
                        </flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'category'" :direction="$sortDirection"
                                           wire:click="sort('category')">
                            Category
                        </flux:table.column>
                        <flux:table.column>
                            Actions
                        </flux:table.column>

                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->permissions as $order)
                            <flux:table.row :key="$order->id">
                                <flux:table.cell class="flex items-center gap-3">
                                    {{ $order->created_at->format("Y-m-d h:i:s") }}
                                </flux:table.cell>

                                <flux:table.cell class="whitespace-nowrap">{{ $order->name }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $order->description }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $order->category }}</flux:table.cell>

                                <flux:table.cell>
                                    <flux:button icon="pencil" square size="xs" wire:click="edit({{$order->id}})"/>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:card>

        {{-- MODAL --}}

        <flux:modal name="add-modal" class="md:w-7xl">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        {{ $editingId ? 'Edit Permission' : 'Create Permission' }}
                    </flux:heading>
                    <flux:text class="mt-2">
                        Please fill the details below.
                    </flux:text>
                </div>
                <form wire:submit="save" class="space-y-6">
                    <div class="space-y-6">

                        <flux:input label="Description" wire:model="description" placeholder="Description"/>
                        <flux:input label="Category" wire:model="category" placeholder="Category"/>
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
