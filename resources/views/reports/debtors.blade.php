<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #3525cd; padding-bottom: 10px; }
        .title { font-size: 20px; font-weight: bold; color: #3525cd; }
        .date { font-size: 10px; color: #777; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #f7f9fb; color: #3525cd; text-align: left; padding: 10px; border-bottom: 1px solid #eee; }
        td { padding: 10px; border-bottom: 1px solid #f9f9f9; }
        .total-row { background-color: #f0f0ff; font-weight: bold; }
        .text-right { text-align: right; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #aaa; padding: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">SVS CREDIT SYSTEM</div>
        <div class="subtitle">{{ $title }}</div>
        <div class="date">Imetolewa Tarehe: {{ $date }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Mteja / Biashara</th>
                <th>Simu</th>
                <th>Eneo</th>
                <th class="text-right">Deni (TZS)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customers as $customer)
            <tr>
                <td>
                    <strong>{{ $customer->name }}</strong><br>
                    <small>{{ $customer->business_name }}</small>
                </td>
                <td>{{ $customer->phone }}</td>
                <td>{{ $customer->location }}</td>
                <td class="text-right">{{ number_format($customer->current_balance) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-right">JUMLA KUU</td>
                <td class="text-right">TZS {{ number_format($total_debt) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Huu ni mfumo wa SVS Credit - Mabibo Sector. Ripoti hii imetengenezwa kidijitali.
    </div>
</body>
</html>
