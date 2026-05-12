@extends('layouts.app')

@section('content')
    <div
        class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-4 rounded-lg shadow-sm">
        <a href="{{ route('customers.index') }}" class="text-blue-600 hover:text-blue-800 font-bold flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Back to List
        </a>

        <form action="{{ route('customers.statement', $customer) }}" method="GET" class="flex flex-wrap items-end gap-2">
            <div>
                <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Start Date</label>
                <input type="date" name="start_date" class="border rounded px-2 py-1 text-xs">
            </div>
            <div>
                <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">End Date</label>
                <input type="date" name="end_date" class="border rounded px-2 py-1 text-xs">
            </div>
            <button type="submit"
                class="bg-gray-800 text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-gray-900 transition flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Download Statement (PDF)
            </button>
        </form>
    </div>

    <!-- Error Messages Area -->
    @if ($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            <strong class="font-bold">Whoops!</strong>
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Customer Profile Card -->
        <div class="bg-white shadow rounded-lg p-6 h-fit">
            <h2 class="text-xl font-bold mb-4">{{ $customer->name }}</h2>
            <div class="text-sm text-gray-600 mb-2">Phone: <span class="text-gray-900">{{ $customer->phone }}</span></div>
            <div class="text-sm text-gray-600 mb-2">Joined: <span
                    class="text-gray-900">{{ $customer->created_at->format('M d, Y') }}</span></div>
            <hr class="my-4">
            <div class="mb-4">
                <p class="text-xs font-bold text-gray-500 uppercase">Trust Score</p>
                <p class="text-2xl font-bold text-blue-600">{{ $customer->trust_score }}</p>
            </div>
            <div class="mb-4">
                <p class="text-xs font-bold text-gray-500 uppercase">Credit Limit</p>
                <p class="text-lg font-bold">{{ number_format($customer->credit_limit, 2) }} TZS</p>
            </div>
            <div class="mb-6">
                <div class="flex justify-between items-center mb-1">
                    <p class="text-xs font-bold text-gray-500 uppercase">Current Balance</p>
                    @if($customer->current_balance > 0 && auth()->user()->isAdmin())
                        <form action="{{ route('customers.reminder', $customer) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded font-bold hover:bg-orange-200 transition flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                                    </path>
                                </svg>
                                Remind
                            </button>
                        </form>
                    @endif
                </div>
                <p class="text-3xl font-bold {{ $customer->current_balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ number_format($customer->current_balance, 2) }} TZS
                </p>
            </div>

            <!-- Issue Credit Form (Simple toggle or inline) -->
            <div class="bg-gray-50 p-4 rounded border border-gray-200">
                <h3 class="font-bold text-gray-700 mb-2">Issue New Credit</h3>
                <form action="{{ route('credits.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">

                    <div class="mb-2">
                        <label class="block text-xs font-bold text-gray-600">Amount (TZS)</label>
                        <input type="number" name="amount" class="w-full border rounded px-2 py-1 text-sm"
                            placeholder="e.g. 5000" required>
                    </div>

                    <div class="mb-2">
                        <label class="block text-xs font-bold text-gray-600">Type</label>
                        <select name="type" class="w-full border rounded px-2 py-1 text-sm" required>
                            <option value="item">Goods/Item</option>
                            <option value="cash">Cash Loan</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="block text-xs font-bold text-gray-600">Notes / Items Description</label>
                        <input type="text" name="description" class="w-full border rounded px-2 py-1 text-sm"
                            placeholder="e.g. 3 bags of rice, 1L oil">
                    </div>

                    <div class="mb-3">
                        <label class="block text-xs font-bold text-gray-600">Due Date</label>
                        <input type="date" name="due_date" class="w-full border rounded px-2 py-1 text-sm" required
                            min="{{ date('Y-m-d') }}">
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-2 rounded">
                        Issue Credit
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Column: Active Credits & History -->
        <div class="md:col-span-2 space-y-6">

            <!-- Active Credits Section -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4 text-gray-800 border-b pb-2">Active Credits</h3>

                @if($customer->credits->where('status', 'active')->isEmpty())
                    <p class="text-gray-500 italic">No active credits.</p>
                @else
                    <div class="space-y-4">
                        @foreach($customer->credits->where('status', 'active') as $credit)
                            <div
                                class="border rounded-lg p-4 bg-gray-50 flex flex-col sm:flex-row justify-between items-start sm:items-center">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-lg text-gray-800">{{ number_format($credit->amount, 0) }}
                                            TZS</span>
                                        <span
                                            class="px-2 py-0.5 rounded text-xs {{ $credit->type == 'cash' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ ucfirst($credit->type) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        Due: <span
                                            class="font-semibold {{ $credit->due_date < now() ? 'text-red-600' : '' }}">{{ $credit->due_date->format('M d, Y') }}</span>
                                    </p>
                                    @if($credit->description)
                                        <p class="text-xs text-gray-500 italic mt-1 bg-yellow-50 px-2 py-1 rounded inline-block">Notes:
                                            {{ $credit->description }}</p>
                                    @endif
                                    <p class="text-sm text-gray-500">Remaining: {{ number_format($credit->balance, 0) }} TZS</p>
                                </div>

                                <!-- Inline Repayment Form -->
                                <div class="mt-3 sm:mt-0 w-full sm:w-auto">
                                    <form action="{{ route('credits.repay', $credit) }}" method="POST"
                                        class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
                                        @csrf
                                        <input type="number" name="amount_paid"
                                            class="border rounded px-2 py-1 text-sm w-full sm:w-32" placeholder="Amount"
                                            max="{{ $credit->balance }}" required>
                                        <select name="method" class="border rounded px-2 py-1 text-sm">
                                            <option value="cash">Cash</option>
                                            <option value="mpesa">M-Pesa</option>
                                        </select>
                                        <button type="submit"
                                            class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-1 px-3 rounded">
                                            Pay
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- History (Closed Credits) -->
            <div class="bg-white shadow rounded-lg p-6 opacity-75">
                <h3 class="text-lg font-bold mb-4 text-gray-800 border-b pb-2">Completed History</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="pb-2">Date Created</th>
                                <th class="pb-2">Amount</th>
                                <th class="pb-2">Type</th>
                                <th class="pb-2">Items/Notes</th>
                                <th class="pb-2">Paid</th>
                                <th class="pb-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->credits->where('status', 'closed') as $credit)
                                <tr class="border-b last:border-0 hover:bg-gray-50">
                                    <td class="py-2">{{ $credit->created_at->format('M d, Y') }}</td>
                                    <td class="py-2 font-medium">{{ number_format($credit->amount) }}</td>
                                    <td class="py-2">{{ ucfirst($credit->type) }}</td>
                                    <td class="py-2 text-gray-500 italic">{{ $credit->description ?: '-' }}</td>
                                    <td class="py-2 text-green-600">{{ number_format($credit->payments->sum('amount_paid')) }}
                                    </td>
                                    <td class="py-2"><span
                                            class="px-2 py-1 rounded bg-gray-200 text-gray-600 text-xs">Closed</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-center text-gray-500">No history available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection