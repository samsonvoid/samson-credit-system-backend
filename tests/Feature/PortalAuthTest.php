<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class PortalAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_customer_cannot_access_dashboard()
    {
        $response = $this->get('/portal/dashboard');
        $response->assertRedirect(route('portal.login'));
    }

    public function test_customer_login_screen_can_be_rendered()
    {
        $response = $this->get('/portal/login');
        $response->assertStatus(200);
    }

    public function test_customer_can_login()
    {
        // Create a customer with password
        $customer = Customer::create([
            'name' => 'John Doe',
            'phone' => '1234567890',
            'credit_limit' => 5000,
            'password' => bcrypt('1234'),
        ]);

        // Attempt login using unified endpoint
        $response = $this->post(route('authenticate'), [
            'login_id' => '1234567890',
            'password' => '1234',
        ]);

        // Follow redirect or check session
        $response->assertRedirect(route('portal.dashboard'));
        $this->assertTrue(Session::has('customer_id'));
        $this->assertEquals($customer->id, Session::get('customer_id'));
    }

    public function test_customer_cannot_login_with_invalid_phone()
    {
        $response = $this->post(route('authenticate'), [
            'login_id' => '0000000000',
            'password' => '1234',
        ]);

        // Expect validation error or failure
        $response->assertSessionHasErrors('login_id');
        $this->assertFalse(Session::has('customer_id'));
    }

    public function test_customer_cannot_login_with_wrong_password()
    {
        $customer = Customer::create([
            'name' => 'John Doe',
            'phone' => '1234567890',
            'credit_limit' => 5000,
            'password' => bcrypt('1234'),
        ]);

        $response = $this->post(route('authenticate'), [
            'login_id' => '1234567890',
            'password' => 'wrong',
        ]);

        $response->assertSessionHasErrors('login_id');
        $this->assertFalse(Session::has('customer_id'));
    }

    public function test_authenticated_customer_can_access_dashboard()
    {
        $customer = Customer::create([
            'name' => 'Jane Doe',
            'phone' => '0987654321',
            'credit_limit' => 5000,
        ]);

        // Simulate logged in state by setting session
        $response = $this->withSession(['customer_id' => $customer->id])
            ->get(route('portal.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Jane Doe');
    }

    public function test_authenticated_customer_can_logout()
    {
        $customer = Customer::create([
            'name' => 'Jane Doe',
            'phone' => '0987654321',
        ]);

        // Log in
        $response = $this->withSession(['customer_id' => $customer->id])
            ->post(route('portal.logout'));

        // Assert redirect to HOME
        $response->assertRedirect(route('home'));
        $this->assertFalse(Session::has('customer_id'));
    }
}
