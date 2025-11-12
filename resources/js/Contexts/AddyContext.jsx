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
            await axios.post(`/api/addy/insights/${insightId}/dismiss`);
            // Refresh page data
            window.location.reload();
        } catch (error) {
            console.error('Failed to dismiss insight:', error);
        }
    };

    const completeInsight = async (insightId) => {
        try {
            await axios.post(`/api/addy/insights/${insightId}/complete`);
            // Refresh page data
            window.location.reload();
        } catch (error) {
            console.error('Failed to complete insight:', error);
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
                hasInsights: addy?.insights_count > 0,
                topInsight: addy?.top_insight,
                state: addy?.state,
            }}
        >
            {children}
        </AddyContext.Provider>
    );
}

export function useAddy() {
    const context = useContext(AddyContext);
    
    if (!context) {
        throw new Error('useAddy must be used within an AddyProvider');
    }
    
    return context;
}
