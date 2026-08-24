@php
    $isLandscape = ($orientation ?? 'portrait') === 'landscape';
@endphp
<style>
    /* The reset deliberately names elements instead of using `*`: dompdf hangs the
       page box off the root frame, so a universal margin rule wipes out the @page
       margins below and the statement prints edge to edge. */
    * { box-sizing: border-box; }
    body, div, p, span, img, table, thead, tbody, tr, th, td { margin: 0; padding: 0; }

    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: {{ $isLandscape ? '8.5px' : '9.5px' }};
        line-height: 1.45;
        color: #1f2937;
    }

    /* ── Letterhead ─────────────────────────────────────────────────────── */
    .letterhead { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .letterhead td { vertical-align: top; padding: 0; border: none; }
    .letterhead-logo { width: 128px; }
    .letterhead-logo img { width: 128px; height: auto; }
    .letterhead-details { text-align: right; }

    .company-name {
        font-size: 12px; font-weight: bold; color: #A0043C;
        text-transform: uppercase; letter-spacing: 0.6px; line-height: 1.25;
    }
    .company-meta { font-size: 8px; color: #6b7280; line-height: 1.6; margin-top: 3px; }
    .company-meta .label { color: #003151; font-weight: bold; }

    .rule { height: 3px; background: #A0043C; margin-bottom: 2px; }
    .rule-thin { height: 1px; background: #003151; margin-bottom: 11px; }

    /* ── Report caption ─────────────────────────────────────────────────── */
    .report-caption { text-align: center; margin-bottom: 9px; }
    .report-title {
        font-size: 13px; font-weight: bold; color: #003151;
        text-transform: uppercase; letter-spacing: 1.2px;
    }
    .report-period { font-size: 10px; color: #374151; margin-top: 3px; }
    .report-generated { font-size: 7.5px; color: #9ca3af; margin-top: 3px; }

    /* ── Notes ──────────────────────────────────────────────────────────── */
    .basis-note {
        border-left: 3px solid #003151; background: #f8fafc;
        padding: 5px 9px; margin-bottom: 10px;
        font-size: 7.5px; color: #4b5563; line-height: 1.45;
    }
    .basis-note strong { color: #003151; }

    .warning-note {
        border-left: 3px solid #b45309; background: #fffbeb;
        padding: 7px 10px; margin-bottom: 14px;
        font-size: 8.5px; color: #7c2d12; line-height: 1.6;
    }

    /* ── Statement table ────────────────────────────────────────────────── */
    table.statement { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    table.statement th,
    table.statement td { padding: 4px 10px; text-align: left; border: none; }

    table.statement thead th {
        font-size: 8px; text-transform: uppercase; letter-spacing: 0.6px;
        color: #003151; border-bottom: 1.5px solid #003151; padding-bottom: 5px;
    }

    .text-right { text-align: right; }
    .amount { width: 22%; white-space: nowrap; }

    .section-heading {
        display: block;
        background: #A0043C; color: #fff;
        font-size: 8.5px; font-weight: bold;
        text-transform: uppercase; letter-spacing: 0.8px;
        padding: 5px 10px;
    }
    /* The maroon band has to reach both edges of the table, so the cell that holds
       it carries no padding of its own — only the gap above it. */
    tr.section-row td { padding: 8px 0 0; }
    tr.section-row:first-child td { padding-top: 0; }

    .line-row td { font-weight: bold; border-bottom: 1px solid #eef0f3; }
    .detail-row td {
        color: #4b5563; border-bottom: 1px solid #f5f6f8;
        padding-top: 3px; padding-bottom: 3px;
    }
    .detail-row td:first-child { padding-left: 26px; }
    .empty-row td { color: #9ca3af; font-style: italic; border-bottom: 1px solid #f5f6f8; }

    .total-row td {
        font-weight: bold; background: #f1f5f9;
        border-top: 1px solid #003151; border-bottom: 1px solid #003151;
    }
    .grand-total td {
        font-weight: bold; background: #003151; color: #fff; font-size: 10.5px;
        padding-top: 6px; padding-bottom: 6px;
    }
    .negative { color: #A0043C; }

    /* Data grids (ageing, client schedules) read as ruled rows, not boxed cells. */
    table.grid { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    table.grid th {
        font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left;
        color: #003151; background: #f1f5f9; padding: 6px 8px;
        border-bottom: 1.5px solid #003151;
    }
    table.grid td { padding: 5px 8px; border-bottom: 1px solid #eef0f3; }
    table.grid th.text-right { text-align: right; }
    table.grid tr.grand-total td { border-bottom: none; }

    .metrics { width: 100%; border-collapse: collapse; margin-top: 12px; page-break-inside: avoid; }
    .metrics td {
        padding: 7px 10px; border: 1px solid #e5e7eb; font-size: 9px;
    }
    .metrics td.metric-label { color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; font-size: 8px; }
    .metrics td.metric-value { font-weight: bold; color: #003151; text-align: right; }

    /* ── Sign-off ───────────────────────────────────────────────────────── */
    .signoff { width: 100%; border-collapse: collapse; margin-top: 22px; page-break-inside: avoid; }
    .signoff td { width: 50%; padding: 0 30px 0 0; border: none; vertical-align: bottom; }
    .signoff td.right { padding: 0 0 0 30px; }
    .signoff-line { border-top: 1px solid #374151; padding-top: 5px; font-size: 8.5px; color: #4b5563; }
    .signoff-date { font-size: 7.5px; color: #9ca3af; margin-top: 2px; }

    /* ── Running footer (repeats on every page) ─────────────────────────── */
    /* dompdf anchors a fixed box to the page's content area, not to the paper edge, so
       the offset has to be negative to sit the footer down inside the bottom margin —
       at 0 it would print over the last rows of the statement. */
    .page-footer {
        position: fixed;
        bottom: {{ $isLandscape ? '-10mm' : '-11mm' }};
        left: 0; right: 0;
        border-top: 1px solid #e5e7eb;
        padding-top: 4px;
        font-size: 7px; color: #9ca3af;
    }
    .page-footer table { width: 100%; border-collapse: collapse; }
    .page-footer td { border: none; padding: 0; }
    .page-footer td.right { text-align: right; }
    .page-number:after { content: counter(page); }

    /* The printable area. dompdf takes this from @page, not from body — without it the
       statement runs to the paper edge and cannot be filed or hole-punched. */
    @page {
        margin-top: {{ $isLandscape ? '14mm' : '16mm' }};
        margin-right: {{ $isLandscape ? '16mm' : '18mm' }};
        margin-bottom: {{ $isLandscape ? '16mm' : '18mm' }};
        margin-left: {{ $isLandscape ? '16mm' : '18mm' }};
    }
</style>
