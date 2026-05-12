@extends('layouts.app')

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
    <div
        class="mb-4 flex flex-col md:flex-row justify-between items-start md:items-end gap-4 bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <form action="{{ route('dashboard') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                    class="border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                    class="border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            <div class="flex gap-2">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">
                    Filter Analytics
                </button>
                @if($startDate || $endDate)
                    <a href="{{ route('dashboard') }}"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-6 rounded shadow transition">
                        Clear
                    </a>
                @endif
            </div>
        </form>
        <div class="text-right">
            <button onclick="window.print()"
                class="bg-gray-800 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded shadow flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                    </path>
                </svg>
                Print Report
            </button>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

        <!-- Repayment Rate -->
        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-10 w-10 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Repayment Rate</dt>
                        <dd>
                            <div class="text-lg font-bold text-gray-900">{{ number_format($repaymentRate, 1) }}%</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Portfolio at Risk -->
        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-red-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Portfolio at Risk</dt>
                        <dd>
                            <div class="text-lg font-bold text-gray-900">{{ number_format($par, 0) }} TZS</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Total Issued (Month) -->
        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-10 w-10 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Issued
                            {{ $startDate || $endDate ? '(Filtered Period)' : '(This Month)' }}</dt>
                        <dd>
                            <div class="text-lg font-bold text-gray-900">{{ number_format($totalIssued, 0) }} TZS</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Total Collected (Month) -->
        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-10 w-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Collected
                            {{ $startDate || $endDate ? '(Filtered Period)' : '(This Month)' }}</dt>
                        <dd>
                            <div class="text-lg font-bold text-gray-900">{{ number_format($totalCollected, 0) }} TZS</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Total Outstanding Card -->
        <div class="bg-white shadow rounded-lg p-6 flex flex-col justify-center border-t-4 border-gray-800">
            <h3 class="text-lg font-bold mb-4 text-gray-800">Total Outstanding Debt</h3>
            <p class="text-4xl font-bold text-gray-900">{{ number_format($totalOutstanding, 0) }} TZS</p>
            <p class="text-sm text-gray-500 mt-2 italic">Across {{ $totalCredits }} total credit records.</p>
            <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-100">
                <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Note</p>
                <p class="text-sm text-gray-600 mt-1">This represents the combined current balance of all active customers
                    in the system.</p>
            </div>
        </div>

        <!-- Trust Score Distribution Chart -->
        <div class="lg:col-span-2 bg-white shadow rounded-lg p-6 border-t-4 border-indigo-500">
            <h3 class="text-lg font-bold mb-4 text-gray-800">Customer Trust Score Distribution</h3>
            <div class="relative h-64">
                <canvas id="trustScoreChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('trustScoreChart').getContext('2d');
            const data = @json($trustScoreData);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(data),
                    datasets: [{
                        label: 'Number of Customers',
                        data: Object.values(data),
                        backgroundColor: 'rgba(79, 70, 229, 0.6)',
                        borderColor: 'rgb(79, 70, 229)',
                        borderWidth: 1,
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    </script>

    <style>
        @media print {
            button {
                display: none;
            }

            nav {
                display: none;
            }

            footer {
                display: none;
            }

            body {
                background: white;
            }

            .shadow {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
@endsection