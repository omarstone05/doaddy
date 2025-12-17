<?php

namespace App\Modules\CRM\Cards;

use App\Models\DashboardCard;

class CRMCards
{
    public static function register(): void
    {
        $cards = [
            [
                'key' => 'crm_dashboard',
                'title' => 'CRM Dashboard',
                'description' => 'Sales pipeline and lead management overview',
                'icon' => 'users',
                'route' => '/crm',
                'module' => 'CRM',
            ],
            [
                'key' => 'crm_leads',
                'title' => 'Leads',
                'description' => 'View and manage leads',
                'icon' => 'user-plus',
                'route' => '/crm/leads',
                'module' => 'CRM',
            ],
            [
                'key' => 'crm_opportunities',
                'title' => 'Opportunities',
                'description' => 'Track sales opportunities',
                'icon' => 'trending-up',
                'route' => '/crm/opportunities',
                'module' => 'CRM',
            ],
            [
                'key' => 'crm_contacts',
                'title' => 'Contacts',
                'description' => 'Manage customer contacts',
                'icon' => 'address-book',
                'route' => '/crm/contacts',
                'module' => 'CRM',
            ],
        ];

        foreach ($cards as $cardData) {
            DashboardCard::firstOrCreate(
                ['key' => $cardData['key']],
                $cardData
            );
        }
    }
}


