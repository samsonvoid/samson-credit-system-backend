@extends('layouts.app')

@section('content')
    <div class="max-w-md mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="py-4 px-6 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-800">Add New Customer</h2>
        </div>
        <div class="p-6">
            <form action="{{ route('customers.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                        Full Name
                    </label>
                    <input
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        id="name" name="name" type="text" placeholder="e.g. Mama John" required>
                    @error('name') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                        Phone Number
                    </label>
                    <input
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        id="phone" name="phone" type="text" placeholder="e.g. 0712345678" required>
                    @error('phone') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="credit_limit">
                        Credit Limit (TZS)
                    </label>
                    <input
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        id="credit_limit" name="credit_limit" type="number" placeholder="e.g. 100000" required>
                    @error('credit_limit') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center justify-between">
                    <button
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                        type="submit">
                        Save Customer
                    </button>
                    <a href="{{ route('customers.index') }}"
                        class="inline-block align-baseline font-bold text-sm text-blue-600 hover:text-blue-800">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection