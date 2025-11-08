import React from 'react';
import { useAddy } from '../../Contexts/AddyContext';

export default function AddyBubble() {
    const { openAddy, hasInsights, state, addy } = useAddy();

    // Debug: log bubble render state
    React.useEffect(() => {
        console.log('AddyBubble render:', { addy: !!addy, state: !!state, hasInsights });
    }, [addy, state, hasInsights]);

    // Always show bubble if we're authenticated (addy context exists)
    // Only hide if addy is explicitly null (user not authenticated)
    // This allows bubble to show even during initialization
    if (addy === null) {
        console.log('AddyBubble: Not showing - addy is null');
        return null;
    }

    const getBubbleColor = () => {
        // Default to blue if no state
        if (!state) return 'bg-blue-500';
        
        const urgency = state.urgency || 0;
        const mood = state.mood || 'neutral';
        
        if (urgency >= 0.8 || mood === 'concerned') {
            return 'bg-red-500';
        } else if (urgency >= 0.6 || mood === 'attentive') {
            return 'bg-orange-500';
        } else if (mood === 'optimistic') {
            return 'bg-green-500';
        }
        
        return 'bg-blue-500';
    };

    // Get default state if state is null
    const displayState = state || {
        context: 'Initializing Addy...',
        mood: 'neutral',
        urgency: 0,
    };

    return (
        <div className="fixed bottom-6 right-6 z-50">
            <button
                onClick={openAddy}
                className={`
                    ${getBubbleColor()}
                    w-16 h-16 rounded-full 
                    text-white font-bold text-xl
                    shadow-2xl
                    hover:scale-110
                    transition-all duration-300
                    flex items-center justify-center
                    relative
                    group
                `}
                title="Open Addy"
            >
                {/* Pulsing animation */}
                <span className={`
                    absolute inset-0 rounded-full
                    ${getBubbleColor()}
                    animate-ping opacity-75
                `} />
                
                {/* Badge */}
                {hasInsights && (
                    <span className="absolute -top-1 -right-1 bg-white text-red-500 text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center shadow-lg">
                        {addy?.insights_count || 0}
                    </span>
                )}
                
                {/* Addy icon */}
                <img 
                    src="/assets/logos/icon-white.png" 
                    alt="Addy" 
                    className="relative z-10 w-12 h-12 object-contain"
                    onError={(e) => {
                        // Fallback to text if image fails to load
                        e.target.style.display = 'none';
                        e.target.nextSibling.style.display = 'block';
                    }}
                />
                <span className="relative z-10 hidden">A</span>
                
                {/* Tooltip */}
                <div className="
                    absolute bottom-full right-0 mb-2
                    bg-gray-900 text-white text-sm
                    px-3 py-2 rounded-lg
                    whitespace-nowrap
                    opacity-0 group-hover:opacity-100
                    transition-opacity duration-200
                    pointer-events-none
                ">
                    {displayState?.context || 'Talk to Addy'}
                    <div className="absolute top-full right-4 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900" />
                </div>
            </button>
        </div>
    );
}

