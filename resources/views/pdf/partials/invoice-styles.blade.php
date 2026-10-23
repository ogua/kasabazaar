{{-- Shared by the shipping invoice and the online payment receipt so the two documents
     look the same. Plain CSS that dompdf understands (table layout, no flex/grid). --}}
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .container {
            max-width: 100%;
            padding: 20px;
        }

        .header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #A0043C;
        }

        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }

        .logo {
            max-width: 180px;
            height: auto;
            margin-top: 5px;
            margin-left: 5px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .company-name .rose {
            color: #A0043C;
        }

        .company-info {
            font-size: 10px;
            color: #666;
            line-height: 1.6;
        }

        .company-info .location-label {
            color: #003151;
            font-weight: bold;
        }

        .amount-box {
            background: #003151;
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 10px;
        }

        .amount-label {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .amount-value {
            font-size: 24px;
            font-weight: bold;
            margin: 5px 0;
        }

        .balance {
            font-size: 11px;
            text-align: right;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            color: #003151;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .invoice-details {
            font-size: 11px;
            color: #666;
        }

        .invoice-details strong {
            color: #333;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #A0043C;
            border-bottom: 2px solid #A0043C;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .client-info {
            display: table;
            width: 100%;
        }

        .client-info-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .client-info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }

        .info-row {
            margin-bottom: 5px;
        }

        .info-label {
            font-weight: bold;
            color: #666;
        }

        .info-value {
            color: #003151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background: transparent;
            color: #333;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            border-bottom: 2px solid #A0043C;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
        }



        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .receiver-box {
            background: #003151;
            color: white;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .receiver-name {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .receiver-details {
            font-size: 10px;
            opacity: 0.9;
        }

        .receiver-table th {
            background: #003151;
            color: #fff;
        }

        .totals-section {
            margin-top: 20px;
            border-top: 2px solid #e5e7eb;
            padding-top: 15px;
            display: table;
            width: 100%;
        }

        .totals-left {
            display: table-cell;
            width: 60%;
            vertical-align: bottom;
            padding-right: 20px;
        }

        .totals-right {
            display: table-cell;
            width: 40%;
            vertical-align: bottom;
            text-align: right;
        }

        .payment-section {
            padding: 15px;
            background: #f3f4f6;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .balance-inline {
            padding: 8px 15px;
            background: #fee2e2;
            border-radius: 6px;
            border: 1px solid #dc2626;
            text-align: center;
        }

        .totals-table {
            width: 300px;
            margin-left: auto;
        }

        .totals-table td {
            padding: 6px 10px;
            border: none;
        }

        .totals-table .total-label {
            font-weight: bold;
            color: #666;
        }

        .totals-table .total-value {
            text-align: right;
            font-weight: bold;
        }

        .grand-total {
            background: #003151;
            color: white;
        }

        .grand-total td {
            font-size: 14px;
            padding: 10px;
        }



        .payment-title {
            font-size: 12px;
            font-weight: bold;
            color: #666;
            margin-bottom: 10px;
        }

        .payment-methods {
            display: table;
            width: 100%;
        }

        .payment-method {
            display: table-cell;
            width: 50%;
            padding: 10px;
        }

        .payment-method h4 {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .payment-method p {
            font-size: 11px;
            color: #666;
            margin-bottom: 3px;
        }

        .footer {
            margin-top: 30px;
            padding: 10px;
            background: #A0043C;
            color: white;
            text-align: center;
            border-radius: 6px;
        }

        .footer p {
            font-size: 12px;
            font-style: italic;
        }

        .tracking-info {
            background: #f0f9ff;
            border: 1px solid #003151;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 15px;
        }

        .tracking-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }

        .tracking-number {
            font-size: 16px;
            font-weight: bold;
            color: #003151;
        }

        .page-break {
            page-break-before: always;
        }

        .signature-section {
            margin-top: 30px;
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 48%;
            padding: 15px;
            vertical-align: top;
        }

        .signature-box.left {
            padding-right: 20px;
        }

        .signature-box.right {
            padding-left: 20px;
        }

        .signature-title {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            height: 40px;
            margin-bottom: 5px;
        }

        .signature-label {
            font-size: 10px;
            color: #666;
        }

        .signature-image {
            max-height: 60px;
            margin-bottom: 5px;
        }

        .client-note-box {
            background: #fffbeb;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 15px;
        }

        .client-note-title {
            font-size: 11px;
            font-weight: bold;
            color: #b45309;
            margin-bottom: 5px;
        }

        .client-note-content {
            font-size: 11px;
            color: #333;
        }

        .insurance-status {
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
            margin-top: 5px;
        }

        .insurance-accepted {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #16a34a;
        }

        .insurance-declined {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #dc2626;
        }

        .track-box {
            display: block;
            text-align: center;
            text-decoration: none;
            background: #fdf2f6;
            border: 2px dashed #A0043C;
            border-radius: 8px;
            padding: 14px 10px;
            margin-bottom: 20px;
        }

        .track-label {
            font-size: 15px;
            font-weight: bold;
            color: #003151;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .track-label .track-word {
            font-size: 30px;
            font-weight: 900;
            color: #A0043C;
            letter-spacing: 3px;
        }

        .track-number {
            font-size: 22px;
            font-weight: bold;
            color: #003151;
            letter-spacing: 2px;
            text-decoration: underline;
        }
