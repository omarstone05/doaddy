import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import DashboardRow from './DashboardRow';
import AddCardModal from './AddCardModal';

const ModularDashboard = ({ layout: initialLayout, availableCards: initialAvailableCards }) => {
  const [layout, setLayout] = useState(initialLayout || { rows: [] });
  const [availableCards, setAvailableCards] = useState(initialAvailableCards || []);
  const [showAddModal, setShowAddModal] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleAddCard = async (cardId) => {
    setLoading(true);
    try {
      const response = await fetch('/api/dashboard/add-card', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({ card_id: cardId }),
      });

      if (response.ok) {
        const newLayout = await response.json();
        setLayout(newLayout);
        setShowAddModal(false);
        
        // Refresh available cards
        const cardsResponse = await fetch('/api/dashboard/available-cards');
        if (cardsResponse.ok) {
          const cards = await cardsResponse.json();
          setAvailableCards(cards);
        }
      }
    } catch (error) {
      console.error('Error adding card:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleRemoveCard = async (cardId) => {
    setLoading(true);
    try {
      const response = await fetch('/api/dashboard/remove-card', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({ card_id: cardId }),
      });

      if (response.ok) {
        const newLayout = await response.json();
        setLayout(newLayout);
        
        // Refresh available cards
        const cardsResponse = await fetch('/api/dashboard/available-cards');
        if (cardsResponse.ok) {
          const cards = await cardsResponse.json();
          setAvailableCards(cards);
        }
      }
    } catch (error) {
      console.error('Error removing card:', error);
    } finally {
      setLoading(false);
    }
  };

  const handlePinCard = async (cardId) => {
    setLoading(true);
    try {
      const response = await fetch('/api/dashboard/pin-card', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({ card_id: cardId }),
      });

      if (response.ok) {
        const newLayout = await response.json();
        setLayout(newLayout);
      }
    } catch (error) {
      console.error('Error pinning card:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen dashboard-background">
      <div className="max-w-7xl mx-auto p-6 space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p className="text-gray-600 mt-1">Welcome back to Addy</p>
          </div>
          
          <button
            onClick={() => setShowAddModal(true)}
            disabled={loading}
            className="flex items-center gap-2 px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition-colors disabled:opacity-50"
          >
            <Plus size={20} />
            Add Card
          </button>
        </div>

        {/* Dashboard Rows */}
        {layout.rows && layout.rows.length > 0 ? (
          layout.rows.map((row) => (
            <DashboardRow
              key={row.id}
              row={row}
              onRemoveCard={handleRemoveCard}
              onPinCard={handlePinCard}
            />
          ))
        ) : (
          <div className="text-center py-12 text-gray-500">
            <p className="text-lg mb-2">No cards yet</p>
            <p className="text-sm">Click "Add Card" to get started</p>
          </div>
        )}

        {/* Add Card Modal */}
        {showAddModal && (
          <AddCardModal
            availableCards={availableCards}
            onAdd={handleAddCard}
            onClose={() => setShowAddModal(false)}
          />
        )}
      </div>
    </div>
  );
};

export default ModularDashboard;

