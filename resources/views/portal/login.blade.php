@extends('layouts.app')

@section('content')
    <div class="max-w-md mx-auto mt-10">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="bg-blue-600 py-4 px-6">
                <h2 class="text-xl font-bold text-white text-center">Customer Portal</h2>
            </div>

            <div class="p-6">
                <p class="text-gray-600 text-center mb-6">Enter your registered phone number to view your statement.</p>

                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('portal.authenticate') }}" method="POST">
                    @csrf
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                            Phone Number
                        </label>
                        <input
                            class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="phone" name="phone" type="text" placeholder="e.g. 0712345678" required>
                    </div>

                    <button
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150"
                        type="submit">
                        Access Dashboard
                    </button>
                </form>
            </div>
            <div class="bg-gray-50 px-6 py-4 text-center">
                <p class="text-sm text-gray-500">Don't have an account? Visit the shopkeeper.</p>
            </div>
        </div>
    </div>
@endsection