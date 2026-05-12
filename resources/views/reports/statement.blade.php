<!DOCTYPE html>
<html>

<head>
    <title>Customer Statement - {{ $customer->name }}</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            color: #333;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
        }

        .business-name {
            font-size: 24px;
            font-bold: true;
            color: #1e3a8a;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 18px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .customer-info {
            margin-bottom: 30px;
        }

        .customer-info h2 {
            margin-bottom: 5px;
            color: #111;
        }

        .customer-info p {
            margin: 0;
            color: #555;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }

        th {
            background-color: #f3f4f6;
            color: #4b5563;
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #f3f4f6;
        }

        .amount {
            text-align: right;
        }

        .status {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }

        .summary {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .total-label {
            font-weight: bold;
        }

        .total-value {
            font-weight: bold;
            color: #dc2626;
            font-size: 18px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #aaa;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="business-name">{{ config('app.name') }}</div>
        <div class="report-title">Credit Statement</div>
    </div>

    <div class="customer-info">
        <h2>{{ $customer->name }}</h2>
        <p>Phone: {{ $customer->phone }}</p>
        <p>Statement Period:
            @if($startDate || $endDate)
                {{ $startDate ?? 'Beginning' }} to {{ $endDate ?? 'Today' }}
            @else
                All History
            @endif
        </p>
        <p>Generated: {{ now()->format('F d, Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Type</th>
                <th>Due Date</th>
                <th class="amount">Issue Amt</th>
                <th class="amount">Paid</th>
                <th class="amount">Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($credits as $credit)
                <tr>
                    <td>{{ $credit->created_at->format('M d, Y') }}</td>
                    <td>{{ $credit->description ?: 'Credit ID #' . $credit->id }}</td>
                    <td>{{ ucfirst($credit->type) }}</td>
                    <td>{{ $credit->due_date->format('M d, Y') }}</td>
                    <td class="amount">{{ number_format($credit->amount) }}</td>
                    <td class="amount">{{ number_format($credit->payments->sum('amount_paid')) }}</td>
                    <td class="amount">{{ number_format($credit->balance) }}</td>
                    <td>
                        <span class="status" style="color: {{ $credit->status == 'active' ? '#dc2626' : '#10b981' }}">
                            {{ $credit->status }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <div style="float: right; width: 250px;">
            <div class="summary-row">
                <span class="total-label text-red-600">Total Outstanding:</span>
                <span class="total-value">{{ number_format($customer->current_balance) }} TZS</span>
            </div>
            <div class="summary-row" style="margin-top: 10px; font-size: 12px; color: #666;">
                <span>Trust Score: {{ $customer->trust_score }}</span>
            </div>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="footer">
        Generated automatically by {{ config('app.name') }} Ledger System. &copy; {{ date('Y') }}
    </div>
</body>

</html>