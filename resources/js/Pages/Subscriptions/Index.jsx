import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Check, Loader2, CreditCard, Sparkles } from 'lucide-react';

export default function Subscriptions({ plans, currentSubscription, organization }) {
    const [subscribing, setSubscribing] = useState(null);
    const [error, setError] = useState('');

    const handleSubscribe = async (planId) => {
        setSubscribing(planId);
        setError('');

        try {
            const response = await fetch('/subscriptions/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ plan_id: planId }),
            });

            const data = await response.json();

            if (data.success) {
                // Redirect to Lenco payment page
                window.location.href = data.authorization_url;
            } else {
                setError(data.message || 'Failed to initialize subscription');
                setSubscribing(null);
            }
        } catch (err) {
            setError('An error occurred. Please try again.');
            setSubscribing(null);
        }
    };

    const handleCancel = async () => {
        if (!confirm('Are you sure you want to cancel your subscription? You will retain access until the end of your billing period.')) {
            return;
        }

        try {
            const response = await fetch('/subscriptions/cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            if (response.ok) {
                router.reload();
            }
        } catch (err) {
            alert('Failed to cancel subscription. Please try again.');
        }
    };

    const isCurrentPlan = (plan) => {
        return currentSubscription && currentSubscription.subscription_plan_id === plan.id;
    };

    const getPlanBadge = (plan) => {
        if (isCurrentPlan(plan)) {
            return (
                <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                    Current Plan
                </span>
            );
        }
        return null;
    };

    return (
        <AuthenticatedLayout title="Subscription Plans">
            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Choose Your Plan</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Select the perfect plan for your business needs
                    </p>
                </div>

                {/* Current Subscription Info */}
                {currentSubscription && (
                    <Card className="p-6 bg-gradient-to-r from-teal-50 to-mint-50 border-teal-200">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900">
                                    Current Subscription: {currentSubscription.plan?.name}
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">
                                    {currentSubscription.status === 'active' && currentSubscription.ends_at && (
                                        <>Renews on {new Date(currentSubscription.ends_at).toLocaleDateString()}</>
                                    )}
                                    {currentSubscription.status === 'cancelled' && (
                                        <>Access until {new Date(currentSubscription.ends_at).toLocaleDateString()}</>
                                    )}
                                </p>
                            </div>
                            {currentSubscription.status === 'active' && (
                                <Button
                                    variant="secondary"
                                    onClick={handleCancel}
                                    className="flex items-center gap-2"
                                >
                                    Cancel Subscription
                                </Button>
                            )}
                        </div>
                    </Card>
                )}

                {/* Error Message */}
                {error && (
                    <div className="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                        {error}
                    </div>
                )}

                {/* Plans Grid */}
                <div className="grid md:grid-cols-3 gap-6">
                    {plans.map((plan) => (
                        <Card
                            key={plan.id}
                            className={`p-6 relative ${
                                isCurrentPlan(plan)
                                    ? 'ring-2 ring-teal-500 bg-teal-50/50'
                                    : 'hover:shadow-lg transition-shadow'
                            }`}
                        >
                            {plan.slug === 'professional' && (
                                <div className="absolute top-0 right-0 bg-gradient-to-r from-teal-500 to-teal-600 text-white px-3 py-1 rounded-bl-lg rounded-tr-lg text-xs font-medium flex items-center gap-1">
                                    <Sparkles className="w-3 h-3" />
                                    Popular
                                </div>
                            )}

                            <div className="mb-4">
                                <div className="flex items-center justify-between mb-2">
                                    <h3 className="text-xl font-bold text-gray-900">{plan.name}</h3>
                                    {getPlanBadge(plan)}
                                </div>
                                <p className="text-sm text-gray-600">{plan.description}</p>
                            </div>

                            <div className="mb-6">
                                <div className="flex items-baseline">
                                    <span className="text-4xl font-bold text-gray-900">
                                        {plan.currency} {plan.price.toLocaleString()}
                                    </span>
                                    <span className="text-gray-600 ml-2">
                                        /{plan.billing_period === 'monthly' ? 'month' : 'year'}
                                    </span>
                                </div>
                                {plan.trial_days > 0 && (
                                    <p className="text-sm text-teal-600 mt-1">
                                        {plan.trial_days}-day free trial
                                    </p>
                                )}
                            </div>

                            <ul className="space-y-3 mb-6">
                                {plan.features?.map((feature, index) => (
                                    <li key={index} className="flex items-start gap-2">
                                        <Check className="w-5 h-5 text-teal-500 flex-shrink-0 mt-0.5" />
                                        <span className="text-sm text-gray-700">{feature}</span>
                                    </li>
                                ))}
                            </ul>

                            <Button
                                onClick={() => handleSubscribe(plan.id)}
                                disabled={subscribing === plan.id || isCurrentPlan(plan)}
                                className={`w-full ${
                                    plan.slug === 'professional'
                                        ? 'bg-gradient-to-br from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700'
                                        : ''
                                }`}
                            >
                                {subscribing === plan.id ? (
                                    <>
                                        <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                        Processing...
                                    </>
                                ) : isCurrentPlan(plan) ? (
                                    'Current Plan'
                                ) : (
                                    <>
                                        <CreditCard className="w-4 h-4 mr-2" />
                                        Subscribe Now
                                    </>
                                )}
                            </Button>
                        </Card>
                    ))}
                </div>

                {/* Additional Info */}
                <Card className="p-6 bg-gray-50">
                    <h3 className="font-semibold text-gray-900 mb-3">What's included in all plans:</h3>
                    <div className="grid md:grid-cols-2 gap-4 text-sm text-gray-600">
                        <div className="flex items-start gap-2">
                            <Check className="w-5 h-5 text-teal-500 flex-shrink-0 mt-0.5" />
                            <span>Access to Addy AI Business COO</span>
                        </div>
                        <div className="flex items-start gap-2">
                            <Check className="w-5 h-5 text-teal-500 flex-shrink-0 mt-0.5" />
                            <span>Mobile app access</span>
                        </div>
                        <div className="flex items-start gap-2">
                            <Check className="w-5 h-5 text-teal-500 flex-shrink-0 mt-0.5" />
                            <span>Regular updates and new features</span>
                        </div>
                        <div className="flex items-start gap-2">
                            <Check className="w-5 h-5 text-teal-500 flex-shrink-0 mt-0.5" />
                            <span>Secure cloud storage</span>
                        </div>
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

