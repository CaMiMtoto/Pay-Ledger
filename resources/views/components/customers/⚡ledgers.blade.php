<?php

use App\Models\Customer;
use App\Models\Debt;
use App\Models\PaymentAllocation;
use App\Models\Transaction;
use App\Services\DebtService;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use function Spatie\LaravelPdf\Support\pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

new class extends Component {
    use WithPagination;

    #[\Livewire\Attributes\Url(except: '')]
    public ?string $startDate = null;
    #[\Livewire\Attributes\Url(except: '')]
    public ?string $endDate = null;
    #[\Livewire\Attributes\Url(except: '')]
    public string $search = '';

    public Customer $customer;

    // Debt form fields
    public string $amount = '';
    public string $transaction_date = '';
    public string $due_date = '';
    public string $description = '';

    // Payment form fields
    public string $payment_amount = '';
    public string $payment_date = '';
    public string $payment_description = '';

    public function mount(Customer $customer): void
    {
        $this->customer = $customer->load('business');
        $this->transaction_date = now()->format('Y-m-d');
        $this->payment_date = now()->format('Y-m-d');
    }

    public function saveDebt(): void
    {
        $data = $this->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'transaction_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        DB::beginTransaction();

        $debt = (new DebtService())->save($this->customer, $this->amount, $this->due_date, $this->description);
        $transaction = (new TransactionService())->save($this->customer, $this->amount, 1, $this->transaction_date, $this->description);
        DB::commit();
        $this->customer->refresh();

        $this->modal('debt-modal')->close();
        $this->resetDebtForm();
        session()->flash("success", "Debt recorded successfully");
    }

    public function savePayment(): void
    {
        $this->validate([
            'payment_amount' => ['required', 'numeric', 'min:1'],
            'payment_date' => ['required', 'date'],
            'payment_description' => ['nullable', 'string', 'max:255'],
        ]);
        DB::beginTransaction();
        $customerId = $this->customer->id;
        $paymentService = new PaymentService();
        $payment = $paymentService->save($this->customer, $this->payment_amount, $this->payment_date, $this->payment_description);
        (new TransactionService())->save($this->customer, $this->payment_amount, -1, $this->payment_date, $this->payment_description);
        $paymentService->updateDebts($payment, $customerId);
        DB::commit();
        $this->customer->refresh();
        $this->modal('payment-modal')->close();
        $this->resetPaymentForm();
        session()->flash("success", "Payment recorded successfully");
    }

    public function resetDebtForm(): void
    {
        $this->reset(['amount', 'description', 'due_date', 'transaction_date']);
        $this->transaction_date = now()->format('Y-m-d');
        $this->resetErrorBag();
    }

    public function resetPaymentForm(): void
    {
        $this->reset(['payment_amount', 'payment_description', 'payment_date']);
        $this->payment_date = now()->format('Y-m-d');
        $this->resetErrorBag();
    }

    #[Livewire\Attributes\Computed]
    public function transactions()
    {
        return $this->getTransactionBuilder()
            ->paginate(10);
    }

    public function exportPdf(): StreamedResponse
    {
        $transactions = $this->getTransactionBuilder()->get();
        $customer = $this->customer;
        $business = $customer->business;
        $fileName = \Illuminate\Support\Str::of($customer->name)->slug('_') . '_transactions.pdf';
        $pdf = pdf()
            ->view('pdf.customer_transactions', compact('customer', 'business', 'transactions'))
            ->name($fileName);

        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->toResponse(request())->getContent();
            },
            $pdf->downloadName ?: $fileName,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * @return Customer|Builder|\Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Support\HigherOrderWhenProxy|mixed
     */
    public function getTransactionBuilder(): mixed
    {
        return $this->customer
            ->transactions()
            ->when($this->startDate, fn(Builder $query) => $query->whereDate('transaction_date', '>=', $this->startDate))
            ->when($this->endDate, fn(Builder $query) => $query->whereDate('transaction_date', '<=', $this->endDate))
            ->when($this->search, fn(Builder $query) => $query->where('description', 'like', '%' . $this->search . '%'))
            ->latest();
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
        <div class="flex flex-col md:flex-row justify-between">
            <div>
                <flux:heading size="xl" level="1">
                    {{ $customer->name }}'s Ledger
                </flux:heading>
                <flux:subheading size="md" class="mb-6">
                    Manage customer transactions and balance.
                </flux:subheading>
            </div>
            <div class="flex gap-2">
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
                    {{ number_format($customer->balance ?? 0) }}
                </flux:heading>
            </div>
        </div>
    </flux:card>

    {{-- Transactions Table --}}
    <flux:card class="space-y-6">
        {{--        filters--}}
        <div class="flex flex-col md:flex-row justify-between items-center gap-2 w-full">
            <div class="flex flex-col md:flex-row gap-2 w-full">
                <flux:input type="date" class="w-full" wire:model.live="startDate"/>
                <flux:input type="date" class="w-full" wire:model.live="endDate"/>
            </div>
            <div class="flex gap-2 items-start w-full">
                <flux:input type="text" wire:model.live.debounce="search" icon="magnifying-glass"/>
                <flux:button type="button" color="red" wire:click="exportPdf" variant="primary" icon="download">PDF
                </flux:button>
            </div>
        </div>

        <flux:table :paginate="$this->transactions">
            <flux:table.columns>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Amount</flux:table.column>
                <flux:table.column>Description</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->transactions as $transaction)
                    <flux:table.row :key="$transaction->id">
                        <flux:table.cell>{{ $transaction->transaction_date->format("Y-m-d") }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$transaction->direction === 1 ? 'red' : 'green'" size="sm">
                                {{ $transaction->direction === 1 ? 'Debt' : 'Payment' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ Number::currency($transaction->amount) }}</flux:table.cell>
                        <flux:table.cell>{{ $transaction->description }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" icon="ellipsis-vertical"/>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center">
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
                    </div>
                    <div>
                        <flux:input label="Date" wire:model="transaction_date" type="date"/>
                    </div>

                    <div>
                        <flux:input label="Reason" wire:model="description" placeholder="Description"/>
                    </div>
                    <div>
                        <flux:input label="Due Date" wire:model="due_date" type="date"/>
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
                        <flux:input label="Amount" wire:model="payment_amount" placeholder="Amount" type="number"
                                    step="any"/>
                    </div>
                    <div>
                        <flux:input label="Date" wire:model="payment_date" type="date"/>
                    </div>
                    <div>
                        <flux:input label="Description" wire:model="payment_description" placeholder="Description"/>
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
