import React from 'react';
import { X } from 'lucide-react';

export function AddCardModal({ isOpen, onClose, availableCards, onAddCard }) {
  if (!isOpen) return null;

  return (
    <>
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50"
        onClick={onClose}
      />

      {/* Modal */}
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
          {/* Header */}
          <div className="bg-gradient-to-r from-teal-500 to-mint-300 text-white p-6 flex items-center justify-between">
            <h2 className="text-2xl font-bold">Add Dashboard Card</h2>
            <button
              onClick={onClose}
              className="p-2 rounded-lg bg-white/20 hover:bg-white/30 transition"
            >
              <X className="h-6 w-6" />
            </button>
          </div>

          {/* Content */}
          <div className="flex-1 overflow-y-auto p-6">
            <p className="text-gray-500 mb-6">
              Select a card to add to your dashboard
            </p>

            {/* Card Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {/* Quick Action Block Option */}
              <button
                onClick={() => {
                  onAddCard('add-quick-action-block');
                  onClose();
                }}
                className="p-4 border-2 border-dashed border-teal-300 rounded-lg hover:border-teal-500 hover:bg-teal-50 transition-colors text-left"
              >
                <div className="flex items-center gap-2 mb-1">
                  <div className="w-8 h-8 bg-teal-500 rounded-lg flex items-center justify-center text-white font-bold">+</div>
                  <h3 className="font-semibold text-gray-900">Quick Action Block</h3>
                </div>
                <p className="text-sm text-gray-500">Add a new quick action block to your dashboard</p>
              </button>
              
              {availableCards && availableCards.length > 0 ? (
                availableCards.map((card) => (
                  <button
                    key={card.id}
                    onClick={() => {
                      onAddCard(card.id);
                      // Don't close immediately - let the server response handle it
                    }}
                    className="p-4 border border-gray-200 rounded-lg hover:border-teal-500 hover:bg-teal-50 transition-colors text-left"
                  >
                    <h3 className="font-semibold text-gray-900 mb-1">{card.name}</h3>
                    <p className="text-sm text-gray-500">{card.description || 'Dashboard card'}</p>
                  </button>
                ))
              ) : (
                <div className="col-span-full text-center py-12 text-gray-500">
                  No cards available. Cards will be added here as they become available.
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </>
  );
}

