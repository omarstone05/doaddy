<?php

namespace App\Services\Document;

use App\Models\Attachment;
use App\Models\AddyChatMessage;
use App\Models\Document;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class DocumentContextService
{
    /**
     * Get historical documents for context
     */
    public function getHistoricalContext(
        string $organizationId,
        ?string $customerName = null,
        ?string $documentType = null,
        ?int $limit = 10
    ): array {
        $context = [];

        // Get recent chat messages with attachments
        $chatMessages = AddyChatMessage::where('organization_id', $organizationId)
            ->whereNotNull('attachments')
            ->whereJsonLength('attachments', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        foreach ($chatMessages as $message) {
            $attachments = $message->attachments ?? [];
            foreach ($attachments as $attachment) {
                if (isset($attachment['extracted_data'])) {
                    $context[] = [
                        'source' => 'chat',
                        'date' => $message->created_at->toDateString(),
                        'data' => $attachment['extracted_data'],
                        'file_name' => $attachment['file_name'] ?? null,
                    ];
                }
            }
        }

        // Get documents linked to customers if customer name provided
        if ($customerName) {
            $customer = Customer::where('organization_id', $organizationId)
                ->where('name', 'like', "%{$customerName}%")
                ->first();

            if ($customer) {
                // Get invoices for this customer
                $invoices = Invoice::where('organization_id', $organizationId)
                    ->where('customer_id', $customer->id)
                    ->orderBy('invoice_date', 'desc')
                    ->limit(5)
                    ->get();

                foreach ($invoices as $invoice) {
                    $context[] = [
                        'source' => 'invoice',
                        'date' => $invoice->invoice_date->toDateString(),
                        'data' => [
                            'type' => 'invoice',
                            'amount' => $invoice->total_amount,
                            'customer_name' => $customer->name,
                            'invoice_number' => $invoice->invoice_number,
                            'status' => $invoice->status,
                        ],
                    ];
                }

                // Get quotes for this customer
                $quotes = Quote::where('organization_id', $organizationId)
                    ->where('customer_id', $customer->id)
                    ->orderBy('quote_date', 'desc')
                    ->limit(5)
                    ->get();

                foreach ($quotes as $quote) {
                    $context[] = [
                        'source' => 'quote',
                        'date' => $quote->quote_date->toDateString(),
                        'data' => [
                            'type' => 'quote',
                            'amount' => $quote->total_amount,
                            'customer_name' => $customer->name,
                            'quote_number' => $quote->quote_number,
                            'status' => $quote->status,
                        ],
                    ];
                }
            }
        }

        // Get recent documents by type
        if ($documentType) {
            $documents = Document::where('organization_id', $organizationId)
                ->where('category', $documentType)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($documents as $document) {
                $context[] = [
                    'source' => 'document',
                    'date' => $document->created_at->toDateString(),
                    'data' => [
                        'type' => $document->category,
                        'name' => $document->name,
                        'description' => $document->description,
                    ],
                ];
            }
        }

        // Sort by date (most recent first)
        usort($context, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return array_slice($context, 0, $limit);
    }

    /**
     * Get context for a specific customer
     */
    public function getCustomerContext(string $organizationId, string $customerId): array
    {
        $customer = Customer::where('organization_id', $organizationId)
            ->findOrFail($customerId);

        $context = [
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'company_name' => $customer->company_name,
            ],
            'invoices' => [],
            'quotes' => [],
            'documents' => [],
        ];

        // Get invoices
        $invoices = Invoice::where('organization_id', $organizationId)
            ->where('customer_id', $customerId)
            ->orderBy('invoice_date', 'desc')
            ->limit(10)
            ->get();

        foreach ($invoices as $invoice) {
            $context['invoices'][] = [
                'invoice_number' => $invoice->invoice_number,
                'date' => $invoice->invoice_date->toDateString(),
                'amount' => $invoice->total_amount,
                'status' => $invoice->status,
            ];
        }

        // Get quotes
        $quotes = Quote::where('organization_id', $organizationId)
            ->where('customer_id', $customerId)
            ->orderBy('quote_date', 'desc')
            ->limit(10)
            ->get();

        foreach ($quotes as $quote) {
            $context['quotes'][] = [
                'quote_number' => $quote->quote_number,
                'date' => $quote->quote_date->toDateString(),
                'amount' => $quote->total_amount,
                'status' => $quote->status,
            ];
        }

        // Get attachments
        $attachments = Attachment::where('organization_id', $organizationId)
            ->where('attachable_type', Customer::class)
            ->where('attachable_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        foreach ($attachments as $attachment) {
            $context['documents'][] = [
                'name' => $attachment->name,
                'file_name' => $attachment->file_name,
                'date' => $attachment->created_at->toDateString(),
                'mime_type' => $attachment->mime_type,
            ];
        }

        return $context;
    }

    /**
     * Search documents by content
     */
    public function searchDocuments(
        string $organizationId,
        string $query,
        ?string $type = null,
        ?int $limit = 20
    ): array {
        $results = [];

        // Search in chat messages with extracted data
        $chatMessages = AddyChatMessage::where('organization_id', $organizationId)
            ->whereNotNull('metadata')
            ->where(function ($q) use ($query) {
                $q->where('content', 'like', "%{$query}%")
                  ->orWhereJsonContains('metadata->extracted_data', $query);
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        foreach ($chatMessages as $message) {
            $results[] = [
                'type' => 'chat',
                'id' => $message->id,
                'content' => $message->content,
                'date' => $message->created_at->toDateString(),
                'attachments' => $message->attachments,
            ];
        }

        // Search in documents
        $documents = Document::where('organization_id', $organizationId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->when($type, fn($q) => $q->where('category', $type))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        foreach ($documents as $document) {
            $results[] = [
                'type' => 'document',
                'id' => $document->id,
                'name' => $document->name,
                'description' => $document->description,
                'category' => $document->category,
                'date' => $document->created_at->toDateString(),
            ];
        }

        return $results;
    }
}

