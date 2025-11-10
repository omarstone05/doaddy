<?php

namespace App\Services\Addy\Actions;

class ApproveLeaveAction extends BaseAction
{
    public function validate(): bool
    {
        return isset($this->parameters['leave_request_id']);
    }

    public function preview(): array
    {
        return [
            'title' => 'Approve Leave Request',
            'description' => 'Approve pending time-off request',
            'items' => [],
            'impact' => 'medium',
            'warnings' => [],
        ];
    }

    public function execute(): array
    {
        return [
            'success' => false,
            'message' => 'Leave approval not yet implemented.',
        ];
    }
}

