<?php

namespace App\Services\Addy\Actions;

use App\Models\Quote;
use Illuminate\Support\Collection;

class FollowUpQuoteAction extends BaseAction
{
    public function validate(): bool
    {
        return $this->getTargetQuotes(1)->isNotEmpty();
    }

    public function preview(): array
    {
        $limit = (int) ($this->parameters['limit'] ?? 5);
        $quotes = $this->getTargetQuotes($limit);

        if ($quotes->isEmpty()) {
            throw new \Exception('No quotes found that require a follow-up.');
        }

        return [
            'title' => 'Follow Up Quote',
            'description' => $quotes->count() > 1
                ? "Ready to follow up {$quotes->count()} pending quotes."
                : 'Send a follow-up reminder for this quote.',
            'items' => $quotes->map(fn(Quote $quote) => [
                'id' => $quote->id,
                'quote_number' => $quote->quote_number,
                'customer' => $quote->customer->name ?? 'Unknown customer',
                'amount' => $quote->total_amount,
                'quote_date' => optional($quote->quote_date)->format('Y-m-d'),
                'expiry_date' => optional($quote->expiry_date)->format('Y-m-d'),
                'follow_up_count' => $quote->follow_up_count,
            ])->toArray(),
            'impact' => 'medium',
            'warnings' => [],
        ];
    }

    public function execute(): array
    {
        $limit = (int) ($this->parameters['limit'] ?? 5);
        $quotes = $this->getTargetQuotes($limit);

        if ($quotes->isEmpty()) {
            throw new \Exception('No quotes available to follow up.');
        }

        $channel = $this->parameters['channel'] ?? 'email';
        $note = $this->parameters['note'] ?? null;

        foreach ($quotes as $quote) {
            $quote->forceFill([
                'follow_up_count' => ($quote->follow_up_count ?? 0) + 1,
                'last_follow_up_at' => now(),
                'last_follow_up_method' => $channel,
                'last_follow_up_notes' => $note,
                'status' => $quote->status === 'draft' ? 'sent' : $quote->status,
            ])->save();
        }

        return [
            'success' => true,
            'message' => "Logged follow-up for {$quotes->count()} quote(s).",
            'followed_up' => $quotes->pluck('quote_number'),
        ];
    }

    protected function getTargetQuotes(int $limit = 5): Collection
    {
        $query = Quote::with('customer')
            ->where('organization_id', $this->organization->id)
            ->whereIn('status', ['sent', 'draft'])
            ->orderByRaw('COALESCE(last_follow_up_at, quote_date) asc');

        if (!empty($this->parameters['quote_id'])) {
            $query->where('id', $this->parameters['quote_id']);
        }

        if (!empty($this->parameters['quote_number'])) {
            $query->where('quote_number', 'like', '%' . strtoupper($this->parameters['quote_number']) . '%');
        }

        if (!empty($this->parameters['customer_name'])) {
            $name = trim($this->parameters['customer_name']);
            $query->whereHas('customer', function ($q) use ($name) {
                $q->where('name', 'like', '%' . $name . '%');
            });
        }

        if (!empty($this->parameters['stale_only'])) {
            $days = (int) ($this->parameters['stale_days'] ?? 3);
            $query->where(function ($q) use ($days) {
                $q->whereNull('last_follow_up_at')
                    ->orWhere('last_follow_up_at', '<', now()->subDays($days));
            });
        }

        if (!empty($this->parameters['expiring_within_days'])) {
            $days = (int) $this->parameters['expiring_within_days'];
            $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
        }

        return $query->limit(max(1, $limit))->get();
    }
}
