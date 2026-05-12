<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin User
        User::firstOrCreate([
            'email' => 'admin@credit-system.com',
        ], [
            'name' => 'Shop Manager',
            'password' => bcrypt('password'),
        ]);

        // 2. Customer User (Juma)
        $customer = \App\Models\Customer::updateOrCreate(
            ['phone' => '0712345678'],
            [
                'name' => 'Juma Hamisi',
                'business_name' => 'Juma Retail',
                'location' => 'Mabibo Sokoni',
                'credit_limit' => 500000,
                'current_balance' => 150000,
                'trust_score' => 85,
                'password' => bcrypt('password123')
            ]
        );

        User::updateOrCreate(
            ['email' => 'juma@svs.com'],
            [
                'name' => 'Juma Hamisi',
                'password' => bcrypt('password123'),
                'role' => 'customer',
                'customer_id' => $customer->id
            ]
        );

        // 3. Initial Transactions (Mzunguko)
        $credit = \App\Models\Credit::create([
            'customer_id' => $customer->id,
            'amount' => 500000,
            'type' => 'item',
            'status' => 'active',
            'due_date' => now()->addDays(30),
            'description' => 'Mzigo wa duka (Ngano, Sukari)'
        ]);

        // Record it in Transactions
        \App\Models\Transaction::create([
            'customer_id' => $customer->id,
            'user_id' => 1,
            'type' => 'PRODUCT_CREDIT',
            'circulation_type' => 'PRODUCT',
            'direction' => 'out',
            'amount' => 500000,
            'reference_id' => $credit->id,
            'description' => 'Mkopo wa bidhaa kwa Juma'
        ]);

        // Partial Payment
        $paymentAmount = 150000;
        \App\Models\Payment::create([
            'credit_id' => $credit->id,
            'amount_paid' => $paymentAmount,
            'payment_date' => now(),
            'method' => 'cash'
        ]);

        \App\Models\Transaction::create([
            'customer_id' => $customer->id,
            'user_id' => 1,
            'type' => 'PAYMENT_RECEIVED',
            'circulation_type' => 'CASH',
            'direction' => 'in',
            'amount' => $paymentAmount,
            'reference_id' => $credit->id,
            'description' => 'Malipo ya kwanza ya Juma'
        ]);
        // 4. Five More Customers (Mabibo SMEs)
        $smes = [
            ['name' => 'Mama Maria', 'biz' => 'Mama Lishe Center', 'loc' => 'Mabibo Hostels', 'limit' => 200000, 'bal' => 45000, 'trust' => 95],
            ['name' => 'Kassim Simba', 'biz' => 'Simba Hardware', 'loc' => 'Mabibo Sokoni', 'limit' => 1500000, 'bal' => 800000, 'trust' => 82],
            ['name' => 'Salma Kipande', 'biz' => 'Salma Groceries', 'loc' => 'Mabibo External', 'limit' => 100000, 'bal' => 95000, 'trust' => 65],
            ['name' => 'John Fix', 'biz' => 'John Phone Repair', 'loc' => 'Mabibo Sahara', 'limit' => 300000, 'bal' => 0, 'trust' => 90],
            ['name' => 'Amina Duka', 'biz' => 'Amina Mini-Mart', 'loc' => 'Mabibo Corner', 'limit' => 500000, 'bal' => 250000, 'trust' => 88],
        ];

        foreach ($smes as $sme) {
            $c = \App\Models\Customer::create([
                'name' => $sme['name'],
                'business_name' => $sme['biz'],
                'location' => $sme['loc'],
                'credit_limit' => $sme['limit'],
                'current_balance' => $sme['bal'],
                'trust_score' => $sme['trust'],
                'phone' => '0' . rand(710, 799) . rand(100000, 999999),
                'password' => bcrypt('password123')
            ]);

            if ($sme['bal'] > 0) {
                // Record the debt in transactions
                \App\Models\Transaction::create([
                    'customer_id' => $c->id,
                    'user_id' => 1,
                    'type' => 'PRODUCT_CREDIT',
                    'circulation_type' => 'PRODUCT',
                    'direction' => 'out',
                    'amount' => $sme['bal'],
                    'description' => 'Mizigo ya awali - ' . $sme['biz']
                ]);
            }
        }
    }
}
