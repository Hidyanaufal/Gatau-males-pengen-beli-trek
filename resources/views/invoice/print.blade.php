<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #1f2937; margin: 0; background: #eef5ff; }
        .page { max-width: 190mm; margin: 12px auto; background: #fff; padding: 12mm; border-radius: 8px; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #2563eb; padding-bottom: 10px; }
        .brand { color: #1d4ed8; font-size: 26px; font-weight: bold; margin: 0; }
        .muted { color: #6b7280; font-size: 12px; }
        .title { text-align: right; }
        .title h2 { margin: 0; font-size: 20px; color: #1d4ed8; }
        .info { display: flex; justify-content: space-between; gap: 16px; margin-top: 16px; font-size: 13px; }
        .info div { flex: 1; }
        .info h4 { margin: 0 0 4px; font-size: 12px; color: #6b7280; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; font-size: 13px; }
        th { background: #dbeafe; color: #1e40af; text-align: left; padding: 8px; }
        td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .right { text-align: right; }
        .total-row td { font-size: 16px; font-weight: bold; color: #1d4ed8; border-bottom: 0; }
        .status { display: inline-block; padding: 2px 10px; border-radius: 999px; background: #dbeafe; color: #1e40af; font-size: 12px; margin-right: 4px; }
        .pay { display: flex; align-items: center; gap: 16px; margin-top: 24px; border: 2px dashed #2563eb; border-radius: 10px; padding: 14px; }
        .pay svg { width: 40mm; height: 40mm; flex-shrink: 0; }
        .pay h3 { margin: 0 0 6px; color: #1d4ed8; font-size: 16px; }
        .pay p { margin: 0 0 4px; font-size: 12px; }
        .sim { margin-top: 16px; font-size: 11px; color: #92400e; background: #fffbeb; padding: 6px 8px; border-radius: 6px; }
        .actions { max-width: 190mm; margin: 12px auto 0; text-align: right; }
        .btn { background: #2563eb; color: #fff; border: 0; border-radius: 8px; padding: 10px 18px; font-size: 14px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .page { margin: 0; padding: 0; max-width: none; border-radius: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
<div class="actions">
    <button class="btn" onclick="window.print()">Cetak invoice</button>
</div>

<div class="page">
    <div class="head">
        <div>
            <p class="brand">LaundryLink</p>
            <div class="muted">Layanan laundry antar-jemput dan datang sendiri</div>
        </div>
        <div class="title">
            <h2>INVOICE</h2>
            <div class="muted">{{ $order->order_number }}</div>
            <div class="muted">{{ $order->ordered_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</div>
        </div>
    </div>

    <div class="info">
        <div>
            <h4>Pelanggan</h4>
            <strong>{{ $order->user?->name }}</strong><br>
            @if ($order->user?->phone) {{ $order->user->phone }}<br> @endif
            @if ($order->address) {{ $order->address }} @endif
        </div>
        <div>
            <h4>Pesanan</h4>
            {{ $order->fulfillment_type === 'pickup_delivery' ? 'Antar-jemput' : 'Datang sendiri' }}<br>
            @if ($order->scheduled_at) Jadwal: {{ $order->scheduled_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB<br> @endif
            @if ($order->notes) Catatan: {{ $order->notes }} @endif
        </div>
        <div>
            <h4>Status</h4>
            <span class="status">{{ $order->status->label() }}</span>
            <span class="status">{{ $order->payment_status->label() }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Layanan</th>
                <th class="right">Harga</th>
                <th class="right">Jumlah</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($order->items as $item)
            <tr>
                <td>{{ $item->service_name }}</td>
                <td class="right">Rp{{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                <td class="right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                <td class="right">Rp{{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
            <tr class="total-row">
                <td colspan="3" class="right">Total</td>
                <td class="right">Rp{{ number_format((float) $order->total, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="pay">
        {!! $qrSvg !!}
        <div>
            <h3>Scan untuk membayar</h3>
            <p>Scan kode QR ini dengan kamera HP untuk membuka halaman pesanan dan membayar dengan QRIS.</p>
            <p class="muted">QR ini tetap sama untuk pesanan {{ $order->order_number }}.</p>
        </div>
    </div>

    <div class="sim">Mode simulasi: pembayaran memakai Midtrans Sandbox, tidak ada uang sungguhan.</div>
</div>
</body>
</html>