<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Transactions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans+Code:ital,wght@0,300..800;1,300..800&family=Google+Sans+Flex:opsz,wght@6..144,1..1000&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: "Google Sans Code", monospace;

            font-optical-sizing: auto;
        }
    </style>
</head>
<body class="bg-gray-100">
<div class="max-w-4xl mx-auto p-8 bg-white text-xs">
    <header class="flex justify-between items-center pb-8 border-b">
        <div>
            <a href="{{ url('/') }}">
                <img src="{{ asset('assets/logos/logo.png') }}" alt="{{ $business->name }}" class="h-32">
            </a>
            <h1 class="text-2xl font-bold">{{ $business->name ?? 'Your Business' }}</h1>
            <p class="text-gray-500">{{ $business->address ?? '' }}</p>
        </div>
        <div class="text-right">
            <h2 class="text-2xl font-bold uppercase text-gray-800">Transaction Ledger</h2>
            <p class="text-gray-500">Date: {{ now()->format('d M, Y') }}</p>
        </div>
    </header>

    <section class="mt-8">
        <div class="grid grid-cols-2 gap-8">
            <div>
                <h3 class="text-lg font-semibold text-gray-700">Bill To:</h3>
                <p class="text-gray-600">{{ $customer->name ?? 'John Doe' }}</p>
                <p class="text-gray-600">{{ $customer->address ?? '456 Customer Ave, City, State' }}</p>
                <p class="text-gray-600">{{ $customer->email ?? 'customer@example.com' }}</p>
                <p class="text-gray-600">{{ $customer->phone ?? '+1234567890' }}</p>
            </div>
        </div>
    </section>

    <section class="mt-8">
        <table class="w-full text-left">
            <thead>
            <tr class="bg-gray-200 text-gray-600 uppercase text-xs leading-normal">
                <th class="py-3 px-6">Date</th>
                <th class="py-3 px-6">Description</th>
                <th class="py-3 px-6 text-center">Type</th>
                <th class="py-3 px-6 text-right">Amount</th>
            </tr>
            </thead>
            <tbody class="text-gray-600 text-xs font-light">
            @forelse ($transactions ?? [] as $transaction)
                <tr class="border-b border-gray-200 hover:bg-gray-100">
                    <td class="py-2 px-4 text-nowrap">{{ $transaction->created_at->format('d M, Y') }}</td>
                    <td class="py-2 px-4">{{ $transaction->description }}</td>
                    <td class="py-2 px-4 text-center">
                        <span
                            class="font-semibold {{ $transaction->direction ==1 ? 'text-red-500' : 'text-green-500' }}">
                            {{ ucfirst($transaction->direction==1?'Debt':'Payment') }}
                        </span>
                    </td>
                    <td class="py-2 px-4 text-right font-semibold">{{ number_format($transaction->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center py-6">No transactions found for this customer.</td>
                </tr>
            @endforelse
            </tbody>
            <tfoot>
            <tr class="font-semibold text-gray-700">
                <td colspan="3" class="text-right py-3 px-6">Total Debits:</td>
                <td class="text-right py-3 px-6 text-red-500">{{ number_format(($transactions ?? [])->where('direction', 1)->sum('amount'), 2) }}</td>
            </tr>
            <tr class="font-semibold text-gray-700">
                <td colspan="3" class="text-right py-3 px-6">Total Credits:</td>
                <td class="text-right py-3 px-6 text-green-500">{{ number_format(($transactions ?? [])->where('direction', -1)->sum('amount'), 2) }}</td>
            </tr>
            <tr class="font-bold text-gray-800 text-lg">
                <td colspan="3" class="text-right py-3 px-6">Balance:</td>
                <td class="text-right py-3 px-6">
                    {{ number_format(
                        ($transactions ?? [])->where('direction', -1)->sum('amount') -
                        ($transactions ?? [])->where('direction', 1)->sum('amount'),
                    2) }}
                </td>
            </tr>
            </tfoot>
        </table>
    </section>

    <footer class="text-center mt-12 pt-8 border-t">
        <p class="text-gray-500">Thank you for your business!</p>
        <p class="text-gray-500">{{ $business->name ?? 'Your Business' }} &copy; {{ date('Y') }}</p>
    </footer>
</div>
</body>
</html>
