@extends('layouts.app')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Welcome, {{ $customer->name }}</h1>
        <form action="{{ route('portal.logout') }}" method="POST">
            @csrf
            <button type="submit" class="text-red-600 hover:text-red-800 underline">Logout</button>
        </form>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
            <p class="text-gray-500 text-sm font-bold uppercase">Trust Score</p>
            <p class="text-3xl font-bold text-blue-600">{{ $customer->trust_score }}</p>
            <p class="text-xs text-gray-400 mt-1">Make payments on time to increase this.</p>
        </div>

        <div
            class="bg-white p-6 rounded-lg shadow border-l-4 {{ $customer->current_balance > 0 ? 'border-red-500' : 'border-green-500' }}">
            <p class="text-gray-500 text-sm font-bold uppercase">Current Debt</p>
            <p class="text-3xl font-bold {{ $customer->current_balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ number_format($customer->current_balance) }} TZS
            </p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-gray-500">
            <p class="text-gray-500 text-sm font-bold uppercase">Credit Limit</p>
            <p class="text-3xl font-bold text-gray-800">{{ number_format($customer->credit_limit) }} TZS</p>
        </div>
    </div>

    <!-- Motivational Tip -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-8 rounded-r shadow-sm flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                    clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm text-blue-700">
                <strong>Tip:</strong> Your credit limit may be increased based on your repayment history. Keep paying on
                time to earn a higher limit!
            </p>
        </div>
    </div>

    <!-- Active Debts -->
    <div class="bg-white shadow rounded-lg overflow-hidden mb-8">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="font-bold text-gray-800">Active Debts</h3>
        </div>
        <div class="p-6">
            @forelse($customer->credits->where('status', 'active') as $credit)
                <div
                    class="flex flex-col md:flex-row justify-between items-start md:items-center border-b last:border-0 pb-4 mb-4 last:pb-0 last:mb-0">
                    <div>
                        <span class="font-bold text-lg block">{{ number_format($credit->amount) }} TZS</span>
                        <span class="text-sm text-gray-500">{{ ucfirst($credit->type) }} Credit &bull; Due: <span
                                class="{{ $credit->due_date < now() ? 'text-red-600 font-bold' : '' }}">{{ $credit->due_date->format('M d, Y') }}</span></span>
                    </div>
                    <div class="mt-2 md:mt-0 text-right">
                        <span class="block text-sm font-bold text-gray-700">Remaining: {{ number_format($credit->balance) }}
                            TZS</span>
                        <span class="text-xs text-gray-400">Please pay at the shop</span>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 italic text-center">You have no active debts. Great job!</p>
            @endforelse
        </div>
    </div>

    <!-- Recent History -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="font-bold text-gray-800">History</h3>
        </div>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="px-6 py-3 text-left">Date</th>
                    <th class="px-6 py-3 text-left">Details</th>
                    <th class="px-6 py-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($customer->credits as $credit)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $credit->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            Credit Issued ({{ ucfirst($credit->type) }})
                            <br>
                            <span class="text-xs text-gray-500">Status: {{ ucfirst($credit->status) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-red-600">
                            +{{ number_format($credit->amount) }}
                        </td>
                    </tr>
                    @foreach($credit->payments as $payment)
                        <tr class="bg-green-50">
                            <td class="px-6 py-4 whitespace-nowrap pl-10">{{ $payment->payment_date->format('M d, Y') }}</td>
                            <td class="px-6 py-4">Payment ({{ ucfirst($payment->method) }})</td>
                            <td class="px-6 py-4 text-right font-bold text-green-600">
                                -{{ number_format($payment->amount_paid) }}
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
@endsection