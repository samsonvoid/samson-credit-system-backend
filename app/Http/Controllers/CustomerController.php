<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customers = Customer::latest()->paginate(10);
        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers',
            'credit_limit' => 'required|numeric|min:0',
        ]);

        // Generate random secure password
        $validated['password'] = \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(8));

        Customer::create($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully. Default password is "1234".');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized. Only admins can edit customers.');
        }
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized. Only admins can update customers.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone,' . $customer->id,
            'credit_limit' => 'required|numeric|min:0',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized. Only admins can delete customers.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully');
    }

    public function sendReminder(Customer $customer)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized. Only admins can send reminders.');
        }

        if ($customer->email) {
            $customer->notify(new \App\Notifications\RepaymentReminderNotification($customer, $customer->current_balance));
            return back()->with('success', 'Real email reminder sent successfully to ' . $customer->email);
        }

        // Fallback for customers without email
        \Illuminate\Support\Facades\Log::info("MANUAL REMINDER LOGGED (No Email): Dear {$customer->name}, you have an outstanding balance of {$customer->current_balance} TZS.");
        return back()->with('info', 'Customer has no email address. Reminder logged to system only.');
    }
}
