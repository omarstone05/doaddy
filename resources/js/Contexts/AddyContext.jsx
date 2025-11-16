import React, { createContext, useContext, useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';

const AddyContext = createContext(null);

export function AddyProvider({ children }) {
    // usePage must be called inside Inertia App context
    const page = usePage();
    const addyData = page?.props?.addy;
    const [isOpen, setIsOpen] = useState(false);
    const [panelView, setPanelView] = useState('chat'); // chat | insights
    const [addy, setAddy] = useState(addyData || null);

    useEffect(() => {
        setAddy(addyData || null);
        // Debug: log when addy data changes
        if (addyData) {
            console.log('Addy data loaded:', addyData);
        }
    }, [addyData]);

    const openAddy = (view = 'chat') => {
        setPanelView(view);
        setIsOpen(true);
    };
    const closeAddy = () => setIsOpen(false);
    const toggleAddy = (view = 'chat') => {
        setPanelView(view);
        setIsOpen(prev => !prev);
    };
    const showChatView = () => setPanelView('chat');
    const showInsightsView = () => {
        setPanelView('insights');
        if (!isOpen) {
            setIsOpen(true);
        }
    };

    const dismissInsight = async (insightId) => {
        try {
            const response = await axios.post(`/api/addy/insights/${insightId}/dismiss`);
            if (response.data.success) {
                // Update local state to remove the dismissed insight
                setAddy(prev => {
                    if (!prev) return prev;
                    
                    // Filter out the dismissed insight
                    const updatedInsights = (prev.insights || []).filter(insight => insight.id !== insightId);
                    
                    // Update top_insight if it was the dismissed one
                    let updatedTopInsight = prev.top_insight;
                    if (prev.top_insight?.id === insightId) {
                        // Set the next highest priority insight as top, or null if none
                        updatedTopInsight = updatedInsights.length > 0 ? updatedInsights[0] : null;
                    }
                    
                    return {
                        ...prev,
                        insights: updatedInsights,
                        top_insight: updatedTopInsight,
                        insights_count: updatedInsights.length,
                    };
                });
            } else {
                throw new Error(response.data.message || 'Failed to dismiss insight');
            }
        } catch (error) {
            console.error('Failed to dismiss insight:', error);
            // Re-throw so the component can handle it
            throw error;
        }
    };

    const completeInsight = async (insightId) => {
        try {
            const response = await axios.post(`/api/addy/insights/${insightId}/complete`);
            if (response.data.success) {
                // Update local state to remove the completed insight
                setAddy(prev => {
                    if (!prev) return prev;
                    
                    // Filter out the completed insight
                    const updatedInsights = (prev.insights || []).filter(insight => insight.id !== insightId);
                    
                    // Update top_insight if it was the completed one
                    let updatedTopInsight = prev.top_insight;
                    if (prev.top_insight?.id === insightId) {
                        // Set the next highest priority insight as top, or null if none
                        updatedTopInsight = updatedInsights.length > 0 ? updatedInsights[0] : null;
                    }
                    
                    return {
                        ...prev,
                        insights: updatedInsights,
                        top_insight: updatedTopInsight,
                        insights_count: updatedInsights.length,
                    };
                });
            }
        } catch (error) {
            console.error('Failed to complete insight:', error);
        }
    };

    const refreshInsights = async () => {
        try {
            const response = await axios.post('/api/addy/insights/refresh');
            if (response.data.success && response.data.data) {
                // Update local state with refreshed data
                setAddy(prev => ({
                    ...prev,
                    state: response.data.data.state,
                    top_insight: response.data.data.top_insight,
                    insights: response.data.data.insights || [],
                    insights_count: response.data.data.insights_count,
                }));
                return { success: true, message: response.data.message };
            }
            return { success: false, message: response.data.message || 'Failed to refresh insights' };
        } catch (error) {
            console.error('Failed to refresh insights:', error);
            return { 
                success: false, 
                message: error.response?.data?.message || 'Failed to refresh insights. Please try again.' 
            };
        }
    };

    return (
        <AddyContext.Provider
            value={{
                addy,
                isOpen,
                panelView,
                openAddy,
                closeAddy,
                toggleAddy,
                showChatView,
                showInsightsView,
                dismissInsight,
                completeInsight,
                refreshInsights,
                hasInsights: addy?.insights_count > 0,
                topInsight: addy?.top_insight,
                insights: addy?.insights || [],
                state: addy?.state,
            }}
        >
            {children}
        </AddyContext.Provider>
    );
}

export function useAddy() {
    const context = useContext(AddyContext);
    
    // Return null instead of throwing to allow graceful handling
    // Components can check for null and render fallbacks
    if (!context) {
        console.warn('useAddy must be used within an AddyProvider');
        return null;
    }
    
    return context;
}
