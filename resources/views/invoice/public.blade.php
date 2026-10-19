<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Pesanan {{ $order->order_number }} - LaundryLink</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #eef5ff; margin: 0; padding: 16px; color: #1f2937; }
        .card { max-width: 480px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(30, 64, 175, .12); }
        h1 { color: #1d4ed8; font-size: 20px; margin: 0 0 4px; }
        .muted { color: #6b7280; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { text-align: left; padding: 8px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        td.right, th.right { text-align: right; }
        .total { font-size: 18px; font-weight: bold; color: #1d4ed8; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; background: #dbeafe; color: #1e40af; font-size: 12px; margin-right: 6px; }
    </style>
</head>
<body>
<div class="card">
    <h1>LaundryLink</h1>
    <div class="muted">Pesanan {{ $order->order_number }}</div>
    <div class="muted">{{ $order->ordered_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</div>

    <p>
        <span class="badge">{{ $order->status->label() }}</span>
        <span class="badge">{{ $order->payment_status->label() }}</span>
    </p>

    <table>
        <thead>
            <tr><th>Layanan</th><th class="right">Jumlah</th><th class="right">Subtotal</th></tr>
        </thead>
        <tbody>
        @foreach ($order->items as $item)
            <tr>
                <td>{{ $item->service_name }}</td>
                <td class="right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                <td class="right">Rp{{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
            <tr>
                <td colspan="2"><strong>Total</strong></td>
                <td class="right total">Rp{{ number_format((float) $order->total, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</div>
</body>
</html>