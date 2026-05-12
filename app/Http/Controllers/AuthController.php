<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login_id' => ['required'],
            'password' => ['required'],
        ]);

        // Try to authenticate based on input type
        $isEmail = filter_var($credentials['login_id'], FILTER_VALIDATE_EMAIL);

        // 1. Try Admin Login (Email only)
        if ($isEmail) {
            if (Auth::attempt(['email' => $credentials['login_id'], 'password' => $credentials['password']])) {
                $request->session()->regenerate();
                return redirect()->intended(route('dashboard'));
            }
        }

        // 2. Try Customer Login (Phone or Email)
        // Try to find customer by phone OR email
        $customer = \App\Models\Customer::where('phone', $credentials['login_id'])
            ->orWhere('email', $credentials['login_id'])
            ->first();

        if ($customer && \Illuminate\Support\Facades\Hash::check($credentials['password'], $customer->password)) {
            $request->session()->regenerate();
            session(['customer_id' => $customer->id]);
            return redirect()->route('portal.dashboard');
        }

        return back()->withErrors([
            'login_id' => 'The provided credentials do not match our records.',
        ])->onlyInput('login_id');
    }

    public function logout(Request $request)
    {
        \Illuminate\Support\Facades\Session::forget('customer_id');
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
