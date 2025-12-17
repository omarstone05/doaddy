import { useState, useEffect } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { User, Lock, Mail, Save, CheckCircle, AlertCircle } from 'lucide-react';

export default function Profile({ user }) {
    const { flash } = usePage().props;
    const [activeTab, setActiveTab] = useState('profile');
    const [successMessage, setSuccessMessage] = useState(flash?.success || '');
    const [errorMessage, setErrorMessage] = useState('');
    
    useEffect(() => {
        if (flash?.success) {
            setSuccessMessage(flash.success);
            setTimeout(() => setSuccessMessage(''), 5000);
        }
    }, [flash]);

    const profileForm = useForm({
        name: user.name || '',
        email: user.email || '',
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleProfileSubmit = (e) => {
        e.preventDefault();
        setSuccessMessage('');
        setErrorMessage('');

        profileForm.put('/admin/profile', {
            preserveScroll: true,
            onSuccess: () => {
                setErrorMessage('');
            },
            onError: (errors) => {
                setSuccessMessage('');
                setErrorMessage('Please check the form for errors.');
            },
        });
    };

    const handlePasswordSubmit = (e) => {
        e.preventDefault();
        setSuccessMessage('');
        setErrorMessage('');

        passwordForm.put('/admin/profile/password', {
            preserveScroll: true,
            onSuccess: () => {
                setErrorMessage('');
                passwordForm.reset();
            },
            onError: (errors) => {
                setSuccessMessage('');
                setErrorMessage('Please check the form for errors.');
            },
        });
    };

    return (
        <AdminLayout title="Profile">
            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Profile Settings</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Manage your account information and security settings
                    </p>
                </div>

                {/* Success/Error Messages */}
                {successMessage && (
                    <div className="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
                        <CheckCircle className="w-5 h-5" />
                        <span>{successMessage}</span>
                    </div>
                )}

                {errorMessage && (
                    <div className="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center gap-2">
                        <AlertCircle className="w-5 h-5" />
                        <span>{errorMessage}</span>
                    </div>
                )}

                {/* Tabs */}
                <div className="border-b border-gray-200">
                    <nav className="-mb-px flex space-x-8">
                        <button
                            onClick={() => setActiveTab('profile')}
                            className={`py-4 px-1 border-b-2 font-medium text-sm ${
                                activeTab === 'profile'
                                    ? 'border-teal-500 text-teal-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            }`}
                        >
                            <div className="flex items-center gap-2">
                                <User className="w-4 h-4" />
                                Profile Information
                            </div>
                        </button>
                        <button
                            onClick={() => setActiveTab('password')}
                            className={`py-4 px-1 border-b-2 font-medium text-sm ${
                                activeTab === 'password'
                                    ? 'border-teal-500 text-teal-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            }`}
                        >
                            <div className="flex items-center gap-2">
                                <Lock className="w-4 h-4" />
                                Change Password
                            </div>
                        </button>
                    </nav>
                </div>

                {/* Profile Information Tab */}
                {activeTab === 'profile' && (
                    <Card className="p-6">
                        <form onSubmit={handleProfileSubmit} className="space-y-6">
                            <div>
                                <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
                                    Full Name
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    value={profileForm.data.name}
                                    onChange={(e) => profileForm.setData('name', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {profileForm.errors.name && (
                                    <p className="mt-1 text-sm text-red-600">{profileForm.errors.name}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
                                    Email Address
                                </label>
                                <div className="relative">
                                    <Mail className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                                    <input
                                        id="email"
                                        type="email"
                                        value={profileForm.data.email}
                                        onChange={(e) => profileForm.setData('email', e.target.value)}
                                        className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                    />
                                </div>
                                {profileForm.errors.email && (
                                    <p className="mt-1 text-sm text-red-600">{profileForm.errors.email}</p>
                                )}
                            </div>

                            <div className="flex justify-end pt-4 border-t">
                                <Button
                                    type="submit"
                                    disabled={profileForm.processing}
                                    className="flex items-center gap-2"
                                >
                                    <Save className="w-4 h-4" />
                                    {profileForm.processing ? 'Saving...' : 'Save Changes'}
                                </Button>
                            </div>
                        </form>
                    </Card>
                )}

                {/* Change Password Tab */}
                {activeTab === 'password' && (
                    <Card className="p-6">
                        <form onSubmit={handlePasswordSubmit} className="space-y-6">
                            <div>
                                <label htmlFor="current_password" className="block text-sm font-medium text-gray-700 mb-1">
                                    Current Password
                                </label>
                                <input
                                    id="current_password"
                                    type="password"
                                    value={passwordForm.data.current_password}
                                    onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {passwordForm.errors.current_password && (
                                    <p className="mt-1 text-sm text-red-600">{passwordForm.errors.current_password}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">
                                    New Password
                                </label>
                                <input
                                    id="password"
                                    type="password"
                                    value={passwordForm.data.password}
                                    onChange={(e) => passwordForm.setData('password', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                    minLength={8}
                                />
                                {passwordForm.errors.password && (
                                    <p className="mt-1 text-sm text-red-600">{passwordForm.errors.password}</p>
                                )}
                                <p className="mt-1 text-xs text-gray-500">Must be at least 8 characters long</p>
                            </div>

                            <div>
                                <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700 mb-1">
                                    Confirm New Password
                                </label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    value={passwordForm.data.password_confirmation}
                                    onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {passwordForm.errors.password_confirmation && (
                                    <p className="mt-1 text-sm text-red-600">{passwordForm.errors.password_confirmation}</p>
                                )}
                            </div>

                            <div className="flex justify-end pt-4 border-t">
                                <Button
                                    type="submit"
                                    disabled={passwordForm.processing}
                                    className="flex items-center gap-2"
                                >
                                    <Lock className="w-4 h-4" />
                                    {passwordForm.processing ? 'Updating...' : 'Update Password'}
                                </Button>
                            </div>
                        </form>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}

