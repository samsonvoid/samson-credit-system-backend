<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PortalController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin()
    {
        return view('portal.login');
    }

    /**
     * Handle login by phone number.
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|exists:customers,phone',
        ]);

        $customer = Customer::where('phone', $request->phone)->first();

        // In a real app, we'd use Auth facade with a custom guard. 
        // For this MVP, we'll use a simple session key.
        Session::put('customer_id', $customer->id);

        return redirect()->route('portal.dashboard');
    }

    /**
     * Show the customer dashboard.
     */
    public function dashboard()
    {
        $customer = Customer::with([
            'credits' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'credits.payments'
        ])->find(Session::get('customer_id'));

        if (!$customer) {
            Session::forget('customer_id');
            return redirect()->route('portal.login');
        }

        return view('portal.dashboard', compact('customer'));
    }

    /**
     * Logout.
     */
    public function logout()
    {
        Session::forget('customer_id');
        return redirect()->route('home');
    }
}
