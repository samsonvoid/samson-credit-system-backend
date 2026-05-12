<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Credit;

$customer = Customer::first();
if ($customer) {
    $customer->email = 'samsonmwamloso@gmail.com';
    $customer->save();
    
    $credit = $customer->credits()->where('status', 'active')->first();
    if ($credit) {
        $credit->due_date = now()->addDays(2)->toDateTimeString();
        $credit->save();
        echo "Data Updated: Customer {$customer->name} now has a credit due in 2 days.\n";
    }
}
