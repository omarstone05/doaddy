<?php

namespace App\Services\Addy\Actions;

class FollowUpQuoteAction extends BaseAction
{
    public function validate(): bool
    {
        return true;
    }

    public function preview(): array
    {
        return [
            'title' => 'Follow Up Quote',
            'description' => 'Send follow-up email for pending quotes',
            'items' => [],
            'impact' => 'low',
            'warnings' => [],
        ];
    }

    public function execute(): array
    {
        return [
            'success' => false,
            'message' => 'Quote follow-up not yet implemented.',
        ];
    }
}

