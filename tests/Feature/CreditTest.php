<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Credit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_issue_credit_and_update_balance()
    {
        $customer = Customer::create([
            'name' => 'Test User',
            'phone' => '123456',
            'credit_limit' => 10000,
            'current_balance' => 0,
        ]);

        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user)->post(route('credits.store'), [
            'customer_id' => $customer->id,
            'amount' => 5000,
            'type' => 'item',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('credits', [
            'customer_id' => $customer->id,
            'amount' => 5000,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'current_balance' => 5000, // Should increase
        ]);
    }

    public function test_cannot_exceed_credit_limit()
    {
        $customer = Customer::create([
            'name' => 'Limit User',
            'phone' => '999999',
            'credit_limit' => 5000,
            'current_balance' => 0,
        ]);

        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user)->post(route('credits.store'), [
            'customer_id' => $customer->id,
            'amount' => 6000, // Exceeds 5000
            'type' => 'cash',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $response->assertSessionHasErrors('amount'); // Should ensure limitation
        $this->assertDatabaseCount('credits', 0);
    }

    public function test_can_record_payment_and_close_credit()
    {
        $customer = Customer::create([
            'name' => 'Payer',
            'phone' => '888888',
            'credit_limit' => 10000,
            'current_balance' => 5000,
        ]);

        $credit = Credit::create([
            'customer_id' => $customer->id,
            'amount' => 5000,
            'type' => 'item',
            'due_date' => now()->addDays(7),
            'status' => 'active',
        ]);

        // Pay 3000
        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user)->post(route('credits.repay', $credit), [
            'amount_paid' => 3000,
            'method' => 'cash',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'credit_id' => $credit->id,
            'amount_paid' => 3000,
        ]);

        // Check Balance decreased
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'current_balance' => 2000, // 5000 - 3000
        ]);

        // Pay remaining 2000
        $this->actingAs($user)->post(route('credits.repay', $credit), [
            'amount_paid' => 2000,
            'method' => 'mpesa',
        ]);

        // Credit should be closed
        $this->assertDatabaseHas('credits', [
            'id' => $credit->id,
            'status' => 'closed',
        ]);
    }
}
