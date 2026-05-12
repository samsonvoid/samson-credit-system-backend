<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$customer = Customer::updateOrCreate(
    ['phone' => '0712345678'],
    [
        'name' => 'Juma Hamisi',
        'business_name' => 'Juma Retail',
        'location' => 'Mabibo Sokoni',
        'credit_limit' => 500000,
        'current_balance' => 150000,
        'trust_score' => 85
    ]
);

User::updateOrCreate(
    ['email' => 'juma@svs.com'],
    [
        'name' => 'Juma Hamisi',
        'password' => Hash::make('password123'),
        'role' => 'customer',
        'customer_id' => $customer->id
    ]
);

echo "Juma created successfully!\n";
