<!DOCTYPE html>
<html lang="en">
@php
    $hasInvestments = $investments->where('capital_type', \App\Enums\InvestmentCapitalType::investment)->isNotEmpty();
    $hasLoans = $investments->where('capital_type', \App\Enums\InvestmentCapitalType::loan)->isNotEmpty();
    $docTitle = $hasInvestments && $hasLoans
        ? 'Investment & Loan Agreement'
        : ($hasLoans ? 'Loan Agreement' : 'Investment Agreement');
@endphp
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #A0043C;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #A0043C;
        }
        .content {
            padding: 20px 0;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">KasaBazaar Group Of Companies</div>
    </div>

    <div class="content">
        <p>Dear {{ $investor->name }},</p>

        <p>Please find attached your {{ strtolower($docTitle) }}, covering
            {{ $investments->count() === 1 ? 'your tranche' : 'all ' . $investments->count() . ' of your tranches' }}
            totaling ${{ number_format($totalPrincipal, 2) }} in principal.</p>

        <p>If you have any questions about this agreement, please contact us.</p>
    </div>

    <div class="footer">
        <p>This is an automated message. Please do not reply directly to this email.</p>
    </div>
</body>
</html>
