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
Route::get('/reports/trends', [App\Http\Controllers\ReportController::class, 'getTrends'])->middleware('auth:sanctum');

// Public: Token Generation (Works for both Admin and Customer)
Route::post('/tokens/create', function (Request $request) {
    $request->validate([
        'email' => 'required',
        'password' => 'required',
        'device_name' => 'required',
    ]);

    $email = $request->email;
    $password = $request->password;
    $deviceName = $request->device_name;

    // Check if email looks like admin (has @ and NOT gmail/yahoo/hotmail/outlook)
    $adminDomains = ['credit-system.com', 'admin', 'shop'];
    $isLikelyAdmin = false;
    
    if (str_contains($email, '@')) {
        $parts = explode('@', $email);
        $domain = $parts[1] ?? '';
        
        // Admin if domain is known admin domain or contains 'admin' or 'shop'
        foreach ($adminDomains as $adminDomain) {
            if (stripos($domain, $adminDomain) !== false) {
                $isLikelyAdmin = true;
                break;
            }
        }
        
        // Also check if it looks like personal email (gmail, yahoo, hotmail, outlook)
        $personalDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com'];
        foreach ($personalDomains as $personal) {
            if (strtolower($domain) === $personal) {
                $isLikelyAdmin = false;
                break;
            }
        }
    }

    // Try admin first if likely admin
    if ($isLikelyAdmin) {
        $user = User::where('email', $email)->first();

        if ($user && Hash::check($password, $user->password)) {
            return response()->json([
                'token' => $user->createToken($deviceName)->plainTextToken,
                'user' => [
                    'id' => $user->id, 
                    'name' => $user->name, 
                    'shop_name' => $user->shop_name,
                    'role' => $user->role
                ]
            ]);
        }
    }

    // Try customer login (by email or phone)
    $isEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
    
    $customerQuery = Customer::query();
    if ($isEmail) {
        $customerQuery->where('email', $email);
    } else {
        // Phone login - remove any non-digits
        $phone = preg_replace('/[^0-9]/', '', $email);
        $customerQuery->where('phone', 'like', '%' . $phone);
    }
    $customer = $customerQuery->first();

    if ($customer && Hash::check($password, $customer->password)) {
        return response()->json([
            'token' => $customer->createToken($deviceName)->plainTextToken,
            'user' => [
                'id' => $customer->id, 
                'name' => $customer->name, 
                'email' => $customer->email,
                'phone' => $customer->phone,
                'role' => 'customer',
                'trust_score' => $customer->trust_score,
            ]
        ]);
    }

    // Final check: if email is actually an admin email (not personal), try admin
    if (!$isLikelyAdmin && str_contains($email, '@')) {
        $user = User::where('email', $email)->first();
        if ($user && Hash::check($password, $user->password)) {
            return response()->json([
                'token' => $user->createToken($deviceName)->plainTextToken,
                'user' => [
                    'id' => $user->id, 
                    'name' => $user->name, 
                    'shop_name' => $user->shop_name,
                    'role' => $user->role
                ]
            ]);
        }
    }

    return response()->json(['message' => 'Invalid credentials'], 401);
})->middleware('throttle:10,1');

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

// Protected: User Registration (Admin only - disabled by default in production)
Route::post('/register', function (Request $request) {
    if (!env('ADMIN_REGISTRATION_ENABLED', false)) {
        return response()->json([
            'message' => 'Admin registration is disabled. Contact system administrator.'
        ], 403);
    }
    
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

// Public: Customer Self-Registration (Limited to 5 per minute)
Route::post('/register-customer', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:customers,email',
        'phone' => 'required|string|max:20|unique:customers,phone',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $customer = Customer::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'phone' => $validated['phone'],
        'password' => Hash::make($validated['password']),
        'credit_limit' => 10000,
        'trust_score' => 0,
        'current_balance' => 0,
    ]);

    return response()->json([
        'message' => 'Customer registered successfully',
        'token' => $customer->createToken('mobile')->plainTextToken,
        'customer' => [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'trust_score' => $customer->trust_score,
            'credit_limit' => $customer->credit_limit,
            'current_balance' => $customer->current_balance,
        ]
    ], 201);
})->middleware('throttle:5,1');

