<?php

use App\Models\Business;
use Livewire\Component;

new class extends Component {
    use \Livewire\WithPagination;

    public string $name = '';
    public string $phone = '';
    public ?string $email = '';
    public ?string $address = '';

    public ?int $editingId = null;
    public ?Business $deletingRecord = null;
    #[\Livewire\Attributes\Url(except: '')]
    public string $sortBy = 'created_at';
    #[\Livewire\Attributes\Url(except: '')]
    public string $sortDirection = 'desc';
    #[\Livewire\Attributes\Url(except: '')]
    public string $search = '';

    public bool $showModal = false;

    protected array $rules = [
        'name' => 'required|string|max:255',
        'phone' => 'required|string|max:20',
        'email' => ['nullable', 'string', 'email', 'max:50'],
        'address' => ['nullable', 'string', 'max:50']
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
        $business = Business::findOrFail($id);

        $this->editingId = $business->id;
        $this->name = $business->name;
        $this->phone = $business->phone;
        $this->email = $business->email;
        $this->address = $business->address;
        $this->modal('add-modal')->show();
    }

    public function save(): void
    {
        $this->validate();
        Business::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'phone' => $this->phone,
                'email' => $this->email,
                'address' => $this->address,
            ]
        );
        $this->modal('add-modal')->close();
        $this->reset(['name', 'phone', 'editingId', 'email', 'address']);
        $this->resetPage();
        session()->flash("success", "Business saved successfully");
    }

    public function confirmDeletion($id): void
    {
        $this->deletingRecord = Business::findOrFail($id);
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
        session()->flash("success", "Business deleted successfully");
    }

    #[\Livewire\Attributes\Computed]
    public function businesses(): object
    {
        return Business::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
};

?>

<div>
    <div class="mb-4">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item wire:navigate href="{{ route('admin.dashboard') }}" separator="slash">Dashboard
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item separator="slash">List</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>
    <div class="relative w-full">
        <div class="flex justify-between">
            <div>
                <flux:heading size="xl" level="1">
                    Businesses
                </flux:heading>
                <flux:subheading size="md" class="mb-6">
                    Manage your businesses
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
                        <flux:button icon:trailing="chevron-down">Sort by</flux:button>

                        <flux:menu>
                            <flux:menu.radio.group wire:model="sortBy">
                                <flux:menu.radio checked>Latest activity</flux:menu.radio>
                                <flux:menu.radio>Date created</flux:menu.radio>
                                <flux:menu.radio>Most popular</flux:menu.radio>
                            </flux:menu.radio.group>
                        </flux:menu>
                    </flux:dropdown>
                </div>
                <div>
                    <flux:input type="text" placeholder="Search businesses..." :loading="false"
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
                <flux:table :paginate="$this->businesses">
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                                           wire:click="sort('created_at')">
                            Date
                        </flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                                           wire:click="sort('name')">
                            Name
                        </flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'phone'" :direction="$sortDirection"
                                           wire:click="sort('phone')">
                            Phone
                        </flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection"
                                           wire:click="sort('status')">Status
                        </flux:table.column>
                        <flux:table.column>
                            Actions
                        </flux:table.column>

                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->businesses as $order)
                            <flux:table.row :key="$order->id">
                                <flux:table.cell class="flex items-center gap-3">
                                    {{ $order->created_at->format("Y-m-d h:i:s") }}
                                </flux:table.cell>

                                <flux:table.cell class="whitespace-nowrap">{{ $order->name }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $order->phone }}</flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$order->statusColor" rounded
                                                inset="top bottom">{{ ucfirst($order->subscription_status) }}</flux:badge>
                                </flux:table.cell>


                                <flux:table.cell>
                                    <flux:dropdown>
                                        <flux:button size="sm" icon:trailing="chevron-down"></flux:button>

                                        <flux:menu>
                                            <flux:menu.item icon="info">Details</flux:menu.item>
                                            <flux:menu.item icon="wallet-cards">Debts</flux:menu.item>
                                            <flux:menu.separator/>
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

        <flux:modal name="add-modal" class="md:w-7xl">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        {{ $editingId ? 'Edit Business' : 'Create Business' }}
                    </flux:heading>
                    <flux:text class="mt-2">
                        Please fill the details below.
                    </flux:text>
                </div>
                <form wire:submit="save" class="space-y-6">
                    <div class="space-y-6">
                        <flux:input label="Name" wire:model="name" placeholder="Business Name"/>
                        <flux:input type="tel" label="Phone" wire:model="phone" placeholder="Phone Number"/>
                        <flux:input type="email" label="Email" wire:model="email" placeholder="Email Address"/>
                        <flux:input label="Address" wire:model="address" placeholder="Business Address"/>
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
