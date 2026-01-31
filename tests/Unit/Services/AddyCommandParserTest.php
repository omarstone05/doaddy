<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Addy\AddyCommandParser;

class AddyCommandParserTest extends TestCase
{
    protected AddyCommandParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new AddyCommandParser();
    }

    // ==================== Greeting Tests ====================

    /** @test */
    public function it_detects_greeting_hi(): void
    {
        $result = $this->parser->parse('hi');

        $this->assertEquals('greeting', $result['intent']);
        $this->assertEquals(1.0, $result['confidence']);
    }

    /** @test */
    public function it_detects_greeting_hello(): void
    {
        $result = $this->parser->parse('Hello there');

        $this->assertEquals('greeting', $result['intent']);
    }

    /** @test */
    public function it_detects_greeting_good_morning(): void
    {
        $result = $this->parser->parse('Good morning Addy');

        $this->assertEquals('greeting', $result['intent']);
    }

    /** @test */
    public function it_detects_greeting_good_afternoon(): void
    {
        $result = $this->parser->parse('good afternoon');

        $this->assertEquals('greeting', $result['intent']);
    }

    /** @test */
    public function it_detects_greeting_hey(): void
    {
        $result = $this->parser->parse('hey');

        $this->assertEquals('greeting', $result['intent']);
    }

    // ==================== Cash Query Tests ====================

    /** @test */
    public function it_detects_cash_query(): void
    {
        $result = $this->parser->parse('How much cash do we have?');

        $this->assertEquals('query_cash', $result['intent']);
        $this->assertEquals(0.9, $result['confidence']);
    }

    /** @test */
    public function it_detects_balance_query(): void
    {
        $result = $this->parser->parse('What is our balance?');

        $this->assertEquals('query_cash', $result['intent']);
    }

    /** @test */
    public function it_detects_money_query(): void
    {
        $result = $this->parser->parse('How much money do we have?');

        $this->assertEquals('query_cash', $result['intent']);
    }

    /** @test */
    public function it_detects_cash_position_query(): void
    {
        $result = $this->parser->parse('What is our cash position?');

        $this->assertEquals('query_cash', $result['intent']);
    }

    // ==================== Budget Query Tests ====================

    /** @test */
    public function it_detects_budget_query(): void
    {
        $result = $this->parser->parse('How is our budget doing?');

        $this->assertEquals('query_budget', $result['intent']);
    }

    /** @test */
    public function it_detects_budgets_query(): void
    {
        $result = $this->parser->parse('Show me our budgets');

        $this->assertEquals('query_budget', $result['intent']);
    }

    /** @test */
    public function it_detects_spending_limit_query(): void
    {
        $result = $this->parser->parse('What is our spending limit?');

        $this->assertEquals('query_budget', $result['intent']);
    }

    // ==================== Expense Query Tests ====================

    /** @test */
    public function it_detects_expense_query(): void
    {
        $result = $this->parser->parse('What are our expenses this month?');

        $this->assertEquals('query_expenses', $result['intent']);
    }

    /** @test */
    public function it_detects_spending_query(): void
    {
        $result = $this->parser->parse('How much are we spending?');

        $this->assertEquals('query_expenses', $result['intent']);
    }

    /** @test */
    public function it_detects_expense_query_as_action_with_specific_category(): void
    {
        $result = $this->parser->parse('How much did we spend on coffee?');

        $this->assertEquals('action', $result['intent']);
        $this->assertEquals('generate_report', $result['action_type']);
        $this->assertEquals('expenses', $result['parameters']['type']);
        $this->assertEquals('coffee', $result['parameters']['category']);
    }

    /** @test */
    public function it_detects_expense_query_with_specific_date(): void
    {
        $result = $this->parser->parse('What did we record on 14th of november last year?');

        $this->assertEquals('action', $result['intent']);
        $this->assertEquals('generate_report', $result['action_type']);
        $this->assertArrayHasKey('specific_date', $result['parameters']);
    }

    // ==================== Transaction Query Tests ====================

    /** @test */
    public function it_detects_transaction_query(): void
    {
        $result = $this->parser->parse('Show me recent transactions');

        $this->assertEquals('query_transactions', $result['intent']);
    }

    /** @test */
    public function it_detects_latest_transactions_query(): void
    {
        $result = $this->parser->parse('What are the latest transactions?');

        $this->assertEquals('query_transactions', $result['intent']);
    }

    // ==================== Invoice Query Tests ====================

    /** @test */
    public function it_detects_invoice_query(): void
    {
        $result = $this->parser->parse('Show me our invoices');

        $this->assertEquals('query_invoices', $result['intent']);
    }

    /** @test */
    public function it_detects_overdue_invoice_query(): void
    {
        $result = $this->parser->parse('Show me overdue invoices');

        $this->assertEquals('query_invoices', $result['intent']);
        $this->assertEquals('overdue', $result['type']);
    }

    /** @test */
    public function it_detects_pending_invoice_query(): void
    {
        $result = $this->parser->parse('What invoices are pending?');

        $this->assertEquals('query_invoices', $result['intent']);
        $this->assertEquals('pending', $result['type']);
    }

    /** @test */
    public function it_detects_paid_invoice_query(): void
    {
        $result = $this->parser->parse('Show paid invoices');

        $this->assertEquals('query_invoices', $result['intent']);
        $this->assertEquals('paid', $result['type']);
    }

    /** @test */
    public function it_detects_general_invoice_query(): void
    {
        $result = $this->parser->parse('List all invoices');

        $this->assertEquals('query_invoices', $result['intent']);
        $this->assertEquals('all', $result['type']);
    }

    // ==================== Sales Query Tests ====================

    /** @test */
    public function it_detects_sales_query(): void
    {
        $result = $this->parser->parse('How are our sales?');

        $this->assertEquals('query_sales', $result['intent']);
    }

    /** @test */
    public function it_detects_revenue_query(): void
    {
        $result = $this->parser->parse('What is our revenue this month?');

        $this->assertEquals('query_sales', $result['intent']);
    }

    // ==================== Team Query Tests ====================

    /** @test */
    public function it_detects_team_query(): void
    {
        $result = $this->parser->parse('Who is on the team?');

        $this->assertEquals('query_team', $result['intent']);
    }

    /** @test */
    public function it_detects_staff_query(): void
    {
        $result = $this->parser->parse('Show me the staff');

        $this->assertEquals('query_team', $result['intent']);
    }

    /** @test */
    public function it_detects_employees_query(): void
    {
        $result = $this->parser->parse('How many employees do we have?');

        $this->assertEquals('query_team', $result['intent']);
    }

    // ==================== Payroll Query Tests ====================

    /** @test */
    public function it_detects_payroll_query(): void
    {
        $result = $this->parser->parse('What is our payroll this month?');

        $this->assertEquals('query_payroll', $result['intent']);
    }

    /** @test */
    public function it_detects_salary_query(): void
    {
        $result = $this->parser->parse('What are the salaries?');

        $this->assertEquals('query_payroll', $result['intent']);
    }

    // ==================== Inventory Query Tests ====================

    /** @test */
    public function it_detects_inventory_query(): void
    {
        $result = $this->parser->parse('How is our inventory?');

        $this->assertEquals('query_inventory', $result['intent']);
    }

    /** @test */
    public function it_detects_stock_query(): void
    {
        $result = $this->parser->parse('What is our stock level?');

        $this->assertEquals('query_inventory', $result['intent']);
    }

    /** @test */
    public function it_detects_products_query(): void
    {
        $result = $this->parser->parse('Show me our products');

        $this->assertEquals('query_inventory', $result['intent']);
    }

    // ==================== Focus Query Tests ====================

    /** @test */
    public function it_detects_focus_query(): void
    {
        $result = $this->parser->parse('What should I focus on?');

        $this->assertEquals('query_focus', $result['intent']);
    }

    /** @test */
    public function it_detects_priority_query(): void
    {
        $result = $this->parser->parse('What are my priorities?');

        $this->assertEquals('query_focus', $result['intent']);
    }

    /** @test */
    public function it_detects_what_should_i_do_query(): void
    {
        $result = $this->parser->parse('What should I do next?');

        $this->assertEquals('query_focus', $result['intent']);
    }

    /** @test */
    public function it_detects_today_query(): void
    {
        $result = $this->parser->parse("What's on my agenda today?");

        $this->assertEquals('query_focus', $result['intent']);
    }

    // ==================== Insights Query Tests ====================

    /** @test */
    public function it_detects_insights_query(): void
    {
        $result = $this->parser->parse('Show me insights');

        $this->assertEquals('query_insights', $result['intent']);
    }

    /** @test */
    public function it_detects_issues_query(): void
    {
        $result = $this->parser->parse('Are there any issues?');

        $this->assertEquals('query_insights', $result['intent']);
    }

    /** @test */
    public function it_detects_concerns_query(): void
    {
        $result = $this->parser->parse('What are the concerns?');

        $this->assertEquals('query_insights', $result['intent']);
    }

    /** @test */
    public function it_detects_risks_query(): void
    {
        $result = $this->parser->parse('What risks should I know about?');

        $this->assertEquals('query_insights', $result['intent']);
    }

    /** @test */
    public function it_detects_alerts_query(): void
    {
        $result = $this->parser->parse('Any alerts?');

        $this->assertEquals('query_insights', $result['intent']);
    }

    // ==================== Action: Create Invoice Tests ====================

    /** @test */
    public function it_detects_create_invoice_action(): void
    {
        $result = $this->parser->parse('Create an invoice for Acme Corp');

        $this->assertEquals('action', $result['intent']);
        $this->assertEquals('create_invoice', $result['action_type']);
        $this->assertEquals('acme corp', $result['parameters']['customer_name']);
    }

    /** @test */
    public function it_extracts_invoice_amount(): void
    {
        $result = $this->parser->parse('Create an invoice for Acme Corp for $5000');

        $this->assertEquals('create_invoice', $result['action_type']);
        $this->assertEquals(5000, $result['parameters']['total_amount']);
    }

    /** @test */
    public function it_extracts_invoice_date(): void
    {
        $result = $this->parser->parse('Create an invoice dated 23 july 2025 for Acme Corp');

        $this->assertEquals('create_invoice', $result['action_type']);
        $this->assertEquals('2025-07-23', $result['parameters']['invoice_date']);
    }

    /** @test */
    public function it_extracts_customer_and_product_from_invoice(): void
    {
        $result = $this->parser->parse('Create invoice for Tobo Industries for website design');

        $this->assertEquals('create_invoice', $result['action_type']);
        $this->assertEquals('tobo industries', $result['parameters']['customer_name']);
        $this->assertEquals('website design', $result['parameters']['product_name']);
    }

    // ==================== Action: Create Transaction Tests ====================

    /** @test */
    public function it_detects_create_transaction_action(): void
    {
        $result = $this->parser->parse('Create a transaction for $500 expense for office supplies');

        $this->assertEquals('action', $result['intent']);
        $this->assertEquals('create_transaction', $result['action_type']);
        $this->assertEquals(500, $result['parameters']['amount']);
        $this->assertEquals('expense', $result['parameters']['flow_type']);
    }

    /** @test */
    public function it_detects_create_income_transaction(): void
    {
        $result = $this->parser->parse('Record income of $1000');

        $this->assertEquals('create_transaction', $result['action_type']);
        $this->assertEquals(1000, $result['parameters']['amount']);
        $this->assertEquals('income', $result['parameters']['flow_type']);
    }

    /** @test */
    public function it_detects_confirm_expense_action(): void
    {
        $result = $this->parser->parse('Confirm expense of $250');

        $this->assertEquals('create_transaction', $result['action_type']);
        $this->assertEquals(250, $result['parameters']['amount']);
        $this->assertEquals('expense', $result['parameters']['flow_type']);
    }

    // ==================== Action: Send Reminder Tests ====================

    /** @test */
    public function it_detects_send_invoice_reminder_action(): void
    {
        $result = $this->parser->parse('Send invoice reminder for overdue invoices');

        $this->assertEquals('action', $result['intent']);
        $this->assertEquals('send_invoice_reminders', $result['action_type']);
    }

    /** @test */
    public function it_extracts_reminder_customer_name(): void
    {
        $result = $this->parser->parse('Send invoice reminder for Acme Corp');

        $this->assertEquals('send_invoice_reminders', $result['action_type']);
        $this->assertEquals('Acme Corp', $result['parameters']['customer_name']);
    }

    /** @test */
    public function it_extracts_reminder_invoice_number(): void
    {
        $result = $this->parser->parse('Send reminder for invoice INV-001');

        $this->assertEquals('send_invoice_reminders', $result['action_type']);
        $this->assertEquals('INV-001', $result['parameters']['invoice_number']);
    }

    /** @test */
    public function it_extracts_reminder_days_overdue(): void
    {
        $result = $this->parser->parse('Send invoice reminders for 30 days overdue');

        $this->assertEquals('send_invoice_reminders', $result['action_type']);
        $this->assertEquals(30, $result['parameters']['min_days_overdue']);
    }

    /** @test */
    public function it_extracts_reminder_channel_sms(): void
    {
        $result = $this->parser->parse('Send invoice reminder via sms');

        $this->assertEquals('send_invoice_reminders', $result['action_type']);
        $this->assertEquals('sms', $result['parameters']['channel']);
    }

    // ==================== Action: Generate Report Tests ====================

    /** @test */
    public function it_detects_generate_report_action(): void
    {
        $result = $this->parser->parse('Generate a sales report');

        $this->assertEquals('action', $result['intent']);
        $this->assertEquals('generate_report', $result['action_type']);
        $this->assertEquals('sales', $result['parameters']['type']);
    }

    /** @test */
    public function it_extracts_report_period_this_week(): void
    {
        $result = $this->parser->parse('Generate weekly report');

        $this->assertEquals('generate_report', $result['action_type']);
        $this->assertEquals('this_week', $result['parameters']['period']);
    }

    /** @test */
    public function it_extracts_report_period_last_month(): void
    {
        $result = $this->parser->parse('Generate report for last month');

        $this->assertEquals('generate_report', $result['action_type']);
        $this->assertEquals('last_month', $result['parameters']['period']);
    }

    /** @test */
    public function it_extracts_report_period_today(): void
    {
        $result = $this->parser->parse("Give me today's report");

        $this->assertEquals('generate_report', $result['action_type']);
        $this->assertEquals('today', $result['parameters']['period']);
    }

    /** @test */
    public function it_extracts_report_type_expenses(): void
    {
        $result = $this->parser->parse('Generate expense report');

        $this->assertEquals('generate_report', $result['action_type']);
        $this->assertEquals('expenses', $result['parameters']['type']);
    }

    /** @test */
    public function it_extracts_report_type_cash_flow(): void
    {
        $result = $this->parser->parse('Show me cash flow report');

        $this->assertEquals('generate_report', $result['action_type']);
        $this->assertEquals('cash_flow', $result['parameters']['type']);
    }

    // ==================== Action: Create Organization Tests ====================

    /** @test */
    public function it_detects_create_organization_action(): void
    {
        $result = $this->parser->parse('Create organization called Tech Solutions');

        $this->assertEquals('action', $result['intent']);
        $this->assertEquals('create_organization', $result['action_type']);
        $this->assertEquals('Tech Solutions', $result['parameters']['name']);
    }

    /** @test */
    public function it_extracts_organization_name_from_company(): void
    {
        $result = $this->parser->parse('Make a new company called ABC Ltd');

        $this->assertEquals('create_organization', $result['action_type']);
        $this->assertEquals('ABC Ltd', $result['parameters']['name']);
    }

    /** @test */
    public function it_extracts_organization_name_from_business(): void
    {
        $result = $this->parser->parse('Start a new business named My Store');

        $this->assertEquals('create_organization', $result['action_type']);
        $this->assertEquals('My Store', $result['parameters']['name']);
    }

    // ==================== Action: Record Invoice Payment Tests ====================

    /** @test */
    public function it_detects_record_invoice_payment_action(): void
    {
        $result = $this->parser->parse('Mark invoice INV-001 as paid');

        $this->assertEquals('action', $result['intent']);
        $this->assertEquals('record_invoice_payment', $result['action_type']);
        $this->assertEquals('INV-001', $result['parameters']['invoice_number']);
    }

    /** @test */
    public function it_extracts_payment_amount(): void
    {
        $result = $this->parser->parse('Record payment of $1500 for invoice INV-002');

        $this->assertEquals('record_invoice_payment', $result['action_type']);
        $this->assertEquals(1500, $result['parameters']['amount']);
    }

    /** @test */
    public function it_extracts_payment_method_cash(): void
    {
        $result = $this->parser->parse('Mark invoice INV-003 as paid by cash');

        $this->assertEquals('record_invoice_payment', $result['action_type']);
        $this->assertEquals('cash', $result['parameters']['payment_method']);
    }

    /** @test */
    public function it_extracts_payment_method_transfer(): void
    {
        $result = $this->parser->parse('Record invoice payment via bank transfer');

        $this->assertEquals('record_invoice_payment', $result['action_type']);
        $this->assertEquals('bank_transfer', $result['parameters']['payment_method']);
    }

    // ==================== Action: Follow Up Quote Tests ====================

    /** @test */
    public function it_detects_follow_up_quote_action(): void
    {
        $result = $this->parser->parse('Follow up on quote QT-001');

        $this->assertEquals('action', $result['intent']);
        $this->assertEquals('follow_up_quote', $result['action_type']);
        $this->assertEquals('QT-001', $result['parameters']['quote_number']);
    }

    /** @test */
    public function it_extracts_quote_stale_only(): void
    {
        $result = $this->parser->parse('Follow up on stale quotes');

        $this->assertEquals('follow_up_quote', $result['action_type']);
        $this->assertTrue($result['parameters']['stale_only']);
    }

    // ==================== Action: Categorize Transactions Tests ====================

    /** @test */
    public function it_detects_categorize_transactions_action(): void
    {
        $result = $this->parser->parse('Categorize uncategorized transactions');

        $this->assertEquals('action', $result['intent']);
        $this->assertEquals('categorize_transactions', $result['action_type']);
    }

    /** @test */
    public function it_extracts_categorization_limit(): void
    {
        $result = $this->parser->parse('Categorize 10 transactions');

        $this->assertEquals('categorize_transactions', $result['action_type']);
        $this->assertEquals(10, $result['parameters']['limit']);
    }

    // ==================== Bank Statement Detection Tests ====================

    /** @test */
    public function it_detects_bank_statement_text(): void
    {
        $bankStatement = "Sep 23 POS Purchase SHOPRITE 1,500.00 Dr 45,000.00 Cr
Sep 24 Bank To Wallet Transfer 500.00 Dr 44,500.00 Cr
Sep 25 FNB OB Pmt SALARY 25,000.00 Cr 69,500.00 Cr";

        $result = $this->parser->parse($bankStatement);

        $this->assertEquals('action', $result['intent']);
        $this->assertEquals('create_transaction', $result['action_type']);
        $this->assertTrue($result['parameters']['is_bank_statement_text']);
        $this->assertEquals($bankStatement, $result['parameters']['raw_text']);
        $this->assertEquals(0.95, $result['confidence']);
    }

    // ==================== Document Status Query Tests ====================

    /** @test */
    public function it_detects_document_status_query(): void
    {
        $result = $this->parser->parse('What is the status of my document?');

        $this->assertEquals('query_document_status', $result['intent']);
    }

    /** @test */
    public function it_extracts_document_status_job_id(): void
    {
        $result = $this->parser->parse('Check status of job #abc123');

        $this->assertEquals('query_document_status', $result['intent']);
        $this->assertEquals('abc123', $result['parameters']['job_id']);
    }

    /** @test */
    public function it_extracts_document_recent_flag(): void
    {
        $result = $this->parser->parse('Show me recent documents');

        $this->assertEquals('query_document_status', $result['intent']);
        $this->assertTrue($result['parameters']['recent']);
    }

    /** @test */
    public function it_extracts_document_status_filter(): void
    {
        $result = $this->parser->parse('Show pending documents');

        $this->assertEquals('query_document_status', $result['intent']);
        $this->assertEquals('pending', $result['parameters']['status']);
    }

    // ==================== General Intent Tests ====================

    /** @test */
    public function it_returns_general_intent_for_unknown_messages(): void
    {
        $result = $this->parser->parse('Tell me a joke');

        $this->assertEquals('general', $result['intent']);
        $this->assertEquals(0.5, $result['confidence']);
    }

    /** @test */
    public function it_handles_empty_message(): void
    {
        $result = $this->parser->parse('');

        $this->assertEquals('general', $result['intent']);
    }

    /** @test */
    public function it_handles_whitespace_message(): void
    {
        $result = $this->parser->parse('   ');

        $this->assertEquals('general', $result['intent']);
    }

    // ==================== Case Insensitivity Tests ====================

    /** @test */
    public function it_handles_uppercase_input(): void
    {
        $result = $this->parser->parse('SHOW ME INVOICES');

        $this->assertEquals('query_invoices', $result['intent']);
    }

    /** @test */
    public function it_handles_mixed_case_input(): void
    {
        $result = $this->parser->parse('Create Invoice For ACME Corp');

        $this->assertEquals('action', $result['intent']);
        $this->assertEquals('create_invoice', $result['action_type']);
    }

    // ==================== Month Parsing Tests ====================

    /** @test */
    public function it_parses_month_names_correctly(): void
    {
        $months = [
            'january' => '01', 'jan' => '01',
            'february' => '02', 'feb' => '02',
            'march' => '03', 'mar' => '03',
            'april' => '04', 'apr' => '04',
            'may' => '05',
            'june' => '06', 'jun' => '06',
            'july' => '07', 'jul' => '07',
            'august' => '08', 'aug' => '08',
            'september' => '09', 'sep' => '09',
            'october' => '10', 'oct' => '10',
            'november' => '11', 'nov' => '11',
            'december' => '12', 'dec' => '12',
        ];

        foreach ($months as $monthName => $expected) {
            $result = $this->parser->parse("Create invoice dated 15 {$monthName} 2025");

            $this->assertEquals("2025-{$expected}-15", $result['parameters']['invoice_date'] ?? null);
        }
    }

    // ==================== Amount Parsing Tests ====================

    /** @test */
    public function it_parses_amount_with_dollar_sign(): void
    {
        $result = $this->parser->parse('Create transaction for $1500');

        $this->assertEquals(1500, $result['parameters']['amount']);
    }

    /** @test */
    public function it_parses_amount_with_decimal(): void
    {
        $result = $this->parser->parse('Create transaction for $1500.50');

        $this->assertEquals(1500.50, $result['parameters']['amount']);
    }

    /** @test */
    public function it_parses_amount_with_commas(): void
    {
        $result = $this->parser->parse('Create invoice for amount $10,000');

        $this->assertEquals(10000, $result['parameters']['total_amount']);
    }

    /** @test */
    public function it_parses_amount_with_spaces(): void
    {
        $result = $this->parser->parse('Create invoice for amount 10 000');

        $this->assertEquals(10000, $result['parameters']['total_amount']);
    }

    // ==================== Report Period Tests ====================

    /** @test */
    public function it_extracts_last_n_days_period(): void
    {
        $result = $this->parser->parse('Generate report for last 7 days');

        $this->assertEquals('last_7_days', $result['parameters']['period']);
    }

    /** @test */
    public function it_extracts_last_n_weeks_period(): void
    {
        $result = $this->parser->parse('Generate report for last 2 weeks');

        $this->assertEquals('last_2_weeks', $result['parameters']['period']);
    }

    /** @test */
    public function it_extracts_last_n_months_period(): void
    {
        $result = $this->parser->parse('Generate report for last 3 months');

        $this->assertEquals('last_3_months', $result['parameters']['period']);
    }

    /** @test */
    public function it_extracts_year_to_date_period(): void
    {
        $result = $this->parser->parse('Generate YTD report');

        $this->assertEquals('year_to_date', $result['parameters']['period']);
    }

    /** @test */
    public function it_extracts_this_quarter_period(): void
    {
        $result = $this->parser->parse('Generate report for this quarter');

        $this->assertEquals('this_quarter', $result['parameters']['period']);
    }

    /** @test */
    public function it_extracts_last_quarter_period(): void
    {
        $result = $this->parser->parse('Generate report for last quarter');

        $this->assertEquals('last_quarter', $result['parameters']['period']);
    }
}
