<?php

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

new class extends Component {

    use \Livewire\WithPagination;

    #[\Livewire\Attributes\Url]
    public $search = '';


    public function mount(): void
    {

    }

    #[\Livewire\Attributes\Computed]
    public function customers(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Customer::where('business_id', Auth::user()->business_id)
            ->where('name', 'like', '%' . $this->search . '%')
            ->paginate();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
};
?>

<div>
    <input type="text" placeholder="Search customer..." wire:model="search" class="border p-2 mb-4 w-full">

    <table class="w-full table-auto border">
        <thead>
        <tr class="bg-gray-100">
            <th class="px-4 py-2">Name</th>
            <th class="px-4 py-2">Phone</th>
            <th class="px-4 py-2">Notes</th>
        </tr>
        </thead>
        <tbody>
        @forelse($this->customers as $customer)
            <tr>
                <td class="border px-4 py-2">{{ $customer->name }}</td>
                <td class="border px-4 py-2">{{ $customer->phone }}</td>
                <td class="border px-4 py-2">{{ $customer->notes }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center py-4">No customers found</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

