<?php

namespace App\Services\Files;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

class FileParserService
{
    public function __construct(protected Parser $parser)
    {
    }

    /**
     * Extract text from a file based on its extension
     */
    public function extractText(string $path, string $mimeType = null): string
    {
        if (!file_exists($path)) {
            Log::warning("FileParser: File not found at $path");
            return '';
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        try {
            return match (strtolower($extension)) {
                'pdf' => $this->parsePdf($path),
                'txt', 'md', 'csv', 'json' => file_get_contents($path),
                default => $this->extractBasedOnMime($path, $mimeType),
            };
        } catch (\Exception $e) {
            Log::error('File parsing failed: ' . $e->getMessage());
            return '';
        }
    }

    protected function parsePdf(string $path): string
    {
        $pdf = $this->parser->parseFile($path);
        return $pdf->getText();
    }

    protected function extractBasedOnMime(string $path, ?string $mimeType): string
    {
        if (!$mimeType) return '';

        return match ($mimeType) {
            'application/pdf' => $this->parsePdf($path),
            'text/plain', 'text/csv', 'application/json' => file_get_contents($path),
            default => '',
        };
    }
}
