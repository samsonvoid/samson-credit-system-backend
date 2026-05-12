<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_login_page_loads()
    {
        $response = $this->get('/portal/login');
        $response->assertStatus(200);
    }

    public function test_customer_can_login_with_phone()
    {
        $customer = Customer::create(['name' => 'Portal User', 'phone' => '07123', 'credit_limit' => 1000]);

        // Login
        $response = $this->post('/portal/login', [
            'phone' => '07123',
        ]);

        $response->assertRedirect('/portal/dashboard');
        $response->assertSessionHas('customer_id', $customer->id);
    }

    public function test_cannot_login_with_invalid_phone()
    {
        $response = $this->post('/portal/login', [
            'phone' => '00000', // Does not exist
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_dashboard_shows_customer_data()
    {
        $customer = Customer::create(['name' => 'Viewer', 'phone' => '555', 'trust_score' => 85, 'credit_limit' => 5000]);

        // Mock Login
        $this->withSession(['customer_id' => $customer->id]);

        $response = $this->get('/portal/dashboard');
        $response->assertStatus(200);
        $response->assertSee('85'); // Trust Score
        $response->assertSee('5,000'); // Credit Limit
    }

    public function test_guest_cannot_access_dashboard()
    {
        $response = $this->get('/portal/dashboard');
        $response->assertRedirect('/portal/login');
    }

    public function test_logout()
    {
        $this->withSession(['customer_id' => 1]);

        $response = $this->post('/portal/logout');

        $response->assertRedirect(route('home'));
        $response->assertSessionMissing('customer_id');
    }
}
