<?php

namespace Tests\Feature;

use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvestorReportApiTest extends TestCase
{
    use DatabaseTransactions;

    private function investorToken(): string
    {
        $investor = Investor::create(['name' => 'Report API Investor', 'status' => 'active']);

        $user = User::create([
            'name' => 'Report API User',
            'email' => 'investor-report-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'investor_id' => $investor->id,
            'status' => 'active',
        ]);

        return $user->createToken('test')->plainTextToken;
    }

    public function test_shipments_report_returns_summary_and_working_pdf_links(): void
    {
        $token = $this->investorToken();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/investor/reports/shipments');

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['summary', 'pdf_url', 'pdf_download_url']]);

        $streamResponse = $this->get($response->json('data.pdf_url'));
        $streamResponse->assertOk();
        $streamResponse->assertHeader('Content-Type', 'application/pdf');

        $downloadResponse = $this->get($response->json('data.pdf_download_url'));
        $downloadResponse->assertOk();
        $downloadResponse->assertDownload();
    }

    public function test_income_report_returns_summary_and_working_pdf_links(): void
    {
        $token = $this->investorToken();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/investor/reports/income');

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['summary', 'pdf_url', 'pdf_download_url']]);

        $this->get($response->json('data.pdf_url'))->assertOk();
    }

    public function test_expenses_report_returns_summary_and_working_pdf_links(): void
    {
        $token = $this->investorToken();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/investor/reports/expenses');

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['summary', 'pdf_url', 'pdf_download_url']]);

        $this->get($response->json('data.pdf_url'))->assertOk();
    }

    public function test_reports_respect_custom_date_range(): void
    {
        $token = $this->investorToken();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/investor/reports/shipments?start=2025-01-01&end=2025-01-31');

        $response->assertOk();
        $response->assertJsonPath('data.summary.start_date', '2025-01-01');
        $response->assertJsonPath('data.summary.end_date', '2025-01-31');
    }

    public function test_non_investor_user_is_blocked_from_report_routes(): void
    {
        $staffUser = User::first() ?? User::factory()->create(['password' => bcrypt('password')]);
        $token = $staffUser->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/investor/reports/shipments');

        $response->assertStatus(403);
    }
}
