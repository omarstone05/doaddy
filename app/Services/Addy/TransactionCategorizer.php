<?php

namespace App\Services\Addy;

use App\Models\MoneyMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TransactionCategorizer
{
    /**
     * Keyword map for expense categories
     */
    protected array $expenseKeywords = [
        '(fuel|petrol|gas|diesel|station)' => 'Fuel & Transport',
        '(uber|taxi|bolt|bus|cab)' => 'Transport',
        '(rent|lease|landlord)' => 'Rent',
        '(salary|payroll|wage|overtime)' => 'Payroll',
        '(airtime|zamtel|airtel|mtn|data|internet|wifi|fiber|isp|phone|phone bill)' => 'Utilities & Internet',
        '(electric|water|utility|zesa|zesco|billing)' => 'Utilities & Internet',
        '(coffee|lunch|dinner|restaurant|cafe|meal|food|bar|kitchen)' => 'Meals & Entertainment',
        '(hotel|lodging|accommodation)' => 'Travel & Lodging',
        '(marketing|advert|promo|campaign|facebook|google ads|instagram|promotion)' => 'Marketing',
        '(office|stationery|printer|paper|toner|furniture|desk)' => 'Office Supplies',
        '(software|subscription|saas|license|adobe|microsoft|google workspace|notion|figma)' => 'Software & Subscriptions',
        '(maintenance|repair|service|technician)' => 'Maintenance & Repairs',
        '(tax|zra|vat|withholding)' => 'Taxes & Regulatory',
        '(loan|interest|bank charge|fee|commission|charges)' => 'Bank & Finance Fees',
        '(charity|donation|tithe)' => 'Donations',
    ];

    /**
     * Keyword map for income categories
     */
    protected array $incomeKeywords = [
        '(invoice|payment|receipt|sale|pos)' => 'Sales Income',
        '(retainer|subscription|membership)' => 'Recurring Revenue',
        '(refund|rebate)' => 'Refunds',
        '(investment|interest|dividend)' => 'Investment Income',
        '(loan|capital|equity)' => 'Capital Injections',
    ];

    /**
     * Guess the category based on description and flow type.
     */
    public function guess(?string $description, string $flowType = 'expense'): array
    {
        $text = Str::lower($description ?? '');
        $default = $flowType === 'income' ? 'General Income' : 'Operational Expense';

        if (empty($text)) {
            return [$default, 0.15];
        }

        $map = $flowType === 'income' ? $this->incomeKeywords : $this->expenseKeywords;

        foreach ($map as $pattern => $category) {
            if (preg_match("/{$pattern}/i", $text)) {
                return [$category, 0.9];
            }
        }

        // Additional heuristics
        if ($flowType === 'income') {
            if (str_contains($text, 'client') || str_contains($text, 'customer')) {
                return ['Client Payments', 0.7];
            }
        } else {
            if (str_contains($text, 'subscription')) {
                return ['Software & Subscriptions', 0.75];
            }
            if (str_contains($text, 'insurance')) {
                return ['Insurance', 0.8];
            }
            if (str_contains($text, 'travel') || str_contains($text, 'flight')) {
                return ['Travel & Lodging', 0.7];
            }
        }

        return [$default, 0.3];
    }

    /**
     * Suggest categories for uncategorized transactions for an organization.
     */
    public function suggestForOrganization(int|string $organizationId, int $limit = 10): Collection
    {
        $transactions = MoneyMovement::where('organization_id', $organizationId)
            ->where(function ($query) {
                $query->whereNull('category')
                    ->orWhere('category', '')
                    ->orWhere('category', 'Uncategorized');
            })
            ->orderBy('transaction_date', 'desc')
            ->limit($limit)
            ->get();

        return $transactions->map(function (MoneyMovement $movement) {
            [$category, $confidence] = $this->guess($movement->description, $movement->flow_type);

            return [
                'movement' => $movement,
                'suggested_category' => $category,
                'confidence' => $confidence,
            ];
        });
    }
}
