<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #1a1a1a; padding: 40px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .brand { font-size: 28px; font-weight: bold; color: #4f46e5; }
        .invoice-meta { text-align: right; }
        .invoice-meta h1 { font-size: 22px; color: #4f46e5; margin-bottom: 4px; }
        .invoice-meta p { color: #666; font-size: 12px; }
        .parties { display: flex; justify-content: space-between; margin-bottom: 32px; }
        .party h3 { font-size: 11px; text-transform: uppercase; color: #888; letter-spacing: 0.5px; margin-bottom: 6px; }
        .party p { line-height: 1.6; }
        .dates { display: flex; gap: 32px; margin-bottom: 32px; padding: 16px; background: #f8f7ff; border-radius: 6px; }
        .date-item label { font-size: 11px; color: #888; text-transform: uppercase; }
        .date-item p { font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead th { background: #4f46e5; color: white; padding: 10px 12px; text-align: left; font-size: 12px; }
        thead th:last-child { text-align: right; }
        tbody td { padding: 10px 12px; border-bottom: 1px solid #eee; }
        tbody td:nth-child(2), tbody td:nth-child(3), tbody td:last-child { text-align: right; }
        .totals { float: right; width: 260px; }
        .totals table td { padding: 6px 12px; }
        .totals .total-row td { font-size: 15px; font-weight: bold; color: #4f46e5; border-top: 2px solid #4f46e5; padding-top: 10px; }
        .notes { margin-top: 40px; clear: both; }
        .notes h3 { font-size: 11px; text-transform: uppercase; color: #888; margin-bottom: 6px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-sent { background: #dbeafe; color: #1e40af; }
        .badge-draft { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">InvoiceFlow</div>
            <p style="color:#666; margin-top:4px;">{{ $invoice->user->name }}</p>
        </div>
        <div class="invoice-meta">
            <h1>{{ $invoice->number }}</h1>
            <p>Data: {{ $invoice->date->format('d/m/Y') }}</p>
            @if($invoice->due_date)
            <p>Scadenza: {{ $invoice->due_date->format('d/m/Y') }}</p>
            @endif
            <br>
            <span class="badge badge-{{ $invoice->status }}">
                {{ ucfirst($invoice->status) }}
            </span>
        </div>
    </div>

    <div class="parties">
        <div class="party">
            <h3>Da</h3>
            <p><strong>{{ $invoice->user->name }}</strong></p>
            <p>{{ $invoice->user->email }}</p>
        </div>
        <div class="party" style="text-align:right">
            <h3>A</h3>
            <p><strong>{{ $invoice->client->name }}</strong></p>
            @if($invoice->client->email)<p>{{ $invoice->client->email }}</p>@endif
            @if($invoice->client->address)<p>{{ $invoice->client->address }}</p>@endif
            @if($invoice->client->city)<p>{{ $invoice->client->city }}</p>@endif
            @if($invoice->client->vat_number)<p>P.IVA: {{ $invoice->client->vat_number }}</p>@endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Descrizione</th>
                <th>Qtà</th>
                <th>Prezzo unit.</th>
                <th>Totale</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td>{{ number_format($item->quantity, 2) }}</td>
                <td>€ {{ number_format($item->unit_price, 2) }}</td>
                <td>€ {{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotale</td>
                <td style="text-align:right">€ {{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            @if($invoice->tax_rate > 0)
            <tr>
                <td>IVA ({{ $invoice->tax_rate }}%)</td>
                <td style="text-align:right">€ {{ number_format($invoice->tax_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Totale</td>
                <td style="text-align:right">€ {{ number_format($invoice->total, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($invoice->notes)
    <div class="notes">
        <h3>Note</h3>
        <p>{{ $invoice->notes }}</p>
    </div>
    @endif
</body>
</html>
