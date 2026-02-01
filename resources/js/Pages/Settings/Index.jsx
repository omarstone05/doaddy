import { Head, useForm, usePage, router, Link } from '@inertiajs/react';
import { Navigation } from '@/Components/layout/Navigation';
import FlashMessages from '@/Components/FlashMessages';
import { Button } from '@/Components/ui/Button';
import { Toggle } from '@/Components/ui/Toggle';
import DepartmentModal from '@/Components/Departments/DepartmentModal';
import TaxRateModal from '@/Components/Tax/TaxRateModal';
import { useState, useRef, useEffect } from 'react';
import axios from 'axios';
import { currencies, getCurrenciesByRegion } from '@/utils/currency';

// Icons
import {
    Settings as SettingsIcon,
    Building2,
    FileText,
    Users,
    Package,
    Sparkles,
    Link2,
    Bell,
    Shield,
    ChevronRight,
    Save,
    Upload,
    X,
    Image as ImageIcon,
    Ticket,
    Cloud,
    CloudOff,
    CheckCircle2,
    Plus,
    Eye,
    Edit,
    Trash2,
    Search,
    Filter,
    ArrowLeft,
    User,
    Mail,
    Lock,
    CreditCard,
    Globe,
    Clock,
    Palette,
    Download,
    Database,
    Key,
    Smartphone,
    LogOut,
    Info,
    XCircle,
    AlertTriangle,
    HelpCircle,
    ExternalLink,
    Receipt,
} from 'lucide-react';

// Settings Navigation Items
// Note: Organization, Team, Security, and Google Drive settings are managed in Penda Cloud
const settingsNav = [
    { id: 'billing', name: 'Billing & Documents', icon: FileText, description: 'Invoices, quotes & banking' },
    { id: 'tax', name: 'Tax', icon: Receipt, description: 'Tax rates & compliance' },
    { id: 'team', name: 'Team', icon: Users, description: 'Manage team members' },
    { id: 'gamification', name: 'Gamification', icon: Sparkles, description: 'XP, badges & streaks' },
    { id: 'modules', name: 'Modules', icon: Package, description: 'Enable/disable features' },
    { id: 'assistant', name: 'AI Assistant', icon: Sparkles, description: 'Addy preferences' },
    { id: 'notifications', name: 'Notifications', icon: Bell, description: 'Alert preferences' },
];

