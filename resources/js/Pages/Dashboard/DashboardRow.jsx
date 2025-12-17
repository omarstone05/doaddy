import React from 'react';
import DashboardCard from './DashboardCard';

const DashboardRow = ({ row, onRemoveCard, onPinCard }) => {
  // Grid columns based on card sizes
  const getGridClasses = () => {
    if (!row.cards || row.cards.length === 0) return 'grid-cols-1';
    
    const sizes = row.cards.map(c => c.size || 'medium');
    
    // If all small cards, use 4 columns
    if (sizes.every(s => s === 'small')) {
      return 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4';
    }
    
    // If has large or wide cards, use 12 columns
    if (sizes.some(s => s === 'large' || s === 'wide')) {
      return 'grid-cols-1 lg:grid-cols-12';
    }
    
    // Default: 3 medium cards
    return 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3';
  };

  return (
    <div className={`grid ${getGridClasses()} gap-6`}>
      {row.cards && row.cards.map((card) => (
        <DashboardCard
          key={card.id}
          cardId={card.id}
          size={card.size || 'medium'}
          pinned={card.pinned || false}
          onRemove={() => onRemoveCard(card.id)}
          onPin={() => onPinCard(card.id)}
        />
      ))}
    </div>
  );
};

export default DashboardRow;

