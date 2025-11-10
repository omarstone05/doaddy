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
            { name: 'Insights', href: '/insights', icon: TrendingUp },
            { name: 'AI Chat', href: '/ai-chat', icon: MessageSquare },
            { name: 'Alerts', href: '/alerts', icon: Bell },
        ],
    },
    {
        name: 'Money',
        icon: DollarSign,
        items: [
            { name: 'Overview', href: '/money', icon: DollarSign },
            { name: 'Accounts', href: '/money/accounts', icon: Wallet },
            { name: 'Movements', href: '/money/movements', icon: TrendingUp },
            { name: 'Budgets', href: '/money/budgets', icon: FileText },
        ],
    },
    {
        name: 'Sales',
        icon: ShoppingCart,
        items: [
            { name: 'Overview', href: '/sales', icon: ShoppingCart },
            { name: 'POS', href: '/pos', icon: ShoppingCart },
            { name: 'Register Sessions', href: '/register-sessions', icon: Receipt },
            { name: 'Customers', href: '/customers', icon: Users },
            { name: 'Quotes', href: '/quotes', icon: FileText },
            { name: 'Invoices', href: '/invoices', icon: FileText },
            { name: 'Payments', href: '/payments', icon: DollarSign },
            { name: 'Returns', href: '/sale-returns', icon: Receipt },
        ],
    },
    {
        name: 'People',
        icon: Users,
        items: [
            { name: 'Overview', href: '/people', icon: Users },
            { name: 'Team', href: '/team', icon: Users },
            { name: 'Payroll', href: '/payroll/runs', icon: Wallet },
            { name: 'Leave', href: '/leave/requests', icon: Calendar },
            { name: 'Leave Types', href: '/leave/types', icon: Calendar },
            { name: 'HR', href: '/people/hr', icon: Briefcase },
            { name: 'Commission Rules', href: '/commissions/rules', icon: DollarSign },
            { name: 'Commission Earnings', href: '/commissions/earnings', icon: DollarSign },
        ],
    },
    {
        name: 'Inventory',
        icon: Package,
        items: [
            { name: 'Overview', href: '/inventory', icon: Package },
            { name: 'Products', href: '/products', icon: Box },
            { name: 'Stock', href: '/stock', icon: Package },
            { name: 'Stock Movements', href: '/stock/movements', icon: TrendingUp },
        ],
    },
    {
        name: 'Decisions',
        icon: Target,
        items: [
            { name: 'Overview', href: '/decisions', icon: Target },
            { name: 'Reports', href: '/reports', icon: BarChart3 },
            { name: 'OKRs', href: '/decisions/okrs', icon: Target },
            { name: 'Strategic Goals', href: '/decisions/goals', icon: Target },
            { name: 'Valuation', href: '/decisions/valuation', icon: TrendingUp },
            { name: 'Projects', href: '/projects', icon: FolderKanban },
        ],
    },
    {
        name: 'Compliance',
        icon: Shield,
        items: [
            { name: 'Overview', href: '/compliance', icon: Shield },
            { name: 'Documents', href: '/compliance/documents', icon: FileText },
            { name: 'Licenses', href: '/compliance/licenses', icon: FileCheck },
            { name: 'Tax', href: '/compliance/tax', icon: Receipt },
            { name: 'Audit Trail', href: '/activity-logs', icon: Shield },
            { name: 'Notifications', href: '/notifications', icon: Bell },
        ],
    },
    {
        name: 'Settings',
        icon: Building2,
        items: [
            { name: 'Overview', href: '/settings', icon: Building2 },
            { name: 'Addy Preferences', href: '/settings/addy', icon: MessageSquare },
        ],
    },
];


