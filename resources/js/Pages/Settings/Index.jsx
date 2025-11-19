import { Head, useForm, usePage, router, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { Save, Building2, Upload, X, Image as ImageIcon, Ticket, Cloud, CloudOff, CheckCircle2, Settings as SettingsIcon, Package, FileText } from 'lucide-react';
import { useState, useRef, useEffect } from 'react';

export default function SettingsIndex({ organization, user }) {
    const { flash, url } = usePage().props;
    const [activeTab, setActiveTab] = useState('general');
    const [logoPreview, setLogoPreview] = useState(organization.logo_url || null);
    const [logoFile, setLogoFile] = useState(null);
    const [logoUploading, setLogoUploading] = useState(false);
    const [showSupportModal, setShowSupportModal] = useState(false);
    const [useOwnDrive, setUseOwnDrive] = useState(user?.use_own_drive || false);
    const [updatingDrive, setUpdatingDrive] = useState(false);
    const fileInputRef = useRef(null);

    // Determine active tab from URL
    useEffect(() => {
        if (url?.includes('/settings/modules')) {
            setActiveTab('modules');
        } else if (url?.includes('/settings/invoices')) {
            setActiveTab('invoices');
        } else {
            setActiveTab('general');
        }
    }, [url]);

    const tabs = [
        { id: 'general', name: 'General', icon: SettingsIcon, href: '/settings' },
        { id: 'modules', name: 'Modules', icon: Package, href: '/settings/modules' },
        { id: 'invoices', name: 'Invoices & Quotes', icon: FileText, href: '/settings/invoices' },
    ];
    
    const supportForm = useForm({
        subject: '',
        description: '',
        priority: 'medium',
        category: 'other',
    });
    const form = useForm({
        name: organization.name || '',
        slug: organization.slug || '',
        business_type: organization.business_type || '',
        industry: organization.industry || '',
        tone_preference: organization.tone_preference || '',
        currency: organization.currency || 'ZMW',
        timezone: organization.timezone || 'Africa/Lusaka',
    });
    const { data, setData, processing, errors } = form;

    useEffect(() => {
        setLogoPreview(organization.logo_url || null);
        setLogoFile(null);
    }, [organization.logo_url]);

    const successMessage = flash?.message || flash?.success;
    const generalError = flash?.error || errors?.error;

    const handleLogoChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setLogoFile(file);
            // Create preview
            const reader = new FileReader();
            reader.onloadend = () => {
                setLogoPreview(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleRemoveLogo = () => {
        setLogoFile(null);
        setLogoPreview(organization.logo_url || null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleLogoSubmit = (e) => {
        e.preventDefault();
        
        if (!logoFile || !(logoFile instanceof File)) {
            return;
        }

        setLogoUploading(true);

        const formData = new FormData();
        formData.append('logo', logoFile);

        router.post('/settings/logo', formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                setLogoFile(null);
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
                // Refresh the page to get updated logo URL
                router.reload({ only: ['organization'] });
            },
            onError: (formErrors) => {
                console.error('Logo upload errors:', formErrors);
            },
            onFinish: () => {
                setLogoUploading(false);
            },
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        // Submit settings without logo
        form.put('/settings', {
            preserveScroll: true,
            onSuccess: () => {
                // Settings saved successfully
            },
            onError: (formErrors) => {
                console.error('Settings update errors:', formErrors);
                if (formErrors) {
                    console.error('Full error details:', JSON.stringify(formErrors, null, 2));
                }
            },
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

    return (
        <SectionLayout sectionName="Settings">
            <Head title="Settings" />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <div className="flex items-center gap-3 mb-2">
                        <Building2 className="h-6 w-6 text-teal-600" />
                        <h1 className="text-3xl font-bold text-gray-900">Organization Settings</h1>
                    </div>
                    <p className="text-gray-500 mt-1">Manage your organization's settings and preferences</p>
                </div>

                {/* Tabs */}
                <div className="bg-white border border-gray-200 rounded-lg mb-6">
                    <div className="border-b border-gray-200 px-6">
                        <nav className="-mb-px flex space-x-8">
                            {tabs.map((tab) => (
                                <Link
                                    key={tab.id}
                                    href={tab.href}
                                    className={`
                                        flex items-center gap-2 py-4 px-1 border-b-2 font-medium text-sm transition-colors
                                        ${activeTab === tab.id
                                            ? 'border-teal-500 text-teal-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        }
                                    `}
                                >
                                    <tab.icon className="h-5 w-5" />
                                    {tab.name}
                                </Link>
                            ))}
                        </nav>
                    </div>
                </div>

                {activeTab === 'general' && (
                <div className="bg-white border border-gray-200 rounded-lg p-6">
                    {successMessage && (
                        <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p className="text-green-800 font-medium">{successMessage}</p>
                        </div>
                    )}

                    {generalError && (
                        <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <p className="text-red-800 font-medium">{generalError}</p>
                        </div>
                    )}
                    
                    {/* Logo Upload Section - Separate Form */}
                    <div className="mb-6 pb-6 border-b border-gray-200">
                        <label className="block text-sm font-medium text-gray-700 mb-3">
                            Organization Logo
                        </label>
                        <form onSubmit={handleLogoSubmit} className="space-y-4">
                            <div className="flex items-start gap-6">
                                <div className="flex-shrink-0">
                                    {logoPreview ? (
                                        <div className="relative">
                                            <img
                                                src={logoPreview}
                                                alt="Organization logo"
                                                className="h-24 w-24 object-contain border border-gray-300 rounded-lg bg-white p-2"
                                            />
                                            {logoFile && (
                                                <button
                                                    type="button"
                                                    onClick={handleRemoveLogo}
                                                    className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors"
                                                    title="Remove selected logo"
                                                >
                                                    <X className="h-4 w-4" />
                                                </button>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="h-24 w-24 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center bg-gray-50">
                                            <ImageIcon className="h-8 w-8 text-gray-400" />
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
                                            className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 cursor-pointer transition-colors"
                                        >
                                            <Upload className="h-4 w-4 mr-2" />
                                            {logoPreview && !logoFile ? 'Change Logo' : 'Select Logo'}
                                        </label>
                                        {logoFile && (
                                            <Button
                                                type="submit"
                                                disabled={logoUploading}
                                                className="bg-teal-600 hover:bg-teal-700 text-white"
                                            >
                                                {logoUploading ? 'Uploading...' : 'Save Logo'}
                                            </Button>
                                        )}
                                    </div>
                                    <p className="mt-2 text-xs text-gray-500">
                                        Recommended: Square image, max 2MB. Formats: JPG, PNG, GIF, SVG
                                    </p>
                                    {errors.logo && <p className="mt-1 text-sm text-red-600">{errors.logo}</p>}
                                </div>
                            </div>
                        </form>
                    </div>

                    {/* Settings Form - Without Logo */}
                    <form onSubmit={handleSubmit} className="space-y-6">

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Organization Name <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Slug</label>
                                <input
                                    type="text"
                                    value={data.slug}
                                    onChange={(e) => setData('slug', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="organization-slug"
                                />
                                {errors.slug && <p className="mt-1 text-sm text-red-600">{errors.slug}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Business Type</label>
                                <input
                                    type="text"
                                    value={data.business_type}
                                    onChange={(e) => setData('business_type', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="e.g., Retail, Service, Manufacturing"
                                />
                                {errors.business_type && <p className="mt-1 text-sm text-red-600">{errors.business_type}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Industry</label>
                                <input
                                    type="text"
                                    value={data.industry}
                                    onChange={(e) => setData('industry', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="e.g., Technology, Healthcare, Retail"
                                />
                                {errors.industry && <p className="mt-1 text-sm text-red-600">{errors.industry}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                                <select
                                    value={data.currency}
                                    onChange={(e) => setData('currency', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="ZMW">ZMW - Zambian Kwacha</option>
                                    <option value="USD">USD - US Dollar</option>
                                    <option value="EUR">EUR - Euro</option>
                                    <option value="GBP">GBP - British Pound</option>
                                    <option value="ZAR">ZAR - South African Rand</option>
                                    <option value="KES">KES - Kenyan Shilling</option>
                                    <option value="NGN">NGN - Nigerian Naira</option>
                                    <option value="GHS">GHS - Ghanaian Cedi</option>
                                </select>
                                {errors.currency && <p className="mt-1 text-sm text-red-600">{errors.currency}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                                <select
                                    value={data.timezone}
                                    onChange={(e) => setData('timezone', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="Africa/Lusaka">Africa/Lusaka (CAT)</option>
                                    <option value="Africa/Johannesburg">Africa/Johannesburg (SAST)</option>
                                    <option value="Africa/Nairobi">Africa/Nairobi (EAT)</option>
                                    <option value="Africa/Lagos">Africa/Lagos (WAT)</option>
                                    <option value="Africa/Accra">Africa/Accra (GMT)</option>
                                    <option value="UTC">UTC</option>
                                </select>
                                {errors.timezone && <p className="mt-1 text-sm text-red-600">{errors.timezone}</p>}
                            </div>

                            <div className="md:col-span-2">
                                <label className="block text-sm font-medium text-gray-700 mb-2">Tone Preference</label>
                                <select
                                    value={data.tone_preference}
                                    onChange={(e) => setData('tone_preference', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="">Select tone</option>
                                    <option value="professional">Professional</option>
                                    <option value="casual">Casual</option>
                                    <option value="motivational">Motivational</option>
                                    <option value="sassy">Sassy</option>
                                    <option value="technical">Technical</option>
                                    <option value="friendly">Friendly (legacy)</option>
                                    <option value="conversational">Conversational (legacy)</option>
                                    <option value="formal">Formal (legacy)</option>
                                </select>
                                {errors.tone_preference && <p className="mt-1 text-sm text-red-600">{errors.tone_preference}</p>}
                            </div>
                        </div>

                        <div className="flex gap-3 pt-4 border-t border-gray-200">
                            <Button type="submit" disabled={processing}>
                                <Save className="h-4 w-4 mr-2" />
                                {processing ? 'Saving...' : 'Save Settings'}
                            </Button>
                        </div>
                    </form>
                </div>

                {/* Google Drive Section */}
                <div className="mt-6 bg-white border border-gray-200 rounded-lg p-6">
                    <div className="flex items-start justify-between mb-4">
                        <div className="flex-1">
                            <h3 className="text-lg font-semibold text-gray-900 mb-1 flex items-center gap-2">
                                <Cloud className="h-5 w-5 text-teal-600" />
                                Google Drive Storage
                            </h3>
                            <p className="text-sm text-gray-500">
                                Choose where your files are stored. You can use your own Google Drive or the shared Drive.
                            </p>
                        </div>
                    </div>

                    <div className="space-y-4 mt-4">
                        {/* Connection Status */}
                        {user?.google_drive_connected ? (
                            <div className="flex items-center gap-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                                <CheckCircle2 className="h-5 w-5 text-green-600" />
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-green-900">Connected to Google Drive</p>
                                    {user?.google_drive_connected_at && (
                                        <p className="text-xs text-green-700">
                                            Connected on {new Date(user.google_drive_connected_at).toLocaleDateString()}
                                        </p>
                                    )}
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        if (confirm('Are you sure you want to disconnect your Google Drive? Files will use the shared Drive instead.')) {
                                            router.post('/settings/disconnect-drive', {}, {
                                                preserveScroll: true,
                                                onSuccess: () => {
                                                    router.reload({ only: ['user'] });
                                                },
                                            });
                                        }
                                    }}
                                    className="text-red-600 hover:text-red-700 border-red-200 hover:border-red-300"
                                >
                                    <CloudOff className="h-4 w-4 mr-1" />
                                    Disconnect
                                </Button>
                            </div>
                        ) : (
                            <div className="p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                <p className="text-sm text-gray-600 mb-3">Not connected to Google Drive</p>
                                <a
                                    href="/auth/google"
                                    className="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-700 font-medium text-sm"
                                >
                                    <svg className="w-4 h-4" viewBox="0 0 24 24">
                                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                    </svg>
                                    Connect Google Drive
                                </a>
                            </div>
                        )}

                        {/* Drive Preference Toggle */}
                        {user?.google_drive_connected && (
                            <div className="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                <label className="flex items-center justify-between cursor-pointer">
                                    <div className="flex-1">
                                        <p className="text-sm font-medium text-gray-900">Use My Own Google Drive</p>
                                        <p className="text-xs text-gray-500 mt-1">
                                            {useOwnDrive 
                                                ? 'Files will be saved to your personal Google Drive'
                                                : 'Files will be saved to the shared Drive (default)'}
                                        </p>
                                    </div>
                                    <div className="ml-4">
                                        <input
                                            type="checkbox"
                                            checked={useOwnDrive}
                                            onChange={(e) => {
                                                const newValue = e.target.checked;
                                                setUseOwnDrive(newValue);
                                                setUpdatingDrive(true);
                                                router.post('/settings/drive-preference', {
                                                    use_own_drive: newValue,
                                                }, {
                                                    preserveScroll: true,
                                                    onSuccess: () => {
                                                        router.reload({ only: ['user'] });
                                                    },
                                                    onFinish: () => {
                                                        setUpdatingDrive(false);
                                                    },
                                                });
                                            }}
                                            disabled={updatingDrive}
                                            className="h-5 w-5 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                                        />
                                    </div>
                                </label>
                            </div>
                        )}
                    </div>
                </div>

                {/* Support Section */}
                <div className="mt-6 bg-white border border-gray-200 rounded-lg p-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900 mb-1">Need Help?</h3>
                            <p className="text-sm text-gray-500">Create a support ticket and we'll get back to you as soon as possible</p>
                        </div>
                        <Button onClick={() => setShowSupportModal(true)}>
                            <Ticket className="h-4 w-4 mr-2" />
                            Create Support Ticket
                        </Button>
                    </div>
                </div>
                )}
            </div>

            {/* Support Ticket Modal */}
            {showSupportModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <Card className="max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-6">
                                <h2 className="text-2xl font-bold text-gray-900">Create Support Ticket</h2>
                                <button
                                    onClick={() => {
                                        setShowSupportModal(false);
                                        supportForm.reset();
                                    }}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    <X className="h-6 w-6" />
                                </button>
                            </div>

                            <form onSubmit={handleSupportSubmit} className="space-y-6">
                                {/* Subject */}
                                <div>
                                    <label htmlFor="modal-subject" className="block text-sm font-medium text-gray-700 mb-2">
                                        Subject <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="modal-subject"
                                        value={supportForm.data.subject}
                                        onChange={(e) => supportForm.setData('subject', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        placeholder="Brief description of your issue"
                                        required
                                    />
                                    {supportForm.errors.subject && (
                                        <p className="mt-1 text-sm text-red-600">{supportForm.errors.subject}</p>
                                    )}
                                </div>

                                {/* Category */}
                                <div>
                                    <label htmlFor="modal-category" className="block text-sm font-medium text-gray-700 mb-2">
                                        Category <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="modal-category"
                                        value={supportForm.data.category}
                                        onChange={(e) => supportForm.setData('category', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="technical">Technical Issue</option>
                                        <option value="billing">Billing Question</option>
                                        <option value="feature_request">Feature Request</option>
                                        <option value="bug">Bug Report</option>
                                        <option value="other">Other</option>
                                    </select>
                                    {supportForm.errors.category && (
                                        <p className="mt-1 text-sm text-red-600">{supportForm.errors.category}</p>
                                    )}
                                </div>

                                {/* Priority */}
                                <div>
                                    <label htmlFor="modal-priority" className="block text-sm font-medium text-gray-700 mb-2">
                                        Priority <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="modal-priority"
                                        value={supportForm.data.priority}
                                        onChange={(e) => supportForm.setData('priority', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                    {supportForm.errors.priority && (
                                        <p className="mt-1 text-sm text-red-600">{supportForm.errors.priority}</p>
                                    )}
                                </div>

                                {/* Description */}
                                <div>
                                    <label htmlFor="modal-description" className="block text-sm font-medium text-gray-700 mb-2">
                                        Description <span className="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        id="modal-description"
                                        value={supportForm.data.description}
                                        onChange={(e) => supportForm.setData('description', e.target.value)}
                                        rows={6}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        placeholder="Please provide as much detail as possible about your issue..."
                                        required
                                        minLength={10}
                                    />
                                    {supportForm.errors.description && (
                                        <p className="mt-1 text-sm text-red-600">{supportForm.errors.description}</p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500">
                                        Minimum 10 characters required
                                    </p>
                                </div>

                                {/* Actions */}
                                <div className="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
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
                                    <Button type="submit" disabled={supportForm.processing}>
                                        {supportForm.processing ? 'Creating...' : 'Create Ticket'}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </Card>
                </div>
            )}
        </SectionLayout>
    );
}
