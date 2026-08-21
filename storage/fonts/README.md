# Signature font

Drop a licensed handwriting/script TrueType font here, named exactly:

    storage/fonts/signature.ttf

It is used by `resources/views/pdf/partials/investment-signature-block.blade.php` to render
the investor's full legal name as their signature on investment and loan agreements.

## Until the file is present

The signature block degrades to a large italic serif and the agreement still renders
correctly — the same `file_exists()` guard the PDF header uses for the company logo and
countersignature. Nothing breaks; the signature just does not look handwritten.

## Licensing

The font must be licensed for embedding in PDFs (most commercial licences call this
"document embedding" or "ePub/PDF embedding"). Free options with permissive licences
include the SIL Open Font License families — e.g. Great Vibes, Dancing Script, Allura.

## After adding it

Confirm it actually rendered — DomPDF falls back to a default face silently rather than
erroring:

    php artisan test --filter=InvestmentConversionDocumentTest

then open a generated agreement and look at the signature line.

## Deployment

This directory is git-ignored apart from these two files, so the font must be copied to
the server as part of the release (or committed deliberately if your licence allows it).
