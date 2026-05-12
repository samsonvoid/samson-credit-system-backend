<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Credit;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RealSmeDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure we have the Admin User (Shop Owner)
        $owner = User::updateOrCreate(
            ['email' => 'owner@mabibo.com'],
            [
                'name' => 'Mabibo Wholesale Admin',
                'password' => Hash::make('mabibo123'),
                'shop_name' => 'Mabibo Wholesale & Retail',
                'location' => 'Mabibo Market, Dar es Salaam'
            ]
        );

        // Clear existing data to start fresh
        Payment::truncate();
        Credit::truncate();
        Customer::truncate();

        // 2. Real SME Customers in Mabibo Context
        $customers = [
            [
                'name' => 'Hamisi Juma',
                'phone' => '0712345678',
                'business_name' => 'Hamisi Retail Shop',
                'business_type' => 'Retail Grocery',
                'location' => 'Mabibo Sokoni',
                'credit_limit' => 1500000,
                'trust_score' => 85,
                'user_id' => $owner->id
            ],
            [
                'name' => 'Sarah Mwakideu',
                'phone' => '0755667788',
                'business_name' => 'Mama Sarah Mama Ntilie',
                'business_type' => 'Food Vendor',
                'location' => 'Mabibo Riverside',
                'credit_limit' => 500000,
                'trust_score' => 92,
                'user_id' => $owner->id
            ],
            [
                'name' => 'John Njoroge',
                'phone' => '0622112233',
                'business_name' => 'Njoroge Hardware',
                'business_type' => 'Construction Materials',
                'location' => 'Mabibo Loyola',
                'credit_limit' => 3000000,
                'trust_score' => 45,
                'user_id' => $owner->id
            ],
            [
                'name' => 'Zuwena Bakari',
                'phone' => '0788990011',
                'business_name' => 'Zuwena Boutique',
                'business_type' => 'Clothing',
                'location' => 'Mabibo Market',
                'credit_limit' => 800000,
                'trust_score' => 78,
                'user_id' => $owner->id
            ],
            [
                'name' => 'Emmanuel Kimaro',
                'phone' => '0744332211',
                'business_name' => 'Kimaro Stationery',
                'business_type' => 'Service',
                'location' => 'External, Mabibo',
                'credit_limit' => 200000,
                'trust_score' => 60,
                'user_id' => $owner->id
            ]
        ];

        foreach ($customers as $cData) {
            $customer = Customer::create($cData);

            // 3. Add Credits for each customer
            if ($customer->name == 'Hamisi Juma') {
                $credit = Credit::create([
                    'customer_id' => $customer->id,
                    'amount' => 1000000,
                    'type' => 'item', // Stock (Goods)
                    'status' => 'active',
                    'due_date' => now()->addDays(30)
                ]);
                // Add some payments
                Payment::create(['credit_id' => $credit->id, 'amount_paid' => 400000, 'payment_date' => now()->subDays(10)]);
                Payment::create(['credit_id' => $credit->id, 'amount_paid' => 300000, 'payment_date' => now()->subDays(2)]);
            }

            if ($customer->name == 'Sarah Mwakideu') {
                $credit = Credit::create([
                    'customer_id' => $customer->id,
                    'amount' => 300000,
                    'type' => 'cash',
                    'status' => 'active',
                    'due_date' => now()->addDays(14)
                ]);
                Payment::create(['credit_id' => $credit->id, 'amount_paid' => 250000, 'payment_date' => now()->subDays(5)]);
            }

            if ($customer->name == 'John Njoroge') {
                Credit::create([
                    'customer_id' => $customer->id,
                    'amount' => 2500000,
                    'type' => 'item',
                    'status' => 'active',
                    'due_date' => now()->subDays(5) // Overdue
                ]);
            }
        }
    }
}
