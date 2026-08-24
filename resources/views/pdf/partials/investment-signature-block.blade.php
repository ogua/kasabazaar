@php
    // The name is snapshotted onto the investment when the agreement is generated, so
    // a later rename of the Investor never retroactively alters an issued document
    // (Investor::booted() recomposes `name` from title/first_name/other_names on every
    // save). Falls back to the live name for tranches predating the snapshot column.
    $signatureName = $signatureName ?? $investor->name;
    // The face is registered against DomPDF directly by App\Service\SignatureFont —
    // @font-face does not resolve a local file in this build and falls back silently.
    $hasSignatureFont = \App\Service\SignatureFont::isInstalled();
    $signatureFamily = \App\Service\SignatureFont::FAMILY;
@endphp

@php
    // Inline rather than a class: dompdf does not reliably apply a <style> block that
    // appears in the body, and this partial is included mid-document. Without the
    // script face installed it degrades to an oversized italic serif.
    $signatureStyle = $hasSignatureFont
        ? "font-family: '{$signatureFamily}', serif; font-style: normal; font-size: 30px;"
        : "font-family: 'DejaVu Serif', serif; font-style: italic; font-size: 20px;";
    $attestationStyle = 'font-size: 9px; color: #666; margin-top: 6px; width: 90%; line-height: 1.4;';
@endphp

<div class="signature-block">
    <div class="signature-column">
        <div style="{{ $signatureStyle }} color: #1a1a2e; min-height: 34px; padding-bottom: 2px;">{{ $signatureName }}</div>
        <div class="signature-line">
            {{ $signatureName }}<br>
            {{ $partyLabel }}<br>
            Date: {{ ($signatureDate ?? now())->format('F j, Y') }}
        </div>
        <div style="{{ $attestationStyle }}">
            Signed electronically by {{ $signatureName }} on {{ ($signatureDate ?? now())->format('F j, Y') }}.
            @if (! empty($acknowledgedAt))
                Acknowledged by return upload on {{ $acknowledgedAt->format('F j, Y') }}.
            @else
                This signature becomes effective upon the {{ strtolower($partyLabel) }} returning a copy of this
                document to the Company.
            @endif
        </div>
    </div>
    <div class="signature-column">
        @if (file_exists(public_path('images/shipping-signature.png')))
            <img src="{{ URL::to('images/shipping-signature.png') }}" alt="Authorized Signature" style="max-height: 60px;">
        @endif
        <div class="signature-line">
            Founder &amp; CVO<br>
            KasaBazaar Group Of Companies<br>
            Date: {{ ($signatureDate ?? now())->format('F j, Y') }}
        </div>
    </div>
</div>
