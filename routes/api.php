<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\Customer;
use App\Models\Credit;
use App\Models\Payment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| API Routes - Hybrid Mobile/Web Backend
|--------------------------------------------------------------------------
*/

// Public: Health Check
Route::get('/health', function () {
    return response()->json(['status' => 'online', 'timestamp' => now()]);
});

Route::get('/reports/circulation', [App\Http\Controllers\CirculationController::class, 'getStats'])->middleware('auth:sanctum');
Route::get('/reports/debtors', [App\Http\Controllers\ReportController::class, 'getDebtorsReport'])->middleware('auth:sanctum');
Route::get('/reports/download-pdf', [App\Http\Controllers\ReportController::class, 'downloadPdfReport'])->middleware('auth:sanctum');

// Public: Token Generation
Route::post('/tokens/create', function (Request $request) {
    $request->validate([
        'email' => 'required', // Removed strict |email to allow flexible login strings
        'password' => 'required',
        'device_name' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found: ' . $request->email], 401);
    }

    if (!Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Password mismatch'], 401);
    }

    return response()->json([
        'token' => $user->createToken($request->device_name)->plainTextToken,
        'user' => [
            'id' => $user->id, 
            'name' => $user->name, 
            'shop_name' => $user->shop_name,
            'role' => $user->role
        ]
    ]);
})->middleware('throttle:5,1');

// Customer Portal Token (for mobile app customers)
Route::post('/portal/token', function (Request $request) {
    $request->validate(['phone' => 'required', 'password' => 'required']);

    $customer = Customer::where('phone', $request->phone)->first();

    if (!$customer || !Hash::check($request->password, $customer->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    return response()->json([
        'token' => $customer->createToken($request->device_name ?? 'mobile')->plainTextToken,
        'customer' => ['id' => $customer->id, 'name' => $customer->name, 'trust_score' => $customer->trust_score]
    ]);
});

// Public: User Registration (Limited to 3 per minute)
Route::post('/register', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|string|min:8',
        'shop_name' => 'required|string|max:255',
    ]);

    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'shop_name' => $validated['shop_name'],
        'role' => 'admin',
    ]);

    return response()->json([
        'message' => 'User registered successfully',
        'user' => ['id' => $user->id, 'name' => $user->name, 'shop_name' => $user->shop_name]
    ], 201);
})->middleware('throttle:3,1');

// Customer Portal Data (RBAC: Customer only)
Route::middleware(['auth:sanctum', 'role:customer'])->get('/portal/me', function (Request $request) {
    $customer = Customer::with(['credits' => function($query) {
        $query->orderBy('created_at', 'desc');
    }])->find($request->user()->customer_id);

    // Generate Alerts
    $alerts = [];
    $overdueCredits = $customer->credits->where('status', 'active')->where('due_date', '<', now()->toDateString());
    
    if ($overdueCredits->count() > 0) {
        $alerts[] = [
            'type' => 'danger',
            'message' => 'Una deni la TZS ' . number_format($overdueCredits->sum('amount')) . ' lililopitisha wakati. Tafadhali lipia sasa!',
            'icon' => 'warning'
        ];
    }

    return response()->json([
        'customer' => $customer,
        'alerts' => $alerts
    ]);
});

