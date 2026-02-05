import React, { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import AddyInsightCards from '@/Components/Addy/AddyInsightCards';
import { Card } from '@/Components/ui';
import { ArrowLeft, Lightbulb, AlertTriangle, TrendingUp, CheckCircle, Sparkles } from 'lucide-react';

/**
 * InsightCardsPage - Full page for reviewing Addy AI insights
 * with swipeable card interface
 */
const InsightCardsPage = ({ insights: initialInsights = [], statistics = {}, section }) => {
  const [insights, setInsights] = useState(initialInsights);
  const [stats, setStats] = useState(statistics);
  const [isLoading, setIsLoading] = useState(false);

  const handleDismiss = async (insightId) => {
    setIsLoading(true);

    try {
      const response = await fetch(`/cards/insights/${insightId}/dismiss`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ direction: 'left' }), // Default dismiss direction
      });

      if (response.ok) {
        // Update local state
        setInsights((prev) => prev.filter((i) => i.id !== insightId));
        
        // Update statistics
        setStats((prev) => ({
          ...prev,
          insights: {
            ...prev?.insights,
            active: Math.max(0, (prev?.insights?.active || 1) - 1),
            reviewed_today: (prev?.insights?.reviewed_today || 0) + 1,
          },
        }));
      }
    } catch (error) {
      console.error('Error dismissing insight:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleAction = async (insight, action) => {
    if (action === 'action' && insight.action_url) {
      // Track as actioned
      try {
        await fetch(`/cards/insights/${insight.id}/dismiss`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify({ direction: 'right' }),
        });
      } catch (error) {
        console.error('Error tracking action:', error);
      }
    }
  };

  return (
    <AuthenticatedLayout>
      <Head title="Addy Insights" />
      
      <div className="max-w-6xl mx-auto px-6 py-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <Link
                href="/dashboard"
                className="p-2 rounded-xl bg-white/90 border border-gray-200 text-gray-600 hover:bg-teal-50 hover:text-teal-600 transition-colors"
              >
                <ArrowLeft className="h-5 w-5" />
              </Link>
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center">
                  <img 
                    src="/assets/logos/icon-white.webp" 
                    alt="Addy" 
                    className="w-8 h-8 object-contain"
                  />
                </div>
                <div>
                  <h1 className="text-2xl font-bold text-gray-900">
                    {section ? `${section} Insights` : 'Addy Insights'}
                  </h1>
                  <p className="text-gray-600 text-sm">
                    Swipe right to action • Swipe left to dismiss
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Card Stack Container */}
        <div className="flex items-center justify-center" style={{ height: '550px' }}>
          {insights.length > 0 ? (
            <AddyInsightCards
              insights={insights}
              onDismiss={handleDismiss}
              onAction={handleAction}
              sectionName={section}
            />
          ) : (
            <div className="text-center">
              <div className="w-24 h-24 mx-auto mb-4 rounded-2xl bg-teal-100 flex items-center justify-center">
                <Sparkles className="w-12 h-12 text-teal-500" />
              </div>
              <h3 className="text-2xl font-bold text-gray-900 mb-2">All Clear! ✨</h3>
              <p className="text-gray-600 mb-6 max-w-sm mx-auto">
                You've reviewed all insights. Addy is monitoring your business and will alert you if anything needs attention.
              </p>
              <Link
                href="/dashboard"
                className="inline-flex items-center gap-2 px-6 py-3 bg-teal-500 text-white rounded-xl hover:bg-teal-600 transition-colors font-semibold"
              >
                Back to Dashboard
              </Link>
            </div>
          )}
        </div>

        {/* Stats Section */}
        {stats?.insights && (
          <div className="mt-12 grid grid-cols-1 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
            <Card variant="glass" padding="md" className="border-t-4 border-teal-500">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-gray-500 text-xs font-semibold uppercase">Active</p>
                  <p className="text-2xl font-black text-gray-900">{stats.insights.active || 0}</p>
                </div>
                <div className="w-10 h-10 bg-teal-100 rounded-xl flex items-center justify-center">
                  <Lightbulb className="w-5 h-5 text-teal-600" />
                </div>
              </div>
            </Card>

            <Card variant="glass" padding="md" className="border-t-4 border-red-500">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-gray-500 text-xs font-semibold uppercase">Alerts</p>
                  <p className="text-2xl font-black text-gray-900">{stats.insights.alerts || 0}</p>
                </div>
                <div className="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                  <AlertTriangle className="w-5 h-5 text-red-600" />
                </div>
              </div>
            </Card>

            <Card variant="glass" padding="md" className="border-t-4 border-emerald-500">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-gray-500 text-xs font-semibold uppercase">Suggestions</p>
                  <p className="text-2xl font-black text-gray-900">{stats.insights.suggestions || 0}</p>
                </div>
                <div className="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                  <TrendingUp className="w-5 h-5 text-emerald-600" />
                </div>
              </div>
            </Card>

            <Card variant="glass" padding="md" className="border-t-4 border-amber-500">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-gray-500 text-xs font-semibold uppercase">Reviewed Today</p>
                  <p className="text-2xl font-black text-gray-900">{stats.insights.reviewed_today || 0}</p>
                </div>
                <div className="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                  <CheckCircle className="w-5 h-5 text-amber-600" />
                </div>
              </div>
            </Card>
          </div>
        )}

        {/* Loading Overlay */}
        {isLoading && (
          <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div className="bg-white rounded-2xl p-6 flex items-center gap-3 shadow-xl">
              <svg className="animate-spin h-6 w-6 text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span className="text-gray-700 font-medium">Updating...</span>
            </div>
          </div>
        )}
      </div>
    </AuthenticatedLayout>
  );
};

export default InsightCardsPage;

