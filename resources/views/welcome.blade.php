<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Credit System - Digital Ledger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 antialiased">

    <!-- Navigation -->
    <nav class="bg-white shadow-sm fixed w-full z-10 transition duration-300 ease-in-out">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="#" class="text-2xl font-bold text-blue-600 flex items-center gap-2">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                    </path>
                </svg>
                CreditSystem
            </a>
            <div class="flex items-center">
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-button" class="md:hidden p-2 text-gray-600 hover:text-blue-600 transition">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16m-7 6h7">
                        </path>
                    </svg>
                </button>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features"
                        class="text-gray-600 hover:text-blue-600 font-medium transition duration-300 relative group">
                        Features
                        <span
                            class="absolute bottom-0 left-0 w-0 h-0.5 bg-blue-600 transition-all duration-300 group-hover:w-full"></span>
                    </a>
                    <a href="#how-it-works"
                        class="text-gray-600 hover:text-blue-600 font-medium transition duration-300 relative group">
                        How it Works
                        <span
                            class="absolute bottom-0 left-0 w-0 h-0.5 bg-blue-600 transition-all duration-300 group-hover:w-full"></span>
                    </a>
                    <a href="{{ route('login') }}"
                        class="text-blue-600 font-bold hover:text-blue-800 transition duration-300">Login</a>
                    <a href="{{ route('register') }}"
                        class="bg-blue-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-blue-700 hover:shadow-lg transition duration-300 transform hover:-translate-y-0.5">Sign
                        Up</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Sidebar Overlay -->
    <div id="mobile-overlay"
        class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity duration-300 opacity-0"></div>

    <!-- Mobile Sidebar Menu -->
    <div id="mobile-sidebar"
        class="fixed top-0 right-0 h-full w-64 bg-white z-50 transform translate-x-full transition-transform duration-300 ease-in-out shadow-2xl md:hidden">
        <div class="p-6">
            <div class="flex justify-between items-center mb-10">
                <span class="text-xl font-bold text-blue-600">Menu</span>
                <button id="close-menu" class="p-2 text-gray-600 hover:bg-gray-100 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div class="flex flex-col space-y-6">
                <a href="#features"
                    class="text-lg font-medium text-gray-600 hover:text-blue-600 transition close-on-click">Features</a>
                <a href="#how-it-works"
                    class="text-lg font-medium text-gray-600 hover:text-blue-600 transition close-on-click">How it
                    Works</a>
                <hr class="border-gray-100">
                <a href="{{ route('login') }}" class="text-lg font-bold text-blue-600">Login</a>
                <a href="{{ route('register') }}"
                    class="bg-blue-600 text-white px-4 py-3 rounded-xl font-bold text-center shadow-md">Sign Up</a>
            </div>
        </div>
    </div>

    <!-- Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('mobile-menu-button');
            const sidebar = document.getElementById('mobile-sidebar');
            const overlay = document.getElementById('mobile-overlay');
            const closeBtn = document.getElementById('close-menu');
            const navLinks = document.querySelectorAll('.close-on-click');

            function toggleMenu() {
                sidebar.classList.toggle('translate-x-full');
                overlay.classList.toggle('hidden');
                setTimeout(() => overlay.classList.toggle('opacity-0'), 10);
            }

            btn.addEventListener('click', toggleMenu);
            closeBtn.addEventListener('click', toggleMenu);
            overlay.addEventListener('click', toggleMenu);

            // Close menu when clicking a hashtag link
            navLinks.forEach(link => {
                link.addEventListener('click', toggleMenu);
            });
        });
    </script>

    <!-- Hero Section -->
    <header class="pt-32 pb-20 bg-gradient-to-b from-blue-50 to-white">
        <div class="container mx-auto px-6 grid md:grid-cols-2 gap-12 items-center">
            <div class="space-y-6">
                <div
                    class="inline-block bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">
                    New: Smart Trust Scoring
                </div>
                <h1 class="text-5xl md:text-6xl font-extrabold leading-tight text-gray-900">
                    Empower Your Business with <span class="text-blue-600">Digital Credit</span>
                </h1>
                <p class="text-xl text-gray-600 leading-relaxed">
                    Say goodbye to paper ledgers. Track debts, build trust, and automate credit scoring for your
                    customers instantly.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <a href="{{ route('register') }}"
                        class="px-8 py-4 bg-blue-600 text-white font-bold rounded-lg shadow-lg hover:bg-blue-700 transition flex justify-center items-center gap-2">
                        Get Started Free
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </a>
                    <a href="{{ route('login') }}"
                        class="px-8 py-4 bg-white text-gray-700 border border-gray-300 font-bold rounded-lg hover:bg-gray-50 transition flex justify-center items-center">
                        Login
                    </a>
                </div>
            </div>
            <div class="relative">
                <div class="absolute -inset-4 bg-blue-200 rounded-full opacity-30 blur-3xl animate-pulse"></div>
                <img src="{{ asset('images/hero.png') }}" alt="Financial Growth"
                    class="relative z-10 w-full drop-shadow-2xl hover:scale-105 transition duration-500">
            </div>
        </div>
    </header>

    <!-- Stats Section -->
    <section class="py-10 border-y border-gray-100 bg-white">
        <div class="container mx-auto px-6 flex flex-wrap justify-center gap-12 md:gap-24 text-center">
            <div>
                <p class="text-4xl font-bold text-gray-900">100%</p>
                <p class="text-gray-500 uppercase text-sm tracking-widest mt-1">Digital Ledger</p>
            </div>
            <div>
                <p class="text-4xl font-bold text-blue-600">+10pts</p>
                <p class="text-gray-500 uppercase text-sm tracking-widest mt-1">For Early Payment</p>
            </div>
            <div>
                <p class="text-4xl font-bold text-gray-900">24/7</p>
                <p class="text-gray-500 uppercase text-sm tracking-widest mt-1">Portal Access</p>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Why Choose Credit System?</h2>
                <p class="text-gray-600">Built for modern shopkeepers and smart customers. We make credit management
                    simple, transparent, and fair.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div
                    class="bg-white p-8 rounded-2xl shadow-sm hover:shadow-xl transition duration-300 border border-gray-100">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Smart Trust Score</h3>
                    <p class="text-gray-600">Customers earn +10 points for paying early and lose -5 points for being
                        late. Build reputation automatically.</p>
                </div>

                <!-- Feature 2 -->
                <div
                    class="bg-white p-8 rounded-2xl shadow-sm hover:shadow-xl transition duration-300 border border-gray-100">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Dynamic Credit Limits</h3>
                    <p class="text-gray-600">Set limits that grow with trust. Protect your business from bad debt while
                        rewarding loyal customers.</p>
                </div>

                <!-- Feature 3 -->
                <div
                    class="bg-white p-8 rounded-2xl shadow-sm hover:shadow-xl transition duration-300 border border-gray-100">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Transparent Portal</h3>
                    <p class="text-gray-600">Customers can log in via phone or email to see their debts, limits, and
                        score anytime. No more confusion.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-6 text-center">
            <h4 class="text-2xl font-bold mb-4">Credit System</h4>
            <div class="flex justify-center space-x-6 mb-8">
                <a href="#" class="text-gray-400 hover:text-white transition">Privacy</a>
                <a href="#" class="text-gray-400 hover:text-white transition">Terms</a>
                <a href="#" class="text-gray-400 hover:text-white transition">Contact</a>
            </div>
            <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} Digital Credit Ledger. All rights reserved.</p>
        </div>
    </footer>

</body>

</html>