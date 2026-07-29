<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 12px;
        line-height: 1.5;
        color: #222;
    }

    .container {
        padding: 20px;
    }

    .header {
        display: table;
        width: 100%;
        border-bottom: 3px solid #A0043C;
        padding-bottom: 12px;
        margin-bottom: 20px;
    }

    .header-left {
        display: table-cell;
        width: 55%;
        vertical-align: top;
    }

    .header-right {
        display: table-cell;
        width: 45%;
        vertical-align: top;
        text-align: right;
    }

    .logo {
        max-width: 150px;
        height: auto;
        margin-bottom: 6px;
    }

    .company-name {
        font-size: 14px;
        font-weight: bold;
        color: #A0043C;
    }

    .doc-title {
        font-size: 20px;
        font-weight: bold;
        color: #003151;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .doc-meta {
        font-size: 11px;
        color: #666;
    }

    .doc-meta strong {
        color: #333;
    }

    .section {
        margin-bottom: 16px;
    }

    .section h2 {
        font-size: 13px;
        color: #A0043C;
        border-bottom: 1px solid #ddd;
        padding-bottom: 4px;
        margin-bottom: 8px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }

    th, td {
        border: 1px solid #ddd;
        padding: 6px 8px;
        text-align: left;
    }

    th {
        background: #f3f4f6;
    }

    .text-right {
        text-align: right;
    }

    .valuation-box {
        background: #fdf2f6;
        border: 1px solid #A0043C;
        border-radius: 6px;
        padding: 12px;
        margin: 12px 0;
    }

    .valuation-box .row {
        display: table;
        width: 100%;
        padding: 3px 0;
    }

    .valuation-box .label {
        display: table-cell;
        width: 60%;
    }

    .valuation-box .value {
        display: table-cell;
        width: 40%;
        text-align: right;
        font-weight: bold;
    }

    .footer {
        margin-top: 30px;
        padding-top: 10px;
        border-top: 1px solid #ddd;
        font-size: 10px;
        color: #888;
        text-align: center;
    }

    .signature-block {
        display: table;
        width: 100%;
        margin-top: 40px;
    }

    .signature-column {
        display: table-cell;
        width: 50%;
        vertical-align: top;
    }

    .signature-line {
        border-top: 1px solid #333;
        margin-top: 40px;
        padding-top: 4px;
        width: 90%;
    }
</style>

<div class="header">
    <div class="header-left">
        @if (file_exists(public_path('images/kasabazaar-logo.png')))
            <img src="{{ URL::to('images/kasabazaar-logo.png') }}" alt="Logo" class="logo">
        @endif
        <div class="company-name">KasaBazaar Group Of Companies</div>
    </div>
    <div class="header-right">
        <div class="doc-title">{{ $docTitle ?? 'Document' }}</div>
        @isset($docSubtitle)
            <div class="doc-meta">{{ $docSubtitle }}</div>
        @endisset
        <div class="doc-meta">Generated: {{ now()->format('M d, Y') }}</div>
    </div>
</div>
