<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Credit System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 h-screen flex justify-center items-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Shopkeeper Login</h2>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('authenticate') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="login_id" class="block text-gray-700 font-bold mb-2">Email or Phone Number</label>
                <input type="text" name="login_id" id="login_id"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300" required
                    value="{{ old('login_id') }}" placeholder="e.g. admin@example.com or 0712345678">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-700 font-bold mb-2">Password</label>
                <input type="password" name="password" id="password"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"
                    required>
            </div>
            <button type="submit"
                class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition duration-200">
                Login
            </button>
        </form>

        <div class="mt-4 text-center">
            <p class="text-gray-600 text-sm">Don't have an account? <a href="{{ route('register') }}"
                    class="text-blue-600 hover:text-blue-800 font-semibold">Sign Up</a></p>
        </div>
    </div>
</body>

</html>