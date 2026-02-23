<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use LaravelIdea\Helper\App\Models\_IH_Business_C;
use LaravelIdea\Helper\App\Models\_IH_Customer_C;
use Livewire\Component;

new class extends Component {
    use \Livewire\WithPagination;

//     fields
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $notes = '';
    public ?int $business_id = null;
    public ?int $editingId = null;

    public ?\App\Models\Customer $deletingRecord = null;
    #[\Livewire\Attributes\Url(except: '')]
    public string $sortBy = 'created_at';
    #[\Livewire\Attributes\Url(except: '')]
    public string $sortDirection = 'desc';
    #[\Livewire\Attributes\Url(except: '')]
    public string $search = '';
    public bool $showModal = false;
    public string $statusFilter = 'All';
    public Collection $businesses;

    public array $financialSummary = [];

    public array $filters = [
        'all', 'owing', 'settled', 'overpaid'
    ];
    #[\Livewire\Attributes\Url(except: 'all')]
    public string $filter = 'all';

    public function mount(): void
    {
        $this->businesses = $this->getBusinesses();
        if (request('add')) {
            $this->modal('add-modal')->show();
        }
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
        $existingRecord = \App\Models\Customer::findOrFail($id);
        $this->editingId = $existingRecord->id;
        $this->name = $existingRecord->name;
        $this->phone = $existingRecord->phone;
        $this->email = $existingRecord->email;
        $this->notes = $existingRecord->notes;
        $this->business_id = $existingRecord->business_id;
        $this->modal('add-modal')->show();
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:20',
                // must be either 10 digits starting with 0, or 12 digits starting with 25
//                'regex:/^(0\d{9}|25\d{11})$/',
                Rule::unique('users', 'phone')->ignore($this->editingId),
            ],
            'notes' => ['nullable', 'string', 'max:255'],
            'business_id' => [
                // If the user is super admin, business_id is required and must exist in businesses table
                Rule::requiredIf(fn() => auth()->user()->is_super_admin && auth()->user()->business_id == null),
            ]
        ]);
        $isNewUser = !$this->editingId;
        // Normalize phone number to always start with 25
        $phone = $this->phone;

// If it starts with 0, replace with 25
        if (preg_match('/^0/', $phone)) {
            $phone = '25' . ltrim($phone, '0');
        }

