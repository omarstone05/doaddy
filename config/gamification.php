<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Addy Gamification Configuration
    |--------------------------------------------------------------------------
    |
    | XP rewards and badge configurations for the Addy gamification system.
    |
    */

'xp_rewards' => [
            // Addy Insights
            'insight_reviewed' => 5,
            'insight_actioned' => 15,
            'insights_cleared_daily' => 25,
            
            // Sales & Revenue
            'sale_recorded' => 10,
        'sale_with_receipt' => 15,
        'large_sale' => 20,  // > K 10,000
        'ten_sales_daily_bonus' => 50,

        // Expenses & Tracking
        'expense_recorded' => 5,
        'expense_with_receipt' => 8,
        'expense_categorized_bonus' => 3,

        // Inventory Management
        'product_added' => 5,
        'stock_updated' => 3,
        'low_stock_restocked' => 10,
        'stock_take_completed' => 50,

        // Financial Health
        'positive_cash_flow_monthly' => 100,
        'budget_review_completed' => 30,
        'report_generated' => 15,

        // Customer Management
        'customer_added' => 5,
        'repeat_customer_sale' => 15,
        'ten_repeat_customers_bonus' => 50,

        // Compliance & Admin
        'invoice_issued' => 5,
        'payment_recorded' => 5,
        'accounts_reconciled' => 25,

        // Streaks
        'daily_streak_3' => 25,
        'daily_streak_7' => 50,
        'daily_streak_14' => 75,
        'daily_streak_21' => 125,
        'daily_streak_30' => 200,
        'daily_streak_45' => 250,
        'daily_streak_60' => 300,
        'daily_streak_90' => 500,
        'daily_streak_180' => 750,
        'daily_streak_365' => 2000,

        // Milestones
        'milestone_10_sales' => 25,
        'milestone_25_sales' => 50,
        'milestone_50_sales' => 75,
        'milestone_100_sales' => 100,
        'milestone_250_sales' => 250,
        'milestone_500_sales' => 500,
        'milestone_1000_sales' => 1000,
        'milestone_100k_revenue' => 100,
        'milestone_500k_revenue' => 250,
        'milestone_1m_revenue' => 500,
        'milestone_5m_revenue' => 1000,
        'milestone_10m_revenue' => 2000,

        // Badge unlocked bonus
        'badge_unlocked_bonus' => 25,
    ],

    'badges' => [
        // =====================
        // REVENUE BADGES
        // =====================
        'first_sale' => [
            'name' => 'First Sale',
            'description' => 'Record your first sale',
            'icon' => '🎯',
            'threshold' => 1,
            'metric' => 'sales_count',
            'category' => 'revenue',
        ],
        'getting_started' => [
            'name' => 'Getting Started',
            'description' => 'Record 10 sales',
            'icon' => '🚀',
            'threshold' => 10,
            'metric' => 'sales_count',
            'category' => 'revenue',
        ],
        'sales_starter' => [
            'name' => 'Sales Starter',
            'description' => 'Record 25 sales',
            'icon' => '📈',
            'threshold' => 25,
            'metric' => 'sales_count',
            'category' => 'revenue',
        ],
        'half_century' => [
            'name' => 'Half Century',
            'description' => 'Record 50 sales',
            'icon' => '⭐',
            'threshold' => 50,
            'metric' => 'sales_count',
            'category' => 'revenue',
        ],
        'sales_warrior' => [
            'name' => 'Sales Warrior',
            'description' => 'Record 100 sales',
            'icon' => '⚔️',
            'threshold' => 100,
            'metric' => 'sales_count',
            'category' => 'revenue',
        ],
        'sales_expert' => [
            'name' => 'Sales Expert',
            'description' => 'Record 250 sales',
            'icon' => '🎖️',
            'threshold' => 250,
            'metric' => 'sales_count',
            'category' => 'revenue',
        ],
        'sales_champion' => [
            'name' => 'Sales Champion',
            'description' => 'Record 500 sales',
            'icon' => '🏆',
            'threshold' => 500,
            'metric' => 'sales_count',
            'category' => 'revenue',
        ],
        'sales_legend' => [
            'name' => 'Sales Legend',
            'description' => 'Record 1,000 sales',
            'icon' => '👑',
            'threshold' => 1000,
            'metric' => 'sales_count',
            'category' => 'revenue',
        ],
        
        // Revenue Amount Badges
        'first_100k' => [
            'name' => 'First 100K',
            'description' => 'K 100,000 total revenue',
            'icon' => '💰',
            'threshold' => 100000,
            'metric' => 'revenue_total',
            'category' => 'revenue',
        ],
        'half_million' => [
            'name' => 'Half Million',
            'description' => 'K 500,000 total revenue',
            'icon' => '💵',
            'threshold' => 500000,
            'metric' => 'revenue_total',
            'category' => 'revenue',
        ],
        'millionaire' => [
            'name' => 'Millionaire',
            'description' => 'K 1M total revenue',
            'icon' => '💎',
            'threshold' => 1000000,
            'metric' => 'revenue_total',
            'category' => 'revenue',
        ],
        'five_million_club' => [
            'name' => 'Five Million Club',
            'description' => 'K 5M total revenue',
            'icon' => '🌟',
            'threshold' => 5000000,
            'metric' => 'revenue_total',
            'category' => 'revenue',
        ],
        'ten_million_maker' => [
            'name' => 'Ten Million Maker',
            'description' => 'K 10M total revenue',
            'icon' => '🚀',
            'threshold' => 10000000,
            'metric' => 'revenue_total',
            'category' => 'revenue',
        ],

        // =====================
        // CONSISTENCY BADGES
        // =====================
        'three_day_starter' => [
            'name' => 'Three Day Starter',
            'description' => '3-day streak',
            'icon' => '✨',
            'threshold' => 3,
            'metric' => 'streak_days',
            'category' => 'consistency',
        ],
        'week_warrior' => [
            'name' => 'Week Warrior',
            'description' => '7-day streak',
            'icon' => '🔥',
            'threshold' => 7,
            'metric' => 'streak_days',
            'category' => 'consistency',
        ],
        'two_week_streak' => [
            'name' => 'Two Week Streak',
            'description' => '14-day streak',
            'icon' => '⚡',
            'threshold' => 14,
            'metric' => 'streak_days',
            'category' => 'consistency',
        ],
        'three_week_habit' => [
            'name' => 'Three Week Habit',
            'description' => '21-day streak',
            'icon' => '🎯',
            'threshold' => 21,
            'metric' => 'streak_days',
            'category' => 'consistency',
        ],
        'month_master' => [
            'name' => 'Month Master',
            'description' => '30-day streak',
            'icon' => '🌟',
            'threshold' => 30,
            'metric' => 'streak_days',
            'category' => 'consistency',
        ],
        'six_week_strong' => [
            'name' => 'Six Week Strong',
            'description' => '45-day streak',
            'icon' => '💫',
            'threshold' => 45,
            'metric' => 'streak_days',
            'category' => 'consistency',
        ],
        'two_month_titan' => [
            'name' => 'Two Month Titan',
            'description' => '60-day streak',
            'icon' => '🏅',
            'threshold' => 60,
            'metric' => 'streak_days',
            'category' => 'consistency',
        ],
        'quarterly_consistent' => [
            'name' => 'Quarterly Consistent',
            'description' => '90-day streak',
            'icon' => '💪',
            'threshold' => 90,
            'metric' => 'streak_days',
            'category' => 'consistency',
        ],
        'half_year_hero' => [
            'name' => 'Half Year Hero',
            'description' => '180-day streak',
            'icon' => '🏆',
            'threshold' => 180,
            'metric' => 'streak_days',
            'category' => 'consistency',
        ],
        'yearly_legend' => [
            'name' => 'Yearly Legend',
            'description' => '365-day streak',
            'icon' => '👑',
            'threshold' => 365,
            'metric' => 'streak_days',
            'category' => 'consistency',
        ],

        // =====================
        // FINANCIAL HEALTH BADGES
        // =====================
        'expense_beginner' => [
            'name' => 'Expense Beginner',
            'description' => 'Record 10 expenses',
            'icon' => '📝',
            'threshold' => 10,
            'metric' => 'expenses_count',
            'category' => 'financial',
        ],
        'expense_tracker' => [
            'name' => 'Expense Tracker',
            'description' => 'Record 50 expenses',
            'icon' => '📋',
            'threshold' => 50,
            'metric' => 'expenses_count',
            'category' => 'financial',
        ],
        'expense_master' => [
            'name' => 'Expense Master',
            'description' => 'Record 200 expenses',
            'icon' => '🧾',
            'threshold' => 200,
            'metric' => 'expenses_count',
            'category' => 'financial',
        ],
        'expense_guru' => [
            'name' => 'Expense Guru',
            'description' => 'Record 500 expenses',
            'icon' => '📊',
            'threshold' => 500,
            'metric' => 'expenses_count',
            'category' => 'financial',
        ],
        'invoice_starter' => [
            'name' => 'Invoice Starter',
            'description' => 'Issue 10 invoices',
            'icon' => '📄',
            'threshold' => 10,
            'metric' => 'invoices_count',
            'category' => 'financial',
        ],
        'invoice_pro' => [
            'name' => 'Invoice Pro',
            'description' => 'Issue 50 invoices',
            'icon' => '📑',
            'threshold' => 50,
            'metric' => 'invoices_count',
            'category' => 'financial',
        ],
        'invoice_master' => [
            'name' => 'Invoice Master',
            'description' => 'Issue 100 invoices',
            'icon' => '📃',
            'threshold' => 100,
            'metric' => 'invoices_count',
            'category' => 'financial',
        ],
        'invoice_expert' => [
            'name' => 'Invoice Expert',
            'description' => 'Issue 250 invoices',
            'icon' => '📚',
            'threshold' => 250,
            'metric' => 'invoices_count',
            'category' => 'financial',
        ],
        'profit_pro' => [
            'name' => 'Profit Pro',
            'description' => '3 consecutive profitable months',
            'icon' => '💵',
            'threshold' => 3,
            'metric' => 'profitable_months',
            'category' => 'financial',
        ],

        // =====================
        // CUSTOMER BADGES
        // =====================
        'first_customer' => [
            'name' => 'First Customer',
            'description' => 'Add your first customer',
            'icon' => '🙋',
            'threshold' => 1,
            'metric' => 'customers_count',
            'category' => 'customer',
        ],
        'customer_starter' => [
            'name' => 'Customer Starter',
            'description' => 'Add 10 customers',
            'icon' => '👥',
            'threshold' => 10,
            'metric' => 'customers_count',
            'category' => 'customer',
        ],
        'customer_builder' => [
            'name' => 'Customer Builder',
            'description' => 'Add 25 customers',
            'icon' => '🤝',
            'threshold' => 25,
            'metric' => 'customers_count',
            'category' => 'customer',
        ],
        'customer_collector' => [
            'name' => 'Customer Collector',
            'description' => 'Add 50 customers',
            'icon' => '🌟',
            'threshold' => 50,
            'metric' => 'customers_count',
            'category' => 'customer',
        ],
        'community_builder' => [
            'name' => 'Community Builder',
            'description' => '100 unique customers',
            'icon' => '🏘️',
            'threshold' => 100,
            'metric' => 'customers_count',
            'category' => 'customer',
        ],
        'customer_champion' => [
            'name' => 'Customer Champion',
            'description' => '250 unique customers',
            'icon' => '🏆',
            'threshold' => 250,
            'metric' => 'customers_count',
            'category' => 'customer',
        ],
        'customer_legend' => [
            'name' => 'Customer Legend',
            'description' => '500 unique customers',
            'icon' => '👑',
            'threshold' => 500,
            'metric' => 'customers_count',
            'category' => 'customer',
        ],

        // =====================
        // ORGANIZATION BADGES
        // =====================
        'product_pioneer' => [
            'name' => 'Product Pioneer',
            'description' => 'Add 10 products',
            'icon' => '🏷️',
            'threshold' => 10,
            'metric' => 'products_count',
            'category' => 'organization',
        ],
        'product_pro' => [
            'name' => 'Product Pro',
            'description' => 'Add 50 products',
            'icon' => '📦',
            'threshold' => 50,
            'metric' => 'products_count',
            'category' => 'organization',
        ],
        'inventory_starter' => [
            'name' => 'Inventory Starter',
            'description' => 'Complete first stock take',
            'icon' => '📝',
            'threshold' => 1,
            'metric' => 'stock_takes_count',
            'category' => 'organization',
        ],
        'inventory_master' => [
            'name' => 'Inventory Master',
            'description' => 'Complete 10 stock takes',
            'icon' => '📊',
            'threshold' => 10,
            'metric' => 'stock_takes_count',
            'category' => 'organization',
        ],
    ],

    'level_titles' => [
        1 => 'Emerging Business',
        3 => 'Startup',
        6 => 'Growing Business',
        10 => 'Established Trader',
        15 => 'Rising Star',
        21 => 'Thriving Business',
        30 => 'Industry Player',
        40 => 'Market Leader',
        51 => 'Business Master',
        75 => 'Enterprise Elite',
        100 => 'Legendary Business',
    ],

    'xp_per_level' => 100,
];
