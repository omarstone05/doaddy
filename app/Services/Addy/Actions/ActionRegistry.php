<?php

namespace App\Services\Addy\Actions;

class ActionRegistry
{
    protected static array $actions = [];

    /**
     * Register all available actions
     */
    public static function register(): void
    {
        self::$actions = [
            // Money Actions
            'send_invoice_reminders' => [
                'class' => SendInvoiceRemindersAction::class,
                'category' => 'sales',
                'label' => 'Send Invoice Reminders',
                'description' => 'Send payment reminder emails for overdue invoices',
                'requires_confirmation' => true,
            ],
            'create_transaction' => [
                'class' => CreateTransactionAction::class,
                'category' => 'money',
                'label' => 'Create Transaction',
                'description' => 'Record a new income or expense transaction',
                'requires_confirmation' => true,
            ],
            'adjust_budget' => [
                'class' => AdjustBudgetAction::class,
                'category' => 'money',
                'label' => 'Adjust Budget',
                'description' => 'Modify budget allocation',
                'requires_confirmation' => true,
            ],
            
            // Sales Actions
            'create_invoice' => [
                'class' => CreateInvoiceAction::class,
                'category' => 'sales',
                'label' => 'Create Invoice',
                'description' => 'Generate a new invoice for a customer',
                'requires_confirmation' => true,
            ],
            'follow_up_quote' => [
                'class' => FollowUpQuoteAction::class,
                'category' => 'sales',
                'label' => 'Follow Up Quote',
                'description' => 'Send follow-up email for pending quotes',
                'requires_confirmation' => true,
            ],
            
            // People Actions
            'approve_leave' => [
                'class' => ApproveLeaveAction::class,
                'category' => 'people',
                'label' => 'Approve Leave Request',
                'description' => 'Approve pending time-off requests',
                'requires_confirmation' => true,
            ],
            'schedule_meeting' => [
                'class' => ScheduleMeetingAction::class,
                'category' => 'people',
                'label' => 'Schedule Meeting',
                'description' => 'Create a team meeting',
                'requires_confirmation' => true,
            ],
            
            // Reports Actions
            'generate_report' => [
                'class' => GenerateReportAction::class,
                'category' => 'reports',
                'label' => 'Generate Report',
                'description' => 'Create a business report',
                'requires_confirmation' => false, // Reports are safe
            ],
            'export_data' => [
                'class' => ExportDataAction::class,
                'category' => 'reports',
                'label' => 'Export Data',
                'description' => 'Export business data to Excel/CSV',
                'requires_confirmation' => false,
            ],
        ];
    }

    /**
     * Get action definition
     */
    public static function get(string $actionType): ?array
    {
        if (empty(self::$actions)) {
            self::register();
        }

        return self::$actions[$actionType] ?? null;
    }

    /**
     * Get all actions
     */
    public static function all(): array
    {
        if (empty(self::$actions)) {
            self::register();
        }

        return self::$actions;
    }

    /**
     * Get actions by category
     */
    public static function byCategory(string $category): array
    {
        if (empty(self::$actions)) {
            self::register();
        }

        return array_filter(self::$actions, fn($action) => $action['category'] === $category);
    }
}

