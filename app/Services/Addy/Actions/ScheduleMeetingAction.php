<?php

namespace App\Services\Addy\Actions;

class ScheduleMeetingAction extends BaseAction
{
    public function validate(): bool
    {
        return true;
    }

    public function preview(): array
    {
        return [
            'title' => 'Schedule Meeting',
            'description' => 'Create a team meeting',
            'items' => [],
            'impact' => 'low',
            'warnings' => [],
        ];
    }

    public function execute(): array
    {
        return [
            'success' => false,
            'message' => 'Meeting scheduling not yet implemented.',
        ];
    }
}

