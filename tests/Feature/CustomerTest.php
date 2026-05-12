<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_customers_list()
    {
        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user)->get('/admin/customers');
        $response->assertStatus(200);
    }

    public function test_can_create_customer()
    {
        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user)->post('/admin/customers', [
            'name' => 'Mama John',
            'phone' => '0712345678',
            'credit_limit' => 100000,
        ]);

        $response->assertRedirect('/admin/customers');
        $this->assertDatabaseHas('customers', [
            'name' => 'Mama John',
            'phone' => '0712345678',
            'current_balance' => 0,
        ]);
    }

    public function test_cannot_create_duplicate_phone()
    {
        Customer::create([
            'name' => 'Mama John',
            'phone' => '0712345678',
            'credit_limit' => 100000,
        ]);

        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user)->post('/admin/customers', [
            'name' => 'Another Person',
            'phone' => '0712345678', // Same phone
            'credit_limit' => 50000,
        ]);

        $response->assertSessionHasErrors('phone');
    }
}
