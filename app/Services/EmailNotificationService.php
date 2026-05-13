<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Credit;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailNotificationService
{
    /**
     * Send notification when new credit is issued
     */
    public static function creditIssued(Credit $credit)
    {
        $customer = $credit->customer;
        
        $data = [
            'customer_name' => $customer->name,
            'amount' => number_format($credit->amount),
            'due_date' => $credit->due_date->format('d M Y'),
            'items_count' => $credit->creditItems->count(),
            'shop_name' => auth()->user()->shop_name ?? 'SVS Credit',
        ];

        // Email to customer
        if ($customer->email) {
            self::sendEmail($customer->email, 'mkopo_mpya', $data, 'Mkopo Mpya Umewekewa');
        }

        // Email to admin
        $adminEmail = auth()->user()->email ?? config('mail.from.address');
        self::sendEmail($adminEmail, 'mkopo_notification', $data, 'Mkopo Mpya Umetolewa');
    }

    /**
     * Send notification when payment is received
     */
    public static function paymentReceived(Payment $payment, Credit $credit)
    {
        $customer = $credit->customer;
        
        $data = [
            'customer_name' => $customer->name,
            'amount_paid' => number_format($payment->amount_paid),
            'payment_date' => $payment->payment_date->format('d M Y'),
            'remaining_balance' => number_format($credit->amount - $payment->amount_paid),
            'mpesa_code' => $payment->mpesa_code ?? 'N/A',
        ];

        // Email to customer
        if ($customer->email) {
            self::sendEmail($customer->email, 'malipo_yamepokelea', $data, 'Malipo Yamepokea - Asante!');
        }
    }

    /**
     * Send overdue reminder
     */
    public static function overdueReminder(Customer $customer, Credit $credit)
    {
        $daysLate = now()->diffInDays($credit->due_date);
        
        $data = [
            'customer_name' => $customer->name,
            'amount_due' => number_format($credit->amount),
            'days_late' => $daysLate,
            'due_date' => $credit->due_date->format('d M Y'),
        ];

        if ($customer->email) {
            self::sendEmail($customer->email, 'kumbusho_deni', $data, "Kumbusho: Deni Limepitisha Wakati - TZS {$data['amount_due']}");
        }
    }

    /**
     * Send trust score update
     */
    public static function trustScoreUpdate(Customer $customer, $oldScore, $newScore)
    {
        $change = $newScore - $oldScore;
        $status = $change >= 0 ? 'imeongezeka' : 'imepungua';

        $data = [
            'customer_name' => $customer->name,
            'old_score' => $oldScore,
            'new_score' => $newScore,
            'change' => abs($change),
            'status' => $status,
        ];

        if ($customer->email) {
            $subject = $change >= 0 ? 'Habari Njema: Score Yako Imeongezeka!' : 'Hitaji: Score Yako Imepungua';
            self::sendEmail($customer->email, 'trust_score_update', $data, $subject);
        }
    }

    /**
     * Send welcome email to new customer
     */
    public static function welcomeCustomer(Customer $customer)
    {
        $data = [
            'customer_name' => $customer->name,
            'credit_limit' => number_format($customer->credit_limit),
            'shop_name' => auth()->user()->shop_name ?? 'SVS Credit',
        ];

        if ($customer->email) {
            self::sendEmail($customer->email, 'welcome_customer', $data, 'Karibu! Umejiunga na SVS Credit');
        }
    }

    /**
     * Generic email sender
     */
    private static function sendEmail($to, $template, $data, $subject)
    {
        // Check if emails are enabled
        if (!config('mail.enabled', false)) {
            Log::info("Email would be sent to {$to}: {$subject}");
            Log::info("Template: {$template}, Data: " . json_encode($data));
            return;
        }

        try {
            // For development, just log
            // In production, use actual mail sending
            Mail::raw("Subject: {$subject}\n\n" . json_encode($data, JSON_PRETTY_PRINT), function($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error("Email send failed: " . $e->getMessage());
        }
    }
}