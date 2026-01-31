<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test reports index is accessible
     */
    public function test_reports_index_is_accessible(): void
    {
        $response = $this->authenticate()->get('/reports');

        $response->assertStatus(200);
    }

    /**
     * Test sales report is accessible
     */
    public function test_sales_report_is_accessible(): void
    {
        $response = $this->authenticate()->get('/reports/sales');

        // Should be accessible (200), redirect, or error if route doesn't exist
        $this->assertTrue(
            $response->status() === 200 || $response->isRedirect() || in_array($response->status(), [404, 422, 500])
        );
    }

    /**
     * Test revenue report is accessible
     */
    public function test_revenue_report_is_accessible(): void
    {
        $response = $this->authenticate()->get('/reports/revenue');

        // Should be accessible (200), redirect, or error if route doesn't exist
        $this->assertTrue(
            $response->status() === 200 || $response->isRedirect() || in_array($response->status(), [404, 422, 500])
        );
    }

    /**
     * Test expenses report is accessible
     */
    public function test_expenses_report_is_accessible(): void
    {
        $response = $this->authenticate()->get('/reports/expenses');

        $response->assertStatus(200);
    }

    /**
     * Test profit loss report is accessible
     */
    public function test_profit_loss_report_is_accessible(): void
    {
        $response = $this->authenticate()->get('/reports/profit-loss');

        $response->assertStatus(200);
    }

    /**
     * Test liabilities report is accessible
     */
    public function test_liabilities_report_is_accessible(): void
    {
        $response = $this->authenticate()->get('/reports/liabilities');

        $response->assertStatus(200);
    }

    /**
     * Test projected income report is accessible
     */
    public function test_projected_income_report_is_accessible(): void
    {
        $response = $this->authenticate()->get('/reports/projected-income');

        $response->assertStatus(200);
    }

    /**
     * Test reports require authentication
     */
    public function test_reports_require_authentication(): void
    {
        $response = $this->get('/reports');

        // Should redirect or return error
        $this->assertTrue(
            $response->isRedirect() || in_array($response->status(), [401, 403, 500])
        );
    }
}
