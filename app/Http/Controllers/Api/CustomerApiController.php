<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerApiController extends Controller
{
    public function show(Customer $customer)
    {
        return response()->json([
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'current_balance' => $customer->current_balance,
            'trust_score' => $customer->trust_score,
            'active_credits_count' => $customer->credits()->where('status', 'active')->count(),
        ]);
    }
}
