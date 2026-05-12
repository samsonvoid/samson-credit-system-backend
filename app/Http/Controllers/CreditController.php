<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreditController extends Controller
{
    /**
     * Store a newly created credit (Issue Credit).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'due_date' => 'required|date|after:today',
            'type' => 'required|in:cash,item',
            'description' => 'nullable|string|max:255',
        ]);

        $customer = Customer::lockForUpdate()->findOrFail($validated['customer_id']);

        if (($customer->current_balance + $validated['amount']) > $customer->credit_limit) {
            return back()->withErrors(['amount' => 'Credit limit exceeded. Current: ' . $customer->current_balance . ' + New: ' . $validated['amount'] . ' > Limit: ' . $customer->credit_limit]);
        }

        DB::transaction(function () use ($validated, $customer) {
            // 1. Create Credit
            $credit = Credit::create([
                'customer_id' => $validated['customer_id'],
                'amount' => $validated['amount'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'due_date' => $validated['due_date'],
                'status' => 'active',
            ]);

            // 2. Update Customer Balance
            $customer->increment('current_balance', $validated['amount']);

            // 3. Log Issuer Transaction
            $itemDesc = ($validated['description'] ?? null) ? " ({$validated['description']})" : "";
            Transaction::create([
                'customer_id' => $customer->id,
                'type' => 'credit_issued',
                'amount' => $validated['amount'],
                'reference_id' => $credit->id,
                'description' => "Issued {$validated['type']} credit{$itemDesc} due on {$validated['due_date']}",
            ]);
        });

        return back()->with('success', 'Credit issued successfully.');
    }

    /**
     * Record a payment for a specific credit.
     */
    public function repayment(Request $request, Credit $credit)
    {
        $validated = $request->validate([
            'amount_paid' => 'required|numeric|min:1',
            'method' => 'required|in:cash,mpesa',
        ]);

        $credit = Credit::lockForUpdate()->findOrFail($credit->id);
        $creditBalance = $credit->amount - $credit->payments()->sum('amount_paid');

        if ($validated['amount_paid'] > $creditBalance) {
            return back()->withErrors(['amount_paid' => 'Amount exceeds remaining debt. Remaining: ' . $creditBalance]);
        }

        DB::transaction(function () use ($validated, $credit) {
            // 1. Record Payment
            Payment::create([
                'credit_id' => $credit->id,
                'amount_paid' => $validated['amount_paid'],
                'payment_date' => now(),
                'method' => $validated['method'],
            ]);

            // 2. Update Customer Balance
            $credit->customer->decrement('current_balance', $validated['amount_paid']);

            // 3. Log Payment Transaction
            Transaction::create([
                'customer_id' => $credit->customer_id,
                'type' => 'payment_received',
                'amount' => $validated['amount_paid'],
                'reference_id' => $credit->id,
                'description' => "Payment received via {$validated['method']}",
            ]);

            // Check if credit is fully paid
            if ($credit->fresh()->payments()->sum('amount_paid') >= $credit->amount) {
                $credit->update(['status' => 'closed']);

                // --- DYNAMIC TIERED TRUST SCORING ---
                $paymentDate = now();
                $createdAt = $credit->created_at;
                $dueDate = \Illuminate\Support\Carbon::parse($credit->due_date);

                $totalDuration = $createdAt->diffInMinutes($dueDate);
                $timeTaken = $createdAt->diffInMinutes($paymentDate);

                // Avoid division by zero for instant credits
                if ($totalDuration <= 0)
                    $totalDuration = 1;

                $percentageUsed = ($timeTaken / $totalDuration) * 100;

                $scoreChange = 0;
                $reason = '';

                if ($paymentDate->lte($dueDate)) {
                    // ON TIME OR EARLY
                    if ($percentageUsed <= 25) {
                        $scoreChange = 20; // Very Early
                        $reason = 'Exceptional speed (Elite Tier)';
                    } elseif ($percentageUsed <= 50) {
                        $scoreChange = 15; // Mid-Period
                        $reason = 'Good repayment speed (Gold Tier)';
                    } elseif ($percentageUsed <= 90) {
                        $scoreChange = 10; // Near Deadline
                        $reason = 'Steady repayment (Silver Tier)';
                    } else {
                        $scoreChange = 5; // Exactly/Just in Time
                        $reason = 'Paid on time (Bronze Tier)';
                    }
                } else {
                    // PAID LATE
                    // Note: No deduction here because the daily command already did it!
                    $scoreChange = 0;
                    $reason = 'Finalized late payment';
                }

                if ($scoreChange > 0) {
                    $credit->customer->increment('trust_score', $scoreChange);

                    // Cap at 100
                    if ($credit->customer->fresh()->trust_score > 100) {
                        $credit->customer->update(['trust_score' => 100]);
                    }

                    \App\Models\Transaction::create([
                        'customer_id' => $credit->customer_id,
                        'type' => 'trust_score_update',
                        'amount' => $scoreChange,
                        'reference_id' => $credit->id,
                        'description' => "Trust Bonus: {$reason}",
                    ]);
                }
            }
        });

        // 5. Send Electronic Receipt (Notification)
        try {
            $customer = $credit->customer;
            if ($customer->email) {
                // We fetch the latest payment recorded for this credit
                $payment = $credit->payments()->latest()->first();
                $customer->notify(new \App\Notifications\PaymentReceivedNotification($payment, $customer->name));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send receipt: " . $e->getMessage());
        }

        return back()->with('success', 'Payment recorded successfully. Electronic receipt sent.');
    }
}
