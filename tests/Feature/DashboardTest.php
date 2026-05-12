<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Credit;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_correct_metrics()
    {
        // 1. Create Data
        // - 1 Closed Credit (Paid)
        // - 1 Overdue Credit (Active, Past Due)
        // - 1 Fresh Credit (Active, Future Due)

        $customer = Customer::create(['name' => 'C1', 'phone' => '000', 'credit_limit' => 1000]);

        // Closed Credit
        $closed = Credit::create([
            'customer_id' => $customer->id,
            'amount' => 1000,
            'type' => 'cash',
            'status' => 'closed',
            'due_date' => now()->subDays(10)
        ]);
        Payment::create(['credit_id' => $closed->id, 'amount_paid' => 1000, 'payment_date' => now()]);

        // Overdue Credit (PAR)
        $overdue = Credit::create([
            'customer_id' => $customer->id,
            'amount' => 5000,
            'type' => 'cash',
            'status' => 'active',
            'due_date' => now()->subDays(1)
        ]);
        // Partial payment on overdue
        Payment::create(['credit_id' => $overdue->id, 'amount_paid' => 1000, 'payment_date' => now()]);
        // Remaining PAR = 4000

        // Fresh Active
        $fresh = Credit::create([
            'customer_id' => $customer->id,
            'amount' => 2000,
            'type' => 'cash',
            'status' => 'active',
            'due_date' => now()->addDays(5)
        ]);

        // Total Issued (This Month) = 1000 + 5000 + 2000 = 8000
        // Total Collected (This Month) = 1000 + 1000 = 2000
        // Repayment Rate = 1 closed / 3 total = 33.3%

        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);

        // Assert View Has Data
        $response->assertViewHas('repaymentRate', function ($val) {
            return abs($val - 33.33) < 0.1;
        });
        $response->assertViewHas('par', 4000); // 5000 - 1000
        $response->assertViewHas('totalIssued', 8000);
        $response->assertViewHas('totalCollected', 2000);
    }
}
