<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Password;

class CustomerRegistrationController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:customers,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:customers,phone'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        // Create customer with default credit limit
        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'credit_limit' => 10000, // Default: 10,000 TZS
            'trust_score' => 0,
            'current_balance' => 0,
        ]);

        // Auto-login the customer
        $request->session()->regenerate();
        Session::put('customer_id', $customer->id);

        return redirect()->route('portal.dashboard')->with('success', 'Registration successful! Welcome to the Credit System.');
    }
}
