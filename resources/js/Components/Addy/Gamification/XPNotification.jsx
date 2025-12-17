import React, { useEffect, useState } from 'react';
import { Sparkles, Trophy, Flame, Star } from 'lucide-react';

const formatReason = (reason) => {
    const reasons = {
        'sale_recorded': 'Sale recorded!',
        'sale_with_receipt': 'Sale with receipt!',
        'large_sale': 'Large sale recorded!',
        'expense_recorded': 'Expense tracked!',
        'expense_with_receipt': 'Expense with receipt!',
        'product_added': 'Product added!',
        'stock_updated': 'Stock updated!',
        'customer_added': 'Customer added!',
        'invoice_issued': 'Invoice issued!',
        'payment_recorded': 'Payment recorded!',
        'daily_streak_7': '7-day streak! 🔥',
        'daily_streak_30': '30-day streak! 🌟',
        'daily_streak_90': '90-day streak! 💪',
        'badge_unlocked_bonus': 'New badge unlocked!',
    };
    return reasons[reason] || 'Great work!';
};

export default function XPNotification({ userId }) {
    const [notification, setNotification] = useState(null);

    useEffect(() => {
        // Listen for XP earned events via WebSocket (if Echo is available)
        if (typeof window !== 'undefined' && window.Echo) {
            const channel = window.Echo.private(`user.${userId}`);

            channel.listen('.xp.earned', (event) => {
                setNotification({
                    xp: event.xp_amount,
                    reason: event.reason,
                    newLevel: event.new_level,
                });

                // Auto-dismiss after 5 seconds
                setTimeout(() => setNotification(null), 5000);
            });

            return () => {
                window.Echo.leave(`user.${userId}`);
            };
        }
    }, [userId]);

    // Also allow manual triggering via custom event
    useEffect(() => {
        const handleXPEarned = (event) => {
            setNotification(event.detail);
            setTimeout(() => setNotification(null), 5000);
        };

        window.addEventListener('xp-earned', handleXPEarned);
        return () => window.removeEventListener('xp-earned', handleXPEarned);
    }, []);

    if (!notification) return null;

    return (
        <div className="fixed top-24 right-8 z-50 animate-slide-in-right">
            <div className="bg-gradient-to-br from-teal-500 via-teal-600 to-teal-700 text-white rounded-2xl p-6 shadow-2xl min-w-[300px] border border-white/20">
                <div className="flex items-center gap-4">
                    <div className="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                        <Sparkles className="w-8 h-8 text-yellow-300" />
                    </div>
                    <div className="flex-1">
                        <div className="text-3xl font-black mb-1">
                            +{notification.xp} XP
                        </div>
                        <div className="text-sm text-white/90">
                            {formatReason(notification.reason)}
                        </div>
                        {notification.newLevel && (
                            <div className="mt-2 flex items-center gap-2 text-sm font-semibold bg-white/20 rounded-lg px-3 py-1.5 inline-flex">
                                <Star className="w-4 h-4 text-yellow-300" />
                                Level {notification.newLevel} Unlocked!
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

// Helper function to trigger XP notification manually (for testing)
export function triggerXPNotification(xp, reason, newLevel = null) {
    window.dispatchEvent(new CustomEvent('xp-earned', {
        detail: { xp, reason, newLevel }
    }));
}

