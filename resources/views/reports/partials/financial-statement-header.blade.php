<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 10px; color: #222; }

    .page-header { text-align: center; border-bottom: 3px solid #A0043C; padding-bottom: 8px; margin-bottom: 14px; }
    .company-name { font-size: 15px; font-weight: bold; color: #A0043C; text-transform: uppercase; letter-spacing: 1px; }
    .company-sub  { font-size: 9px; color: #555; margin-top: 2px; }
    .report-title { font-size: 13px; font-weight: bold; margin-top: 6px; color: #003151; }
    .report-period{ font-size: 10px; color: #666; margin-top: 2px; }
    .generated    { font-size: 8px; color: #999; margin-top: 3px; }

    .section-heading {
        background: #A0043C; color: #fff;
        font-size: 10px; font-weight: bold;
        padding: 4px 8px; margin: 14px 0 4px;
        text-transform: uppercase; letter-spacing: 0.5px;
    }

    table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    th, td { border: 1px solid #ddd; padding: 5px 8px; text-align: left; }
    th { background: #f3f4f6; font-size: 9px; text-transform: uppercase; letter-spacing: 0.3px; }
    .text-right { text-align: right; }
    .line-row td { font-weight: bold; background: #fafafa; }
    .detail-row td { padding-left: 22px; color: #555; border-top: none; }
    .total-row td { font-weight: bold; background: #eef2f7; border-top: 2px solid #003151; }
    .grand-total td { font-weight: bold; background: #003151; color: #fff; font-size: 11px; }
    .negative { color: #A0043C; }

    .basis-note {
        border: 1px solid #ddd; background: #fafafa; border-radius: 4px;
        padding: 8px 10px; margin-bottom: 12px; font-size: 8.5px; color: #555; line-height: 1.5;
    }
    .basis-note strong { color: #003151; }

    .warning-note {
        border: 1px solid #b45309; background: #fffbeb; border-radius: 4px;
        padding: 8px 10px; margin-bottom: 12px; font-size: 9px; color: #7c2d12; line-height: 1.5;
    }

    .signoff { margin-top: 40px; }
    .signoff-col { display: inline-block; width: 45%; vertical-align: top; }
    .signoff-line { border-top: 1px solid #333; margin-top: 34px; padding-top: 4px; font-size: 9px; }
</style>

<div class="page-header">
    <div class="company-name">{{ config('financials.company.name') }}</div>
    @if (config('financials.company.registration_number'))
        <div class="company-sub">Registration No. {{ config('financials.company.registration_number') }}</div>
    @endif
    <div class="report-title">{{ $reportTitle }}</div>
    <div class="report-period">{{ $reportPeriod }}</div>
    <div class="generated">Generated {{ now()->format('F j, Y \a\t g:i A') }}</div>
</div>

<div class="basis-note">
    <strong>Basis of preparation.</strong>
    Presented in {{ $statement['currency'] }}.
    @if (($statement['source'] ?? null) === 'manual')
        Figures for this year were entered from the accounts prepared by the company's accountant;
        this system does not hold the underlying transactions for the period.
    @else
        Figures are derived from the company's cashbook, shipment, expense, payroll and investor records.
        Amounts held in Ghana Cedis have been translated at the year-end rate of
        GHS {{ number_format($statement['exchange_rate'] ?? 0, 4) }} to USD 1.00; records that carry their
        own exchange rate at the date of the transaction are stated at that rate.
    @endif
</div>
