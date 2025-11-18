// AddCardModal.jsx - Modal for adding cards to dashboard
import React, { useState, useMemo } from 'react';
import { X, Search, Package, Briefcase, TrendingUp, DollarSign, CheckSquare, Clock, Users, Calendar, Target, List, PieChart, LineChart, BarChart, AlertTriangle } from 'lucide-react';

// Icon mapping for cards
const ICON_MAP = {
  'TrendingUp': TrendingUp,
  'TrendingDown': TrendingUp,
  'DollarSign': DollarSign,
  'ArrowLeftRight': TrendingUp,
  'LineChart': LineChart,
  'PieChart': PieChart,
  'BarChart': BarChart,
  'List': List,
  'Target': Target,
  'Briefcase': Briefcase,
  'CheckSquare': CheckSquare,
  'Clock': Clock,
  'Users': Users,
  'Calendar': Calendar,
  'Package': Package,
  'AlertTriangle': AlertTriangle,
};

const AddCardModal = ({ availableCards = [], onAdd, onClose }) => {
  const [search, setSearch] = useState('');
  const [selectedModule, setSelectedModule] = useState('all');
  const [selectedCategory, setSelectedCategory] = useState('all');

  // Group cards by module
  const cardsByModule = useMemo(() => {
    return availableCards.reduce((acc, card) => {
      const module = card.module_id;
      if (!acc[module]) {
        acc[module] = {
          cards: [],
          metadata: {
            name: module.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()),
            color: card.color || '#00635D',
          }
        };
      }
      acc[module].cards.push(card);
      return acc;
    }, {});
  }, [availableCards]);

  // Get unique modules
  const modules = Object.keys(cardsByModule);

  // Get unique categories
  const categories = useMemo(() => {
    const cats = new Set(availableCards.map(card => card.category));
    return Array.from(cats);
  }, [availableCards]);

  // Filter cards
  const filteredCards = useMemo(() => {
    return availableCards.filter(card => {
      // Search filter
      const matchesSearch = search === '' ||
        card.name.toLowerCase().includes(search.toLowerCase()) ||
        card.description.toLowerCase().includes(search.toLowerCase()) ||
        (card.tags || '').toLowerCase().includes(search.toLowerCase());
      
      // Module filter
      const matchesModule = selectedModule === 'all' || card.module_id === selectedModule;

      // Category filter
      const matchesCategory = selectedCategory === 'all' || card.category === selectedCategory;

      return matchesSearch && matchesModule && matchesCategory;
    });
  }, [availableCards, search, selectedModule, selectedCategory]);

  // Get icon component
  const getIconComponent = (iconName) => {
    return ICON_MAP[iconName] || Package;
  };

  // Get category badge color
  const getCategoryColor = (category) => {
    const colors = {
      'metric': 'bg-teal-100 text-teal-700',
      'chart': 'bg-purple-100 text-purple-700',
      'list': 'bg-blue-100 text-blue-700',
      'progress': 'bg-green-100 text-green-700',
    };
    return colors[category] || 'bg-gray-100 text-gray-700';
  };

  // Get size badge
  const getSizeBadge = (size) => {
    const labels = {
      'small': 'Small',
      'medium': 'Medium',
      'large': 'Large',
      'wide': 'Full Width',
    };
    return labels[size] || size;
  };

  return (
    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[85vh] overflow-hidden flex flex-col">
        {/* Header */}
        <div className="p-6 border-b border-gray-200 flex-shrink-0">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h2 className="text-2xl font-bold text-gray-900">Add Card to Dashboard</h2>
              <p className="text-sm text-gray-600 mt-1">
                Choose from {availableCards.length} available cards
              </p>
            </div>
            <button
              onClick={onClose}
              className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <X size={24} className="text-gray-600" />
            </button>
          </div>

          {/* Search & Filters */}
          <div className="space-y-3">
            {/* Search Bar */}
            <div className="relative">
              <Search size={20} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search cards by name, description, or tags..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-sm"
                autoFocus
              />
            </div>

            {/* Filter Row */}
            <div className="flex gap-3">
              {/* Module Filter */}
              <select
                value={selectedModule}
                onChange={(e) => setSelectedModule(e.target.value)}
                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 text-sm"
              >
                <option value="all">All Modules ({availableCards.length})</option>
                {modules.map(module => (
                  <option key={module} value={module}>
                    {cardsByModule[module].metadata.name} ({cardsByModule[module].cards.length})
                  </option>
                ))}
              </select>

              {/* Category Filter */}
              <select
                value={selectedCategory}
                onChange={(e) => setSelectedCategory(e.target.value)}
                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 text-sm"
              >
                <option value="all">All Categories</option>
                {categories.map(category => (
                  <option key={category} value={category}>
                    {category.charAt(0).toUpperCase() + category.slice(1)}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </div>

        {/* Cards Grid */}
        <div className="flex-1 overflow-y-auto p-6">
          {filteredCards.length === 0 ? (
            <div className="flex flex-col items-center justify-center h-full text-gray-500 py-12">
              <Search size={48} className="mb-3 opacity-20" />
              <p className="text-lg font-medium">No cards found</p>
              <p className="text-sm mt-1">Try adjusting your search or filters</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {filteredCards.map((card) => {
                const IconComponent = getIconComponent(card.icon);
                
                return (
                  <button
                    key={card.id}
                    onClick={() => onAdd(card.id)}
                    className="glass-card p-5 text-left hover:shadow-xl transition-all group border-2 border-transparent hover:border-teal-500"
                  >
                    {/* Card Icon */}
                    <div
                      className="w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110"
                      style={{
                        background: `linear-gradient(135deg, ${card.color}dd, ${card.color})`
                      }}
                    >
                      <IconComponent size={24} className="text-white" />
                    </div>

                    {/* Card Info */}
                    <h3 className="font-semibold text-gray-900 mb-1.5 group-hover:text-teal-600 transition-colors">
                      {card.name}
                    </h3>
                    <p className="text-sm text-gray-600 mb-3 line-clamp-2">
                      {card.description}
                    </p>

                    {/* Tags */}
                    <div className="flex flex-wrap gap-2">
                      {/* Category Badge */}
                      <span className={`px-2 py-0.5 rounded text-xs font-medium ${getCategoryColor(card.category)}`}>
                        {card.category}
                      </span>
                      
                      {/* Size Badge */}
                      <span className="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs font-medium">
                        {getSizeBadge(card.size)}
                      </span>

                      {/* Priority indicator */}
                      {card.priority >= 8 && (
                        <span className="px-2 py-0.5 bg-amber-100 text-amber-700 rounded text-xs font-medium">
                          Popular
                        </span>
                      )}
                    </div>

                    {/* Hover indicator */}
                    <div className="mt-3 pt-3 border-t border-gray-200 opacity-0 group-hover:opacity-100 transition-opacity">
                      <span className="text-xs text-teal-600 font-medium">
                        Click to add →
                      </span>
                    </div>
                  </button>
                );
              })}
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="p-6 border-t border-gray-200 bg-gray-50 flex-shrink-0">
          <div className="flex items-center justify-between">
            <div className="text-sm text-gray-600">
              Showing {filteredCards.length} of {availableCards.length} cards
            </div>
            <button
              onClick={onClose}
              className="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors font-medium"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AddCardModal;

