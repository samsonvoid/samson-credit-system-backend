<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditApiController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'due_date' => 'required|date|after:today',
            'type' => 'required|in:cash,item',
            'description' => 'nullable|string|max:255',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);

        if (($customer->current_balance + $validated['amount']) > $customer->credit_limit) {
            return response()->json([
                'error' => 'Credit limit exceeded.',
                'current_balance' => $customer->current_balance,
                'limit' => $customer->credit_limit
            ], 422);
        }

        $credit = DB::transaction(function () use ($validated, $customer) {
            $credit = Credit::create([
                'customer_id' => $validated['customer_id'],
                'amount' => $validated['amount'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'due_date' => $validated['due_date'],
                'status' => 'active',
            ]);

            $customer->increment('current_balance', $validated['amount']);

            $itemDesc = ($validated['description'] ?? null) ? " ({$validated['description']})" : "";
            Transaction::create([
                'customer_id' => $customer->id,
                'type' => 'credit_issued',
                'amount' => $validated['amount'],
                'reference_id' => $credit->id,
                'description' => "API ISSUED: {$validated['type']} credit{$itemDesc}",
            ]);

            return $credit;
        });

        return response()->json([
            'message' => 'Credit issued successfully via API.',
            'credit_id' => $credit->id,
            'new_balance' => $customer->fresh()->current_balance
        ]);
    }
}
