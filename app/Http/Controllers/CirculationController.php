<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CirculationController extends Controller
{
    public function getStats(Request $request)
    {
        $period = $request->query('period', 'month');
        
        // Build period filter
        $periodFilter = match($period) {
            'day' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };
        
        $periodQuery = Transaction::query()->where('created_at', '>=', $periodFilter);

        // Stats for selected period
        $stats = (clone $periodQuery)->select(
            DB::raw("SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END) as money_in"),
            DB::raw("SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) as money_out")
        )->first();

        $moneyIn = $stats->money_in ?? 0;
        $moneyOut = $stats->money_out ?? 0;

        // Get Chart Data (Last 6 Months) - always shows last 6 months
        $chartData = Transaction::select(
            DB::raw("DATE_FORMAT(created_at, '%b') as label"),
            DB::raw("YEAR(created_at) as year"),
            DB::raw("SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END) as `in`"),
            DB::raw("SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) as `out`")
        )
        ->where('created_at', '>=', Carbon::now()->subMonths(5)->startOfMonth())
        ->groupBy('label', 'year', DB::raw("MONTH(created_at)"))
        ->orderBy('year')
        ->orderBy(DB::raw("MONTH(created_at)"))
        ->get();

        // Get Ledger - FILTERED by period (10 most recent for selected period)
        $ledger = Transaction::with('customer')
            ->where('created_at', '>=', $periodFilter)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'money_in' => $moneyIn,
            'money_out' => $moneyOut,
            'net_flow' => $moneyIn - $moneyOut,
            'chart_data' => $chartData,
            'ledger' => $ledger,
            'period' => $period,
            'period_label' => match($period) {
                'day' => 'Leo',
                'week' => 'Wiki hii',
                'month' => 'Mwezi huu',
                'year' => 'Mwaka huu',
                default => 'Mwezi huu'
            }
        ]);
    }
}
