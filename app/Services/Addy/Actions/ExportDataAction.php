<?php

namespace App\Services\Addy\Actions;

class ExportDataAction extends BaseAction
{
    public function validate(): bool
    {
        return true;
    }

    public function preview(): array
    {
        return [
            'title' => 'Export Data',
            'description' => 'Export business data to Excel/CSV',
            'items' => [],
            'impact' => 'low',
            'warnings' => [],
        ];
    }

    public function execute(): array
    {
        return [
            'success' => false,
            'message' => 'Data export not yet implemented.',
        ];
    }
}

