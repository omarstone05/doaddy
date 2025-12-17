import {
    LayoutDashboard,
    TrendingUp,
    MessageSquare,
    Bell,
    DollarSign,
    Receipt,
    FileText,
    ShoppingCart,
    Users,
    Calendar,
    Briefcase,
    Wallet,
    Package,
    Box,
    FolderKanban,
    Target,
    BarChart3,
    Building2,
    FileCheck,
    Shield,
    Settings,
} from 'lucide-react';

export const navigation = [
    {
        name: 'Need to Know',
        icon: LayoutDashboard,
        items: [
            { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
        ],
    },
    {
        name: 'Money',
        icon: DollarSign,
        items: [
            { name: 'Overview', href: '/money', icon: DollarSign },
            { name: 'Accounts', href: '/money/accounts', icon: Wallet },
            { name: 'Income', href: '/income', icon: TrendingUp },
            { name: 'Expenses', href: '/money/expenses', icon: TrendingUp },
            { name: 'Transactions', href: '/transactions', icon: TrendingUp },
        ],
    },
    {
        name: 'Sales',
        icon: ShoppingCart,
        items: [
            { name: 'Overview', href: '/sales', icon: ShoppingCart },
            { name: 'Customers', href: '/customers', icon: Users },
            { name: 'Prospects', href: '/prospects', icon: Users },
            { name: 'Quotations', href: '/quotations', icon: FileText },
            { name: 'Invoices', href: '/invoices', icon: FileText },
            { name: 'Payments', href: '/payments', icon: DollarSign },
        ],
    },
    {
        name: 'Expenses',
        icon: Receipt,
        items: [
            { name: 'Overview', href: '/expenses', icon: Receipt },
            { name: 'Vendors', href: '/vendors', icon: Building2 },
            { name: 'Bills', href: '/bills', icon: Receipt },
        ],
    },
    {
        name: 'Inventory',
        icon: Package,
        items: [
            { name: 'Overview', href: '/inventory', icon: Package },
            { name: 'Products', href: '/products', icon: Box },
            { name: 'Assets', href: '/assets', icon: Package },
        ],
    },
    {
        name: 'Reports',
        icon: BarChart3,
        items: [
            { name: 'Overview', href: '/reports', icon: BarChart3 },
            { name: 'Sales', href: '/reports/sales', icon: TrendingUp },
            { name: 'Revenue', href: '/reports/revenue', icon: DollarSign },
            { name: 'Expenses', href: '/reports/expenses', icon: Receipt },
            { name: 'Profit & Loss', href: '/reports/profit-loss', icon: FileText },
        ],
    },
    {
        name: 'Decisions',
        icon: Target,
        items: [
            { name: 'Overview', href: '/decisions', icon: Target },
            { name: 'OKRs', href: '/decisions/okrs', icon: Target },
            { name: 'Strategic Goals', href: '/decisions/goals', icon: Target },
            { name: 'Valuation', href: '/decisions/valuation', icon: TrendingUp },
        ],
    },
    {
        name: 'Consulting',
        icon: Briefcase,
        items: [
            { name: 'Projects', href: '/consulting/projects', icon: Briefcase },
        ],
    },
    {
        name: 'Settings',
        icon: Building2,
        items: [
            { name: 'Overview', href: '/settings', icon: Building2 },
            { name: 'Team', href: '/team', icon: Users },
            { name: 'Addy Preferences', href: '/settings/addy', icon: MessageSquare },
            { name: 'Invoice Settings', href: '/settings/invoices', icon: FileText },
        ],
    },
];

