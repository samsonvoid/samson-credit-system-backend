<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_authentication()
    {
        $customer = Customer::create([
            'name' => 'Secret Customer',
            'phone' => '1234',
            'credit_limit' => 10000,
            'current_balance' => 0,
        ]);

        // Unauthorized call to get customer
        $response = $this->getJson("/api/customers/{$customer->id}");
        $response->assertStatus(401);

        // Unauthorized call to issue credit
        $response = $this->postJson("/api/credits/issue", [
            'customer_id' => $customer->id,
            'amount' => 500,
            'type' => 'cash',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);
        $response->assertStatus(401);
    }

    public function test_can_fetch_customer_data_via_api()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $customer = Customer::create([
            'name' => 'API Payer',
            'phone' => '9999',
            'credit_limit' => 10000,
            'current_balance' => 1000,
            'trust_score' => 60,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonPath('name', 'API Payer')
            ->assertJsonPath('current_balance', 1000)
            ->assertJsonPath('trust_score', 60);
    }

    public function test_can_issue_credit_via_api()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $customer = Customer::create([
            'name' => 'Remote Payer',
            'phone' => '7777',
            'credit_limit' => 5000,
            'current_balance' => 0,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/credits/issue", [
                'customer_id' => $customer->id,
                'amount' => 1000,
                'type' => 'item',
                'description' => 'API Purchased Goods',
                'due_date' => now()->addDays(10)->toDateString(),
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Credit issued successfully via API.')
            ->assertJsonPath('new_balance', 1000);

        $this->assertDatabaseHas('credits', [
            'customer_id' => $customer->id,
            'amount' => 1000,
            'description' => 'API Purchased Goods',
        ]);
    }
}
