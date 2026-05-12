<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#3B82F6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="/manifest.json">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    @stack('scripts')
</head>

<body class="bg-gray-100 text-gray-800">
    <div class="min-h-screen flex flex-col">
        <nav class="bg-blue-600 text-white shadow-lg">
            <div class="container mx-auto px-4 py-4 flex justify-between items-center">
                <a href="{{ url('/') }}" class="text-xl font-bold">Credit System</a>

                <div class="flex items-center">
                    <!-- Mobile Menu Button -->
                    <button id="mobile-menu-button"
                        class="md:hidden p-2 hover:bg-blue-700 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16m-7 6h7"></path>
                        </svg>
                    </button>

                    <!-- Desktop Navigation (Hidden on Mobile) -->
                    <div class="hidden md:flex items-center space-x-4">
                        @auth
                            {{-- Shopkeeper Navigation --}}
                            <a href="{{ route('dashboard') }}"
                                class="hover:text-blue-200 {{ request()->routeIs('dashboard') ? 'underline' : '' }}">Dashboard</a>
                            <a href="{{ route('customers.index') }}"
                                class="hover:text-blue-200 {{ request()->routeIs('customers.index') ? 'underline' : '' }}">Customers</a>
                            <a href="{{ route('customers.create') }}"
                                class="bg-white text-blue-600 px-3 py-1 rounded font-bold hover:bg-gray-100">+ Add
                                Customer</a>

                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="hover:text-blue-200 ml-4">Logout</button>
                            </form>
                        @else
                            @if(session()->has('customer_id'))
                                <a href="{{ route('portal.dashboard') }}" class="hover:text-blue-200 font-bold">My Dashboard</a>
                                <form action="{{ route('portal.logout') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="hover:text-blue-200 ml-4">Logout</button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="hover:text-blue-200">Shopkeeper Login</a>
                                <a href="{{ route('portal.login') }}"
                                    class="bg-blue-800 hover:bg-blue-900 px-3 py-1 rounded font-bold">Customer Portal</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile Sidebar Overlay (Hidden by default) -->
        <div id="mobile-overlay"
            class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity duration-300 opacity-0"></div>

        <!-- Mobile Sidebar Menu -->
        <div id="mobile-sidebar"
            class="fixed top-0 right-0 h-full w-64 bg-blue-700 text-white z-50 transform translate-x-full transition-transform duration-300 ease-in-out shadow-2xl md:hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-10">
                    <span class="text-xl font-bold">Menu</span>
                    <button id="close-menu" class="p-2 hover:bg-blue-800 rounded-full">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="flex flex-col space-y-6">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="flex items-center space-x-3 text-lg font-medium hover:bg-blue-800 p-2 rounded-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                                </path>
                            </svg>
                            <span>Dashboard</span>
                        </a>
                        <a href="{{ route('customers.index') }}"
                            class="flex items-center space-x-3 text-lg font-medium hover:bg-blue-800 p-2 rounded-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                            <span>Customers</span>
                        </a>
                        <hr class="border-blue-500">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center space-x-3 text-lg font-medium text-red-100 hover:bg-red-800 p-2 rounded-lg transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                    </path>
                                </svg>
                                <span>Logout</span>
                            </button>
                        </form>
                    @else
                        @if(session()->has('customer_id'))
                            <a href="{{ route('portal.dashboard') }}"
                                class="flex items-center space-x-3 text-lg font-medium hover:bg-blue-800 p-2 rounded-lg transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>Portal Dashboard</span>
                            </a>
                            <form action="{{ route('portal.logout') }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="w-full flex items-center space-x-3 text-lg font-medium text-red-100 hover:bg-red-800 p-2 rounded-lg transition">
                                    <span>Logout</span>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}"
                                class="text-lg font-medium hover:bg-blue-800 p-2 rounded-lg">Shopkeeper Login</a>
                            <a href="{{ route('portal.login') }}"
                                class="bg-white text-blue-600 px-4 py-2 rounded-lg font-bold text-center">Customer Portal</a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>

        <!-- Toggle Script -->
        <script>
            const btn = document.getElementById('mobile-menu-button');
            const sidebar = document.getElementById('mobile-sidebar');
            const overlay = document.getElementById('mobile-overlay');
            const closeBtn = document.getElementById('close-menu');

            function toggleMenu() {
                sidebar.classList.toggle('translate-x-full');
                overlay.classList.toggle('hidden');
                setTimeout(() => overlay.classList.toggle('opacity-0'), 10);
            }

            btn.addEventListener('click', toggleMenu);
            closeBtn.addEventListener('click', toggleMenu);
            overlay.addEventListener('click', toggleMenu);
        </script>

        <main class="flex-grow container mx-auto px-4 py-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                    role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="bg-gray-800 text-gray-400 py-6">
            <div class="container mx-auto px-4 text-center">
                &copy; {{ date('Y') }} Credit System.
            </div>
        </footer>
    </div>
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/service-worker.js')
                .then(reg => console.log('Service Worker registered:', reg.scope))
                .catch(err => console.log('Service Worker failed:', err));
        });
    }
</script>

</body>

</html>