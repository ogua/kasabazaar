@php
    $company = config('financials.company');
    $logoPath = $company['logo'] ?? null;
    $logoFile = $logoPath ? public_path($logoPath) : null;
    /* Embedded rather than linked: dompdf resolves neither a Windows filesystem path
       nor an http:// asset reliably from a queued or console render. */
    $logo = $logoFile && is_file($logoFile)
        ? 'data:'.mime_content_type($logoFile).';base64,'.base64_encode(file_get_contents($logoFile))
        : null;
@endphp
<div class="page-footer">
    <table>
        <tr>
            <td>{{ $company['name'] }} — {{ $reportTitle }}</td>
            <td class="right">Page <span class="page-number"></span></td>
        </tr>
    </table>
</div>

<table class="letterhead">
    <tr>
        @if ($logo)
            <td class="letterhead-logo">
                <img src="{{ $logo }}" alt="">
            </td>
        @endif
        <td class="letterhead-details">
            <div class="company-name">{{ $company['name'] }}</div>
            <div class="company-meta">
                @if ($company['registration_number'])
                    Registration No. {{ $company['registration_number'] }}<br>
                @endif
                @if ($company['address'])
                    {{ $company['address'] }}<br>
                @endif
                @if ($company['phone_ghana'])
                    <span class="label">GH</span> {{ $company['phone_ghana'] }}
                @endif
                @if ($company['phone_usa'])
                    &nbsp;&nbsp;<span class="label">US</span> {{ $company['phone_usa'] }}
                @endif
                @if ($company['email'] || $company['website'])
                    <br>{{ collect([$company['email'], $company['website']])->filter()->join('  •  ') }}
                @endif
            </div>
        </td>
    </tr>
</table>

<div class="rule"></div>
<div class="rule-thin"></div>

<div class="report-caption">
    <div class="report-title">{{ $reportTitle }}</div>
    <div class="report-period">{{ $reportPeriod }}</div>
    <div class="report-generated">Generated {{ now()->format('F j, Y \a\t g:i A') }}</div>
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
