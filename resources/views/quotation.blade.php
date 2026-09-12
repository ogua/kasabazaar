<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - {{ $quotation->id }}</title>
    @include('pdf.partials.investment-pdf-header', [
        'docTitle' => 'Quotation',
        'docSubtitle' => $quotation->branch?->name,
    ])
</head>

<body>
    <div class="container">
        <div class="section">
            <h2>Client</h2>
            <p><strong>{{ $quotation->client?->name }}</strong></p>
            @if ($quotation->client?->email)
                <p>{{ $quotation->client->email }}</p>
            @endif
            @if ($quotation->client?->phone)
                <p>{{ $quotation->client->phone }}</p>
            @endif
        </div>

        <div class="section">
            <h2>Items</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th class="text-right">Unit Cost</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quotation->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td class="text-right">${{ number_format($item->item_cost, 2) }}</td>
                            <td class="text-right">${{ number_format($item->quantity * $item->item_cost, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="valuation-box">
            <div class="row">
                <div class="label">Sub Total</div>
                <div class="value">${{ number_format($quotation->sub_total, 2) }}</div>
            </div>
            <div class="row">
                <div class="label">Shipping Cost</div>
                <div class="value">${{ number_format($quotation->shipping_cost, 2) }}</div>
            </div>
            <div class="row">
                <div class="label">Total</div>
                <div class="value">${{ number_format($quotation->total, 2) }}</div>
            </div>
        </div>

        <div class="footer">
            @if ($quotation->enteredBy)
                Prepared by {{ $quotation->enteredBy->name }} &middot;
            @endif
            {{ now()->format('M d, Y') }}
        </div>
    </div>
</body>

</html>
