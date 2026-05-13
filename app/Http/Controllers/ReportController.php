<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\Credit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function getDebtorsReport(Request $request)
    {
        $debtors = Customer::where('current_balance', '>', 0)
            ->orderBy('current_balance', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $debtors
        ]);
    }

    public function getTrends(Request $request)
    {
        $months = $request->input('months', 6);
        $startDate = Carbon::now()->subMonths($months);

        // Collection trend data (monthly)
        $collectionTrend = Transaction::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
            DB::raw("SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END) as collected"),
            DB::raw("SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) as issued")
        )
        ->where('created_at', '>=', $startDate)
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        // Stats metrics
        $totalIssued = Transaction::where('direction', 'out')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        $totalCollected = Transaction::where('direction', 'in')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        $defaultRate = Credit::where('status', 'active')
            ->where('due_date', '<', Carbon::today())
            ->count();

        $totalCredits = Credit::where('status', 'active')->count();
        $defaultPercentage = $totalCredits > 0 ? round(($defaultRate / $totalCredits) * 100, 1) : 0;

        // Calculate growth
        $previousMonthIssued = Transaction::where('direction', 'out')
            ->where('created_at', '>=', Carbon::now()->subMonths($months * 2))
            ->where('created_at', '<', $startDate)
            ->sum('amount');

        $growthRate = $previousMonthIssued > 0 
            ? round((($totalIssued - $previousMonthIssued) / $previousMonthIssued) * 100, 1) 
            : 0;

        // Business health score (calculated from recovery rate)
        $recoveryRate = $totalIssued > 0 
            ? round(($totalCollected / $totalIssued) * 100, 1) 
            : 0;

        $healthScore = min(100, $recoveryRate);

        // New customers this period
        $newCustomers = Customer::where('created_at', '>=', $startDate)->count();

        // Money circulation ratio
        $circulationRatio = $totalIssued > 0 
            ? round($totalCollected / $totalIssued, 1) 
            : 0;

        // Area/Segment analysis (based on customer locations)
        $segments = Customer::select(
            'location',
            DB::raw("COUNT(*) as customer_count"),
            DB::raw("SUM(current_balance) as total_balance")
        )
        ->whereNotNull('location')
        ->where('current_balance', '>', 0)
        ->groupBy('location')
        ->orderBy('total_balance', 'desc')
        ->limit(10)
        ->get()
        ->map(function ($seg) {
            return [
                'area' => $seg->location ?? 'Maeneo mengine',
                'customers' => $seg->customer_count,
                'total_balance' => (float) $seg->total_balance,
                'health' => $seg->total_balance > 5000000 ? 'Mzuri' : ($seg->total_balance > 1000000 ? 'Tahadhari' : 'Chini'),
                'health_class' => $seg->total_balance > 5000000 ? 'text-emerald-500' : ($seg->total_balance > 1000000 ? 'text-amber-500' : 'text-rose-500'),
            ];
        });

        return response()->json([
            'collection_trend' => $collectionTrend,
            'stats' => [
                'capital_growth' => $growthRate,
                'default_rate' => $defaultPercentage,
                'new_customers' => $newCustomers,
                'money_circulation' => $circulationRatio,
            ],
            'health_score' => (int) $healthScore,
            'segments' => $segments,
            'period' => $months . ' months',
        ]);
    }

    public function downloadPdfReport(Request $request)
    {
        $type = $request->query('type', 'debtors');
        $customers = [];
        $title = "Ripoti ya Biashara";

        if ($type === 'debtors') {
            $customers = Customer::where('current_balance', '>', 0)->get();
            $title = "Orodha ya Wadaiwa";
        }

        $data = [
            'title' => $title,
            'date' => date('d/m/Y'),
            'customers' => $customers,
            'total_debt' => $customers->sum('current_balance')
        ];

        $pdf = Pdf::loadView('reports.debtors', $data);
        return $pdf->download('ripoti_ya_madeni_' . date('Ymd') . '.pdf');
    }
}
