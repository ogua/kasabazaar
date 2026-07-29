<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Investment Statement {{ $statement->year }}</title>
</head>
<body>
    <div class="container">
        @include('pdf.partials.investment-pdf-header', [
            'docTitle' => 'Annual Investment Statement',
            'docSubtitle' => "{$investor->name} — {$statement->year}",
        ])

        <div class="section">
            <h2>Investment Summary</h2>
            <table>
                <tr>
                    <th>Investment</th>
                    <th class="text-right">Opening Balance</th>
                    <th class="text-right">Interest Credited</th>
                    <th class="text-right">Closing Balance</th>
                </tr>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['reference'] }}</td>
                        <td class="text-right">${{ number_format($row['opening_balance'], 2) }}</td>
                        <td class="text-right">${{ number_format($row['interest_credited'], 2) }}</td>
                        <td class="text-right">${{ number_format($row['closing_balance'], 2) }}</td>
                    </tr>
                @endforeach
                <tr style="font-weight: bold; background: #fdf2f6;">
                    <td>Total Investment Value</td>
                    <td></td>
                    <td></td>
                    <td class="text-right">${{ number_format($totalValue, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>Significant Business Updates</h2>
            <div class="valuation-box">
                {!! nl2br(e($statement->business_updates ?? 'No updates provided for this period.')) !!}
            </div>
        </div>

        <div class="signature-block">
            <div class="signature-column">
                @if (file_exists(public_path('images/shipping-signature.png')))
                    <img src="{{ URL::to('images/shipping-signature.png') }}" alt="Authorized Signature" style="max-height: 60px;">
                @endif
                <div class="signature-line">
                    Founder &amp; CVO<br>
                    KasaBazaar Group Of Companies<br>
                    Date: {{ now()->format('F j, Y') }}
                </div>
            </div>
        </div>

        <div class="footer">
            This statement was generated on {{ now()->format('M d, Y') }} and reflects the investment's posted ledger
            activity through the end of {{ $statement->year }}.
        </div>
    </div>
</body>
</html>
