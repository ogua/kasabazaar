@php
    // The name is snapshotted onto the investment when the agreement is generated, so
    // a later rename of the Investor never retroactively alters an issued document
    // (Investor::booted() recomposes `name` from title/first_name/other_names on every
    // save). Falls back to the live name for tranches predating the snapshot column.
    $signatureName = $signatureName ?? $investor->name;
    $signatureFont = storage_path('fonts/signature.ttf');
    $hasSignatureFont = file_exists($signatureFont);
@endphp

@if ($hasSignatureFont)
    <style>
        @font-face {
            font-family: 'SignatureScript';
            font-style: normal;
            font-weight: 400;
            src: url('{{ $signatureFont }}') format('truetype');
        }
    </style>
@endif

<style>
    /* Without the licensed script face installed, this degrades to an oversized
       italic serif rather than silently rendering the name in body text — the same
       file_exists() guard the header uses for the company logo and countersignature. */
    .affixed-signature {
        font-family: {{ $hasSignatureFont ? "'SignatureScript', " : '' }}'DejaVu Serif', serif;
        font-style: {{ $hasSignatureFont ? 'normal' : 'italic' }};
        font-size: {{ $hasSignatureFont ? '26px' : '20px' }};
        color: #1a1a2e;
        min-height: 34px;
        padding-bottom: 2px;
    }

    .signature-attestation {
        font-size: 9px;
        color: #666;
        margin-top: 6px;
        width: 90%;
        line-height: 1.4;
    }
</style>

<div class="signature-block">
    <div class="signature-column">
        <div class="affixed-signature">{{ $signatureName }}</div>
        <div class="signature-line">
            {{ $signatureName }}<br>
            {{ $partyLabel }}<br>
            Date: {{ ($signatureDate ?? now())->format('F j, Y') }}
        </div>
        <div class="signature-attestation">
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
