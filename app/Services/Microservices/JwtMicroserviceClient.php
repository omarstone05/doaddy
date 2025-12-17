<?php

namespace App\Services\Microservices;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class JwtMicroserviceClient
{
    public function __construct(protected string $baseUrl)
    {
    }

    abstract protected function tokenFor(User $user): string;

    protected function url(string $path): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }

    protected function withAuth(User $user)
    {
        $token = $this->tokenFor($user);

        return Http::withToken($token);
    }

    protected function get(User $user, string $path, array $query = []): Response
    {
        return $this->withAuth($user)->get($this->url($path), $query);
    }

    protected function post(User $user, string $path, array $data = []): Response
    {
        return $this->withAuth($user)->post($this->url($path), $data);
    }
}
