<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration - Credit System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 h-screen flex justify-center items-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Customer Registration</h2>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li class="text-sm">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="name" class="block text-gray-700 font-bold mb-2">Full Name</label>
                <input type="text" name="name" id="name"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300" required
                    value="{{ old('name') }}">
            </div>

            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-bold mb-2">Email Address</label>
                <input type="email" name="email" id="email"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300" required
                    value="{{ old('email') }}">
            </div>

            <div class="mb-4">
                <label for="phone" class="block text-gray-700 font-bold mb-2">Phone Number</label>
                <input type="text" name="phone" id="phone"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300" required
                    value="{{ old('phone') }}" placeholder="e.g. 0712345678">
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700 font-bold mb-2">Password</label>
                <input type="password" name="password" id="password"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"
                    required>
                <p class="text-xs text-gray-500 mt-1">Min 8 characters, mixed case, and numbers</p>
            </div>

            <div class="mb-6">
                <label for="password_confirmation" class="block text-gray-700 font-bold mb-2">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"
                    required>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition duration-200">
                Register
            </button>
        </form>

        <div class="mt-4 text-center">
            <p class="text-gray-600 text-sm">Already have an account? <a href="{{ route('login') }}"
                    class="text-blue-600 hover:text-blue-800 font-semibold">Login</a></p>
        </div>
    </div>
</body>

</html>