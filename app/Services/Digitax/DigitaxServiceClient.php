<?php

namespace App\Services\Digitax;

use App\Models\User;
use App\Services\Microservices\JwtMicroserviceClient;
use Illuminate\Http\Client\Response;

class DigitaxServiceClient extends JwtMicroserviceClient
{
    public function __construct(
        protected DigitaxTokenService $tokenService,
        string $baseUrl
    ) {
        parent::__construct($baseUrl);
    }

    protected function tokenFor(User $user): string
    {
        return $this->tokenService->issue($user);
    }

    // Business management
    public function listBusinesses(User $user, array $filters = []): Response
    {
        return $this->get($user, '/api/v1/businesses', $filters);
    }

    public function createBusiness(User $user, array $payload): Response
    {
        return $this->post($user, '/api/v1/businesses', $payload);
    }

    public function syncBusiness(User $user, string $businessId): Response
    {
        return $this->post($user, "/api/v1/businesses/{$businessId}/sync-to-digitax");
    }

    // Item management
    public function listItems(User $user, array $filters = []): Response
    {
        return $this->get($user, '/api/v1/items', $filters);
    }

    public function createItem(User $user, array $payload): Response
    {
        return $this->post($user, '/api/v1/items', $payload);
    }

    public function syncItem(User $user, string $itemId): Response
    {
        return $this->post($user, "/api/v1/items/{$itemId}/sync");
    }

    // Invoices
    public function createInvoice(User $user, array $payload): Response
    {
        return $this->post($user, '/api/v1/invoices', $payload);
    }

    public function getInvoiceStatus(User $user, string $invoiceId): Response
    {
        return $this->get($user, "/api/v1/invoices/{$invoiceId}/status");
    }
}
