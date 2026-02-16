<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use LaravelIdea\Helper\App\Models\_IH_Business_C;
use Livewire\Component;

new class extends Component {
    use \Livewire\WithPagination;

//    users fields
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public bool $is_active = false;
    public ?int $business_id = null;
    public array $roleIds = [];


    public ?int $editingId = null;
    public ?User $deletingRecord = null;
    #[\Livewire\Attributes\Url(except: '')]
    public string $sortBy = 'created_at';
    #[\Livewire\Attributes\Url(except: '')]
    public string $sortDirection = 'desc';
    #[\Livewire\Attributes\Url(except: '')]
    public string $search = '';
    public bool $showModal = false;
    public string $statusFilter = 'All';
    public Collection $roles;


    public function mount(): void
    {
        $this->roles = $this->getRoles();
    }

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
        $existingUser = User::findOrFail($id);
        $this->editingId = $existingUser->id;
        $this->name = $existingUser->name;
        $this->phone = $existingUser->phone;
        $this->email = $existingUser->email;
        $this->address = $existingUser->address;
        $this->business_id = $existingUser->business_id;
        $this->is_active = $existingUser->is_active;
        $this->roleIds = $existingUser->roles()->pluck('id')->toArray();
        $this->modal('add-modal')->show();
    }

    public function save(): void
    {
        $this->validate([
                'name' => ['required', 'max:50'],
                'email' => ['required', 'email', 'max:255',
                    Rule::unique('users', 'email')->ignore($this->editingId)
                ],
                'phone' => ['required', 'string', 'max:20'],
                'address' => ['nullable', 'string', 'max:255'],
                'is_active' => ['nullable', 'boolean'],
                'business_id' => ['required', 'exists:businesses,id'],
                'roleIds' => ['array'],
                'roleIds.*' => ['exists:roles,id'],
            ]
        );
        $isNewUser = !$this->editingId;

        $user = User::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'phone' => $this->phone,
                'email' => $this->email,
                'address' => $this->address,
                'is_active' => $this->is_active,
                'business_id' => $this->business_id,
            ]
        );
        $user->roles()->sync($this->roleIds);
        if ($isNewUser) {
            Password::sendResetLink([
                'email' => $user->email
            ]);
        }

        $this->modal('add-modal')->close();
        $this->reset(['name', 'phone', 'editingId', 'email', 'address', 'is_active', 'business_id']);
        $this->resetPage();
        session()->flash("success", "Record saved successfully");
    }

    public function confirmDeletion($id): void
    {
        $this->deletingRecord = User::findOrFail($id);
        $this->modal('delete-record')->show();
    }

    public function delete(): void
    {
        if ($this->deletingRecord) {
            $this->deletingRecord->delete();
        }
        $this->modal('delete-record')->close();
        $this->reset(['deletingRecord']);
        $this->resetPage();
        session()->flash("success", "Record deleted successfully");
    }


    public function getBusinesses(): Collection|array|_IH_Business_C
    {
        return \App\Models\Business::query()->latest()->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[\Livewire\Attributes\Computed]
    public function users()
    {
        return User::query()
            ->with(['business'])
            ->withCount(['roles'])
            ->when($this->search, function (Builder $query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter != 'All', function (Builder $query) {
                if ($this->statusFilter == 'Active') {
                    $query->where('is_active', true);
                } else if ($this->statusFilter == 'Inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    private function getRoles(): Collection
    {
        return \Spatie\Permission\Models\Role::query()->latest()->get();
    }

    public function resetFormData(): void
    {
        $this->reset(['name', 'phone', 'editingId', 'email', 'address', 'is_active', 'business_id', 'roleIds']);
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
                    Users List
                </flux:heading>
                <flux:subheading size="md" class="mb-6">
                    Manage system users here.
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

                    <flux:dropdown>
                        <flux:button icon:trailing="chevron-down">Filter Status</flux:button>
                        <flux:menu>
                            <flux:menu.radio.group wire:model.live="statusFilter">
                                <flux:menu.radio>All</flux:menu.radio>
                                <flux:menu.radio>Active</flux:menu.radio>
                                <flux:menu.radio>Inactive</flux:menu.radio>
                            </flux:menu.radio.group>
                        </flux:menu>
                    </flux:dropdown>
                </div>
                <div class="flex gap-2">
                    <flux:input type="text" placeholder="Search ..." :loading="false"
                                wire:model.live.debounce="search"
                                icon="magnifying-glass">
                        <x-slot name="iconTrailing">
                            <flux:button size="sm" wire:loading variant="subtle" icon="loading" class="-mr-1"/>
                        </x-slot>
                    </flux:input>
                </div>
            </div>
            {{--            <flux:input  placeholder="Search orders" />--}}

            <div wire:loading.class="opacity-50 pointer-events-none">
                <flux:table :paginate="$this->users">
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                                           wire:click="sort('created_at')">
                            Date
                        </flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                                           wire:click="sort('name')">
                            Name
                        </flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection"
                                           wire:click="sort('email')">
                            Email
                        </flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'business_id'" :direction="$sortDirection"
                                           wire:click="sort('business_id')">
                            Business
                        </flux:table.column>
                        <flux:table.column>Roles</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection"
                                           wire:click="sort('status')">
                            Status
                        </flux:table.column>
                        <flux:table.column>
                            Actions
                        </flux:table.column>

                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->users as $order)
                            <flux:table.row :key="$order->id">
                                <flux:table.cell
                                    class="flex items-center gap-3">{{ $order->created_at->format("Y-m-d h:i:s") }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $order->name }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">
                                    <span class="text-sm block text-black mb-1">{{ $order->email }}</span>
                                    <span class="text-xs block">{{ $order->phone }}</span>
                                </flux:table.cell>
                                <flux:table.cell
                                    class="whitespace-nowrap">{{ $order->business->name??'N/A' }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $order->roles_count }}</flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$order->is_active?'green':'red'" rounded
                                                inset="top bottom">{{ $order->is_active?'Active':'Inactive' }}</flux:badge>
                                </flux:table.cell>


                                <flux:table.cell>
                                    <flux:dropdown>
                                        <flux:button size="sm" icon:trailing="chevron-down"></flux:button>

                                        <flux:menu>

                                            <flux:menu.item icon="square-pen" wire:click="edit({{$order->id}})">
                                                Edit
                                            </flux:menu.item>
                                            <flux:menu.item variant="danger"
                                                            wire:click="confirmDeletion({{$order->id}})"
                                                            icon="trash">
                                                Delete
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:card>

        {{-- MODAL --}}

        <flux:modal name="add-modal" class="md:w-7xl" @cancel="resetFormData">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        {{ $editingId ? 'Edit User' : 'Create User' }}
                    </flux:heading>
                    <flux:text class="mt-2">
                        Please fill the details below.
                    </flux:text>
                </div>
                <form wire:submit="save" class="space-y-6">
                    <div class="space-y-6">
                        <div>
                            <x-forms.select
                                :options="$this->getBusinesses()"
                                id="business_id"
                                label="Business"
                                placeholder="Choose a business"
                                wire:model="business_id"
                            />

                            @error('business_id')
                            <flux:text size="sm" color="red" class="mt-2">{{ $message }}</flux:text>
                            @enderror

                        </div>
                        <flux:input label="Name" wire:model="name" placeholder="Name"/>
                        <flux:input type="email" label="Email" wire:model="email" placeholder="Email Address"/>
                        <flux:input type="tel" label="Phone" wire:model="phone" placeholder="Phone Number"/>
                        <div class="space-y-2 grid grid-cols-1 gap-2 md:grid-cols-2 place-items-start">
                            @foreach($roles as $permission)
                                <flux:field variant="inline">
                                    <flux:checkbox wire:model="roleIds" value="{{ $permission->id }}"/>
                                    <flux:label>{{ $permission->name }}</flux:label>
                                    <flux:error name="roleIds"/>
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


        <flux:modal
            name="delete-record" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete record?</flux:heading>

                    <flux:text class="mt-2">
                        You're about to delete this record (<strong>{{ $this->deletingRecord?->name }}</strong>).<br>
                        This action cannot be reversed.
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer/>

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="danger" wire:click="delete">
                        Yes, delete it
                    </flux:button>
                </div>
            </div>
        </flux:modal>

    </div>
</div>
