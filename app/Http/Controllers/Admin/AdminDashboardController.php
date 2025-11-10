<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminAnalyticsService;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function __construct(
        protected AdminAnalyticsService $analytics
    ) {}

    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => $this->analytics->getDashboardStats(),
            'charts' => [
                'organizations' => $this->analytics->getOrganizationGrowth(30),
                'users' => $this->analytics->getUserActivity(30),
                'tickets' => $this->analytics->getTicketVolume(30),
                'revenue' => $this->analytics->getRevenueChart(12),
            ],
            'system_health' => $this->analytics->getSystemHealth(),
        ]);
    }
}

