<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Credit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MpesaIntegrationService
{
    private $consumerKey;
    private $consumerSecret;
    private $shortCode;
    private $passkey;
    private $callbackUrl;

    public function __construct()
    {
        $this->consumerKey = config('services.mpesa.consumer_key', '');
        $this->consumerSecret = config('services.mpesa.consumer_secret', '');
        $this->shortCode = config('services.mpesa.short_code', '174379');
        $this->passkey = config('services.mpesa.passkey', '');
        $this->callbackUrl = config('services.mpesa.callback_url', url('/api/mpesa/callback'));
    }

    /**
     * Get OAuth token for M-Pesa
     */
    public function getAccessToken()
    {
        // Check cache first
        $cacheKey = 'mpesa_access_token';
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        
        if ($cached) {
            return $cached;
        }

        // If not configured, return mock for development
        if (empty($this->consumerKey) || empty($this->consumerSecret)) {
            Log::info('M-Pesa not configured, returning mock token');
            return 'mock_token_' . time();
        }

        try {
            $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
            
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $credentials,
                'Content-Type' => 'application/json'
            ])->post('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');

            $data = $response->json();
            
            if (isset($data['access_token'])) {
                // Cache for 55 minutes (tokens expire in 1 hour)
                \Illuminate\Support\Facades\Cache::put($cacheKey, $data['access_token'], 3300);
                return $data['access_token'];
            }
        } catch (\Exception $e) {
            Log::error("M-Pesa token error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Initiate STK Push (Payment Request)
     */
    public function stkPush($phone, $amount, $reference, $description)
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            return [
                'success' => false,
                'message' => 'Failed to get M-Pesa access token'
            ];
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortCode . $this->passkey . $timestamp);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ])->post('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest', [
                'BusinessShortCode' => $this->shortCode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => round($amount),
                'PartyA' => $this->formatPhone($phone),
                'PartyB' => $this->shortCode,
                'PhoneNumber' => $this->formatPhone($phone),
                'CallBackURL' => $this->callbackUrl,
                'AccountReference' => $reference,
                'TransactionDesc' => $description
            ]);

            $result = $response->json();
            
            if (isset($result['ResponseCode']) && $result['ResponseCode'] == '0') {
                return [
                    'success' => true,
                    'checkout_request_id' => $result['CheckoutRequestID'],
                    'customer_message' => $result['CustomerMessage'] ?? 'Payment request sent'
                ];
            }

            return [
                'success' => false,
                'message' => $result['ResponseDescription'] ?? 'STK Push failed'
            ];
        } catch (\Exception $e) {
            Log::error("M-Pesa STK Push error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'M-Pesa service unavailable'
            ];
        }
    }

    /**
     * Handle M-Pesa Callback
     */
    public function handleCallback($callbackData)
    {
        $resultCode = $callbackData['Body']['stkCallback']['ResultCode'] ?? 99;
        $resultDesc = $callbackData['Body']['stkCallback']['ResultDesc'] ?? 'Unknown';
        $checkoutId = $callbackData['Body']['stkCallback']['CheckoutRequestID'] ?? '';
        
        // Find pending payment by checkout ID
        $pendingPayment = \App\Models\MpesaPayment::where('checkout_request_id', $checkoutId)
            ->where('status', 'pending')
            ->first();

        if (!$pendingPayment) {
            Log::warning("M-Pesa callback: Payment not found for {$checkoutId}");
            return ['success' => false, 'message' => 'Payment not found'];
        }

        if ($resultCode == 0) {
            // Success
            $items = $callbackData['Body']['stkCallback']['CallbackItem'] ?? [];
            $amount = 0;
            
            foreach ($items as $item) {
                if ($item['Name'] === 'Amount') {
                    $amount = floatval($item['Value']);
                }
            }

            // Create actual payment
            $payment = Payment::create([
                'credit_id' => $pendingPayment->credit_id,
                'amount_paid' => $amount,
                'payment_date' => now(),
                'method' => 'mpesa',
                'mpesa_code' => $callbackData['Body']['stkCallback']['MerchantRequestID'] ?? null,
            ]);

            // Update customer balance
            $credit = Credit::find($pendingPayment->credit_id);
            if ($credit) {
                $credit->customer->decrement('current_balance', $amount);
                
                if ($credit->fresh()->payments()->sum('amount_paid') >= $credit->amount) {
                    $credit->update(['status' => 'closed']);
                }
            }

            $pendingPayment->update([
                'status' => 'completed',
                'response' => json_encode($callbackData)
            ]);

            // Send email notification
            if ($credit) {
                EmailNotificationService::paymentReceived($payment, $credit);
            }

            return ['success' => true, 'payment_id' => $payment->id];

        } else {
            // Failed
            $pendingPayment->update([
                'status' => 'failed',
                'response' => json_encode($callbackData)
            ]);

            return ['success' => false, 'message' => $resultDesc];
        }
    }

    /**
     * Check payment status
     */
    public function checkStatus($checkoutId)
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            return ['success' => false, 'message' => 'Token unavailable'];
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortCode . $this->passkey . $timestamp);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ])->post('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/query', [
                'BusinessShortCode' => $this->shortCode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutId
            ]);

            return [
                'success' => true,
                'result' => $response->json()
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Format phone number to 254 format
     */
    private function formatPhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (substr($phone, 0, 1) === '0') {
            return '254' . substr($phone, 1);
        }
        
        if (substr($phone, 0, 3) === '254') {
            return $phone;
        }

        return '254' . $phone;
    }
}