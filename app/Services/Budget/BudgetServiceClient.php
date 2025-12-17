<?php

namespace App\Services\Budget;

use App\Models\User;
use App\Services\Microservices\JwtMicroserviceClient;
use Illuminate\Http\Client\Response;

class BudgetServiceClient extends JwtMicroserviceClient
{
    public function __construct(
        protected BudgetTokenService $tokenService,
        string $baseUrl
    ) {
        parent::__construct($baseUrl);
    }

    protected function tokenFor(User $user): string
    {
        return $this->tokenService->issue($user);
    }

    public function listBudgets(User $user, array $filters = []): Response
    {
        return $this->get($user, '/api/v1/budgets', $filters);
    }

    public function getBudget(User $user, string $budgetId): Response
    {
        return $this->get($user, "/api/v1/budgets/{$budgetId}");
    }

    public function createBudget(User $user, array $payload): Response
    {
        return $this->post($user, '/api/v1/budgets', $payload);
    }

    public function updateBudget(User $user, string $budgetId, array $payload): Response
    {
        return $this->withAuth($user)->put($this->url("/api/v1/budgets/{$budgetId}"), $payload);
    }

    public function deleteBudget(User $user, string $budgetId): Response
    {
        return $this->withAuth($user)->delete($this->url("/api/v1/budgets/{$budgetId}"));
    }
}
