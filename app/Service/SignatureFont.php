<?php

namespace App\Service;

use Barryvdh\DomPDF\PDF;

/**
 * Installs the handwriting face used to affix an investor's name as their signature.
 *
 * DomPDF's `@font-face` does not resolve a local file in this build — every URL form
 * silently falls back to Times-Roman — so the face is registered against FontMetrics
 * directly instead. Two details matter and both were found the hard way:
 *
 *   - `fontDir` must point somewhere writable. Unconfigured, DomPDF writes to its own
 *     vendor directory and registration fails, so the options below are applied to
 *     every PDF that needs the face rather than relying on published config.
 *   - `registerFont()` wants the RAW filesystem path. A `file://` URI is rejected by
 *     its protocol rules (FontMetrics::registerFont, the allowed-protocols check).
 *
 * When no font is installed this is a no-op and the signature block degrades to an
 * italic serif — see resources/views/pdf/partials/investment-signature-block.blade.php.
 */
class SignatureFont
{
    public const FAMILY = 'SignatureScript';

    public static function path(): string
    {
        return storage_path('fonts/signature.ttf');
    }

    public static function isInstalled(): bool
    {
        return file_exists(self::path());
    }

    /**
     * PDF options required for the face to load. Merged into each document's own
     * options rather than set globally, so nothing else changes behaviour.
     *
     * @return array<string, mixed>
     */
    public static function pdfOptions(): array
    {
        return [
            'fontDir' => storage_path('fonts'),
            'fontCache' => storage_path('fonts'),
            'chroot' => [base_path()],
        ];
    }

    /**
     * Register the face on a prepared PDF. Must be called after the document is loaded
     * and before output(), which is when DomPDF resolves fonts.
     */
    public static function register(PDF $pdf): PDF
    {
        if (! self::isInstalled()) {
            return $pdf;
        }

        try {
            $pdf->getDomPDF()->getFontMetrics()->registerFont(
                ['family' => self::FAMILY, 'style' => 'normal', 'weight' => 'normal'],
                self::path()
            );
        } catch (\Throwable $e) {
            // A missing signature face must never stop an agreement being issued —
            // the block falls back to italic serif and the document is still valid.
            logger()->warning("Could not register the signature font: {$e->getMessage()}");
        }

        return $pdf;
    }
}
