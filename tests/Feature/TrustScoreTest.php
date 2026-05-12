<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Credit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrustScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_trust_score_increases_on_elite_tier_repayment()
    {
        $customer = Customer::create([
            'name' => 'Fast Payer',
            'phone' => '1111',
            'credit_limit' => 10000,
            'trust_score' => 0,
            'current_balance' => 0,
        ]);

        $credit = Credit::create([
            'customer_id' => $customer->id,
            'amount' => 5000,
            'type' => 'cash',
            'due_date' => now()->addDays(10), // Long duration
            'status' => 'active',
            'created_at' => now()->subDay(), // Issued yesterday
        ]);
        $customer->increment('current_balance', 5000);

        // Repay today (1 day used out of 10 = 10% = Elite Tier)
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user)->post(route('credits.repay', $credit), [
            'amount_paid' => 5000,
            'method' => 'cash',
        ]);

        // Score should increase by 20 (Elite Tier)
        $this->assertEquals(20, $customer->fresh()->trust_score);

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $customer->id,
            'type' => 'trust_score_update',
            'amount' => 20,
        ]);
    }

    public function test_trust_score_decreases_daily_via_command()
    {
        $customer = Customer::create([
            'name' => 'Overdue Customer',
            'phone' => '2222',
            'credit_limit' => 10000,
            'trust_score' => 50,
            'current_balance' => 0,
        ]);

        $credit = Credit::create([
            'customer_id' => $customer->id,
            'amount' => 5000,
            'type' => 'cash',
            'due_date' => now()->subDays(2), // 2 days overdue
            'status' => 'active',
        ]);

        // Run the daily reminder/penalty command
        \Illuminate\Support\Facades\Artisan::call('app:send-repayment-reminders');

        // Score should decrease by 5
        $this->assertEquals(45, $customer->fresh()->trust_score);

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $customer->id,
            'type' => 'trust_score_update',
            'amount' => -5,
        ]);
    }

    public function test_late_payment_repayment_does_not_deduct_further_points()
    {
        $customer = Customer::create([
            'name' => 'Late Payer',
            'phone' => '3333',
            'credit_limit' => 10000,
            'trust_score' => 45, // Already penalized by 5
            'current_balance' => 5000,
        ]);

        $credit = Credit::create([
            'customer_id' => $customer->id,
            'amount' => 5000,
            'type' => 'cash',
            'due_date' => now()->subDays(1),
            'status' => 'active',
        ]);

        // Repay today (late)
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user)->post(route('credits.repay', $credit), [
            'amount_paid' => 5000,
            'method' => 'cash',
        ]);

        // Score should stay at 45 (no extra deduction in repayment)
        $this->assertEquals(45, $customer->fresh()->trust_score);
    }
}