// If it already starts with 25, leave it as is
        if (!preg_match('/^25/', $phone)) {
            $phone = '25' . $phone;
        }

        $user = \App\Models\Customer::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'email' => $this->email,
                'business_id' => $this->business_id ?? auth()->user()->business_id,
                'created_by' => auth()->id(),
                'phone' => $phone,
                'notes' => $this->notes
            ]
        );


        $this->modal('add-modal')->close();
        $this->resetFormData();
        $this->resetPage();
        session()->flash("success", "Record saved successfully");
    }

    public function confirmDeletion($id): void
    {
        $this->deletingRecord = \App\Models\Customer::findOrFail($id);
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


    public function getBusinesses(): Collection
    {
        return \App\Models\Business::query()->latest()->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[\Livewire\Attributes\Computed]
    public function customers(): LengthAwarePaginator
    {
        return Customer::query()
            ->with('business')
            ->leftJoin('transactions', 'customers.id', '=', 'transactions.customer_id')
            ->select('customers.*')
            ->selectRaw('COALESCE(SUM(transactions.amount * transactions.direction),0) as balance_amount')
            ->where('customers.business_id', auth()->user()->business_id)
            ->groupBy('customers.id')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('customers.name', 'like', '%' . $this->search . '%')
                        ->orWhere('customers.email', 'like', '%' . $this->search . '%')
                        ->orWhere('customers.phone', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filter !== 'all', function ($query) {
                if ($this->filter === 'owing') {
                    $query->havingRaw('balance_amount > 0');
                } elseif ($this->filter === 'settled') {
                    $query->havingRaw('balance_amount = 0');
                } elseif ($this->filter === 'overpaid') {
                    $query->havingRaw('balance_amount < 0');
                }
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    public function resetFormData(): void
    {
        $this->reset(['name', 'phone', 'email', 'notes', 'business_id', 'editingId']);
    }


    #[\Livewire\Attributes\Computed]
    public function totalOutstanding(): float
    {
        $query = \App\Models\Customer::query()
            ->leftJoin('transactions', function ($join) {
                $join->on('customers.id', '=', 'transactions.customer_id')
                    ->where('transactions.business_id', auth()->user()->business_id);
            })
            ->where('customers.business_id', auth()->user()->business_id)
            ->selectRaw('COALESCE(SUM(transactions.amount * transactions.direction),0) as balance')
            ->groupBy('customers.id')
            ->havingRaw('balance > 0');

        return DB::query()->fromSub($query, 'sub')->sum('balance');
    }

    #[\Livewire\Attributes\Computed]
    public function totalOverpaid(): float
    {
        $query = \App\Models\Customer::query()
            ->leftJoin('transactions', function ($join) {
                $join->on('customers.id', '=', 'transactions.customer_id')
                    ->where('transactions.business_id', auth()->user()->business_id);
            })
            ->where('customers.business_id', auth()->user()->business_id)
            ->selectRaw('COALESCE(SUM(transactions.amount * transactions.direction),0) as balance')
            ->groupBy('customers.id')
            ->havingRaw('balance < 0');

        return abs(DB::query()->fromSub($query, 'sub')->sum('balance'));
    }

    #[\Livewire\Attributes\Computed]
    public function netPosition(): float
    {
        return \App\Models\Transaction::query()
            ->where('business_id', auth()->user()->business_id)
            ->selectRaw('COALESCE(SUM(amount * direction),0) as net')
            ->value('net');
    }

};
?>

<div>
    <div class="mb-4">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item wire:navigate href="{{ route('admin.dashboard') }}" separator="slash">
                Dashboard
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item separator="slash">Customers</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>
    <div class="relative w-full">
        <div class="flex justify-between">
            <div>
                <flux:heading size="xl" level="1">
                    Customers List
                </flux:heading>
                <flux:subheading size="md" class="mb-6">
                    Manage business customers here.
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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        <!-- Outstanding -->
        <flux:card>
            <div class="text-sm text-gray-500">Total Outstanding</div>
            <div class="text-2xl font-bold text-red-600">
                {{ number_format($this->totalOutstanding, 2) }}
            </div>
        </flux:card>

        <!-- Overpaid -->
        <flux:card>
            <div class="text-sm text-gray-500">Total Overpaid</div>
            <div class="text-2xl font-bold text-green-600">
                {{ number_format($this->totalOverpaid, 2) }}
            </div>
        </flux:card>

        <!-- Net Position -->
        <flux:card>
            <div class="text-sm text-gray-500">Net Position</div>
            <div
                class="text-2xl font-bold {{ $this->netPosition >= 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ number_format($this->netPosition, 2) }}
            </div>
        </flux:card>

    </div>

    <div>
        {{-- TABLE --}}
        <flux:card class="space-y-6">
            <div class="flex items-center gap-4 justify-between">
                <div>
                    <flux:dropdown>
                        <flux:button icon:trailing="chevron-down">Filter</flux:button>
                        <flux:menu>
                            <flux:menu.radio.group wire:model.live="filter">
                                @foreach ($filters as $item)
                                    <flux:menu.radio :value="$item">{{ ucfirst($item) }}</flux:menu.radio>
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
                <flux:table :paginate="$this->customers">
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
                        <flux:table.column>Balance</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection"
                                           wire:click="sort('email')">
                            Email
                        </flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'business_id'" :direction="$sortDirection"
                                           wire:click="sort('business_id')">
                            Business
                        </flux:table.column>
                        <flux:table.column>
                            Actions
                        </flux:table.column>

                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->customers as $order)
                            <flux:table.row :key="$order->id">
                                <flux:table.cell
                                    class="flex items-center gap-3">{{ $order->created_at->format("Y-m-d h:i:s") }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $order->name }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">
                                    {{ $order->phone }}
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">
                                    <flux:badge size="sm" variant="primary" rounded
                                                color="{{$order->balance_amount>0?'red':'green'}}">
                                        {{ number_format($order->balance_amount) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">
                                    {{ $order->email }}
                                </flux:table.cell>
                                <flux:table.cell
                                    class="whitespace-nowrap">{{ $order->business->name??'N/A' }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:dropdown>
                                        <flux:button icon:trailing="ellipsis-vertical" size="xs"></flux:button>
                                        <flux:menu>
                                            <flux:menu.item wire:navigate
                                                            href="{{ route('admin.customers.ledgers', encodeId($order->id)) }}">
                                                Ledgers
                                            </flux:menu.item>
                                            <flux:menu.item wire:click="edit({{$order->id}})">
                                                Edit
                                            </flux:menu.item>
                                            <flux:menu.item variant="danger"
                                                            wire:click="confirmDeletion({{$order->id}})">
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
                        {{ $editingId ? 'Edit Customer' : 'Create Customer' }}
                    </flux:heading>
                    <flux:text class="mt-2">
                        Please fill the details below.
                    </flux:text>
                </div>
                <form wire:submit="save" class="space-y-6">
                    <div class="space-y-6">
                        @if(auth()->user()->is_super_admin && auth()->user()->business_id == null)
                            <div>
                                <x-forms.select
                                    :options="$this->businesses"
                                    id="business_id"
                                    label="Business"
                                    placeholder="Choose a business"
                                    wire:model="business_id"
                                />
                                @error('business_id')
                                <flux:text size="sm" color="red" class="mt-1">{{ $message }}</flux:text>
                                @enderror
                            </div>
                        @else
                            <input type="hidden" wire:model="business_id" value="{{auth()->user()->business_id}}"/>
                        @endif

                        <flux:input label="Name" wire:model="name" placeholder="Name"/>
                        <flux:input type="email" label="Email" wire:model="email" placeholder="Email Address"/>
                        <flux:input type="tel" label="Phone" wire:model="phone" placeholder="Phone Number"/>
                        <flux:textarea
                            label="Notes" wire:model="notes" placeholder="Additional notes"
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
