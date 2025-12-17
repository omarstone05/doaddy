import React, { useState } from 'react';
import { motion, useMotionValue, useTransform } from 'framer-motion';

/**
 * StackedCards Component
 * 
 * A card stack with swipe-to-dismiss functionality, similar to Tinder/dating apps
 * Cards are stacked with depth effect and can be swiped left/right to dismiss
 * 
 * @param {Array} cards - Array of card data objects
 * @param {Function} renderCard - Function to render each card's content
 * @param {Function} onDismiss - Callback when a card is dismissed
 * @param {number} maxVisible - Maximum number of visible stacked cards (default: 3)
 * @param {number} cardGap - Vertical gap between stacked cards in pixels (default: 8)
 * @param {number} scaleStep - Scale reduction for each stacked card (default: 0.04)
 */
const StackedCards = ({ 
  cards = [], 
  renderCard, 
  onDismiss,
  maxVisible = 3,
  cardGap = 8,
  scaleStep = 0.04
}) => {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [exitDirection, setExitDirection] = useState(null);

  // Get visible cards (current card + next cards up to maxVisible)
  const visibleCards = cards.slice(currentIndex, currentIndex + maxVisible);

  const handleDismiss = (direction) => {
    setExitDirection(direction);
    
    if (onDismiss) {
      onDismiss(cards[currentIndex], direction, currentIndex);
    }

    // Move to next card after animation completes
    setTimeout(() => {
      setCurrentIndex((prev) => prev + 1);
      setExitDirection(null);
    }, 300);
  };

  if (currentIndex >= cards.length) {
    return (
      <div className="flex items-center justify-center h-full">
        <div className="text-center">
          <div className="w-20 h-20 mx-auto mb-4 rounded-full bg-teal-100 flex items-center justify-center">
            <svg className="w-10 h-10 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <p className="text-gray-600 font-medium">All caught up! 🎉</p>
        </div>
      </div>
    );
  }

  return (
    <div className="relative w-full h-full flex items-center justify-center">
      {/* Render cards in reverse order so top card renders last (on top) */}
      {[...visibleCards].reverse().map((card, reverseIndex) => {
        const index = visibleCards.length - 1 - reverseIndex;
        const isTop = index === 0;

        return (
          <Card
            key={`${currentIndex + index}-${card.id || index}`}
            card={card}
            index={index}
            isTop={isTop}
            onDismiss={handleDismiss}
            renderCard={renderCard}
            cardGap={cardGap}
            scaleStep={scaleStep}
            totalCards={visibleCards.length}
          />
        );
      })}
    </div>
  );
};

const Card = ({ 
  card, 
  index, 
  isTop, 
  onDismiss, 
  renderCard, 
  cardGap, 
  scaleStep,
  totalCards 
}) => {
  const x = useMotionValue(0);
  const y = useMotionValue(0);

  // Rotation based on drag
  const rotate = useTransform(x, [-200, 200], [-15, 15]);
  
  // Opacity for dismiss indicators
  const opacity = useTransform(x, [-150, 0, 150], [1, 0, 1]);

  // Calculate scale and position for stacked effect
  const scale = 1 - (index * scaleStep);
  const yOffset = index * cardGap;
  const zIndex = totalCards - index;
  
  // Slight shadow increase for depth
  const shadowIntensity = isTop ? '0 25px 50px -12px rgba(0, 0, 0, 0.25)' : `0 ${15 - index * 3}px ${30 - index * 5}px -10px rgba(0, 0, 0, ${0.15 - index * 0.03})`;

  const handleDragEnd = (event, info) => {
    const threshold = 120;
    
    if (Math.abs(info.offset.x) > threshold) {
      const direction = info.offset.x > 0 ? 'right' : 'left';
      onDismiss(direction);
    }
  };

  const cardStyles = {
    width: '450px',
    height: '460px',
    maxWidth: '90vw',
  };

  // Top card is draggable and has full interactivity
  if (isTop) {
    return (
      <>
        {/* Left indicator */}
        <motion.div
          className="absolute left-4 top-1/2 -translate-y-1/2 z-50 pointer-events-none"
          style={{ opacity }}
        >
          <div className="bg-gradient-to-r from-red-500 to-rose-600 text-white px-5 py-2.5 rounded-xl font-bold text-lg rotate-[-15deg] shadow-xl">
            DISMISS
          </div>
        </motion.div>
        
        {/* Right indicator */}
        <motion.div
          className="absolute right-4 top-1/2 -translate-y-1/2 z-50 pointer-events-none"
          style={{ opacity }}
        >
          <div className="bg-gradient-to-r from-teal-500 to-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-lg rotate-[15deg] shadow-xl">
            ACTION
          </div>
        </motion.div>

        {/* Draggable card */}
        <motion.div
          className="absolute cursor-grab active:cursor-grabbing"
          style={{
            x,
            y,
            rotate,
            zIndex: 20,
            boxShadow: shadowIntensity,
            ...cardStyles,
          }}
          drag={true}
          dragConstraints={{ left: 0, right: 0, top: 0, bottom: 0 }}
          dragElastic={0.9}
          onDragEnd={handleDragEnd}
          whileTap={{ cursor: 'grabbing' }}
          whileHover={{ scale: 1.01 }}
          transition={{ type: 'spring', stiffness: 400, damping: 30 }}
        >
          <div 
            className="bg-white rounded-2xl overflow-hidden border border-gray-200/80 h-full"
            style={{ boxShadow: shadowIntensity }}
          >
            {renderCard ? renderCard(card) : (
              <div className="w-full h-full flex items-center justify-center">
                <p>Card {index}</p>
              </div>
            )}
          </div>
        </motion.div>
      </>
    );
  }

  // Background stacked cards (not draggable)
  return (
    <motion.div
      className="absolute pointer-events-none"
      initial={{
        scale: 1 - ((index - 1) * scaleStep),
        y: (index - 1) * cardGap,
        opacity: 1 - (index - 1) * 0.15,
      }}
      animate={{
        scale: scale,
        y: yOffset,
        opacity: 1 - index * 0.15,
      }}
      transition={{
        type: 'spring',
        stiffness: 400,
        damping: 35,
      }}
      style={{ 
        zIndex,
        ...cardStyles,
      }}
    >
      <div 
        className="bg-white rounded-2xl overflow-hidden border border-gray-200/60 h-full"
        style={{ 
          boxShadow: shadowIntensity,
          filter: `brightness(${1 - index * 0.03})`,
        }}
      >
        {renderCard ? renderCard(card) : (
          <div className="w-full h-full flex items-center justify-center">
            <p>Card {index}</p>
          </div>
        )}
      </div>
    </motion.div>
  );
};

export default StackedCards;
