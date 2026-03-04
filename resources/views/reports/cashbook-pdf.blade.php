<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cashbook Report — {{ $monthName }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #222; }

        /* ── Header ─────────────────────────────────────────── */
        .page-header { text-align: center; border-bottom: 3px solid #A0043C; padding-bottom: 8px; margin-bottom: 12px; }
        .company-name { font-size: 15px; font-weight: bold; color: #A0043C; text-transform: uppercase; letter-spacing: 1px; }
        .company-sub  { font-size: 9px; color: #555; margin-top: 2px; }
        .report-title { font-size: 13px; font-weight: bold; margin-top: 6px; color: #003151; }
        .report-period{ font-size: 10px; color: #666; margin-top: 2px; }
        .generated    { font-size: 8px; color: #999; margin-top: 3px; }

        /* ── Section headings ───────────────────────────────── */
        .section-heading {
            background: #A0043C; color: #fff;
            font-size: 10px; font-weight: bold;
            padding: 4px 8px; margin: 14px 0 4px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        /* ── Summary cards ─────────────────────────────────── */
        .cards { display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
        .card {
            flex: 1; min-width: 120px;
            border: 1px solid #ddd; border-radius: 4px;
            padding: 8px 10px; background: #fafafa;
        }
        .card-label  { font-size: 8px; color: #777; text-transform: uppercase; letter-spacing: 0.4px; }
        .card-value  { font-size: 14px; font-weight: bold; margin-top: 3px; }
        .card-value.green  { color: #1a6b3a; }
        .card-value.red    { color: #A0043C; }
        .card-value.blue   { color: #003151; }
        .card-value.purple { color: #6B21A8; }

        /* ── Tables ─────────────────────────────────────────── */
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9px; }
        th {
            background: #003151; color: #fff;
            padding: 4px 6px; text-align: left;
            font-size: 8.5px; font-weight: bold;
            text-transform: uppercase; letter-spacing: 0.3px;
        }
        td { padding: 3px 6px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) td { background: #f8f8f8; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold     { font-weight: bold; }
        .text-green  { color: #1a6b3a; }
        .text-red    { color: #A0043C; }
        .text-blue   { color: #003151; }

        /* ── Totals row ─────────────────────────────────────── */
        .totals-row td { background: #003151 !important; color: #fff; font-weight: bold; border-top: 2px solid #A0043C; }

        /* ── Two-column layout ──────────────────────────────── */
        .two-col { display: flex; gap: 10px; }
        .col-half { flex: 1; }

        /* ── Bank reconciliation ────────────────────────────── */
        .recon-box {
            border: 1px solid #A0043C; border-radius: 4px;
            padding: 10px 14px; margin-top: 8px; background: #fff9f9;
        }
        .recon-box .recon-title { font-size: 11px; font-weight: bold; color: #A0043C; margin-bottom: 6px; }
        .recon-row { display: flex; justify-content: space-between; padding: 2px 0; border-bottom: 1px dotted #eee; }
        .recon-total { display: flex; justify-content: space-between; padding: 4px 0; font-weight: bold; border-top: 2px solid #A0043C; margin-top: 4px; }

        /* ── WHT table ─────────────────────────────────────── */
        .wht-table th { background: #6B21A8; }

        /* ── Signature block ────────────────────────────────── */
        .signature-block {
            margin-top: 24px; display: flex; gap: 40px;
            border-top: 1px solid #ccc; padding-top: 10px;
        }
        .sig-line { border-bottom: 1px solid #333; width: 160px; margin-bottom: 3px; }
        .sig-label { font-size: 8px; color: #666; }

        /* ── Page break ─────────────────────────────────────── */
        .page-break { page-break-before: always; }
    </style>
</head>
<body>

{{-- ── HEADER ───────────────────────────────────────────────── --}}
<div class="page-header">
    <div class="company-name">Rose Door to Door and Delivery Company</div>
    <div class="company-sub">Kasabazaar Limited · Plot 17, Block A, Adako Jachie, Ejisu · Tel: 050 9725073</div>
    <div class="report-title">CASHBOOK REPORT</div>
    <div class="report-period">{{ $monthName }}</div>
    <div class="generated">Generated: {{ now()->format('d M Y, H:i') }}</div>
</div>

{{-- ── SUMMARY CARDS ─────────────────────────────────────────── --}}
<div class="cards">
    <div class="card">
        <div class="card-label">Opening Balance (Bank)</div>
        <div class="card-value blue">₵{{ number_format($summary['opening_bank'], 2) }}</div>
    </div>
    <div class="card">
        <div class="card-label">Opening Balance (MOMO)</div>
        <div class="card-value blue">₵{{ number_format($summary['opening_momo'], 2) }}</div>
    </div>
    <div class="card">
        <div class="card-label">Closing Balance (Bank)</div>
        <div class="card-value {{ $summary['closing_bank'] >= $summary['opening_bank'] ? 'green' : 'red' }}">₵{{ number_format($summary['closing_bank'], 2) }}</div>
    </div>
    <div class="card">
        <div class="card-label">Closing Balance (MOMO)</div>
        <div class="card-value {{ $summary['closing_momo'] >= $summary['opening_momo'] ? 'green' : 'red' }}">₵{{ number_format($summary['closing_momo'], 2) }}</div>
    </div>
    <div class="card">
        <div class="card-label">Total Receipts</div>
        <div class="card-value green">₵{{ number_format($summary['total_receipts'], 2) }}</div>
    </div>
    <div class="card">
        <div class="card-label">Total Expenditures</div>
        <div class="card-value red">₵{{ number_format($summary['total_expenditures'], 2) }}</div>
    </div>
    <div class="card">
        <div class="card-label">Net Position</div>
        <div class="card-value {{ $summary['net_position'] >= 0 ? 'green' : 'red' }}">₵{{ number_format($summary['net_position'], 2) }}</div>
    </div>
    <div class="card">
        <div class="card-label">Total Entries</div>
        <div class="card-value purple">{{ $summary['entry_count'] }}</div>
    </div>
</div>

{{-- ── TWO-COLUMN: RECEIPTS + EXPENDITURES ─────────────────── --}}
<div class="two-col">
    <div class="col-half">
        <div class="section-heading">Receipts by Cost Centre</div>
        <table>
            <thead><tr><th>Category</th><th class="text-right">GH₵</th></tr></thead>
            <tbody>
                @foreach($summary['receipts_breakdown'] as $label => $amount)
                @if($amount > 0)
                <tr>
                    <td>{{ $label }}</td>
                    <td class="text-right text-green">{{ number_format($amount, 2) }}</td>
                </tr>
                @endif
                @endforeach
                <tr class="totals-row">
                    <td>TOTAL BANK DEBIT</td>
                    <td class="text-right">{{ number_format($totals['bank_debit'] ?? 0, 2) }}</td>
                </tr>
                <tr class="totals-row">
                    <td>TOTAL MOMO DEBIT</td>
                    <td class="text-right">{{ number_format($totals['momo_debit'] ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="col-half">
        <div class="section-heading">Expenditures by Cost Centre</div>
        <table>
            <thead><tr><th>Category</th><th class="text-right">GH₵</th></tr></thead>
            <tbody>
                @foreach($summary['expenditures_breakdown'] as $label => $amount)
                @if($amount > 0)
                <tr>
                    <td>{{ $label }}</td>
                    <td class="text-right text-red">{{ number_format($amount, 2) }}</td>
                </tr>
                @endif
                @endforeach
                <tr class="totals-row">
                    <td>TOTAL BANK CREDIT</td>
                    <td class="text-right">{{ number_format($totals['bank_credit'] ?? 0, 2) }}</td>
                </tr>
                <tr class="totals-row">
                    <td>TOTAL MOMO CREDIT</td>
                    <td class="text-right">{{ number_format($totals['momo_credit'] ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── BANK RECONCILIATION ───────────────────────────────────── --}}
<div class="recon-box">
    <div class="recon-title">Bank Reconciliation Statement — {{ $monthName }}</div>
    <div class="recon-row">
        <span>Balance as per Cash Book (Bank)</span>
        <span class="fw-bold">₵{{ number_format($summary['closing_bank'], 2) }}</span>
    </div>
    <div class="recon-row">
        <span>Add: Unpresented Cheques</span>
        <span>₵0.00</span>
    </div>
    <div class="recon-row">
        <span>Less: Uncredited Cheques</span>
        <span>₵0.00</span>
    </div>
    <div class="recon-total">
        <span>Balance as per Bank Statement</span>
        <span>₵{{ number_format($summary['closing_bank'], 2) }}</span>
    </div>
</div>

{{-- ── PAGE BREAK → DETAILED ENTRIES ───────────────────────── --}}
<div class="page-break"></div>

<div class="page-header">
    <div class="company-name">Rose Door to Door and Delivery Company</div>
    <div class="report-title">CASHBOOK ENTRIES — {{ $monthName }}</div>
</div>

<div class="section-heading">All Transactions</div>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>PV No</th>
            <th>Details</th>
            <th>CHQ</th>
            <th class="text-right">Bank Dr</th>
            <th class="text-right">MOMO Dr</th>
            <th class="text-right">Bank Cr</th>
            <th class="text-right">MOMO Cr</th>
            <th class="text-right">Bank Bal</th>
            <th class="text-right">MOMO Bal</th>
            <th>Cost Center</th>
        </tr>
    </thead>
    <tbody>
        @foreach($entries as $entry)
        <tr>
            <td>{{ \Carbon\Carbon::parse($entry['date'])->format('d/m') }}</td>
            <td>{{ $entry['pv_no'] ?? '' }}</td>
            <td style="max-width:120px; word-break:break-word;">{{ $entry['details'] }}</td>
            <td>{{ $entry['chq_ref'] ?? '' }}</td>
            <td class="text-right {{ $entry['bank_debit'] > 0 ? 'text-green' : '' }}">
                {{ $entry['bank_debit'] > 0 ? number_format($entry['bank_debit'], 2) : '' }}
            </td>
            <td class="text-right {{ $entry['momo_debit'] > 0 ? 'text-green' : '' }}">
                {{ $entry['momo_debit'] > 0 ? number_format($entry['momo_debit'], 2) : '' }}
            </td>
            <td class="text-right {{ $entry['bank_credit'] > 0 ? 'text-red' : '' }}">
                {{ $entry['bank_credit'] > 0 ? number_format($entry['bank_credit'], 2) : '' }}
            </td>
            <td class="text-right {{ $entry['momo_credit'] > 0 ? 'text-red' : '' }}">
                {{ $entry['momo_credit'] > 0 ? number_format($entry['momo_credit'], 2) : '' }}
            </td>
            <td class="text-right fw-bold text-blue">{{ number_format($entry['bank_balance'], 2) }}</td>
            <td class="text-right fw-bold text-blue">{{ number_format($entry['momo_balance'], 2) }}</td>
            <td style="font-size:8px;">{{ $entry['cost_center_label'] }}</td>
        </tr>
        @endforeach
        {{-- Totals row --}}
        <tr class="totals-row">
            <td colspan="4">MONTHLY TOTALS</td>
            <td class="text-right">{{ number_format($totals['bank_debit'] ?? 0, 2) }}</td>
            <td class="text-right">{{ number_format($totals['momo_debit'] ?? 0, 2) }}</td>
            <td class="text-right">{{ number_format($totals['bank_credit'] ?? 0, 2) }}</td>
            <td class="text-right">{{ number_format($totals['momo_credit'] ?? 0, 2) }}</td>
            <td class="text-right">{{ number_format($summary['closing_bank'], 2) }}</td>
            <td class="text-right">{{ number_format($summary['closing_momo'], 2) }}</td>
            <td></td>
        </tr>
    </tbody>
</table>

{{-- ── WHT BREAKDOWN (if any) ───────────────────────────────── --}}
@if(!empty($whtEntries))
<div class="section-heading" style="background:#6B21A8;">Withholding Tax Breakdown</div>
<table class="wht-table">
    <thead>
        <tr>
            <th>Staff Name</th>
            <th class="text-right">Gross Amount</th>
            <th class="text-right">Rate</th>
            <th class="text-right">WHT Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($whtEntries as $wht)
        <tr>
            <td>{{ $wht['staff_name'] }}</td>
            <td class="text-right">₵{{ number_format($wht['gross_amount'], 2) }}</td>
            <td class="text-right">{{ number_format($wht['rate'] * 100, 1) }}%</td>
            <td class="text-right text-red fw-bold">₵{{ number_format($wht['wht_amount'], 2) }}</td>
        </tr>
        @endforeach
        <tr class="totals-row">
            <td colspan="3">TOTAL WHT</td>
            <td class="text-right">₵{{ number_format(collect($whtEntries)->sum('wht_amount'), 2) }}</td>
        </tr>
    </tbody>
</table>
@endif

{{-- ── SIGNATURE BLOCK ───────────────────────────────────────── --}}
<div class="signature-block">
    <div>
        <div class="sig-line">&nbsp;</div>
        <div class="sig-label">Prepared By</div>
        <div class="sig-label" style="margin-top:2px;">Date: ___________</div>
    </div>
    <div>
        <div class="sig-line">&nbsp;</div>
        <div class="sig-label">Approved By</div>
        <div class="sig-label" style="margin-top:2px;">Date: ___________</div>
    </div>
</div>

</body>
</html>
