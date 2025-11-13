<?php

namespace App\Services\Addy\Actions;

use App\Models\MoneyMovement;
use App\Services\Addy\TransactionCategorizer;
use Illuminate\Support\Collection;

class CategorizeTransactionsAction extends BaseAction
{
    protected TransactionCategorizer $categorizer;
    protected ?Collection $cachedSuggestions = null;

    public function __construct($organization, $user, array $parameters = [])
    {
        parent::__construct($organization, $user, $parameters);
        $this->categorizer = new TransactionCategorizer();
    }

    public function validate(): bool
    {
        return $this->getSuggestions()->isNotEmpty();
    }

    public function preview(): array
    {
        $suggestions = $this->getSuggestions();

        return [
            'title' => 'Categorize Transactions',
            'description' => "Apply categories to {$suggestions->count()} uncategorized transaction(s).",
            'items' => $suggestions->map(function ($suggestion) {
                /** @var MoneyMovement $movement */
                $movement = $suggestion['movement'];
                return [
                    'transaction_id' => $movement->id,
                    'description' => $movement->description,
                    'amount' => (float) $movement->amount,
                    'date' => $movement->transaction_date->format('Y-m-d'),
                    'suggested_category' => $suggestion['suggested_category'],
                    'confidence' => $suggestion['confidence'],
                ];
            })->toArray(),
            'impact' => 'medium',
            'warnings' => $suggestions->contains(fn ($s) => $s['confidence'] < 0.5)
                ? ['Some suggestions have low confidence. Low-confidence matches will be skipped automatically.']
                : [],
        ];
    }

    public function execute(): array
    {
        $suggestions = $this->getSuggestions($this->parameters['limit'] ?? 10, true);

        $updated = 0;
        $skipped = [];

        foreach ($suggestions as $suggestion) {
            /** @var MoneyMovement $movement */
            $movement = $suggestion['movement'];
            $confidence = $suggestion['confidence'];

            if ($confidence < 0.35) {
                $skipped[] = [
                    'transaction_id' => $movement->id,
                    'description' => $movement->description,
                    'reason' => 'Low confidence',
                ];
                continue;
            }

            $movement->forceFill([
                'category' => $suggestion['suggested_category'],
            ])->save();

            $updated++;
        }

        return [
            'success' => true,
            'message' => "Updated {$updated} transaction(s)." . ($skipped ? ' Some were skipped due to low confidence.' : ''),
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    protected function getSuggestions(?int $limit = null, bool $refresh = false): Collection
    {
        if ($this->cachedSuggestions && !$refresh && ($limit === null || $this->cachedSuggestions->count() === $limit)) {
            return $this->cachedSuggestions;
        }

        $limit ??= $this->parameters['limit'] ?? 5;

        $this->cachedSuggestions = $this->categorizer
            ->suggestForOrganization($this->organization->id, $limit)
            ->filter(fn ($suggestion) => $suggestion['movement'] instanceof MoneyMovement);

        return $this->cachedSuggestions;
    }
}
