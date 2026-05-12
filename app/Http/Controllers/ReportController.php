<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Transaction;
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
