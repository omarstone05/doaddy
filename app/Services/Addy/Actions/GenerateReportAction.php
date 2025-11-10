<?php

namespace App\Services\Addy\Actions;

class GenerateReportAction extends BaseAction
{
    public function validate(): bool
    {
        return true;
    }

    public function preview(): array
    {
        return [
            'title' => 'Generate Report',
            'description' => 'Create a business report',
            'items' => [],
            'impact' => 'low',
            'warnings' => [],
        ];
    }

    public function execute(): array
    {
        return [
            'success' => false,
            'message' => 'Report generation not yet implemented.',
        ];
    }
}