export default function SettingsIndex({ 
    organization, 
    user, 
    teamMembers, 
    teamMember, 
    teamViewMode, 
    departments, 
    users, 
    organizationRoles, 
    userRole, 
    filters,
    modules: initialModules,
    invoiceSettings,
    bankDetails,
    addySettings,
    userPattern,
    taxRates: initialTaxRates = [],
    digitaxAvailable = false,
    digitaxCredentials = null,
    gamificationData = {},
}) {
    const { flash, url } = usePage().props;
    const [activeSection, setActiveSection] = useState('billing');
    const [activeSubTab, setActiveSubTab] = useState(null);
    
    // Organization states
    const [logoPreview, setLogoPreview] = useState(organization?.logo_url || null);
    const [logoFile, setLogoFile] = useState(null);
    const [logoUploading, setLogoUploading] = useState(false);
    const fileInputRef = useRef(null);
    
    // Drive states
    const [useOwnDrive, setUseOwnDrive] = useState(user?.use_own_drive || false);
    const [updatingDrive, setUpdatingDrive] = useState(false);
    
    // Modules states
    const [modules, setModules] = useState(initialModules || []);
    const [togglingModules, setTogglingModules] = useState({});
    
    // Tax states
    const [taxRates, setTaxRates] = useState(initialTaxRates || []);
    const [showTaxRateModal, setShowTaxRateModal] = useState(false);
    const [editingTaxRate, setEditingTaxRate] = useState(null);
    const [loadingTaxRates, setLoadingTaxRates] = useState(false);
    
    // DigiTax states
    const [digitaxApiKey, setDigitaxApiKey] = useState('');
    const [digitaxEnvironment, setDigitaxEnvironment] = useState(digitaxCredentials?.environment || 'sandbox');
    const [digitaxSaving, setDigitaxSaving] = useState(false);
    const [digitaxTesting, setDigitaxTesting] = useState(false);
    const [digitaxCredentialId, setDigitaxCredentialId] = useState(digitaxCredentials?.id || null);
    const [digitaxStatus, setDigitaxStatus] = useState(digitaxCredentials?.status || null); // 'connected', 'pending', 'error'
    const [digitaxDetails, setDigitaxDetails] = useState(digitaxCredentials || null);
    
    // Support modal
    const [showSupportModal, setShowSupportModal] = useState(false);
    const [showDepartmentModal, setShowDepartmentModal] = useState(false);
    const [editingDepartment, setEditingDepartment] = useState(null);
    
    // Team member forms
    const [showInviteForm, setShowInviteForm] = useState(false);
    
    // Message states
    const [successMessage, setSuccessMessage] = useState(null);
    const [errorMessage, setErrorMessage] = useState(null);

    // Organization form
    const orgForm = useForm({
        name: organization?.name || '',
        slug: organization?.slug || '',
        business_type: organization?.business_type || '',
        industry: organization?.industry || '',
        tone_preference: organization?.tone_preference || '',
        currency: organization?.currency || 'ZMW',
        timezone: organization?.timezone || 'Africa/Lusaka',
        address: organization?.address || '',
        phone: organization?.phone || '',
        email: organization?.email || '',
        website: organization?.website || '',
    });

    // Invoice settings form
    const invoiceForm = useForm({
        company_name: invoiceSettings?.company_name || '',
        company_address: invoiceSettings?.company_address || '',
        company_city: invoiceSettings?.company_city || '',
        company_phone: invoiceSettings?.company_phone || '',
        company_email: invoiceSettings?.company_email || '',
        company_tax_id: invoiceSettings?.company_tax_id || '',
        invoice_prefix: invoiceSettings?.invoice_prefix || 'INV',
        quote_prefix: invoiceSettings?.quote_prefix || 'QUO',
        default_due_days: invoiceSettings?.default_due_days || 30,
        quote_validity_days: invoiceSettings?.quote_validity_days || 30,
        invoice_notes: invoiceSettings?.invoice_notes || '',
        invoice_terms: invoiceSettings?.invoice_terms || '',
        bank_name: bankDetails?.bank_name || '',
        account_name: bankDetails?.account_name || '',
        account_number: bankDetails?.account_number || '',
        branch: bankDetails?.branch || '',
        swift_code: bankDetails?.swift_code || '',
    });

    // Addy settings form
    const addyForm = useForm({
        tone: addySettings?.tone || 'professional',
        enable_predictions: addySettings?.enable_predictions ?? true,
        enable_proactive_suggestions: addySettings?.enable_proactive_suggestions ?? true,
        work_style: userPattern?.work_style || 'balanced',
        adhd_mode: userPattern?.adhd_mode || false,
        preferred_task_chunk_size: userPattern?.preferred_task_chunk_size || 3,
        quiet_hours_start: addySettings?.quiet_hours_start || '',
        quiet_hours_end: addySettings?.quiet_hours_end || '',
    });

    // Notification settings form
    const notificationForm = useForm({
        email_notifications: true,
        push_notifications: true,
        task_reminders: true,
        weekly_summary: true,
        marketing_emails: false,
    });

    // Team create form
    const teamCreateForm = useForm({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        employee_number: '',
        hire_date: '',
        job_title: '',
        salary: '',
        employment_type: '',
        department_id: '',
        user_id: '',
        is_active: true,
    });
    
    // Team edit form
    const teamEditForm = useForm({
        first_name: teamMember?.first_name || '',
        last_name: teamMember?.last_name || '',
        email: teamMember?.email || '',
        phone: teamMember?.phone || '',
        employee_number: teamMember?.employee_number || '',
        hire_date: teamMember?.hire_date || '',
        job_title: teamMember?.job_title || '',
        salary: teamMember?.salary || '',
        employment_type: teamMember?.employment_type || '',
        department_id: teamMember?.department_id || '',
        user_id: teamMember?.user_id || '',
        is_active: teamMember?.is_active ?? true,
    });
    
    const accessSystemForm = useForm({
        email: teamMember?.email || '',
        password: '',
        action: 'invite',
    });
    
    const permissionsForm = useForm({
        role_id: userRole?.id || '',
    });
    
    const supportForm = useForm({
        subject: '',
        description: '',
        priority: 'medium',
        category: 'other',
    });

    // Determine active section from URL on mount and when URL changes (for team navigation)
    useEffect(() => {
        // Safely get the pathname from url prop or window.location
        let path = window.location.pathname;
        
        if (url) {
            try {
                // Check if it's a full URL (starts with http:// or https://)
                if (typeof url === 'string' && (url.startsWith('http://') || url.startsWith('https://'))) {
                    path = new URL(url).pathname;
                } else if (typeof url === 'string') {
                    // If it's already a pathname, use it directly
                    path = url.startsWith('/') ? url : `/${url}`;
                }
            } catch (e) {
                // If URL parsing fails, use window.location.pathname
                console.warn('Failed to parse URL:', e);
                path = window.location.pathname;
            }
        }
        
        if (path.includes('/settings/team')) {
            setActiveSection('team');
        } else if (path.includes('/settings/modules')) {
            setActiveSection('modules');
        } else if (path.includes('/settings/invoices')) {
            setActiveSection('billing');
        } else if (path.includes('/settings/addy')) {
            setActiveSection('assistant');
        } else if (path.includes('/settings/tax')) {
            setActiveSection('tax');
        } else if (path.includes('/settings')) {
            setActiveSection('organization');
        }
    }, [url]);

    // Update forms when props change
    useEffect(() => {
        if (teamMember && teamViewMode === 'edit') {
            teamEditForm.setData({
                first_name: teamMember.first_name || '',
                last_name: teamMember.last_name || '',
                email: teamMember.email || '',
                phone: teamMember.phone || '',
                employee_number: teamMember.employee_number || '',
                hire_date: teamMember.hire_date || '',
                job_title: teamMember.job_title || '',
                salary: teamMember.salary || '',
                employment_type: teamMember.employment_type || '',
                department_id: teamMember.department_id || '',
                user_id: teamMember.user_id || '',
                is_active: teamMember.is_active ?? true,
            });
        }
    }, [teamMember, teamViewMode]);
    
    useEffect(() => {
        if (teamMember && teamViewMode === 'show') {
            accessSystemForm.setData({
                email: teamMember.email || '',
                password: '',
                action: 'invite',
            });
        }
    }, [teamMember, teamViewMode]);

    useEffect(() => {
        setModules(initialModules || []);
    }, [initialModules]);

    useEffect(() => {
        setLogoPreview(organization?.logo_url || null);
        setLogoFile(null);
    }, [organization?.logo_url]);

    // Clear messages after 5 seconds
    useEffect(() => {
        if (successMessage) {
            const timer = setTimeout(() => setSuccessMessage(null), 5000);
            return () => clearTimeout(timer);
        }
    }, [successMessage]);

    useEffect(() => {
        if (errorMessage) {
            const timer = setTimeout(() => setErrorMessage(null), 5000);
            return () => clearTimeout(timer);
        }
    }, [errorMessage]);

    // Handlers
    const handleLogoChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setLogoFile(file);
            const reader = new FileReader();
            reader.onloadend = () => setLogoPreview(reader.result);
            reader.readAsDataURL(file);
        }
    };

    const handleRemoveLogo = () => {
        setLogoFile(null);
        setLogoPreview(organization?.logo_url || null);
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    const handleLogoSubmit = (e) => {
        e.preventDefault();
        if (!logoFile || !(logoFile instanceof File)) return;

        setLogoUploading(true);
        const formData = new FormData();
        formData.append('logo', logoFile);

        router.post('/settings/logo', formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                setLogoFile(null);
                if (fileInputRef.current) fileInputRef.current.value = '';
                router.reload({ only: ['organization'] });
            },
            onFinish: () => setLogoUploading(false),
        });
    };

    const handleOrgSubmit = (e) => {
        e.preventDefault();
        orgForm.put('/settings', {
            preserveScroll: true,
            onSuccess: () => setSuccessMessage('Organization settings saved successfully'),
        });
    };

    const handleInvoiceSubmit = (e) => {
        e.preventDefault();
        invoiceForm.post('/settings/invoices', {
            preserveScroll: true,
            onSuccess: () => setSuccessMessage('Billing settings saved successfully'),
        });
    };

    const handleAddySubmit = (e) => {
        e.preventDefault();
        addyForm.post('/settings/addy', {
            preserveScroll: true,
            onSuccess: () => setSuccessMessage('AI assistant settings saved successfully'),
        });
    };

    const handleNotificationSubmit = (e) => {
        e.preventDefault();
        setSuccessMessage('Notification preferences saved successfully');
    };

    const handleModuleToggle = async (moduleName) => {
        const module = modules.find(m => m.name === moduleName);
        if (!module) return;

        const newEnabledState = !module.enabled;
        
        setModules(prev => prev.map(m => 
            m.name === moduleName ? { ...m, enabled: newEnabledState } : m
        ));
        setTogglingModules(prev => ({ ...prev, [moduleName]: true }));
        
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await axios.post(`/modules/${moduleName}/toggle`, {}, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
            },
        });

            if (response.data?.success) {
                setSuccessMessage(response.data.message || 'Module toggled successfully');
                window.dispatchEvent(new CustomEvent('moduleToggled', {
                    detail: { moduleName, enabled: newEnabledState }
                }));

                await new Promise(resolve => setTimeout(resolve, 100));
                const modulesResponse = await axios.get('/api/modules/all', {
                    params: { _t: Date.now() }
                });
                if (modulesResponse.data?.modules) {
                    setModules(modulesResponse.data.modules);
                }
            } else {
                throw new Error(response.data?.error || 'Failed to toggle module');
            }
        } catch (error) {
            setModules(prev => prev.map(m => 
                m.name === moduleName ? { ...m, enabled: !newEnabledState } : m
            ));
            setErrorMessage(error.response?.data?.error || 'Failed to toggle module');
        } finally {
            setTogglingModules(prev => ({ ...prev, [moduleName]: false }));
        }
    };
    
    const handleTeamCreateSubmit = (e) => {
        e.preventDefault();
        teamCreateForm.post('/settings/team', {
            preserveScroll: true,
            onSuccess: () => router.visit('/settings/team'),
        });
    };
    
    const handleTeamEditSubmit = (e) => {
        e.preventDefault();
        teamEditForm.put(`/settings/team/${teamMember.id}`, {
            preserveScroll: true,
            onSuccess: () => router.visit(`/settings/team/${teamMember.id}`),
        });
    };

    const handleSupportSubmit = (e) => {
        e.preventDefault();
        supportForm.post('/support/tickets', {
            preserveScroll: true,
            onSuccess: () => {
                setShowSupportModal(false);
                supportForm.reset();
                router.visit('/support/tickets');
            },
        });
    };
    
    const formatCurrency = (amount) => {
        if (!amount) return 'ZMW 0.00';
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    // Section Card Component
    const SectionCard = ({ title, description, children, className = '' }) => (
        <div className={`bg-white rounded-2xl border border-gray-100 shadow-sm ${className}`}>
            {(title || description) && (
                <div className="px-6 py-5 border-b border-gray-100">
                    {title && <h3 className="text-lg font-semibold text-gray-900">{title}</h3>}
                    {description && <p className="text-sm text-gray-500 mt-1">{description}</p>}
                    </div>
            )}
            <div className="p-6">{children}</div>
                </div>
    );

    // Form Input Component
    const FormInput = ({ label, required, error, className = '', ...props }) => (
        <div className={className}>
            {label && (
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    {label} {required && <span className="text-red-500">*</span>}
                </label>
            )}
            <input
                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all"
                {...props}
            />
            {error && <p className="mt-1.5 text-sm text-red-500">{error}</p>}
                    </div>
    );

    // Form Select Component
    const FormSelect = ({ label, required, error, children, className = '', ...props }) => (
        <div className={className}>
            {label && (
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    {label} {required && <span className="text-red-500">*</span>}
                </label>
            )}
            <select
                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all"
                {...props}
            >
                {children}
            </select>
            {error && <p className="mt-1.5 text-sm text-red-500">{error}</p>}
                        </div>
    );

    // Form Textarea Component
    const FormTextarea = ({ label, required, error, className = '', ...props }) => (
        <div className={className}>
            {label && (
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    {label} {required && <span className="text-red-500">*</span>}
                        </label>
            )}
            <textarea
                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all resize-none"
                {...props}
            />
            {error && <p className="mt-1.5 text-sm text-red-500">{error}</p>}
        </div>
    );

    // Render Organization Section
    const renderOrganizationSection = () => (
        <div className="space-y-6">
            {/* Logo Upload */}
            <SectionCard title="Organization Logo" description="Upload your company logo for branding">
                <form onSubmit={handleLogoSubmit}>
                            <div className="flex items-start gap-6">
                                <div className="flex-shrink-0">
                                    {logoPreview ? (
                                <div className="relative group">
                                            <img
                                                src={logoPreview}
                                                alt="Organization logo"
                                        className="h-28 w-28 object-contain border-2 border-gray-100 rounded-2xl bg-white p-3"
                                            />
                                            {logoFile && (
                                                <button
                                                    type="button"
                                                    onClick={handleRemoveLogo}
                                            className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1.5 hover:bg-red-600 transition-colors shadow-lg"
                                                >
                                            <X className="h-3.5 w-3.5" />
                                                </button>
                                            )}
                                        </div>
                                    ) : (
                                <div className="h-28 w-28 border-2 border-dashed border-gray-200 rounded-2xl flex items-center justify-center bg-gray-50">
                                    <ImageIcon className="h-10 w-10 text-gray-300" />
                                        </div>
                                    )}
                                </div>
                                <div className="flex-1">
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept="image/*"
                                        onChange={handleLogoChange}
                                        className="hidden"
                                        id="logo-upload"
                                    />
                                    <div className="flex items-center gap-3">
                                        <label
                                            htmlFor="logo-upload"
                                    className="inline-flex items-center px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 cursor-pointer transition-colors"
                                        >
                                            <Upload className="h-4 w-4 mr-2" />
                                    {logoPreview && !logoFile ? 'Change Logo' : 'Upload Logo'}
                                        </label>
                                        {logoFile && (
                                    <Button type="submit" disabled={logoUploading}>
                                                {logoUploading ? 'Uploading...' : 'Save Logo'}
                                            </Button>
                                        )}
                                    </div>
                            <p className="mt-3 text-xs text-gray-500">
                                        Recommended: Square image, max 2MB. Formats: JPG, PNG, GIF, SVG
                                    </p>
                                </div>
                            </div>
                        </form>
            </SectionCard>

            {/* Organization Details */}
            <SectionCard title="Organization Details" description="Basic information about your company">
                <form onSubmit={handleOrgSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <FormInput
                            label="Organization Name"
                                    required
                            value={orgForm.data.name}
                            onChange={(e) => orgForm.setData('name', e.target.value)}
                            error={orgForm.errors.name}
                        />
                        <FormInput
                            label="Slug"
                            value={orgForm.data.slug}
                            onChange={(e) => orgForm.setData('slug', e.target.value)}
                                    placeholder="organization-slug"
                            error={orgForm.errors.slug}
                        />
                        <FormInput
                            label="Business Type"
                            value={orgForm.data.business_type}
                            onChange={(e) => orgForm.setData('business_type', e.target.value)}
                            placeholder="e.g., Retail, Service"
                        />
                        <FormInput
                            label="Industry"
                            value={orgForm.data.industry}
                            onChange={(e) => orgForm.setData('industry', e.target.value)}
                            placeholder="e.g., Technology, Healthcare"
                        />
                        <FormSelect
                            label="Currency"
                            value={orgForm.data.currency}
                            onChange={(e) => orgForm.setData('currency', e.target.value)}
                        >
                            {Object.entries(getCurrenciesByRegion()).map(([region, regionCurrencies]) => (
                                <optgroup key={region} label={region}>
                                    {regionCurrencies.map(c => (
                                        <option key={c.code} value={c.code}>
                                            {c.symbol} - {c.code} ({c.name})
                                        </option>
                                    ))}
                                </optgroup>
                            ))}
                        </FormSelect>
                        <FormSelect
                            label="Timezone"
                            value={orgForm.data.timezone}
                            onChange={(e) => orgForm.setData('timezone', e.target.value)}
                                >
                                    <option value="Africa/Lusaka">Africa/Lusaka (CAT)</option>
                                    <option value="Africa/Johannesburg">Africa/Johannesburg (SAST)</option>
                                    <option value="Africa/Nairobi">Africa/Nairobi (EAT)</option>
                                    <option value="Africa/Lagos">Africa/Lagos (WAT)</option>
                                    <option value="UTC">UTC</option>
                        </FormSelect>
                            </div>

                    <div className="pt-4 border-t border-gray-100">
                        <h4 className="text-sm font-semibold text-gray-900 mb-4">Contact Information</h4>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <FormInput
                                label="Phone"
                                type="tel"
                                value={orgForm.data.phone}
                                onChange={(e) => orgForm.setData('phone', e.target.value)}
                                placeholder="+260 XXX XXX XXX"
                            />
                            <FormInput
                                label="Email"
                                type="email"
                                value={orgForm.data.email}
                                onChange={(e) => orgForm.setData('email', e.target.value)}
                            />
                            <FormInput
                                label="Website"
                                type="url"
                                value={orgForm.data.website}
                                onChange={(e) => orgForm.setData('website', e.target.value)}
                                placeholder="https://example.com"
                            />
                            <FormInput
                                label="Address"
                                value={orgForm.data.address}
                                onChange={(e) => orgForm.setData('address', e.target.value)}
                            />
                            </div>
                        </div>

                    <div className="flex justify-end pt-4">
                        <Button type="submit" disabled={orgForm.processing}>
                                <Save className="h-4 w-4 mr-2" />
                            {orgForm.processing ? 'Saving...' : 'Save Changes'}
                            </Button>
                        </div>
                    </form>
            </SectionCard>

            {/* Support */}
            <SectionCard>
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-teal-50 flex items-center justify-center">
                            <HelpCircle className="h-6 w-6 text-teal-600" />
                        </div>
                        <div>
                            <h3 className="font-semibold text-gray-900">Need Help?</h3>
                            <p className="text-sm text-gray-500">Create a support ticket and we'll assist you</p>
                    </div>
                                </div>
                    <Button onClick={() => setShowSupportModal(true)} variant="secondary">
                        <Ticket className="h-4 w-4 mr-2" />
                        Create Ticket
                                </Button>
                            </div>
            </SectionCard>
                            </div>
    );

    // Render Billing Section
    const renderBillingSection = () => (
        <div className="space-y-6">
            {/* Invoice Settings */}
            <SectionCard title="Invoice & Quote Settings" description="Configure your document preferences">
                <form onSubmit={handleInvoiceSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <FormInput
                            label="Invoice Prefix"
                            value={invoiceForm.data.invoice_prefix}
                            onChange={(e) => invoiceForm.setData('invoice_prefix', e.target.value)}
                            placeholder="INV"
                        />
                        <FormInput
                            label="Quote Prefix"
                            value={invoiceForm.data.quote_prefix}
                            onChange={(e) => invoiceForm.setData('quote_prefix', e.target.value)}
                            placeholder="QUO"
                        />
                        <FormInput
                            label="Default Due Days"
                            type="number"
                            min="1"
                            max="365"
                            value={invoiceForm.data.default_due_days}
                            onChange={(e) => invoiceForm.setData('default_due_days', parseInt(e.target.value) || 30)}
                        />
                        <FormInput
                            label="Quote Validity (Days)"
                            type="number"
                            min="1"
                            max="365"
                            value={invoiceForm.data.quote_validity_days}
                            onChange={(e) => invoiceForm.setData('quote_validity_days', parseInt(e.target.value) || 30)}
                        />
                                </div>
                                
                    <FormTextarea
                        label="Default Invoice Notes"
                        value={invoiceForm.data.invoice_notes}
                        onChange={(e) => invoiceForm.setData('invoice_notes', e.target.value)}
                        rows={3}
                        placeholder="Notes to appear on invoices"
                    />

                    <FormTextarea
                        label="Default Terms & Conditions"
                        value={invoiceForm.data.invoice_terms}
                        onChange={(e) => invoiceForm.setData('invoice_terms', e.target.value)}
                        rows={4}
                        placeholder="Payment terms, late fees, etc."
                    />

                    <div className="pt-4 border-t border-gray-100">
                        <h4 className="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <CreditCard className="h-4 w-4 text-gray-500" />
                            Banking Details
                        </h4>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <FormInput
                                label="Bank Name"
                                value={invoiceForm.data.bank_name}
                                onChange={(e) => invoiceForm.setData('bank_name', e.target.value)}
                            />
                            <FormInput
                                label="Account Name"
                                value={invoiceForm.data.account_name}
                                onChange={(e) => invoiceForm.setData('account_name', e.target.value)}
                            />
                            <FormInput
                                label="Account Number"
                                value={invoiceForm.data.account_number}
                                onChange={(e) => invoiceForm.setData('account_number', e.target.value)}
                            />
                            <FormInput
                                label="Branch"
                                value={invoiceForm.data.branch}
                                onChange={(e) => invoiceForm.setData('branch', e.target.value)}
                            />
                            <FormInput
                                label="SWIFT Code"
                                value={invoiceForm.data.swift_code}
                                onChange={(e) => invoiceForm.setData('swift_code', e.target.value)}
                            />
                                        </div>
                                </div>

                    <div className="flex justify-end pt-4">
                        <Button type="submit" disabled={invoiceForm.processing}>
                            <Save className="h-4 w-4 mr-2" />
                            {invoiceForm.processing ? 'Saving...' : 'Save Settings'}
                        </Button>
                    </div>
                </form>
            </SectionCard>
                </div>
    );

    // Render Tax Section
    const renderTaxSection = () => {
        const handleCreateTaxRate = () => {
            setEditingTaxRate(null);
            setShowTaxRateModal(true);
        };

        const handleEditTaxRate = (taxRate) => {
            setEditingTaxRate(taxRate);
            setShowTaxRateModal(true);
        };

        const handleDeleteTaxRate = async (taxRate) => {
            if (!taxRate || !taxRate.id) {
                setErrorMessage('Invalid tax rate');
                return;
            }

            if (!confirm(`Are you sure you want to delete "${taxRate.name || 'this tax rate'}"?`)) {
                return;
            }

            try {
                await axios.delete(`/api/tax-rates/${taxRate.id}`);
                setTaxRates(taxRates.filter(tr => tr && tr.id && tr.id !== taxRate.id));
                setSuccessMessage('Tax rate deleted successfully');
                setTimeout(() => setSuccessMessage(null), 3000);
            } catch (error) {
                console.error('Error deleting tax rate:', error);
                setErrorMessage(error.response?.data?.error || error.message || 'Failed to delete tax rate');
                setTimeout(() => setErrorMessage(null), 5000);
            }
        };

        const handleSaveTaxRate = async (formData) => {
            try {
                const isEditing = !!editingTaxRate;
                let response;
                if (isEditing) {
                    response = await axios.put(`/api/tax-rates/${editingTaxRate.id}`, formData);
                    setTaxRates(taxRates.map(tr => tr.id === editingTaxRate.id ? response.data : tr));
                } else {
                    response = await axios.post('/api/tax-rates', formData);
                    setTaxRates([...taxRates, response.data]);
                }
                setShowTaxRateModal(false);
                setEditingTaxRate(null);
                setSuccessMessage(`Tax rate ${isEditing ? 'updated' : 'created'} successfully`);
                setTimeout(() => setSuccessMessage(null), 3000);
            } catch (error) {
                console.error('Error saving tax rate:', error);
                setErrorMessage(error.response?.data?.message || error.message || 'Failed to save tax rate');
                setTimeout(() => setErrorMessage(null), 5000);
            }
        };

        // DigiTax handlers
        const handleSaveDigitax = async () => {
            if (!digitaxApiKey.trim()) {
                setErrorMessage('Please enter your DigiTax API Key');
                setTimeout(() => setErrorMessage(null), 3000);
                return;
            }
            
            setDigitaxSaving(true);
            try {
                const response = await axios.post('/api/settings/digitax', {
                    api_key: digitaxApiKey,
                    environment: digitaxEnvironment,
                });
                
                if (response.data?.success) {
                    setDigitaxCredentialId(response.data.credential_id);
                    setDigitaxStatus('disconnected'); // Saved but not tested yet
                    setSuccessMessage('DigiTax credentials saved. Please test the connection.');
                    setTimeout(() => setSuccessMessage(null), 3000);
                } else {
                    throw new Error(response.data?.error || 'Failed to save credentials');
                }
            } catch (error) {
                console.error('Error saving DigiTax credentials:', error);
                setErrorMessage(error.response?.data?.error || error.message || 'Failed to save DigiTax credentials');
                setTimeout(() => setErrorMessage(null), 5000);
            } finally {
                setDigitaxSaving(false);
            }
        };

        const handleTestDigitax = async () => {
            if (!digitaxApiKey.trim()) {
                setErrorMessage('Please enter your DigiTax API Key first');
                setTimeout(() => setErrorMessage(null), 3000);
                return;
            }
            
            setDigitaxTesting(true);
            try {
                const response = await axios.post('/api/settings/digitax/test', {
                    api_key: digitaxApiKey,
                    environment: digitaxEnvironment,
                });
                
                if (response.data?.success) {
                    setDigitaxStatus('connected');
                    // Update details with response data
                    if (response.data?.data) {
                        setDigitaxDetails(prev => ({
                            ...prev,
                            ...response.data.data,
                            is_active: true,
                            status: 'connected',
                            last_tested_at: new Date().toISOString(),
                        }));
                    }
                    setSuccessMessage(response.data?.message || 'DigiTax connection successful!');
                    setTimeout(() => setSuccessMessage(null), 3000);
                    // Reload page to get fresh data from backend
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    setDigitaxStatus('error');
                    throw new Error(response.data?.error || 'Connection test failed');
                }
            } catch (error) {
                console.error('Error testing DigiTax connection:', error);
                setDigitaxStatus('error');
                setErrorMessage(error.response?.data?.error || error.message || 'Failed to test DigiTax connection');
                setTimeout(() => setErrorMessage(null), 5000);
            } finally {
                setDigitaxTesting(false);
            }
        };

        return (
            <div className="space-y-6">
                {/* Tax Rates Management */}
                <SectionCard 
                    title="Tax Rates" 
                    description="Manage tax rates for your organization. These rates can be used across invoices, quotations, and other documents."
                >
                    <div className="space-y-4">
                        <div className="flex justify-between items-center">
                            <p className="text-sm text-gray-600">
                                {taxRates.length === 0 
                                    ? 'No tax rates configured. Create your first tax rate to get started.'
                                    : `${taxRates.length} tax rate${taxRates.length !== 1 ? 's' : ''} configured`
                                }
                            </p>
                            <Button onClick={handleCreateTaxRate}>
                                <Plus className="h-4 w-4 mr-2" />
                                Create Tax Rate
                            </Button>
                        </div>

                        {taxRates.length > 0 && (
                            <div className="border border-gray-200 rounded-lg overflow-hidden">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {taxRates.filter(tr => tr && tr.id).map((taxRate) => (
                                            <tr key={taxRate.id} className="hover:bg-gray-50">
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-sm font-medium text-gray-900">{taxRate.name || 'Unnamed'}</span>
                                                        {taxRate.is_default && (
                                                            <span className="px-2 py-0.5 text-xs font-medium bg-teal-100 text-teal-700 rounded-full">Default</span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                    {taxRate.code || '-'}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {taxRate.rate ? parseFloat(taxRate.rate).toFixed(2) : '0.00'}%
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-500 capitalize">
                                                    {taxRate.tax_type ? taxRate.tax_type.replace('_', ' ') : 'VAT'}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                        taxRate.is_active 
                                                            ? 'bg-green-100 text-green-700' 
                                                            : 'bg-gray-100 text-gray-700'
                                                    }`}>
                                                        {taxRate.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button
                                                            onClick={() => handleEditTaxRate(taxRate)}
                                                            className="text-teal-600 hover:text-teal-900"
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </button>
                                                        <button
                                                            onClick={() => handleDeleteTaxRate(taxRate)}
                                                            className="text-red-600 hover:text-red-900"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </SectionCard>

                {/* Digitax Integration (Smart Invoice enabled) */}
                {(digitaxAvailable || modules?.some(m => m.alias === 'smart-invoice' && m.enabled)) && (
                    <SectionCard 
                        title="DigiTax Smart Invoice" 
                        description="ZRA Smart Invoice compliance for Zambia"
                    >
                        <div className="space-y-4">
                            <div className="flex items-start gap-3 p-4 bg-emerald-50 rounded-lg border border-emerald-100">
                                <Receipt className="h-5 w-5 text-emerald-600 mt-0.5 flex-shrink-0" />
                                <div className="flex-1">
                                    <p className="text-sm text-emerald-900 font-medium mb-1">ZRA Smart Invoice Integration</p>
                                    <p className="text-sm text-emerald-700">
                                        Connect to DigiTax to automatically submit invoices to ZRA's Smart Invoice system.
                                        Invoices will include a QR code for ZRA verification.
                                    </p>
                                </div>
                            </div>
                            
                            {/* DigiTax Configuration Form */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1.5">
                                        DigiTax API Key
                                    </label>
                                    <input
                                        type="password"
                                        placeholder="api_key_..."
                                        className="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        value={digitaxApiKey}
                                        onChange={(e) => setDigitaxApiKey(e.target.value)}
                                    />
                                    <p className="text-xs text-gray-500 mt-1">From DigiTax Integrations tab</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1.5">
                                        Environment
                                    </label>
                                    <select
                                        className="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        value={digitaxEnvironment}
                                        onChange={(e) => setDigitaxEnvironment(e.target.value)}
                                    >
                                        <option value="sandbox">Sandbox (Testing)</option>
                                        <option value="production">Production (Live)</option>
                                    </select>
                                </div>
                            </div>
                            
                            {/* Connection Status */}
                            {digitaxStatus && (
                                <div className={`flex items-center gap-2 px-3 py-2 rounded-lg text-sm ${
                                    digitaxStatus === 'connected' ? 'bg-green-50 text-green-700' :
                                    digitaxStatus === 'error' ? 'bg-red-50 text-red-700' :
                                    'bg-yellow-50 text-yellow-700'
                                }`}>
                                    <div className={`w-2 h-2 rounded-full ${
                                        digitaxStatus === 'connected' ? 'bg-green-500' :
                                        digitaxStatus === 'error' ? 'bg-red-500' :
                                        'bg-yellow-500'
                                    }`} />
                                    {digitaxStatus === 'connected' ? 'Connected to DigiTax' :
                                     digitaxStatus === 'error' ? 'Connection failed' :
                                     'Credentials saved - please test connection'}
                                </div>
                            )}
                            
                            <div className="flex items-center justify-between pt-4">
                                <div className="flex items-center gap-2">
                                    <a 
                                        href="https://zm.docs.digitax.tech/docs/start-using-the-api" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        className="text-sm text-teal-600 hover:text-teal-700 flex items-center gap-1"
                                    >
                                        <HelpCircle className="h-4 w-4" />
                                        DigiTax Documentation
                                        <ExternalLink className="h-3 w-3" />
                                    </a>
                                </div>
                                <div className="flex gap-2">
                                    <Button 
                                        variant="secondary" 
                                        size="sm"
                                        onClick={handleTestDigitax}
                                        disabled={digitaxTesting || !digitaxApiKey.trim()}
                                    >
                                        {digitaxTesting ? 'Testing...' : 'Test Connection'}
                                    </Button>
                                    <Button 
                                        variant="primary" 
                                        size="sm"
                                        onClick={handleSaveDigitax}
                                        disabled={digitaxSaving || !digitaxApiKey.trim()}
                                    >
                                        <Save className="h-4 w-4 mr-1" />
                                        {digitaxSaving ? 'Saving...' : 'Save'}
                                    </Button>
                                </div>
                            </div>
                            
                            {/* Smart Invoice Details Panel */}
                            {digitaxDetails?.is_active && (
                                <div className="mt-6 pt-6 border-t border-gray-200">
                                    <h4 className="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                        <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
                                        Smart Invoice Connected
                                    </h4>
                                    <div className="bg-gray-50 rounded-lg p-4">
                                        <dl className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            {digitaxDetails.business_name && (
                                                <div>
                                                    <dt className="text-xs font-medium text-gray-500 uppercase tracking-wider">Business Name</dt>
                                                    <dd className="mt-1 text-sm font-medium text-gray-900">{digitaxDetails.business_name}</dd>
                                                </div>
                                            )}
                                            {digitaxDetails.tpin && digitaxDetails.tpin !== 'pending' && (
                                                <div>
                                                    <dt className="text-xs font-medium text-gray-500 uppercase tracking-wider">TPIN</dt>
                                                    <dd className="mt-1 text-sm font-medium text-gray-900 font-mono">{digitaxDetails.tpin}</dd>
                                                </div>
                                            )}
                                            {digitaxDetails.serial_number && digitaxDetails.serial_number !== 'pending' && (
                                                <div>
                                                    <dt className="text-xs font-medium text-gray-500 uppercase tracking-wider">Serial Number</dt>
                                                    <dd className="mt-1 text-sm font-medium text-gray-900 font-mono">{digitaxDetails.serial_number}</dd>
                                                </div>
                                            )}
                                            {digitaxDetails.device_id && (
                                                <div>
                                                    <dt className="text-xs font-medium text-gray-500 uppercase tracking-wider">Device ID</dt>
                                                    <dd className="mt-1 text-sm font-medium text-gray-900 font-mono">{digitaxDetails.device_id}</dd>
                                                </div>
                                            )}
                                            {digitaxDetails.branch_name && (
                                                <div>
                                                    <dt className="text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</dt>
                                                    <dd className="mt-1 text-sm font-medium text-gray-900">{digitaxDetails.branch_name}</dd>
                                                </div>
                                            )}
                                            {digitaxDetails.address && (
                                                <div className="sm:col-span-2">
                                                    <dt className="text-xs font-medium text-gray-500 uppercase tracking-wider">Address</dt>
                                                    <dd className="mt-1 text-sm font-medium text-gray-900">{digitaxDetails.address}</dd>
                                                </div>
                                            )}
                                            <div>
                                                <dt className="text-xs font-medium text-gray-500 uppercase tracking-wider">Environment</dt>
                                                <dd className="mt-1">
                                                    <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
                                                        digitaxDetails.environment === 'production' 
                                                            ? 'bg-green-100 text-green-800' 
                                                            : 'bg-yellow-100 text-yellow-800'
                                                    }`}>
                                                        {digitaxDetails.environment === 'production' ? 'Production (Live)' : 'Sandbox (Test)'}
                                                    </span>
                                                </dd>
                                            </div>
                                            {digitaxDetails.last_tested_at && (
                                                <div>
                                                    <dt className="text-xs font-medium text-gray-500 uppercase tracking-wider">Last Verified</dt>
                                                    <dd className="mt-1 text-sm text-gray-600">
                                                        {new Date(digitaxDetails.last_tested_at).toLocaleDateString('en-US', {
                                                            year: 'numeric',
                                                            month: 'short',
                                                            day: 'numeric',
                                                            hour: '2-digit',
                                                            minute: '2-digit'
                                                        })}
                                                    </dd>
                                                </div>
                                            )}
                                        </dl>
                                        <div className="mt-4 pt-4 border-t border-gray-200">
                                            <p className="text-xs text-gray-500">
                                                Your invoices will automatically include ZRA Smart Invoice QR codes when issued.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </SectionCard>
                )}

                {/* Tax Rate Modal */}
                {showTaxRateModal && (
                    <TaxRateModal
                        taxRate={editingTaxRate}
                        onClose={() => {
                            setShowTaxRateModal(false);
                            setEditingTaxRate(null);
                        }}
                        onSave={handleSaveTaxRate}
                    />
                )}
            </div>
        );
    };

    // Render Team Section
    const renderTeamSection = () => {
        // Show loading state if teamViewMode is null but we're on team section
        if (!teamViewMode && activeSection === 'team') {
            return (
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-xl font-bold text-gray-900">Team Members</h2>
                            <p className="text-sm text-gray-500 mt-1">Loading team data...</p>
                        </div>
                    </div>
                    <div className="bg-white rounded-2xl border border-gray-100 p-12 text-center">
                        <div className="animate-pulse">
                            <Users className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                            <p className="text-gray-500">Loading team members...</p>
                        </div>
                    </div>
                </div>
            );
        }
        
        // Team create view
        if (teamViewMode === 'create') {
            return (
                <div className="space-y-6">
                    <button
                                    onClick={() => router.visit('/settings/team')}
                        className="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition-colors"
                                >
                        <ArrowLeft className="h-4 w-4" />
                                    Back to Team List
                    </button>

                    <SectionCard title="Add Team Member" description="Add a new member to your team">
                                <form onSubmit={handleTeamCreateSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <FormInput
                                    label="First Name"
                                    required
                                    value={teamCreateForm.data.first_name}
                                    onChange={(e) => teamCreateForm.setData('first_name', e.target.value)}
                                    error={teamCreateForm.errors.first_name}
                                />
                                <FormInput
                                    label="Last Name"
                                    required
                                    value={teamCreateForm.data.last_name}
                                    onChange={(e) => teamCreateForm.setData('last_name', e.target.value)}
                                    error={teamCreateForm.errors.last_name}
                                />
                                <FormInput
                                    label="Email"
                                    type="email"
                                    value={teamCreateForm.data.email}
                                    onChange={(e) => teamCreateForm.setData('email', e.target.value)}
                                />
                                <FormInput
                                    label="Phone"
                                    value={teamCreateForm.data.phone}
                                    onChange={(e) => teamCreateForm.setData('phone', e.target.value)}
                                />
                                <FormSelect
                                    label="Department"
                                    value={teamCreateForm.data.department_id}
                                    onChange={(e) => teamCreateForm.setData('department_id', e.target.value)}
                                >
                                                <option value="">Select department</option>
                                    {departments?.map((dept) => (
                                        <option key={dept.id} value={dept.id}>{dept.name}</option>
                                    ))}
                                </FormSelect>
                                <FormInput
                                    label="Job Title"
                                    value={teamCreateForm.data.job_title}
                                    onChange={(e) => teamCreateForm.setData('job_title', e.target.value)}
                                />
                                <FormInput
                                    label="Employee Number"
                                    value={teamCreateForm.data.employee_number}
                                    onChange={(e) => teamCreateForm.setData('employee_number', e.target.value)}
                                />
                                <FormInput
                                    label="Hire Date"
                                    type="date"
                                    value={teamCreateForm.data.hire_date}
                                    onChange={(e) => teamCreateForm.setData('hire_date', e.target.value)}
                                />
                                <FormSelect
                                    label="Employment Type"
                                    value={teamCreateForm.data.employment_type}
                                    onChange={(e) => teamCreateForm.setData('employment_type', e.target.value)}
                                >
                                                    <option value="">Select type</option>
                                                    <option value="full_time">Full Time</option>
                                                    <option value="part_time">Part Time</option>
                                                    <option value="contract">Contract</option>
                                                    <option value="freelance">Freelance</option>
                                </FormSelect>
                                <FormInput
                                    label="Salary"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={teamCreateForm.data.salary}
                                    onChange={(e) => teamCreateForm.setData('salary', e.target.value)}
                                />
                                            </div>

                            <div className="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    checked={teamCreateForm.data.is_active}
                                    onChange={(e) => teamCreateForm.setData('is_active', e.target.checked)}
                                    className="rounded border-gray-300 text-teal-500 focus:ring-teal-500"
                                />
                                <label htmlFor="is_active" className="text-sm font-medium text-gray-700">Active</label>
                                        </div>

                            <div className="flex gap-3 pt-4">
                                <Button type="submit" disabled={teamCreateForm.processing}>
                                    Create Team Member
                                </Button>
                                <Button type="button" variant="secondary" onClick={() => router.visit('/settings/team')}>
                                    Cancel
                                </Button>
                                    </div>
                                </form>
                    </SectionCard>
                            </div>
            );
        }

        // Team edit view
        if (teamViewMode === 'edit' && teamMember) {
            return (
                <div className="space-y-6">
                    <button
                        onClick={() => router.visit('/settings/team')}
                        className="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Team List
                    </button>

                    <SectionCard title="Edit Team Member" description={`${teamMember.first_name} ${teamMember.last_name}`}>
                                <form onSubmit={handleTeamEditSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <FormInput
                                    label="First Name"
                                    required
                                    value={teamEditForm.data.first_name}
                                    onChange={(e) => teamEditForm.setData('first_name', e.target.value)}
                                />
                                <FormInput
                                    label="Last Name"
                                    required
                                    value={teamEditForm.data.last_name}
                                    onChange={(e) => teamEditForm.setData('last_name', e.target.value)}
                                />
                                <FormInput
                                    label="Email"
                                    type="email"
                                    value={teamEditForm.data.email}
                                    onChange={(e) => teamEditForm.setData('email', e.target.value)}
                                />
                                <FormInput
                                    label="Phone"
                                    value={teamEditForm.data.phone}
                                    onChange={(e) => teamEditForm.setData('phone', e.target.value)}
                                />
                                <FormSelect
                                    label="Department"
                                    value={teamEditForm.data.department_id}
                                    onChange={(e) => teamEditForm.setData('department_id', e.target.value)}
                                >
                                                <option value="">Select department</option>
                                    {departments?.map((dept) => (
                                        <option key={dept.id} value={dept.id}>{dept.name}</option>
                                    ))}
                                </FormSelect>
                                <FormInput
                                    label="Job Title"
                                    value={teamEditForm.data.job_title}
                                    onChange={(e) => teamEditForm.setData('job_title', e.target.value)}
                                />
                                        </div>

                            <div className="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    id="edit_is_active"
                                    checked={teamEditForm.data.is_active}
                                    onChange={(e) => teamEditForm.setData('is_active', e.target.checked)}
                                    className="rounded border-gray-300 text-teal-500 focus:ring-teal-500"
                                />
                                        <label htmlFor="edit_is_active" className="text-sm font-medium text-gray-700">Active</label>
                                    </div>

                            <div className="flex gap-3 pt-4">
                                <Button type="submit" disabled={teamEditForm.processing}>
                                    Save Changes
                                </Button>
                                <Button type="button" variant="secondary" onClick={() => router.visit(`/settings/team/${teamMember.id}`)}>
                                    Cancel
                                </Button>
                                    </div>
                                </form>
                    </SectionCard>
                            </div>
            );
        }
                        
        // Team show view
        if (teamViewMode === 'show' && teamMember) {
            return (
                            <div className="space-y-6">
                    <button
                        onClick={() => router.visit('/settings/team')}
                        className="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Team List
                    </button>

                    <SectionCard>
                        <div className="flex items-start justify-between mb-6">
                            <div className="flex items-center gap-4">
                                <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center text-white text-xl font-bold">
                                    {teamMember.first_name?.charAt(0)}{teamMember.last_name?.charAt(0)}
                                </div>
                                        <div>
                                    <h2 className="text-xl font-bold text-gray-900">{teamMember.first_name} {teamMember.last_name}</h2>
                                    <p className="text-gray-500">{teamMember.job_title || 'Team Member'}</p>
                                    <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold mt-2 ${
                                        teamMember.is_active ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-600'
                                    }`}>
                                        {teamMember.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                                        </div>
                                        <Link href={`/settings/team/${teamMember.id}/edit`}>
                                <Button variant="secondary">
                                    <Edit className="h-4 w-4 mr-2" />
                                    Edit
                                </Button>
                                        </Link>
                                    </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                <h4 className="text-sm font-semibold text-gray-500 mb-3">Contact Information</h4>
                                <div className="space-y-2 text-sm">
                                    {teamMember.email && (
                                        <p className="flex items-center gap-2">
                                            <Mail className="h-4 w-4 text-gray-400" />
                                            {teamMember.email}
                                        </p>
                                    )}
                                    {teamMember.phone && (
                                        <p className="flex items-center gap-2">
                                            <Smartphone className="h-4 w-4 text-gray-400" />
                                            {teamMember.phone}
                                        </p>
                                                )}
                                            </div>
                                        </div>
                                        <div>
                                <h4 className="text-sm font-semibold text-gray-500 mb-3">Employment Details</h4>
                                <div className="space-y-2 text-sm">
                                    {teamMember.department && (
                                        <p><span className="text-gray-500">Department:</span> {teamMember.department.name}</p>
                                    )}
                                    {teamMember.employee_number && (
                                        <p><span className="text-gray-500">Employee #:</span> {teamMember.employee_number}</p>
                                    )}
                                    {teamMember.hire_date && (
                                        <p><span className="text-gray-500">Hire Date:</span> {new Date(teamMember.hire_date).toLocaleDateString()}</p>
                                    )}
                                                </div>
                                                </div>
                                                    </div>
                    </SectionCard>
                                                </div>
            );
        }

        // Team list view
        return (
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                            <div>
                        <h2 className="text-xl font-bold text-gray-900">Team Members</h2>
                        <p className="text-sm text-gray-500 mt-1">Manage your team and departments</p>
                                    </div>
                                    <div className="flex gap-3">
                        <Button variant="secondary" onClick={() => setShowDepartmentModal(true)}>
                            <Building2 className="h-4 w-4 mr-2" />
                                            Add Department
                                        </Button>
                        <Button onClick={() => router.visit('/settings/team/create')}>
                            <Plus className="h-4 w-4 mr-2" />
                            Add Member
                                        </Button>
                                    </div>
                                </div>

                {/* Filters */}
                <div className="bg-white rounded-2xl border border-gray-100 p-5">
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div className="relative">
                                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                                <input
                                                    type="text"
                                                    defaultValue={filters?.search || ''}
                                                    onChange={(e) => router.visit(`/settings/team?search=${e.target.value}`)}
                                placeholder="Search members..."
                                className="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500"
                                                />
                                            </div>
                                            <select
                                                defaultValue={filters?.department_id || ''}
                                                onChange={(e) => router.visit(`/settings/team?department_id=${e.target.value}`)}
                            className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm"
                                            >
                                                <option value="">All Departments</option>
                            {departments?.map((dept) => (
                                                    <option key={dept.id} value={dept.id}>{dept.name}</option>
                                                ))}
                                            </select>
                                            <select
                                                defaultValue={filters?.is_active || ''}
                                                onChange={(e) => router.visit(`/settings/team?is_active=${e.target.value}`)}
                            className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm"
                                            >
                                                <option value="">All Status</option>
                                                <option value="true">Active</option>
                                                <option value="false">Inactive</option>
                                            </select>
                                    </div>
                                </div>

                {/* Team List */}
                {!teamMembers || teamMembers?.data?.length === 0 ? (
                    <div className="bg-white rounded-2xl border border-gray-100 p-12 text-center">
                        <Users className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                        <h3 className="text-lg font-semibold text-gray-900 mb-2">No team members yet</h3>
                                        <p className="text-gray-500 mb-6">Add your first team member to get started</p>
                        <Button onClick={() => router.visit('/settings/team/create')}>
                            <Plus className="h-4 w-4 mr-2" />
                                            Add Team Member
                                        </Button>
                                    </div>
                ) : (
                    <div className="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th className="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                                </tr>
                                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {teamMembers?.data?.map((member) => (
                                    <tr key={member.id} className="hover:bg-gray-50/50 transition-colors">
                                                        <td className="px-6 py-4">
                                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center text-white text-sm font-bold">
                                                                    {member.first_name?.charAt(0)}{member.last_name?.charAt(0)}
                                                                </div>
                                                                <div>
                                                    <p className="font-semibold text-gray-900">{member.first_name} {member.last_name}</p>
                                                    <p className="text-xs text-gray-500">{member.job_title || '-'}</p>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4">
                                            {member.email && <p className="text-sm text-gray-900">{member.email}</p>}
                                            {member.phone && <p className="text-xs text-gray-500">{member.phone}</p>}
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            {member.department ? (
                                                <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-teal-50 text-teal-700">
                                                                    {member.department.name}
                                                                </span>
                                                            ) : (
                                                <span className="text-sm text-gray-400">-</span>
                                                            )}
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                                                member.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600'
                                                            }`}>
                                                                {member.is_active ? 'Active' : 'Inactive'}
                                                            </span>
                                                        </td>
                                        <td className="px-6 py-4">
                                                            <div className="flex items-center justify-center gap-1">
                                                                <Link
                                                                    href={`/settings/team/${member.id}`}
                                                    className="p-2 rounded-lg text-gray-500 hover:text-teal-600 hover:bg-teal-50 transition-colors"
                                                                >
                                                                    <Eye className="h-4 w-4" />
                                                                </Link>
                                                                <Link
                                                                    href={`/settings/team/${member.id}/edit`}
                                                    className="p-2 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Link>
                                                                <button
                                                                    onClick={() => {
                                                                        if (confirm('Are you sure you want to delete this team member?')) {
                                                                            router.delete(`/settings/team/${member.id}`);
                                                                        }
                                                                    }}
                                                    className="p-2 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 transition-colors"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                        
                                        {/* Pagination */}
                        {teamMembers?.links?.length > 3 && (
                            <div className="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                                                <p className="text-sm text-gray-600">
                                                    Showing <span className="font-semibold">{teamMembers.from}</span> to <span className="font-semibold">{teamMembers.to}</span> of <span className="font-semibold">{teamMembers.total}</span>
                                                </p>
                                                <div className="flex gap-1">
                                                    {teamMembers.links.map((link, index) => (
                                                        <Link
                                                            key={index}
                                                            href={link.url || '#'}
                                                            className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all ${
                                                                link.active
                                                    ? 'bg-teal-500 text-white'
                                                    : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'
                                                            } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                                        />
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                )}
                            </div>
        );
    };

    // Render Modules Section
    const renderModulesSection = () => (
        <div className="space-y-6">
            <div>
                <h2 className="text-xl font-bold text-gray-900">Modules</h2>
                <p className="text-sm text-gray-500 mt-1">Enable or disable modules to customize your experience</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {modules?.map((module) => (
                    <div key={module.name} className="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-md transition-shadow">
                        <div className="flex items-start justify-between mb-3">
                            <div className="flex-1">
                                <div className="flex items-center gap-2 mb-1">
                                    <h3 className="font-semibold text-gray-900">{module.display_name}</h3>
                                    {module.enabled ? (
                                        <CheckCircle2 className="h-4 w-4 text-green-500" />
                                    ) : (
                                        <XCircle className="h-4 w-4 text-gray-300" />
                        )}
                            </div>
                                <p className="text-sm text-gray-500">{module.description}</p>
                            </div>
                        </div>

                        {module.features?.length > 0 && (
                            <div className="flex flex-wrap gap-1 mb-4">
                                {module.features.slice(0, 3).map((feature, idx) => (
                                    <span key={idx} className="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs">
                                        {feature.replace(/_/g, ' ')}
                                    </span>
                                ))}
                                {module.features.length > 3 && (
                                    <span className="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs">
                                        +{module.features.length - 3} more
                                    </span>
                        )}
                    </div>
                )}

                        <div className="flex items-center justify-between pt-3 border-t border-gray-100">
                            <span className="text-xs text-gray-500">v{module.version}</span>
                            <Toggle
                                checked={module.enabled}
                                onChange={() => handleModuleToggle(module.name)}
                                disabled={togglingModules[module.name]}
                            />
                        </div>
                    </div>
                ))}
            </div>

            {(!modules || modules.length === 0) && (
                <div className="bg-white rounded-2xl border border-gray-100 p-12 text-center">
                    <Package className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">No Modules Available</h3>
                    <p className="text-gray-500">Modules will appear here once they are installed.</p>
                </div>
            )}
        </div>
    );

    // Render AI Assistant Section
    const renderAssistantSection = () => (
        <div className="space-y-6">
            <SectionCard title="AI Assistant Preferences" description="Customize how Addy interacts with you">
                <form onSubmit={handleAddySubmit} className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <FormSelect
                            label="Communication Tone"
                            value={addyForm.data.tone}
                            onChange={(e) => addyForm.setData('tone', e.target.value)}
                        >
                            <option value="professional">Professional</option>
                            <option value="casual">Casual</option>
                            <option value="motivational">Motivational</option>
                            <option value="sassy">Sassy</option>
                            <option value="technical">Technical</option>
                        </FormSelect>
                        <FormSelect
                            label="Work Style"
                            value={addyForm.data.work_style}
                            onChange={(e) => addyForm.setData('work_style', e.target.value)}
                        >
                            <option value="focused">Focused (Deep work)</option>
                            <option value="balanced">Balanced</option>
                            <option value="creative">Creative (Flexible)</option>
                        </FormSelect>
                    </div>

                    <div className="space-y-4 pt-4 border-t border-gray-100">
                        <h4 className="text-sm font-semibold text-gray-900">Intelligence Features</h4>
                        <div className="space-y-3">
                            <label className="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={addyForm.data.enable_predictions}
                                    onChange={(e) => addyForm.setData('enable_predictions', e.target.checked)}
                                    className="rounded border-gray-300 text-teal-500 focus:ring-teal-500"
                                />
                                <div>
                                    <span className="text-sm font-medium text-gray-700">Enable predictive analytics</span>
                                    <p className="text-xs text-gray-500">Get AI-powered predictions for tasks and deadlines</p>
                                </div>
                            </label>
                            <label className="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={addyForm.data.enable_proactive_suggestions}
                                    onChange={(e) => addyForm.setData('enable_proactive_suggestions', e.target.checked)}
                                    className="rounded border-gray-300 text-teal-500 focus:ring-teal-500"
                                />
                                <div>
                                    <span className="text-sm font-medium text-gray-700">Enable proactive suggestions</span>
                                    <p className="text-xs text-gray-500">Receive suggestions before you ask</p>
                                </div>
                            </label>
                            <label className="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={addyForm.data.adhd_mode}
                                    onChange={(e) => addyForm.setData('adhd_mode', e.target.checked)}
                                    className="rounded border-gray-300 text-teal-500 focus:ring-teal-500"
                                />
                                <div>
                                    <span className="text-sm font-medium text-gray-700">ADHD-aware mode</span>
                                    <p className="text-xs text-gray-500">Breaks up overwhelming tasks into smaller chunks</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-5 pt-4 border-t border-gray-100">
                        <div>
                            <h4 className="text-sm font-semibold text-gray-900 mb-4">Quiet Hours</h4>
                            <div className="grid grid-cols-2 gap-3">
                                <FormInput
                                    label="Start"
                                    type="time"
                                    value={addyForm.data.quiet_hours_start}
                                    onChange={(e) => addyForm.setData('quiet_hours_start', e.target.value)}
                                />
                                <FormInput
                                    label="End"
                                    type="time"
                                    value={addyForm.data.quiet_hours_end}
                                    onChange={(e) => addyForm.setData('quiet_hours_end', e.target.value)}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-end pt-4">
                        <Button type="submit" disabled={addyForm.processing}>
                            <Save className="h-4 w-4 mr-2" />
                            {addyForm.processing ? 'Saving...' : 'Save Preferences'}
                        </Button>
                    </div>
                </form>
            </SectionCard>
        </div>
    );

    // Render Integrations Section
    const renderIntegrationsSection = () => (
        <div className="space-y-6">
            <div>
                <h2 className="text-xl font-bold text-gray-900">Integrations</h2>
                <p className="text-sm text-gray-500 mt-1">Connect third-party services to enhance your workflow</p>
            </div>

            {/* Google Drive */}
            <SectionCard>
                <div className="flex items-start gap-4">
                    <div className="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                        <Cloud className="h-6 w-6 text-blue-600" />
                    </div>
                    <div className="flex-1">
                        <div className="flex items-center justify-between mb-2">
                            <div>
                                <h3 className="font-semibold text-gray-900">Google Drive</h3>
                                <p className="text-sm text-gray-500">Store and sync files with Google Drive</p>
                            </div>
                            {user?.google_drive_connected ? (
                                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700">
                                    <CheckCircle2 className="h-3.5 w-3.5" />
                                    Connected
                                </span>
                            ) : (
                                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    Not Connected
                                </span>
                            )}
                        </div>

                        {user?.google_drive_connected ? (
                            <div className="space-y-4 mt-4">
                                <div className="flex items-center gap-4">
                                    <label className="flex items-center gap-3 cursor-pointer flex-1 p-3 rounded-xl border-2 transition-all" style={{ borderColor: !useOwnDrive ? '#10b981' : '#e5e7eb' }}>
                                        <input
                                            type="radio"
                                            checked={!useOwnDrive}
                                            onChange={() => {
                                                if (useOwnDrive) {
                                                    setUseOwnDrive(false);
                                                    setUpdatingDrive(true);
                                                    router.post('/settings/drive-preference', { use_own_drive: false }, {
                                                        preserveScroll: true,
                                                        onFinish: () => setUpdatingDrive(false),
                                                    });
                                                }
                                            }}
                                            disabled={updatingDrive}
                                            className="text-teal-600 focus:ring-teal-500"
                                        />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">Organization Storage</p>
                                            <p className="text-xs text-gray-500">Shared with team members</p>
                                        </div>
                                    </label>
                                    <label className="flex items-center gap-3 cursor-pointer flex-1 p-3 rounded-xl border-2 transition-all" style={{ borderColor: useOwnDrive ? '#10b981' : '#e5e7eb' }}>
                                        <input
                                            type="radio"
                                            checked={useOwnDrive}
                                            onChange={() => {
                                                if (!useOwnDrive) {
                                                    setUseOwnDrive(true);
                                                    setUpdatingDrive(true);
                                                    router.post('/settings/drive-preference', { use_own_drive: true }, {
                                                        preserveScroll: true,
                                                        onFinish: () => setUpdatingDrive(false),
                                                    });
                                                }
                                            }}
                                            disabled={updatingDrive}
                                            className="text-teal-600 focus:ring-teal-500"
                                        />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">Personal Drive</p>
                                            <p className="text-xs text-gray-500">Private to you only</p>
                                        </div>
                                    </label>
                                </div>
                                <Button
                                    variant="secondary"
                                    onClick={() => {
                                        if (confirm('Are you sure you want to disconnect Google Drive?')) {
                                            router.post('/settings/disconnect-drive', {}, { preserveScroll: true });
                                        }
                                    }}
                                    className="text-red-600 hover:text-red-700 border-red-200 hover:border-red-300"
                                >
                                    <CloudOff className="h-4 w-4 mr-2" />
                                    Disconnect
                                </Button>
                            </div>
                        ) : (
                            <a
                                href="/auth/google"
                                className="inline-flex items-center gap-2 mt-4 px-4 py-2.5 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 text-gray-700 font-medium text-sm transition-colors"
                            >
                                <svg className="w-4 h-4" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                Connect Google Drive
                            </a>
                        )}
                    </div>
                </div>
            </SectionCard>
        </div>
    );

    // Render Notifications Section
    const renderNotificationsSection = () => (
        <div className="space-y-6">
            <SectionCard title="Notification Preferences" description="Choose how you want to be notified">
                <form onSubmit={handleNotificationSubmit} className="space-y-6">
                    <div className="space-y-4">
                        <label className="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                            <div className="flex items-center gap-3">
                                <Mail className="h-5 w-5 text-gray-500" />
                                <div>
                                    <span className="text-sm font-medium text-gray-900">Email Notifications</span>
                                    <p className="text-xs text-gray-500">Receive updates via email</p>
                                </div>
                            </div>
                            <Toggle
                                checked={notificationForm.data.email_notifications}
                                onChange={(checked) => notificationForm.setData('email_notifications', checked)}
                            />
                        </label>

                        <label className="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                            <div className="flex items-center gap-3">
                                <Bell className="h-5 w-5 text-gray-500" />
                                <div>
                                    <span className="text-sm font-medium text-gray-900">Push Notifications</span>
                                    <p className="text-xs text-gray-500">Receive browser notifications</p>
                                </div>
                            </div>
                            <Toggle
                                checked={notificationForm.data.push_notifications}
                                onChange={(checked) => notificationForm.setData('push_notifications', checked)}
                            />
                        </label>

                        <label className="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                            <div className="flex items-center gap-3">
                                <Clock className="h-5 w-5 text-gray-500" />
                                <div>
                                    <span className="text-sm font-medium text-gray-900">Task Reminders</span>
                                    <p className="text-xs text-gray-500">Get reminded about upcoming tasks</p>
                                </div>
                            </div>
                            <Toggle
                                checked={notificationForm.data.task_reminders}
                                onChange={(checked) => notificationForm.setData('task_reminders', checked)}
                            />
                        </label>

                        <label className="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                            <div className="flex items-center gap-3">
                                <FileText className="h-5 w-5 text-gray-500" />
                                <div>
                                    <span className="text-sm font-medium text-gray-900">Weekly Summary</span>
                                    <p className="text-xs text-gray-500">Receive a weekly activity summary</p>
                                </div>
                            </div>
                            <Toggle
                                checked={notificationForm.data.weekly_summary}
                                onChange={(checked) => notificationForm.setData('weekly_summary', checked)}
                            />
                        </label>
                    </div>

                    <div className="flex justify-end pt-4">
                        <Button type="submit">
                            <Save className="h-4 w-4 mr-2" />
                            Save Preferences
                        </Button>
                    </div>
                </form>
            </SectionCard>
        </div>
    );

    // Render Security Section
    const renderSecuritySection = () => (
        <div className="space-y-6">
            <SectionCard title="Security Settings" description="Manage your account security">
                <div className="space-y-4">
                    <div className="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div className="flex items-center gap-3">
                            <Key className="h-5 w-5 text-gray-500" />
                            <div>
                                <span className="text-sm font-medium text-gray-900">Change Password</span>
                                <p className="text-xs text-gray-500">Update your account password</p>
                            </div>
                        </div>
                        <Button variant="secondary" size="sm">
                            Change
                        </Button>
                    </div>

                    <div className="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div className="flex items-center gap-3">
                            <Smartphone className="h-5 w-5 text-gray-500" />
                            <div>
                                <span className="text-sm font-medium text-gray-900">Two-Factor Authentication</span>
                                <p className="text-xs text-gray-500">Add an extra layer of security</p>
                            </div>
                        </div>
                        <Button variant="secondary" size="sm">
                            Enable
                        </Button>
                    </div>

                    <div className="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div className="flex items-center gap-3">
                            <Download className="h-5 w-5 text-gray-500" />
                            <div>
                                <span className="text-sm font-medium text-gray-900">Export Data</span>
                                <p className="text-xs text-gray-500">Download a copy of your data</p>
                            </div>
                        </div>
                        <Button variant="secondary" size="sm">
                            Export
                        </Button>
                    </div>
                </div>
            </SectionCard>

            <SectionCard title="Active Sessions" description="Manage your active login sessions">
                <div className="space-y-3">
                    <div className="flex items-center justify-between p-4 bg-green-50 rounded-xl border border-green-100">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                                <Globe className="h-5 w-5 text-green-600" />
                            </div>
                            <div>
                                <span className="text-sm font-medium text-gray-900">Current Session</span>
                                <p className="text-xs text-gray-500">Active now • This device</p>
                            </div>
                        </div>
                        <span className="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                    </div>
                </div>
            </SectionCard>

            <SectionCard>
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center">
                            <LogOut className="h-5 w-5 text-red-600" />
                        </div>
                        <div>
                            <span className="text-sm font-medium text-gray-900">Sign Out Everywhere</span>
                            <p className="text-xs text-gray-500">Log out from all devices</p>
                        </div>
                    </div>
                    <Button variant="secondary" className="text-red-600 hover:text-red-700 border-red-200 hover:border-red-300">
                        Sign Out All
                    </Button>
                </div>
            </SectionCard>
        </div>
    );

    // Render Gamification Section
    const renderGamificationSection = () => {
        const {
            xp_total = 0,
            level = 1,
            level_title = 'Emerging Business',
            xp_for_next_level = 100,
            xp_progress = 0,
            xp_progress_percent = 0,
            badges = [],
            available_badges = {},
            earned_badge_types = [],
            streak = { current: 0, longest: 0 },
            recent_xp = [],
            leaderboard = [],
        } = gamificationData;

        const badgeCategories = {
            revenue: { name: 'Revenue', color: 'bg-green-500' },
            consistency: { name: 'Consistency', color: 'bg-orange-500' },
            financial: { name: 'Financial', color: 'bg-blue-500' },
            customer: { name: 'Customer', color: 'bg-purple-500' },
            organization: { name: 'Organization', color: 'bg-teal-500' },
        };

        return (
            <div className="space-y-6">
                {/* XP Overview */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <SectionCard>
                        <div className="text-center">
                            <div className="w-16 h-16 mx-auto mb-3 rounded-full bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center">
                                <span className="text-2xl font-bold text-white">{level}</span>
                            </div>
                            <h3 className="font-semibold text-gray-900">{level_title}</h3>
                            <p className="text-sm text-gray-500">Level {level}</p>
                        </div>
                    </SectionCard>

                    <SectionCard>
                        <div className="text-center">
                            <div className="text-3xl font-bold text-teal-600 mb-1">{xp_total.toLocaleString()}</div>
                            <p className="text-sm text-gray-500">Total XP</p>
                            <div className="mt-3">
                                <div className="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>{xp_progress} XP</span>
                                    <span>{xp_for_next_level} XP</span>
                                </div>
                                <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div 
                                        className="h-full bg-gradient-to-r from-teal-500 to-teal-600 transition-all duration-500"
                                        style={{ width: `${xp_progress_percent}%` }}
                                    />
                                </div>
                            </div>
                        </div>
                    </SectionCard>

                    <SectionCard>
                        <div className="text-center">
                            <div className="flex items-center justify-center gap-2 mb-2">
                                <span className="text-3xl">🔥</span>
                                <span className="text-3xl font-bold text-orange-500">{streak.current}</span>
                            </div>
                            <p className="text-sm text-gray-500">Day Streak</p>
                            <p className="text-xs text-gray-400 mt-1">Best: {streak.longest} days</p>
                        </div>
                    </SectionCard>
                </div>

                {/* Badges */}
                <SectionCard title="Badges" description="Unlock badges by reaching milestones">
                    <div className="space-y-6">
                        {Object.entries(badgeCategories).map(([categoryKey, category]) => {
                            const categoryBadges = Object.entries(available_badges).filter(
                                ([, badge]) => badge.category === categoryKey
                            );
                            if (categoryBadges.length === 0) return null;

                            return (
                                <div key={categoryKey}>
                                    <h4 className="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                        <span className={`w-2 h-2 rounded-full ${category.color}`} />
                                        {category.name}
                                    </h4>
                                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                                        {categoryBadges.map(([badgeKey, badge]) => {
                                            const isEarned = earned_badge_types.includes(badgeKey);
                                            return (
                                                <div
                                                    key={badgeKey}
                                                    className={`p-3 rounded-xl border-2 text-center transition-all ${
                                                        isEarned
                                                            ? 'border-teal-300 bg-teal-50'
                                                            : 'border-gray-100 bg-gray-50 opacity-50'
                                                    }`}
                                                >
                                                    <div className="text-2xl mb-1">{badge.icon}</div>
                                                    <div className="text-xs font-medium text-gray-900 truncate">{badge.name}</div>
                                                    <div className="text-[10px] text-gray-500 truncate">{badge.description}</div>
                                                    {isEarned && (
                                                        <div className="mt-1">
                                                            <CheckCircle2 className="h-4 w-4 text-teal-500 mx-auto" />
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </SectionCard>

                {/* Leaderboard */}
                <SectionCard title="Leaderboard" description="Top performers in your organization">
                    {leaderboard.length > 0 ? (
                        <div className="divide-y divide-gray-100">
                            {leaderboard.map((entry) => (
                                <div key={entry.user_id} className="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                                    <div className="flex items-center gap-3">
                                        <div className={`w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm ${
                                            entry.rank === 1 ? 'bg-yellow-100 text-yellow-700' :
                                            entry.rank === 2 ? 'bg-gray-100 text-gray-700' :
                                            entry.rank === 3 ? 'bg-orange-100 text-orange-700' :
                                            'bg-gray-50 text-gray-500'
                                        }`}>
                                            {entry.rank <= 3 ? ['🥇', '🥈', '🥉'][entry.rank - 1] : entry.rank}
                                        </div>
                                        <div>
                                            <div className="font-medium text-gray-900">{entry.name}</div>
                                            <div className="text-xs text-gray-500">Level {entry.level}</div>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div className="font-semibold text-teal-600">{entry.total_xp.toLocaleString()} XP</div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-center text-gray-500 py-8">No leaderboard data yet</p>
                    )}
                </SectionCard>

                {/* Recent XP Activity */}
                <SectionCard title="Recent Activity" description="Your latest XP earnings">
                    {recent_xp.length > 0 ? (
                        <div className="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                            {recent_xp.map((xp, index) => (
                                <div key={xp.id || index} className="flex items-center justify-between py-2 first:pt-0 last:pb-0">
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm text-gray-600">{xp.reason}</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm font-medium text-teal-600">+{xp.xp_amount} XP</span>
                                        <span className="text-xs text-gray-400">
                                            {new Date(xp.created_at).toLocaleDateString()}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-center text-gray-500 py-8">No activity yet. Start using Addy to earn XP!</p>
                    )}
                </SectionCard>
            </div>
        );
    };

    // Render active section content
    const renderSectionContent = () => {
        switch (activeSection) {
            case 'billing':
                return renderBillingSection();
            case 'tax':
                return renderTaxSection();
            case 'modules':
                return renderModulesSection();
            case 'assistant':
                return renderAssistantSection();
            case 'notifications':
                return renderNotificationsSection();
            case 'team':
                return renderTeamSection();
            case 'gamification':
                return renderGamificationSection();
            default:
                return renderBillingSection();
        }
    };

    return (
        <div className="min-h-screen bg-gray-50/50">
            <Navigation />
            <FlashMessages />
            <Head title="Settings" />

            <div className="max-w-[1600px] mx-auto px-6 py-8">
                {/* Header */}
                <div className="mb-8">
                    <div className="flex items-center gap-3 mb-2">
                        <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center">
                            <SettingsIcon className="h-5 w-5 text-white" />
                        </div>
                        <h1 className="text-2xl font-bold text-gray-900">Settings</h1>
                    </div>
                    <p className="text-gray-500 ml-13">Manage your organization and preferences</p>
                </div>

                {/* Success/Error Messages */}
                {successMessage && (
                    <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
                        <CheckCircle2 className="h-5 w-5 text-green-600 flex-shrink-0" />
                        <p className="text-green-800 font-medium">{successMessage}</p>
                    </div>
                )}

                {errorMessage && (
                    <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3">
                        <AlertTriangle className="h-5 w-5 text-red-600 flex-shrink-0" />
                        <p className="text-red-800 font-medium">{errorMessage}</p>
                    </div>
                )}

                {/* Penda Cloud Notice (owners only) */}
                {organization?.name && user?.role === 'owner' && (
                    <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                        <div className="flex items-start gap-3">
                            <Info className="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" />
                            <div className="flex-1">
                                <p className="text-sm text-blue-900 font-medium mb-1">
                                    Organization settings moved to Penda Cloud
                                </p>
                                <p className="text-sm text-blue-700">
                                    Organization profile, team management, security settings, and Google Drive integration are now managed in{' '}
                                    <a href="https://penda.cloud/dashboard" target="_blank" rel="noopener noreferrer" className="underline font-medium hover:text-blue-900">
                                        Penda Cloud
                                    </a>.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                <div className="flex gap-8">
                    {/* Sidebar Navigation */}
                    <div className="w-64 flex-shrink-0">
                        <nav className="bg-white rounded-2xl border border-gray-100 p-3 sticky top-8">
                            {settingsNav.map((item) => (
                                <button
                                    key={item.id}
                                    onClick={() => {
                                        setActiveSection(item.id);
                                    }}
                                    className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl text-left transition-all mb-1 ${
                                        activeSection === item.id
                                            ? 'bg-teal-50 text-teal-700'
                                            : 'text-gray-600 hover:bg-gray-50'
                                    }`}
                                >
                                    <item.icon className={`h-5 w-5 ${activeSection === item.id ? 'text-teal-600' : 'text-gray-400'}`} />
                                    <div className="flex-1 min-w-0">
                                        <span className={`text-sm font-medium block ${activeSection === item.id ? 'text-teal-700' : 'text-gray-900'}`}>
                                            {item.name}
                                        </span>
                                        <span className="text-xs text-gray-500 truncate block">{item.description}</span>
                                    </div>
                                    {activeSection === item.id && (
                                        <ChevronRight className="h-4 w-4 text-teal-500" />
                                    )}
                                </button>
                            ))}
                        </nav>
                    </div>

                    {/* Main Content */}
                    <div className="flex-1 min-w-0">
                        {renderSectionContent()}
                    </div>
                </div>
            </div>

                {/* Department Modal */}
                <DepartmentModal
                    isOpen={showDepartmentModal}
                    onClose={() => setShowDepartmentModal(false)}
                onDepartmentCreated={() => router.reload()}
                />

            {/* Support Modal */}
                {showSupportModal && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                            <div className="p-6">
                            <div className="flex items-center justify-between mb-6">
                                <h2 className="text-xl font-bold text-gray-900">Create Support Ticket</h2>
                                <button
                                    onClick={() => {
                                        setShowSupportModal(false);
                                        supportForm.reset();
                                    }}
                                    className="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            </div>

                            <form onSubmit={handleSupportSubmit} className="space-y-5">
                                <FormInput
                                    label="Subject"
                                    required
                                        value={supportForm.data.subject}
                                        onChange={(e) => supportForm.setData('subject', e.target.value)}
                                        placeholder="Brief description of your issue"
                                    error={supportForm.errors.subject}
                                />

                                <FormSelect
                                    label="Category"
                                    required
                                        value={supportForm.data.category}
                                        onChange={(e) => supportForm.setData('category', e.target.value)}
                                    >
                                        <option value="technical">Technical Issue</option>
                                        <option value="billing">Billing Question</option>
                                        <option value="feature_request">Feature Request</option>
                                        <option value="bug">Bug Report</option>
                                        <option value="other">Other</option>
                                </FormSelect>

                                <FormSelect
                                    label="Priority"
                                    required
                                        value={supportForm.data.priority}
                                        onChange={(e) => supportForm.setData('priority', e.target.value)}
                                    >
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                </FormSelect>

                                <FormTextarea
                                    label="Description"
                                    required
                                        value={supportForm.data.description}
                                        onChange={(e) => supportForm.setData('description', e.target.value)}
                                    rows={5}
                                    placeholder="Please provide as much detail as possible..."
                                    error={supportForm.errors.description}
                                />

                                <div className="flex gap-3 pt-4">
                                    <Button type="submit" disabled={supportForm.processing} className="flex-1">
                                        {supportForm.processing ? 'Creating...' : 'Create Ticket'}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={() => {
                                            setShowSupportModal(false);
                                            supportForm.reset();
                                        }}
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </form>
                            </div>
                    </div>
                    </div>
                )}
        </div>
    );
}
