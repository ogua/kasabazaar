<?php

namespace Tests\Feature;

use App\Enums\InvestmentCapitalType;
use App\Enums\InvestmentConversionDirection;
use App\Enums\InvestmentConversionSourceMode;
use App\Enums\InvestmentConversionStatus;
use App\Enums\InvestmentStatus;
use App\Enums\InvestmentTransactionType;
use App\Models\Investment;
use App\Models\InvestmentConversion;
use App\Models\InvestmentConversionSource;
use App\Models\InvestmentRateSetting;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\User;
use App\Service\InvestmentAgreementService;
use App\Service\InvestmentConversionService;
use App\Service\InvestmentStatementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvestmentConversionDocumentTest extends TestCase
{
    use DatabaseTransactions;

    private const CONVERSION_DATE = '2026-01-01';

    private Investor $investor;

    private Investment $source;

    private Investment $loan;

    private InvestmentConversion $conversion;

    protected function setUp(): void
    {
        parent::setUp();

        InvestmentRateSetting::updateOrCreate(['year' => 2026], ['annual_rate' => 10]);

        $staff = User::factory()->create();
        // Investor::booted() recomposes `name` from these on every save, so setting
        // `name` directly would be overwritten with an empty string.
        $this->investor = Investor::create([
            'first_name' => 'Fiifi',
            'other_names' => 'Mensah',
            'status' => 'active',
        ]);

        $this->source = Investment::create([
            'investor_id' => $this->investor->id,
            'principal_amount' => 10000,
            'current_balance' => 12000,
            'capital_type' => InvestmentCapitalType::investment->value,
            'start_date' => '2024-01-01',
            'contract_term_months' => 12,
            'maturity_date' => '2025-01-01',
            'status' => InvestmentStatus::active->value,
            'last_interest_posted_year' => 2026,
            'last_interest_posted_through' => self::CONVERSION_DATE,
        ]);

        InvestmentTransaction::create([
            'investment_id' => $this->source->id,
            'investor_id' => $this->investor->id,
            'date' => self::CONVERSION_DATE,
            'period_start' => '2026-01-01',
            'period_end' => self::CONVERSION_DATE,
            'rate_applied' => 10,
            'type' => InvestmentTransactionType::interest_credit->value,
            'op_balance' => 10000,
            'credit' => 2000,
            'year' => 2026,
            'posted' => true,
            'posted_at' => now(),
        ]);

        $this->conversion = InvestmentConversion::create([
            'investor_id' => $this->investor->id,
            'direction' => InvestmentConversionDirection::to_loan->value,
            'conversion_date' => self::CONVERSION_DATE,
            'status' => InvestmentConversionStatus::approved->value,
            'target_contract_term_months' => 24,
            'target_payout_frequency' => 'quarterly',
            'target_annual_rate' => 9,
        ]);

        InvestmentConversionSource::create([
            'investment_conversion_id' => $this->conversion->id,
            'source_investment_id' => $this->source->id,
            'mode' => InvestmentConversionSourceMode::full->value,
        ]);

        $this->loan = app(InvestmentConversionService::class)
            ->execute($this->conversion->fresh('sources'), $staff);
    }

    public function test_the_converted_tranche_agreement_renders_with_a_novation_preamble(): void
    {
        $output = InvestmentAgreementService::generatePdf($this->loan->fresh())->output();

        $this->assertNotEmpty($output);
        $this->assertStringStartsWith('%PDF', $output, 'DomPDF must produce a real PDF, not an error page.');
    }

    public function test_the_agreement_snapshots_the_signature_name_so_a_later_rename_cannot_restate_it(): void
    {
        InvestmentAgreementService::generatePdf($this->loan->fresh());

        $this->loan->refresh();
        $this->assertSame('Fiifi Mensah', $this->loan->agreement_signature_name);
        $this->assertNotNull($this->loan->agreement_signature_affixed_at);

        // Renaming the investor must not change the name on the already-issued document.
        $this->investor->update(['title' => 'Dr', 'first_name' => 'Kwame', 'other_names' => 'Boateng']);
        $this->assertSame('Dr Kwame Boateng', $this->investor->fresh()->name);

        InvestmentAgreementService::generatePdf($this->loan->fresh());

        $this->assertSame('Fiifi Mensah', $this->loan->fresh()->agreement_signature_name);
    }

    /**
     * DomPDF falls back to a default face silently when a font cannot be loaded, so a
     * PDF that merely renders proves nothing. This asserts the script face is actually
     * embedded in the output.
     */
    public function test_the_signature_font_is_embedded_in_the_agreement(): void
    {
        $font = storage_path('fonts/signature.ttf');

        if (! file_exists($font)) {
            $this->markTestSkipped('No signature font installed — the block degrades to italic serif by design.');
        }

        $output = InvestmentAgreementService::generatePdf($this->loan->fresh())->output();

        $this->assertStringStartsWith('%PDF', $output);
        $this->assertMatchesRegularExpression(
            '/GreatVibes|SignatureScript/i',
            $output,
            'The script face must be embedded, not silently substituted.'
        );
    }

    public function test_the_combined_agreement_renders_and_excludes_converted_tranches_from_its_totals(): void
    {
        $pdf = InvestmentAgreementService::generateCombinedPdf($this->investor->fresh());

        $this->assertStringStartsWith('%PDF', $pdf->output());

        // The live position is the loan alone. Counting the settled source tranche too
        // would state a principal the investor does not hold.
        $liveTotal = $this->investor->fresh()->investments()
            ->excludingConverted()
            ->sum('principal_amount');

        $this->assertEqualsWithDelta(12000.00, (float) $liveTotal, 0.01);
    }

    public function test_the_account_statement_still_lists_the_converted_tranche_as_history(): void
    {
        $output = InvestmentStatementService::generatePdf($this->investor->fresh())->output();

        $this->assertStringStartsWith('%PDF', $output);

        // The settled tranche is still a row on the statement — it is a history
        // document, and dropping it would leave an unexplained balance drop — but its
        // capital is counted once, on the successor.
        $this->assertSame(InvestmentStatus::converted, $this->source->fresh()->status);
        $this->assertTrue(
            $this->investor->fresh()->investments->contains(fn ($i) => $i->id === $this->source->id)
        );
    }
}
