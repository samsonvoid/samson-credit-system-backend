<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\CreditItem;
use App\Models\Transaction;
use App\Models\Item;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditApiController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'due_date' => 'required|date|after_or_equal:today',
            'type' => 'required|in:cash,item',
            'description' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*.item_id' => 'nullable|integer',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.subtotal' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:1',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);

        // Block if customer has outstanding debt
        if ($customer->current_balance > 0) {
            return response()->json([
                'error' => "{$customer->name} ana deni lililosalia (TZS " . number_format($customer->current_balance) . "). Hairuhusiwi kuchukua mkopo mpya hadi alipe!",
                'current_balance' => $customer->current_balance
            ], 422);
        }

        $totalAmount = $validated['amount'] ?? 0;
        
        if (!empty($validated['items'])) {
            $totalAmount = array_sum(array_column($validated['items'], 'subtotal'));
        }

        if (($customer->current_balance + $totalAmount) > $customer->credit_limit) {
            return response()->json([
                'error' => 'Credit limit exceeded.',
                'current_balance' => $customer->current_balance,
                'limit' => $customer->credit_limit
            ], 422);
        }

        $credit = DB::transaction(function () use ($validated, $customer, $totalAmount) {
            $credit = Credit::create([
                'customer_id' => $validated['customer_id'],
                'amount' => $totalAmount,
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'due_date' => $validated['due_date'],
                'status' => 'active',
            ]);

            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $itemData) {
                    if (!empty($itemData['item_id'])) {
                        CreditItem::create([
                            'credit_id' => $credit->id,
                            'item_id' => $itemData['item_id'],
                            'quantity' => $itemData['quantity'] ?? 1,
                            'unit_price' => $itemData['unit_price'] ?? 0,
                            'subtotal' => $itemData['subtotal'] ?? 0,
                        ]);
                    }
                }
            }

            $customer->increment('current_balance', $totalAmount);

            $itemDesc = !empty($validated['items']) ? ' (' . count($validated['items']) . ' items)' : '';
            $itemDesc .= ($validated['description'] ?? null) ? " - {$validated['description']}" : "";
            
            Transaction::create([
                'user_id' => auth('sanctum')->user()?->id ?? $request->user()?->id ?? null,
                'customer_id' => $customer->id,
                'type' => 'credit_issued',
                'amount' => $totalAmount,
                'reference_id' => $credit->id,
                'description' => "MKOPO WA {$validated['type']}{$itemDesc}",
            ]);

            return $credit;
        });

        $credit->load('creditItems.item');

        // Send email notification to customer (async in background)
        try {
            EmailNotificationService::creditIssued($credit, $request->user());
        } catch (\Exception $e) {
            // Don't fail the operation if email fails
            \Log::error("Failed to send credit email: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Credit issued successfully.',
            'credit_id' => $credit->id,
            'total_amount' => $totalAmount,
            'items_count' => count($validated['items'] ?? []),
            'new_balance' => $customer->fresh()->current_balance
        ]);
    }

    public function getItems()
    {
        return \Illuminate\Support\Facades\Cache::remember('items_list', 60, function() {
            $items = Item::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'price', 'unit', 'category']);
            
            return response()->json(['items' => $items]);
        });
    }

    public function addItems(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:50|unique:items,sku',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'stock_quantity' => 'nullable|integer|min:0',
        ]);

        $item = Item::create($validated);
        
        \Illuminate\Support\Facades\Cache::forget('items_list');

        return response()->json([
            'message' => 'Item added successfully.',
            'item' => $item
        ], 201);
    }
}