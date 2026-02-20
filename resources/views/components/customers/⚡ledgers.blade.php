<?php

use App\Models\Customer;
use App\Models\Transaction;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Customer $customer;

    // Debt form fields
    public string $amount = '';
    public string $due_date = '';
    public string $debt_notes = '';

    // Payment form fields
    public string $payment_amount = '';
    public string $payment_date = '';
    public string $payment_notes = '';

    public function mount(Customer $customer): void
    {
        $this->customer = $customer->load('business');
        $this->payment_date = now()->format('Y-m-d');
    }

    public function saveDebt(): void
    {
        $this->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'debt_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $this->customer->transactions()->create([
            'business_id' => $this->customer->business_id,
            'customer_id' => $this->customer->id,
            'amount' => $this->amount,
            'type' => 'debt',
            'direction' => 1, // 1 for debt
            'due_date' => $this->due_date,
            'notes' => $this->debt_notes,
            'created_by' => auth()->id(),
        ]);

        $this->customer->refresh();

        $this->modal('debt-modal')->close();
        $this->resetDebtForm();
        session()->flash("success", "Debt recorded successfully");
    }

    public function savePayment(): void
    {
        $this->validate([
            'payment_amount' => ['required', 'numeric', 'min:1', 'max:' . $this->customer->balance],
            'payment_date' => ['required', 'date'],
            'payment_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $this->customer->transactions()->create([
            'business_id' => $this->customer->business_id,
            'customer_id' => $this->customer->id,
            'amount' => $this->payment_amount,
            'type' => 'payment',
            'direction' => -1, // -1 for payment
            'transaction_date' => $this->payment_date,
            'notes' => $this->payment_notes,
            'created_by' => auth()->id(),
        ]);

        $this->customer->refresh();

        $this->modal('payment-modal')->close();
        $this->resetPaymentForm();
        session()->flash("success", "Payment recorded successfully");
    }

    public function resetDebtForm(): void
    {
        $this->reset(['amount', 'due_date', 'debt_notes']);
        $this->resetErrorBag();
    }

    public function resetPaymentForm(): void
    {
        $this->reset(['payment_amount', 'payment_notes']);
        $this->payment_date = now()->format('Y-m-d');
        $this->resetErrorBag();
    }

    #[Livewire\Attributes\Computed]
    public function transactions()
    {
        return $this->customer->transactions()->latest()->paginate(10);
    }
};
?>


<div>
    <div class="mb-4">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item wire:navigate href="{{ route('admin.dashboard') }}" separator="slash">
                Dashboard
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item separator="slash" :href="route('admin.customers.index')" wire:navigate>
                Customers
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item separator="slash">
                Ledger
            </flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>
    <div class="relative w-full">
        <div class="flex justify-between">
            <div>
                <flux:heading size="xl" level="1">
                    {{ $customer->name }}'s Ledger
                </flux:heading>
                <flux:subheading size="md" class="mb-6">
                    Manage customer transactions and balance.
                </flux:subheading>
            </div>
            <div>
                <flux:modal.trigger name="debt-modal">
                    <flux:button type="button" variant="primary">
                        Record Debt
                    </flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="payment-modal">
                    <flux:button type="button" variant="primary" color="green">
                        Record Payment
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    </div>
    <x-app-flash/>

    <flux:card class="mb-6">
        <div class="flex justify-between items-center p-6">
            <div>
                <flux:heading size="lg" level="2">{{ $customer->name }}</flux:heading>
                <flux:text class="text-gray-500 dark:text-gray-400">{{ $customer->email }}</flux:text>
                <flux:text class="text-gray-500 dark:text-gray-400">{{ $customer->phone }}</flux:text>
            </div>
            <div class="text-right">
                <flux:subheading class="text-gray-500 dark:text-gray-400">Current Balance</flux:subheading>
                <flux:heading size="3xl" level="2"
                              @class([
                                  'text-red-600 dark:text-red-500' => $customer->balance > 0,
                                  'text-green-600 dark:text-green-500' => $customer->balance <= 0,
                              ])>
                    {{ Number::currency($customer->balance ?? 0) }}
                </flux:heading>
            </div>
        </div>
    </flux:card>

    {{-- Transactions Table --}}
    <flux:card class="space-y-6">
        <flux:table :paginate="$this->transactions">
            <flux:table.columns>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Amount</flux:table.column>
                <flux:table.column>Due Date</flux:table.column>
                <flux:table.column>Notes</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->transactions as $transaction)
                    <flux:table.row :key="$transaction->id">
                        <flux:table.cell>{{ $transaction->created_at->format("Y-m-d") }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$transaction->type === 'debt' ? 'red' : 'green'">
                                {{ ucfirst($transaction->type) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ Number::currency($transaction->amount) }}</flux:table.cell>
                        <flux:table.cell>{{ $transaction->due_date?->format("Y-m-d") }}</flux:table.cell>
                        <flux:table.cell>{{ $transaction->notes }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" icon="ellipsis-vertical"/>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center">
                            No transactions found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Record Debt Modal --}}
    <flux:modal name="debt-modal" class="md:w-2xl" @cancel="resetDebtForm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    Record Debt
                </flux:heading>
                <flux:text class="mt-2">
                    Please fill the details below to record a new debt for {{ $customer->name }}.
                </flux:text>
            </div>
            <form wire:submit="saveDebt" class="space-y-6">
                <div class="space-y-6">
                    <div>
                        <flux:input label="Amount" wire:model="amount" placeholder="Amount" type="number" step="any"/>
                        @error('amount') <flux:text size="sm" color="red" class="mt-1">{{ $message }}</flux:text> @enderror
                    </div>
                    <div>
                        <flux:input label="Due Date" wire:model="due_date" type="date"/>
                        @error('due_date') <flux:text size="sm" color="red" class="mt-1">{{ $message }}</flux:text> @enderror
                    </div>
                    <div>
                        <flux:textarea label="Notes" wire:model="debt_notes" placeholder="Additional notes"/>
                        @error('debt_notes') <flux:text size="sm" color="red" class="mt-1">{{ $message }}</flux:text> @enderror
                    </div>
                </div>
                <div class="flex gap-2 justify-end">
                    <flux:modal.close>
                        <flux:button type="button">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Save Debt</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Record Payment Modal --}}
    <flux:modal name="payment-modal" class="md:w-2xl" @cancel="resetPaymentForm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    Record Payment
                </flux:heading>
                <flux:text class="mt-2">
                    Please fill the details below to record a new payment from {{ $customer->name }}.
                </flux:text>
            </div>
            <form wire:submit="savePayment" class="space-y-6">
                <div class="space-y-6">
                    <div>
                        <flux:input label="Amount" wire:model="payment_amount" placeholder="Amount" type="number" step="any"/>
                        @error('payment_amount') <flux:text size="sm" color="red" class="mt-1">{{ $message }}</flux:text> @enderror
                    </div>
                    <div>
                        <flux:input label="Payment Date" wire:model="payment_date" type="date"/>
                        @error('payment_date') <flux:text size="sm" color="red" class="mt-1">{{ $message }}</flux:text> @enderror
                    </div>
                    <div>
                        <flux:textarea label="Notes" wire:model="payment_notes" placeholder="Additional notes"/>
                        @error('payment_notes') <flux:text size="sm" color="red" class="mt-1">{{ $message }}</flux:text> @enderror
                    </div>
                </div>
                <div class="flex gap-2 justify-end">
                    <flux:modal.close>
                        <flux:button type="button">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" color="green">Save Payment</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

</div>
