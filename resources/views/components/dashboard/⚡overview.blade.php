<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

new class extends Component {
    public string $transactionSearch = '';

    #[Computed]
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

    #[Computed]
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

    #[Computed]
    public function netPosition(): float
    {
        return \App\Models\Transaction::query()
            ->where('business_id', auth()->user()->business_id)
            ->sum(DB::raw('amount * direction'));
    }

    #[Computed]
    public function totalCustomers(): int
    {
        return \App\Models\Customer::where('business_id', auth()->user()->business_id)->count();
    }

    #[Computed]
    public function recentTransactions()
    {
        return \App\Models\Transaction::query()
            ->with('customer')
            ->where('business_id', auth()->user()->business_id)
            ->when($this->transactionSearch, function ($query) {
                $query->whereHas('customer', function ($q) {
                    $q->where('name', 'like', '%' . $this->transactionSearch . '%');
                });
            })
            ->latest('transaction_date')
            ->latest('id')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function topDebtors()
    {
        return \App\Models\Customer::query()
            ->leftJoin('transactions', function ($join) {
                $join->on('customers.id', '=', 'transactions.customer_id')
                    ->where('transactions.business_id', auth()->user()->business_id);
            })
            ->where('customers.business_id', auth()->user()->business_id)
            ->select('customers.*')
            ->selectRaw('COALESCE(SUM(transactions.amount * transactions.direction),0) as balance')
            ->groupBy('customers.id')
            ->havingRaw('balance > 0')
            ->orderByDesc('balance')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function chartData(): array
    {
        $customers = \App\Models\Customer::query()
            ->leftJoin('transactions', function ($join) {
                $join->on('customers.id', '=', 'transactions.customer_id')
                    ->where('transactions.business_id', auth()->user()->business_id);
            })
            ->where('customers.business_id', auth()->user()->business_id)
            ->select('customers.name')
            ->selectRaw('COALESCE(SUM(transactions.amount * transactions.direction),0) as balance')
            ->groupBy('customers.id', 'customers.name')
            ->havingRaw('balance != 0')
            ->orderByRaw('ABS(balance) DESC')
            ->limit(10)
            ->get();
        $randomData = [];
        for ($i = 0; $i < $customers->count(); $i++) {
            $randomData[] = mt_rand(10000, 50000); // Generates a random number for each customer
        }
        return [
            'labels' => $customers->pluck('name'),
            'data' => $randomData,
            'colors' => $customers->map(fn($c) => $c->balance > 0 ? '#DC2626' : '#16A34A'), // Red for debt, Green for overpaid
        ];
    }

    #[Computed]
    public function monthlyStats(): \Illuminate\Support\Collection
    {
        // Monthly collections (Payments)
        return \App\Models\Transaction::query()
            ->where('business_id', auth()->user()->business_id)
            ->where('direction', -1) // Payments
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();
    }

    public function sendReminder($id)
    {
        $customer = \App\Models\Customer::find($id);
        // Placeholder for actual reminder logic (SMS/Email)
        $this->dispatch('toast', message: "Reminder sent to {$customer->name}");
    }
};
?>


<div>


    <div class="mb-4">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item wire:navigate href="{{ route('admin.dashboard') }}" separator="slash">Dashboard
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item separator="slash">Overview</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="flex justify-between items-end mb-6">
        <div>
            <flux:heading size="xl" level="1">Dashboard Overview</flux:heading>
            <flux:subheading size="md">Welcome back, {{ auth()->user()->name }}</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <flux:card>
            <div class="flex items-center gap-2 mb-2">
                <flux:icon name="users" class="text-gray-400"/>
                <div class="text-sm font-medium text-gray-500">Total Customers</div>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ $this->totalCustomers }}</div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-2 mb-2">
                <flux:icon name="banknotes" class="text-gray-400"/>
                <div class="text-sm font-medium text-gray-500">Total Outstanding</div>
            </div>
            <div class="text-2xl font-bold text-red-600">{{ number_format($this->totalOutstanding, 2) }}</div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-2 mb-2">
                <flux:icon name="credit-card" class="text-gray-400"/>
                <div class="text-sm font-medium text-gray-500">Total Overpaid</div>
            </div>
            <div class="text-2xl font-bold text-green-600">{{ number_format($this->totalOverpaid, 2) }}</div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-2 mb-2">
                <flux:icon name="scale" class="text-gray-400"/>
                <div class="text-sm font-medium text-gray-500">Net Position</div>
            </div>
            <div class="text-2xl font-bold {{ $this->netPosition >= 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ number_format($this->netPosition, 2) }}
            </div>
        </flux:card>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Monthly Collections -->
        <flux:card class="min-h-[400px]">
            <flux:heading size="lg" class="mb-4">Monthly Collections</flux:heading>
            <div wire:ignore id="monthly-collections-chart"></div>
        </flux:card>

        <!-- Customer Balances -->
        <flux:card class="min-h-[400px]">
            <flux:heading size="lg" class="mb-4">Top Balances</flux:heading>
            <div wire:ignore id="customer-balances-chart"></div>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Recent Transactions -->
        <flux:card class="xl:col-span-2 space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <flux:heading size="lg">Recent Transactions</flux:heading>
                </div>
                <div>
                    <flux:input wire:model.live.debounce="transactionSearch" placeholder="Search customer..."
                                icon="magnifying-glass" size="sm"/>
                </div>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Customer</flux:table.column>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column align="right">Amount</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->recentTransactions as $transaction)
                        <flux:table.row :key="$transaction->id">
                            <flux:table.cell class="font-medium">{{ $transaction->customer->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$transaction->direction === 1 ? 'red' : 'green'">
                                    {{ $transaction->direction === 1 ? 'Debt' : 'Payment' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell
                                class="text-gray-500">{{ $transaction->transaction_date->format('M d, Y') }}</flux:table.cell>
                            <flux:table.cell align="end" class="font-mono">
                                {{ number_format($transaction->amount, 2) }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            @if($this->recentTransactions->isEmpty())
                <div class="text-center text-gray-500 py-8">No transactions found.</div>
            @endif
        </flux:card>

        <!-- Top Debtors / Reminders -->
        <flux:card class="space-y-4">
            <div class="flex justify-between items-center">
                <flux:heading size="lg">Top Debtors</flux:heading>
                <flux:button size="sm" variant="ghost" wire:navigate
                             href="{{ route('admin.customers.index', ['filter' => 'owing']) }}">View All
                </flux:button>
            </div>

            <div class="space-y-4">
                @forelse($this->topDebtors as $debtor)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div>
                            <div class="font-medium text-gray-900">{{ $debtor->name }}</div>
                            <div class="text-sm text-red-600 font-bold">
                                {{ number_format($debtor->balance, 2) }}
                            </div>
                        </div>
                        <flux:button size="xs" icon="bell" wire:click="sendReminder({{ $debtor->id }})">Remind
                        </flux:button>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-4">No outstanding debts.</div>
                @endforelse
            </div>

            <div class="pt-4 border-t border-gray-100">
                <flux:heading size="sm" class="mb-3">Quick Actions</flux:heading>
                <div class="grid grid-cols-2 gap-2">
                    <flux:button size="sm" variant="primary" icon="plus" wire:navigate
                                 href="{{ route('admin.customers.index', ['add' => 'true']) }}">
                        Add Customer
                    </flux:button>
                    <flux:button size="sm" icon="document-text" wire:navigate
                                 href="{{ route('admin.customers.index') }}">
                        View Ledger
                    </flux:button>
                </div>
            </div>
        </flux:card>
    </div>

</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endassets
@script
<script>
    function buildMonthCollectionChart() {
        let options = {
            series: [{
                name: 'Collected',
                data: @json($this->monthlyStats->pluck('total'))
            }],
            chart: {type: 'area', height: 320, toolbar: {show: false}},
            dataLabels: {enabled: false},
            stroke: {curve: 'smooth'},
            xaxis: {categories: @json($this->monthlyStats->pluck('month'))},
            colors: ['#4F46E5'],
            fill: {
                type: 'gradient',
                gradient: {shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.9, stops: [0, 90, 100]}
            }
        };
        new ApexCharts(document.getElementById('monthly-collections-chart'), options).render();
    }


    function buildCustomerTopBalances() {
        console.log(@json($this->chartData))
        let options = {
            series: [{
                name: 'Balance',
                data: @json($this->chartData['data'])
            }],
            chart: {type: 'bar', height: 320, toolbar: {show: false}},
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: true,
                    distributed: true
                }
            },
            colors: @json($this->chartData['colors']),
            dataLabels: {enabled: true, formatter: (val) => val.toLocaleString()},
            xaxis: {categories: @json($this->chartData['labels'])},
            legend: {show: false}
        };
        new ApexCharts(document.querySelector('#customer-balances-chart'), options).render();
    }
    buildMonthCollectionChart();
    buildCustomerTopBalances();
</script>
@endscript