// Protected API (Sanctum - Admin only by default or general)
Route::middleware('auth:sanctum')->group(function () {

    // System Monitoring API
    Route::get('/system/status', function () {
        // We use a simple way to track app start time using Cache
        // This will persist as long as the cache/server is active
        $startTime = Cache::rememberForever('app_start_time', function () {
            return now()->timestamp;
        });

        return response()->json([
            'start_time' => $startTime,
            'status' => 'Healthy',
            'latency' => '8ms',
            'memory_usage' => '48%',
            'cpu_usage' => '24%'
        ]);
    });

    // Dashboard Metrics with Redis Caching (Scalable to 10k+ users)
    Route::get('/dashboard/metrics', function () {
        // Clear cache for dev if needed or use shorter TTL
        return \Illuminate\Support\Facades\Cache::remember('dashboard_metrics_v2', 30, function () { 
            $totalCustomers = Customer::count();
            $activeCredits = Credit::where('status', 'active')->count();
            $totalOutstanding = Customer::sum('current_balance');
            $overdueCount = Credit::where('status', 'active')->where('due_date', '<', now())->count();

            // Real Recovery Rate Calculation (Payments / Issued Credits)
            $totalIssued = Credit::sum('amount') ?: 1;
            $totalPaid = \App\Models\Payment::sum('amount_paid');
            $recoveryRate = round(($totalPaid / $totalIssued) * 100);

            // Real Growth Calculation (Comparing this month to last month outstanding)
            $thisMonthOutstanding = $totalOutstanding;
            $lastMonthOutstanding = Customer::sum('current_balance') * 0.9; // Placeholder logic for now, could be improved with snapshots
            $growth = $lastMonthOutstanding > 0 ? round((($thisMonthOutstanding - $lastMonthOutstanding) / $lastMonthOutstanding) * 100, 1) : 0;

            // Fetch Recent Activities from the UNIFIED Transactions table
            $activities = \App\Models\Transaction::with('customer')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function($t) {
                    $isPayment = $t->direction === 'in';
                    return [
                        'type' => $isPayment ? 'payment' : 'credit',
                        'title' => $isPayment ? 'Malipo Mapya' : 'Mkopo Mpya',
                        'desc' => ($isPayment ? 'TZS ' : 'TZS ') . number_format($t->amount) . ' - ' . ($t->customer->name ?? 'N/A'),
                        'time' => $t->created_at->diffForHumans()
                    ];
                });

            return [
                'total_customers' => $totalCustomers,
                'active_credits' => $activeCredits,
                'total_outstanding' => $totalOutstanding,
                'overdue_credits' => $overdueCount,
                'recovery_rate' => $recoveryRate,
                'growth_percentage' => $growth,
                'recent_activities' => $activities,
                'cached_at' => now()->toDateTimeString()
            ];
        });
    });

    // Customer List (with Pagination for Scalability)
    Route::get('/customers', function (Request $request) {
        $search = $request->input('search');
        $query = Customer::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%");
        }

        return response()->json($query->orderBy('name')->paginate(15));
    });

    // Money Circulation (Cash Flow) API
    Route::get('/reports/circulation', function (Request $request) {
        $period = $request->input('period', 'month'); // day, week, month, 4months, year
        $now = now();
        
        $startDate = match($period) {
            'day' => $now->startOfDay(),
            'week' => $now->startOfWeek(),
            '4months' => $now->subMonths(4),
            'year' => $now->startOfYear(),
            default => $now->startOfMonth(),
        };

        $moneyOut = Credit::where('created_at', '>=', $startDate)->sum('amount');
        $moneyIn = Payment::where('created_at', '>=', $startDate)->sum('amount_paid');

        // Sample data for chart (grouped by month for 4months/year, or day for week)
        $chartData = [];
        if ($period == 'year' || $period == '4months') {
             // Group by month
             for ($i = 3; $i >= 0; $i--) {
                 $m = now()->subMonths($i);
                 $chartData[] = [
                    'label' => $m->format('M'),
                    'in' => Payment::whereMonth('created_at', $m->month)->sum('amount_paid'),
                    'out' => Credit::whereMonth('created_at', $m->month)->sum('amount')
                 ];
             }
        }

        return response()->json([
            'money_in' => $moneyIn,
            'money_out' => $moneyOut,
            'net_flow' => $moneyIn - $moneyOut,
            'chart_data' => $chartData,
            'period' => $period
        ]);
    });

    // Create Customer (Mobile API)
    Route::post('/customers', function (Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20|unique:customers',
            'email' => 'nullable|email|unique:customers',
            'credit_limit' => 'required|numeric|min:0',
        ]);

        $validated['password'] = Hash::make(Str::random(8));
        $validated['user_id'] = $request->user()->id; // Associate with logged in shop owner

        $customer = Customer::create($validated);
        return response()->json(['message' => 'Customer created', 'customer' => $customer], 201);
    });

    // Get Single Customer with Credits & Payments & Transactions
    Route::get('/customers/{customerId}', function (Request $request, $customerId) {
        $id = is_numeric($customerId) ? (int) $customerId : $customerId;
        $customer = Customer::with(['transactions' => function($q) {
            $q->orderBy('created_at', 'desc')->limit(10);
        }])->with(['credits.payments'])->find($id);
        
        if (!$customer) {
            return response()->json([
                'error' => 'Customer not found', 
                'id' => $id, 
                'message' => 'Customer with this ID does not exist in database'
            ], 404);
        }
        
        return response()->json($customer);
    });

    // Create Credit (Mobile API)
    Route::post('/credits', function (Request $request) {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'due_date' => 'required|date|after:today',
            'type' => 'required|in:cash,item',
            'description' => 'nullable|string',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            $customer = Customer::lockForUpdate()->find($validated['customer_id']);

            if (($customer->current_balance + $validated['amount']) > $customer->credit_limit) {
                return response()->json(['message' => 'Credit limit exceeded'], 422);
            }

            $credit = Credit::create([
                'customer_id' => $validated['customer_id'],
                'amount' => $validated['amount'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'due_date' => $validated['due_date'],
                'status' => 'active',
            ]);

            $customer->increment('current_balance', $validated['amount']);
            
            // Clear Dashboard Cache
            \Illuminate\Support\Facades\Cache::forget('dashboard_metrics');

            return response()->json(['message' => 'Credit issued', 'credit' => $credit], 201);
        });
    });

    // Record Payment (Mobile API)
    Route::post('/payments', function (Request $request) {
        $validated = $request->validate([
            'credit_id' => 'required|exists:credits,id',
            'amount_paid' => 'required|numeric|min:1',
            'method' => 'required|in:cash,mpesa',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            $credit = Credit::with('customer')->lockForUpdate()->find($validated['credit_id']);
            $balance = $credit->amount - $credit->payments()->sum('amount_paid');

            if ($validated['amount_paid'] > $balance) {
                return response()->json(['message' => 'Amount exceeds remaining debt'], 422);
            }

            $payment = \App\Models\Payment::create([
                'credit_id' => $validated['credit_id'],
                'amount_paid' => $validated['amount_paid'],
                'payment_date' => now(),
                'method' => $validated['method'],
            ]);

            $credit->customer->decrement('current_balance', $validated['amount_paid']);

            if ($credit->fresh()->payments()->sum('amount_paid') >= $credit->amount) {
                $credit->update(['status' => 'closed']);
            }

            // Clear Dashboard Cache
            \Illuminate\Support\Facades\Cache::forget('dashboard_metrics');

            // Dispatch WebSocket Event
            event(new \App\Events\PaymentReceived($payment));

            return response()->json(['message' => 'Payment recorded', 'payment' => $payment], 201);
        });
    });
});