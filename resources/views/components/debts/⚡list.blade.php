<?php

use App\Models\Debt;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

new class extends Component {
    use \Livewire\WithPagination;

    public ?int $customer_id = null;
    public float $amount;
    public string $description = '';
    public string $due_date = '';
    public string $status = 'unpaid';
    public ?int $editingId = null;

    public array $pageSizes = [
        10, 25, 50, 100
    ];

    #[\Livewire\Attributes\Url(except: 'perPage')]
    public int $perPage = 10;
    public ?\App\Models\Debt $deletingRecord = null;
    public Collection $customers;
    #[\Livewire\Attributes\Url(except: 'created_at')]
    public string $sortBy = 'created_at';
    #[\Livewire\Attributes\Url(except: 'desc')]
    public string $sortDir = 'desc';
    #[\Livewire\Attributes\Url(except: '')]
    public string $search = '';
    public bool $showModal = false;


    public function mount(): void
    {
        $this->customers = $this->getCustomers();
    }

    public function handleSort($col): void
    {
        if ($this->sortBy === $col) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $col;
            $this->sortDir = 'asc';
        }
    }


    public function save(): void
    {
        $this->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            'due_date' => 'required|date|after_or_equal:today',
            'status' => 'required',
        ]);

        Debt::create([
            'business_id' => Auth::user()->business_id,
            'customer_id' => $this->customer_id,
            'amount' => $this->amount,
            'description' => $this->description,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'created_by' => Auth::id(),
        ]);

        $this->dispatch('debtCreated'); // notify parent component
        $this->reset(['customer_id', 'amount', 'description', 'due_date']);
        session()->flash('success', 'Debt recorded successfully');
    }

    #[\Livewire\Attributes\Computed]
    public function debts(): \Illuminate\Pagination\LengthAwarePaginator|array|\LaravelIdea\Helper\App\Models\_IH_Debt_C
    {
        return Debt::query()
            ->with(['customer', 'business'])
            ->when(auth()->user()->business_id, function ($query) {
                $query->where('business_id', auth()->user()->business_id);
            })
            ->paginate(10);
    }

    /**
     * @return \App\Models\Customer[]|Collection|\Illuminate\Support\Collection|\LaravelIdea\Helper\App\Models\_IH_Customer_C
     */
    public function getCustomers(): \Illuminate\Support\Collection|array|\LaravelIdea\Helper\App\Models\_IH_Customer_C|Collection
    {
        return \App\Models\Customer::query()
            ->when(auth()->user()->business_id, function ($query) {
                $query->where('business_id', auth()->user()->business_id);
            })
            ->orderBy('name')
            ->get();
    }

    public function edit(int $id): void
    {
        $debt = Debt::findOrFail($id);
        $this->customer_id = $debt->customer_id;
        $this->amount = $debt->amount;
        $this->description = $debt->description;
        $this->due_date = $debt->due_date?->format('Y-m-d');
        $this->editingId = $id;
        $this->showModal = true;
    }

    public function confirmDeletion($id): void
    {
        $this->deletingRecord = \App\Models\Debt::findOrFail($id);
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function resetFormData(): void
    {
        $this->reset(['customer_id', 'amount', 'description', 'due_date', 'editingId']);
    }
};
?>


<div>
    <div class="mb-4">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item wire:navigate href="{{ route('admin.dashboard') }}" separator="slash">
                Dashboard
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item separator="slash">
                Debts
            </flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>
    <div class="relative w-full">
        <div class="flex justify-between">
            <div>
                <flux:heading size="xl" level="1">
                    Debts List
                </flux:heading>
                <flux:subheading size="md" class="mb-6">
                    Manage your customers' debts here. You can add, edit, or delete debt records as needed.
                </flux:subheading>
            </div>
            <div>
                <flux:modal.trigger name="add-modal">
                    <flux:button type="button" size="sm" variant="primary" icon="plus">
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
                        <flux:button icon:trailing="chevron-down">Per Page</flux:button>
                        <flux:menu>
                            <flux:menu.radio.group wire:model.live="perPage">
                                @foreach ($pageSizes as $size)
                                    <flux:menu.radio :value="$size">{{ $size }} per page</flux:menu.radio>
                                @endforeach
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
                <flux:table :paginate="$this->debts">
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDir"
                                           wire:click="sort('created_at')">
                            Date
                        </flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'due_date'" :direction="$sortDir"
                                           wire:click="sort('due_date')">
                            Due Date
                        </flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'amount'" :direction="$sortDir"
                                           wire:click="sort('amount')">
                            Amount
                        </flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDir"
                                           wire:click="sort('status')">
                            Status
                        </flux:table.column>
                        <flux:table.column>
                            Actions
                        </flux:table.column>

                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->debts as $item)
                            <flux:table.row :key="$item->id">
                                <flux:table.cell
                                    class="flex items-center gap-3">{{ $item->created_at->format("Y-m-d h:i:s") }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $item->due_date->toDateString() }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">
                                    {{ number_format($item->amount,0) }}
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">
                                    <flux:badge :color="$item->statusColor" rounded variant="solid"
                                                size="sm">{{ ucfirst($item->status) }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:dropdown>
                                        <flux:button size="sm" icon:trailing="chevron-down"></flux:button>

                                        <flux:menu>

                                            <flux:menu.item icon="square-pen" wire:click="edit({{$item->id}})">
                                                Edit
                                            </flux:menu.item>
                                            <flux:menu.item variant="danger"
                                                            wire:click="confirmDeletion({{$item->id}})"
                                                            icon="trash">
                                                Delete
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center">
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
                        {{ $editingId ? 'Edit Debt' : 'Create Debt' }}
                    </flux:heading>
                    <flux:text class="mt-2">
                        Please fill the details below.
                    </flux:text>
                </div>
                <form wire:submit="save" class="space-y-6">
                    <div class="space-y-6">
                        <div>
                            <x-forms.select
                                :options="$this->customers"
                                id="customer_id"
                                label="Customer"
                                placeholder="Choose a Customer"
                                wire:model="customer_id"
                            />
                            @error('customer_id')
                            <flux:text size="sm" color="red" class="mt-1">{{ $message }}</flux:text>
                            @enderror
                        </div>

                        <flux:input label="Amount" wire:model="amount" placeholder="Amount"/>
                        <flux:input type="date" label="Due Date" wire:model="due_date" placeholder="Due Date"/>
                        <div>
                            <x-forms.select
                                :options="\App\Constants\Status::debtStatuses()"
                                id="status"
                                label="Status"
                                placeholder="Status"
                                wire:model="status"
                            />
                            @error('status')
                            <flux:text size="sm" color="red" class="mt-1">{{ $message }}</flux:text>
                            @enderror
                        </div>
                        <flux:textarea
                            label="Description" wire:model="description" placeholder="Description"
                        />
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
