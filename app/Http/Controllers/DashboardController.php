<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Base query for Repayment Rate
        $creditQuery = Credit::query();
        if ($startDate)
            $creditQuery->where('created_at', '>=', $startDate);
        if ($endDate)
            $creditQuery->where('created_at', '<=', $endDate . ' 23:59:59');

        $totalCredits = (clone $creditQuery)->count();
        $closedCredits = (clone $creditQuery)->where('status', 'closed')->count();
        $repaymentRate = $totalCredits > 0 ? ($closedCredits / $totalCredits) * 100 : 0;

        // Portfolio at Risk (PAR): Always based on CURRENT status, but we can filter by when credit was issued
        $overdueQuery = Credit::where('status', 'active')->where('due_date', '<', now());
        if ($startDate)
            $overdueQuery->where('created_at', '>=', $startDate);
        if ($endDate)
            $overdueQuery->where('created_at', '<=', $endDate . ' 23:59:59');

        $overdueCredits = $overdueQuery->with('payments')->get();

        $par = $overdueCredits->sum(function ($credit) {
            return $credit->amount - $credit->payments->sum('amount_paid');
        });

        // Cash-Flow Summary
        $issueQuery = Credit::query();
        if ($startDate) {
            $issueQuery->where('created_at', '>=', $startDate);
        } else {
            $issueQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }
        if ($endDate)
            $issueQuery->where('created_at', '<=', $endDate . ' 23:59:59');
        $totalIssued = $issueQuery->sum('amount');

        $paymentQuery = Payment::query();
        if ($startDate) {
            $paymentQuery->where('payment_date', '>=', $startDate);
        } else {
            $paymentQuery->whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year);
        }
        if ($endDate)
            $paymentQuery->where('payment_date', '<=', $endDate);
        $totalCollected = $paymentQuery->sum('amount_paid');

        // Total Outstanding (General - usually not filtered by date, but by current state)
        $totalOutstanding = Customer::sum('current_balance');

        // Trust Score Distribution (Usually current snapshot)
        $trustScoreData = [
            '0-20' => Customer::whereBetween('trust_score', [0, 20])->count(),
            '21-40' => Customer::whereBetween('trust_score', [21, 40])->count(),
            '41-60' => Customer::whereBetween('trust_score', [41, 60])->count(),
            '61-80' => Customer::whereBetween('trust_score', [61, 80])->count(),
            '81-100' => Customer::whereBetween('trust_score', [81, 100])->count(),
        ];

        return view('dashboard', compact(
            'repaymentRate',
            'par',
            'totalIssued',
            'totalCollected',
            'totalOutstanding',
            'totalCredits',
            'trustScoreData',
            'startDate',
            'endDate'
        ));
    }
}
