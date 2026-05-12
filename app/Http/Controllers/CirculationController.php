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
        $query = Transaction::query();

        if ($period === 'day') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($period === 'week') {
            $query->where('created_at', '>=', Carbon::now()->startOfWeek());
        } elseif ($period === 'month') {
            $query->where('created_at', '>=', Carbon::now()->startOfMonth());
        } elseif ($period === 'year') {
            $query->where('created_at', '>=', Carbon::now()->startOfYear());
        }

        $stats = $query->select(
            DB::raw("SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END) as money_in"),
            DB::raw("SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) as money_out")
        )->first();

        $moneyIn = $stats->money_in ?? 0;
        $moneyOut = $stats->money_out ?? 0;

        // Get Chart Data (Last 6 Months)
        $chartData = Transaction::select(
            DB::raw("DATE_FORMAT(created_at, '%b') as label"),
            DB::raw("SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END) as `in`"),
            DB::raw("SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) as `out`")
        )
        ->where('created_at', '>=', Carbon::now()->subMonths(5)->startOfMonth())
        ->groupBy('label', DB::raw("MONTH(created_at)"))
        ->orderBy(DB::raw("MONTH(created_at)"))
        ->get();

        // Get Recent Ledger
        $ledger = Transaction::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'money_in' => $moneyIn,
            'money_out' => $moneyOut,
            'net_flow' => $moneyIn - $moneyOut,
            'chart_data' => $chartData,
            'ledger' => $ledger
        ]);
    }
}