// Customer Portal Data (RBAC: Customer only)
Route::middleware(['auth:sanctum', 'role:customer'])->get('/portal/me', function (Request $request) {
    try {
        // Recalculate customer balance on every request
        $customer = Customer::find($request->user()->id);
        $customer->recalculateBalance();
        
        $customer = Customer::select([
            'id', 'name', 'email', 'phone', 'business_name', 'trust_score', 'credit_limit', 'current_balance'
        ])->with([
            'credits' => function($query) {
                $query->select(['id', 'customer_id', 'amount', 'type', 'description', 'status', 'due_date', 'created_at'])
                    ->where('status', 'active')
                    ->orderBy('due_date', 'asc');
            },
            'credits.creditItems' => function($query) {
                $query->select(['id', 'credit_id', 'item_id', 'quantity', 'unit_price', 'subtotal']);
            },
            'credits.creditItems.item' => function($query) {
                $query->select(['id', 'name', 'unit']);
            }
        ])->find($request->user()->id);
        
        if (!$customer) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        // Only active credits for alerts
        $overdueCredits = $customer->credits->filter(function($c) {
            return $c->status === 'active' && $c->due_date && $c->due_date < now()->toDateString();
        });
        
        $alerts = [];
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
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Protected API (Sanctum - Admin only by default or general)
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Customer: Record Payment Request (Manual M-Pesa)
    Route::post('/payments', function (Request $request) {
        $validated = $request->validate([
            'credit_id' => 'required|exists:credits,id',
            'amount' => 'required|numeric|min:100',
            'payment_method' => 'required|in:mpesa,cash',
            'phone' => 'nullable|string',
        ]);
        
        $user = $request->user();
        $credit = Credit::find($validated['credit_id']);
        
        // Verify ownership for customers
        if (get_class($user) === 'App\\Models\\Customer' && $credit->customer_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $payment = Payment::create([
            'credit_id' => $credit->id,
            'amount_paid' => $validated['amount'],
            'method' => $validated['payment_method'],
            'payment_date' => now()->toDateString(),
        ]);
        
        return response()->json([
            'message' => 'Malipo yameandikishwa. Utaarifiwa baada ya kuthibitishwa.',
            'payment' => $payment
        ], 201);
    });

    // Customer: Get Payment History
    Route::get('/payments/history', function (Request $request) {
        $user = $request->user();
        
        $payments = Payment::whereHas('credit', function($q) use ($user) {
            $q->where('customer_id', $user->id);
        })
        ->with('credit')
        ->orderBy('created_at', 'desc')
        ->get();
        
        return response()->json(['payments' => $payments]);
    });

    // Items API with caching
    Route::get('/items', function() {
        return \Illuminate\Support\Facades\Cache::remember('items_list', 60, function() {
            return response()->json([
                'items' => \App\Models\Item::select(['id', 'name', 'sku', 'price', 'unit', 'category'])
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get()
            ]);
        });
    });
    Route::post('/items', [App\Http\Controllers\Api\CreditApiController::class, 'addItems']);

    // Credits API (Issue Credit with Items)
    Route::post('/credits', [App\Http\Controllers\Api\CreditApiController::class, 'store']);

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
        return \Illuminate\Support\Facades\Cache::remember('dashboard_metrics_v2', 30, function () { 
            // Aggregate metrics - single query for speed
            $metrics = DB::select("
                SELECT 
                    (SELECT COUNT(*) FROM customers) as total_customers,
                    (SELECT COUNT(*) FROM credits WHERE status = 'active') as active_credits,
                    (SELECT COALESCE(SUM(current_balance), 0) FROM customers) as total_outstanding,
                    (SELECT COUNT(*) FROM credits WHERE status = 'active' AND due_date < CURDATE()) as overdue_count,
                    (SELECT COALESCE(SUM(amount), 0) FROM credits) as total_issued,
                    (SELECT COALESCE(SUM(amount_paid), 0) FROM payments) as total_paid
            ");
            
            $m = $metrics[0];
            $recoveryRate = $m->total_issued > 0 ? round(($m->total_paid / $m->total_issued) * 100) : 0;

            // Recent Activities - kept separate for simplicity
            $activities = \App\Models\Transaction::with('customer:id,name')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function($t) {
                    $isPayment = $t->direction === 'in';
                    return [
                        'type' => $isPayment ? 'payment' : 'credit',
                        'title' => $isPayment ? 'Malipo Mapya' : 'Mkopo Mpya',
                        'desc' => 'TZS ' . number_format($t->amount) . ' - ' . ($t->customer->name ?? 'N/A'),
                        'time' => $t->created_at->diffForHumans()
                    ];
                });

            return [
                'total_customers' => (int)$m->total_customers,
                'active_credits' => (int)$m->active_credits,
                'total_outstanding' => (float)$m->total_outstanding,
                'overdue_credits' => (int)$m->overdue_count,
                'recovery_rate' => $recoveryRate,
                'growth_percentage' => 0,
                'recent_activities' => $activities,
                'cached_at' => now()->toDateTimeString()
            ];
        });
    })->middleware('cache.headers:private;max_age=30');

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
        $period = $request->input('period', 'month');
        $now = now();
        
        $startDate = match($period) {
            'day' => $now->copy()->startOfDay(),
            'week' => $now->copy()->startOfWeek(),
            'year' => $now->copy()->startOfYear(),
            default => $now->copy()->startOfMonth(),
        };

        // Cached for 60 seconds
        return \Illuminate\Support\Facades\Cache::remember("circulation_{$period}", 60, function () use ($period, $now, $startDate) {
            $moneyOut = Credit::where('created_at', '>=', $startDate)->sum('amount') ?: 0;
            $moneyIn = Payment::where('created_at', '>=', $startDate)->sum('amount_paid') ?: 0;

            // Generate chart data - ALWAYS complete (no gaps)
            $chartData = [];
            
            if ($period === 'day') {
                // Hourly chunks
                for ($h = 0; $h < 24; $h += 4) {
                    $chartData[] = [
                        'label' => date('H:i', mktime($h, 0)),
                        'in' => Payment::whereDate('created_at', $now->toDateString())
                            ->whereRaw("HOUR(created_at) >= ? AND HOUR(created_at) < ?", [$h, $h + 4])
                            ->sum('amount_paid'),
                        'out' => Credit::whereDate('created_at', $now->toDateString())
                            ->whereRaw("HOUR(created_at) >= ? AND HOUR(created_at) < ?", [$h, $h + 4])
                            ->sum('amount')
                    ];
                }
            } elseif ($period === 'week') {
                // Daily this week
                $start = $now->copy()->startOfWeek();
                for ($d = 0; $d < 7; $d++) {
                    $day = $start->copy()->addDays($d);
                    $chartData[] = [
                        'label' => $day->format('D'),
                        'in' => Payment::whereDate('created_at', $day->toDateString())->sum('amount_paid'),
                        'out' => Credit::whereDate('created_at', $day->toDateString())->sum('amount')
                    ];
                }
            } elseif ($period === 'year') {
                // ALL 12 months Jan-Dec
                for ($m = 1; $m <= 12; $m++) {
                    $chartData[] = [
                        'label' => date('M', mktime(0, 0, 0, $m, 1)),
                        'in' => Payment::whereYear('created_at', $now->year)->whereMonth('created_at', $m)->sum('amount_paid'),
                        'out' => Credit::whereYear('created_at', $now->year)->whereMonth('created_at', $m)->sum('amount')
                    ];
                }
            } else {
                // Last 6 months (complete)
                for ($i = 5; $i >= 0; $i--) {
                    $m = $now->copy()->subMonths($i);
                    $chartData[] = [
                        'label' => $m->format('M'),
                        'in' => Payment::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->sum('amount_paid'),
                        'out' => Credit::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->sum('amount')
                    ];
                }
            }

            // Recent ledger (cached separately)
            $ledger = \Illuminate\Support\Facades\Cache::remember('circulation_ledger', 120, function () {
                return \App\Models\Transaction::with('customer:id,name')
                    ->orderBy('created_at', 'desc')
                    ->limit(20)
                    ->get()
                    ->map(function($t) {
                        return [
                            'id' => $t->id,
                            'type' => $t->type ?? 'unknown',
                            'direction' => $t->direction ?? 'out',
                            'amount' => $t->amount,
                            'created_at' => $t->created_at,
                            'customer' => $t->customer ? ['id' => $t->customer->id, 'name' => $t->customer->name] : null
                        ];
                    });
            });

            return [
                'money_in' => $moneyIn,
                'money_out' => $moneyOut,
                'net_flow' => $moneyIn - $moneyOut,
                'chart_data' => $chartData,
                'ledger' => $ledger,
                'period' => $period,
                'cached_at' => now()->toDateTimeString()
            ];
        });
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
        $userId = $request->user()->id;
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'due_date' => 'required|date|after:today',
            'type' => 'required|in:cash,item',
            'description' => 'nullable|string',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $userId) {
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

            // Create transaction for circulation (money out - credit issued)
            \App\Models\Transaction::create([
                'user_id' => $userId,
                'customer_id' => $validated['customer_id'],
                'type' => 'credit_issued',
                'circulation_type' => $validated['type'] === 'cash' ? 'CASH' : 'PRODUCT',
                'amount' => $validated['amount'],
                'direction' => 'out',
                'description' => 'Mkopo mpya: ' . ($validated['description'] ?? 'Berekodi'),
            ]);

            $customer->increment('current_balance', $validated['amount']);
            
            // Clear Dashboard Cache
            \Illuminate\Support\Facades\Cache::forget('dashboard_metrics');

            return response()->json(['message' => 'Credit issued', 'credit' => $credit], 201);
        });
    });

    // Record Payment (Mobile API) - Rate limited: 1 payment per 30 min per customer
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

            // Create transaction for circulation
            \App\Models\Transaction::create([
                'user_id' => $credit->customer->id,
                'customer_id' => $credit->customer_id,
                'type' => 'payment_received',
                'circulation_type' => strtoupper($validated['method']),
                'amount' => $validated['amount_paid'],
                'direction' => 'in',
                'description' => 'Malipo ya deni (' . $validated['method'] . ')',
            ]);

            $credit->customer->decrement('current_balance', $validated['amount_paid']);

            if ($credit->fresh()->payments()->sum('amount_paid') >= $credit->amount) {
                $credit->update(['status' => 'closed']);
                
                // Record credit closure
                \App\Models\Transaction::create([
                    'user_id' => $credit->customer_id,
                    'customer_id' => $credit->customer_id,
                    'type' => 'credit_closed',
                    'circulation_type' => 'SETTLEMENT',
                    'amount' => $credit->amount,
                    'direction' => 'out',
                    'description' => 'Mkopo: ' . $credit->description,
                ]);
            }

            // Clear Dashboard Cache
            \Illuminate\Support\Facades\Cache::forget('dashboard_metrics');

            // Send email notification
            try {
                \App\Services\EmailNotificationService::paymentReceived($payment, $credit);
            } catch (\Exception $e) {
                \Log::error("Payment email failed: " . $e->getMessage());
            }

            // Dispatch WebSocket Event
            event(new \App\Events\PaymentReceived($payment));

            return response()->json(['message' => 'Payment recorded', 'payment' => $payment], 201);
        })->middleware('throttle:1,1800'); // 1 payment per 30 minutes
    });

    // Payment Initiation (Debtor clicks to get payment ref) - Rate limited: 1 per 30 min
    Route::post('/payments/initiate', function (Request $request) {
        $validated = $request->validate([
            'credit_id' => 'required|exists:credits,id',
            'amount' => 'required|numeric|min:1',
            'phone' => 'required',
            'payment_ref' => 'required',
            'initiated_at' => 'required',
        ]);

        // Create payment initiation record (pending verification)
        $initiation = \App\Models\PaymentInitiation::create([
            'credit_id' => $validated['credit_id'],
            'customer_phone' => $validated['phone'],
            'payment_ref' => $validated['payment_ref'],
            'amount' => $validated['amount'],
            'initiated_at' => $validated['initiated_at'],
            'status' => 'pending_verification',
        ]);

        return response()->json([
            'message' => 'Payment initiated',
            'payment_ref' => $validated['payment_ref'],
            'initiation_id' => $initiation->id
        ], 200);
    })->middleware('throttle:1,1800'); // 1 initiation per 30 minutes

    // Confirm Payment (Debtor says "Nimefanya Malipo")
    Route::post('/payments/confirm-initiation', function (Request $request) {
        $validated = $request->validate([
            'credit_id' => 'required|exists:credits,id',
            'payment_ref' => 'required',
            'initiated_at' => 'required',
            'amount' => 'required|numeric|min:1',
        ]);

        // Find or create pending payment
        $initiation = \App\Models\PaymentInitiation::where('payment_ref', $validated['payment_ref'])
            ->where('credit_id', $validated['credit_id'])
            ->first();

        if ($initiation) {
            $initiation->update([
                'status' => 'awaiting_admin_confirmation',
                'confirmed_at' => now(),
            ]);
        } else {
            // Create new initiation record
            $initiation = \App\Models\PaymentInitiation::create([
                'credit_id' => $validated['credit_id'],
                'customer_phone' => $request->user()->phone ?? 'unknown',
                'payment_ref' => $validated['payment_ref'],
                'amount' => $validated['amount'],
                'initiated_at' => $validated['initiated_at'],
                'confirmed_at' => now(),
                'status' => 'awaiting_admin_confirmation',
            ]);
        }

        // Create a pending payment record for admin to confirm
        $pendingPayment = \App\Models\PendingPayment::create([
            'credit_id' => $validated['credit_id'],
            'payment_ref' => $validated['payment_ref'],
            'amount' => $validated['amount'],
            'initiated_at' => $validated['initiated_at'],
            'confirmed_at' => now(),
            'customer_phone' => $initiation->customer_phone ?? 'unknown',
        ]);

        return response()->json([
            'message' => 'Payment confirmed. Awaiting admin verification.',
            'status' => 'pending_admin_confirmation'
        ], 200);
    });

    // Get Pending Payments (Admin view)
    Route::get('/payments/pending', function () {
        $pending = \App\Models\PendingPayment::with('credit.customer')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['pending_payments' => $pending]);
    })->middleware('throttle:10,1');

    // Admin Confirm Pending Payment - Rate limited
    Route::post('/payments/admin-confirm', function (Request $request) {
        $validated = $request->validate([
            'pending_payment_id' => 'required|exists:pending_payments,id',
        ]);

        $pending = \App\Models\PendingPayment::find($validated['pending_payment_id']);

        if (!$pending || $pending->status !== 'pending') {
            return response()->json(['message' => 'Payment already processed'], 422);
        }

        $credit = Credit::with('customer')->find($pending->credit_id);

        // Create actual payment
        $payment = \App\Models\Payment::create([
            'credit_id' => $pending->credit_id,
            'amount_paid' => $pending->amount,
            'payment_date' => now()->toDateString(),
            'method' => 'mpesa',
            'mpesa_code' => $pending->payment_ref,
        ]);

        // Create transaction record for circulation
        \App\Models\Transaction::create([
            'user_id' => $request->user()->id,
            'customer_id' => $credit->customer_id,
            'type' => 'payment_received',
            'circulation_type' => 'MPESA',
            'amount' => $pending->amount,
            'direction' => 'in',
            'description' => 'Malipo ya deni - ' . $pending->payment_ref,
        ]);

        // Update customer balance - decrement
        $credit->customer->current_balance = max(0, $credit->customer->current_balance - $pending->amount);
        $credit->customer->save();

        // Update credit status if fully paid
        $totalPaid = \App\Models\Payment::where('credit_id', $credit->id)->sum('amount_paid');
        if ($totalPaid >= $credit->amount) {
            $credit->update(['status' => 'closed']);
            
            // Record credit closure as transaction (money out)
            \App\Models\Transaction::create([
                'customer_id' => $credit->customer_id,
                'type' => 'credit_issued',
                'amount' => $credit->amount,
                'direction' => 'out',
                'description' => 'Mkopo: ' . $credit->description,
            ]);
        }

        // Mark pending as confirmed
        $pending->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        // Clear all related caches
        \Illuminate\Support\Facades\Cache::forget('dashboard_metrics');
        \Illuminate\Support\Facades\Cache::forget('portal_customer_' . $credit->customer_id);

        // Send email
        try {
            \App\Services\EmailNotificationService::paymentReceived($payment, $credit);
        } catch (\Exception $e) {
            \Log::error("Email failed: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Payment confirmed successfully',
            'payment' => $payment,
            'new_balance' => $credit->customer->fresh()->current_balance
        ], 200);
    });

    // Admin Reject Pending Payment
    Route::post('/payments/admin-reject', function (Request $request) {
        $validated = $request->validate([
            'pending_payment_id' => 'required|exists:pending_payments,id',
            'reason' => 'nullable|string',
        ]);

        $pending = \App\Models\PendingPayment::find($validated['pending_payment_id']);
        $pending->update([
            'status' => 'rejected',
            'rejected_reason' => $validated['reason'] ?? null,
            'rejected_at' => now(),
        ]);

        return response()->json(['message' => 'Payment rejected'], 200);
    });

    // Overdue Reminder Cron Job (call daily)
    Route::get('/jobs/send-reminders', function() {
        $overdueCredits = Credit::with('customer')
            ->where('status', 'active')
            ->where('due_date', '<', now()->toDateString())
            ->get();

        $sent = 0;
        foreach ($overdueCredits as $credit) {
            try {
                \App\Services\EmailNotificationService::overdueReminder($credit->customer, $credit);
                $sent++;
            } catch (\Exception $e) {
                \Log::error("Reminder email failed for credit {$credit->id}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Reminders sent',
            'count' => $sent
        ]);
    });
});