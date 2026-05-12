<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendRepaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-repayment-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminders to customers with overdue or upcoming repayments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Send Email Reminders (Due in 3 days or less)
        $this->info('Scanning for repayment reminders (3-day window)...');
        $customersForReminders = \App\Models\Customer::whereHas('credits', function ($query) {
            $query->where('status', 'active')
                ->where('due_date', '<=', now()->addDays(3))
                ->where('due_date', '>=', now()); // Only upcoming, not overdue yet for reminders
        })->get();

        $reminderCount = 0;
        foreach ($customersForReminders as $customer) {
            if ($customer->email) {
                $customer->notify(new \App\Notifications\RepaymentReminderNotification($customer, $customer->current_balance));
                $reminderCount++;
            }
        }
        $this->info("Successfully sent {$reminderCount} reminders.");

        // 2. Apply Daily Overdue Penalties (-5 points per day)
        $this->info('Applying daily overdue penalties...');
        $overdueCredits = \App\Models\Credit::where('status', 'active')
            ->where('due_date', '<', now()->startOfDay())
            ->get();

        $penaltyCount = 0;
        foreach ($overdueCredits as $credit) {
            $customer = $credit->customer;

            // Deduct points
            $customer->decrement('trust_score', 5);

            // Floor at 0
            if ($customer->trust_score < 0) {
                $customer->update(['trust_score' => 0]);
            }

            // Log the trust score update
            \App\Models\Transaction::create([
                'customer_id' => $customer->id,
                'type' => 'trust_score_update',
                'amount' => -5,
                'reference_id' => $credit->id,
                'description' => "Daily penalty: Credit #{$credit->id} is overdue",
            ]);
            $penaltyCount++;
        }
        $this->info("Successfully penalized {$penaltyCount} overdue records.");
    }
}
