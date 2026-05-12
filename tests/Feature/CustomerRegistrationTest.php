<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CustomerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_new_customer_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '0712345678',
            'password' => 'Secure@CrdSys#2026!',
            'password_confirmation' => 'Secure@CrdSys#2026!',
        ]);

        $this->assertDatabaseHas('customers', [
            'email' => 'customer@example.com',
            'phone' => '0712345678',
        ]);

        // Should auto-login
        $this->assertTrue(Session::has('customer_id'));
        $response->assertRedirect(route('portal.dashboard'));
    }

    public function test_customer_cannot_register_with_weak_password()
    {
        $response = $this->post('/register', [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '0712345678',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('customers', [
            'email' => 'customer@example.com',
        ]);
    }

    public function test_customer_cannot_register_with_duplicate_email()
    {
        Customer::create([
            'name' => 'Existing Customer',
            'email' => 'customer@example.com',
            'phone' => '0711111111',
            'password' => bcrypt('Secure@CrdSys#2026!'),
            'credit_limit' => 10000,
        ]);

        $response = $this->post('/register', [
            'name' => 'New Customer',
            'email' => 'customer@example.com',
            'phone' => '0722222222',
            'password' => 'Secure@CrdSys#2026!',
            'password_confirmation' => 'Secure@CrdSys#2026!',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_customer_cannot_register_with_duplicate_phone()
    {
        Customer::create([
            'name' => 'Existing Customer',
            'email' => 'existing@example.com',
            'phone' => '0712345678',
            'password' => bcrypt('Secure@CrdSys#2026!'),
            'credit_limit' => 10000,
        ]);

        $response = $this->post('/register', [
            'name' => 'New Customer',
            'email' => 'new@example.com',
            'phone' => '0712345678',
            'password' => 'Secure@CrdSys#2026!',
            'password_confirmation' => 'Secure@CrdSys#2026!',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_customer_can_login_with_email()
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '0712345678',
            'password' => bcrypt('Secure@CrdSys#2026!'),
            'credit_limit' => 10000,
        ]);

        $response = $this->post(route('authenticate'), [
            'login_id' => 'customer@example.com',
            'password' => 'Secure@CrdSys#2026!',
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertEquals($customer->id, Session::get('customer_id'));
    }
}
