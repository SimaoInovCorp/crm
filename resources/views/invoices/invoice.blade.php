<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoiceNumber }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 13px; color: #1a1a1a; background: #fff; }
        .page { padding: 48px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        .company-name { font-size: 22px; font-weight: bold; color: #111; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { font-size: 28px; font-weight: bold; color: #4f46e5; letter-spacing: 2px; }
        .invoice-title .invoice-number { color: #666; margin-top: 4px; }
        hr { border: none; border-top: 2px solid #e5e7eb; margin: 24px 0; }
        .grid-2 { display: flex; gap: 40px; margin-bottom: 32px; }
        .grid-2 > div { flex: 1; }
        .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: #888; margin-bottom: 4px; }
        .value { font-size: 14px; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead tr { background: #f3f4f6; }
        th { padding: 10px 12px; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; font-weight: 600; }
        th.right, td.right { text-align: right; }
        td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; }
        tbody tr:last-child td { border-bottom: none; }
        .totals { display: flex; justify-content: flex-end; }
        .totals table { width: 280px; }
        .totals td { padding: 6px 12px; }
        .totals .total-row td { font-weight: bold; font-size: 15px; border-top: 2px solid #e5e7eb; }
        .footer { margin-top: 48px; border-top: 1px solid #e5e7eb; padding-top: 20px; }
        .verification { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 14px; font-size: 10px; color: #666; }
        .verification .ver-id { font-family: monospace; font-size: 10px; word-break: break-all; color: #333; margin-top: 4px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600;
            background: @if($deal->stage === 'won') #dcfce7; color: #166534; @elseif($deal->stage === 'lost') #fee2e2; color: #991b1b; @else #e0e7ff; color: #3730a3; @endif }
    </style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <div class="header">
        <div>
            <div class="company-name">{{ $tenantName }}</div>
            <div style="color:#666; margin-top:4px; font-size:12px;">{{ now()->format('d/m/Y') }}</div>
        </div>
        <div class="invoice-title">
            <h1>INVOICE</h1>
            <div class="invoice-number"># {{ $invoiceNumber }}</div>
        </div>
    </div>

    <hr>

    <!-- Bill To / Deal Info -->
    <div class="grid-2">
        <div>
            <div class="label">Bill To</div>
            <div class="value" style="font-weight:600;">{{ $person->name }}</div>
            @if($person->email)
                <div style="color:#555; margin-top:2px;">{{ $person->email }}</div>
            @endif
            @if($entity)
                <div style="color:#555; margin-top:2px;">{{ $entity->name }}</div>
            @endif
        </div>
        <div>
            <div class="label">Deal</div>
            <div class="value" style="font-weight:600;">{{ $deal->title }}</div>
            <div style="margin-top:4px;"><span class="badge">{{ strtoupper($deal->stage) }}</span></div>
            @if($deal->expected_close_date)
                <div style="color:#555; margin-top:4px; font-size:12px;">Expected close: {{ $deal->expected_close_date->format('d/m/Y') }}</div>
            @endif
        </div>
        <div>
            <div class="label">Event</div>
            <div class="value">{{ $event->title }}</div>
            <div style="color:#555; margin-top:2px; font-size:12px;">{{ $event->start_at->format('d/m/Y H:i') }}</div>
            @if($event->location)
                <div style="color:#555; font-size:12px;">{{ $event->location }}</div>
            @endif
        </div>
    </div>

    <!-- Products Table -->
    @if($products->isNotEmpty())
    <table>
        <thead>
            <tr>
                <th>Product / Service</th>
                <th class="right">Qty</th>
                <th class="right">Unit Price</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td>
                    {{ $product->name }}
                    @if($product->pivot->description ?? null)
                        <div style="font-size:11px; color:#666;">{{ $product->pivot->description }}</div>
                    @endif
                </td>
                <td class="right">{{ $product->pivot->quantity }}</td>
                <td class="right">€{{ number_format($product->pivot->price, 2, ',', '.') }}</td>
                <td class="right" style="font-weight:600;">€{{ number_format($product->pivot->quantity * $product->pivot->price, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotal</td>
                <td class="right">€{{ number_format($subtotal, 2, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>Total</td>
                <td class="right">€{{ number_format($subtotal, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>
    @else
    <div style="padding: 20px 0; color:#888;">
        <p>Deal value: <strong>€{{ number_format($deal->value, 2, ',', '.') }}</strong></p>
    </div>
    @endif

    <!-- Notes -->
    @if($deal->notes)
    <div style="margin-top:16px; padding:12px; background:#f9fafb; border-radius:4px;">
        <div class="label" style="margin-bottom:6px;">Notes</div>
        <div style="font-size:12px; color:#555; white-space:pre-line;">{{ $deal->notes }}</div>
    </div>
    @endif

    <!-- Footer / Verification -->
    <div class="footer">
        <div class="verification">
            <strong>Document Verification</strong><br>
            This invoice was generated by the CRM system and is cryptographically signed.
            <div class="ver-id">Verification ID: {{ $verificationId }}</div>
            <div style="margin-top:2px;">Generated: {{ $generatedAt }}</div>
        </div>
    </div>

</div>
</body>
</html>
