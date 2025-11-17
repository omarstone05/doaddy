import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Building2, Check, ChevronDown, Plus, Settings, Users } from 'lucide-react';

const BusinessSwitcher = ({ currentBusiness, businesses, className = '' }) => {
  const [isOpen, setIsOpen] = useState(false);

  const handleSwitch = (businessId) => {
    router.post(`/business/${businessId}/switch`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        setIsOpen(false);
      },
    });
  };

  const businessesByRole = businesses.reduce((acc, business) => {
    const roleGroup = business.role.slug;
    if (!acc[roleGroup]) {
      acc[roleGroup] = [];
    }
    acc[roleGroup].push(business);
    return acc;
  }, {});

  const getRoleBadgeColor = (roleSlug) => {
    const colors = {
      owner: 'bg-purple-100 text-purple-700',
      admin: 'bg-blue-100 text-blue-700',
      manager: 'bg-green-100 text-green-700',
      accountant: 'bg-yellow-100 text-yellow-700',
      employee: 'bg-gray-100 text-gray-700',
      viewer: 'bg-gray-100 text-gray-600',
    };
    return colors[roleSlug] || 'bg-gray-100 text-gray-600';
  };

  if (!currentBusiness) {
    return (
      <div className={className}>
        <a
          href="/business/create"
          className="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
        >
          <Plus size={20} />
          <span>Create Business</span>
        </a>
      </div>
    );
  }

  return (
    <div className={`relative ${className}`}>
      {/* Trigger Button */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="flex items-center gap-3 px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors w-full"
      >
        <div className="p-2 bg-indigo-100 rounded-lg">
          <Building2 size={20} className="text-indigo-600" />
        </div>
        <div className="flex-1 text-left">
          <div className="font-semibold text-gray-900 text-sm">
            {currentBusiness.name}
          </div>
          <div className="text-xs text-gray-500 capitalize">
            {currentBusiness.role.name}
          </div>
        </div>
        <ChevronDown
          size={18}
          className={`text-gray-400 transition-transform ${isOpen ? 'rotate-180' : ''}`}
        />
      </button>

      {/* Dropdown Menu */}
      {isOpen && (
        <>
          {/* Backdrop */}
          <div
            className="fixed inset-0 z-10"
            onClick={() => setIsOpen(false)}
          />

          {/* Menu */}
          <div className="absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-xl shadow-lg z-20 max-h-96 overflow-y-auto">
            {/* Current Business */}
            <div className="p-3 border-b border-gray-100">
              <div className="text-xs font-semibold text-gray-500 uppercase mb-2">
                Current Business
              </div>
              <div className="flex items-center gap-3 px-3 py-2 bg-indigo-50 rounded-lg">
                <Building2 size={18} className="text-indigo-600" />
                <div className="flex-1">
                  <div className="font-medium text-gray-900 text-sm">
                    {currentBusiness.name}
                  </div>
                  <div className="text-xs text-gray-500">
                    {currentBusiness.user_count} {currentBusiness.user_count === 1 ? 'member' : 'members'}
                  </div>
                </div>
                <Check size={18} className="text-indigo-600" />
              </div>
            </div>

            {/* Other Businesses Grouped by Role */}
            {Object.entries(businessesByRole).map(([roleSlug, roleBusinesses]) => {
              const roleLabel = roleBusinesses[0].role.name;
              
              return (
                <div key={roleSlug} className="p-3 border-b border-gray-100 last:border-0">
                  <div className="text-xs font-semibold text-gray-500 uppercase mb-2">
                    {roleLabel} In
                  </div>
                  <div className="space-y-1">
                    {roleBusinesses
                      .filter(b => b.id !== currentBusiness.id)
                      .map((business) => (
                        <button
                          key={business.id}
                          onClick={() => handleSwitch(business.id)}
                          className="w-full flex items-center gap-3 px-3 py-2 hover:bg-gray-50 rounded-lg transition-colors text-left"
                        >
                          <Building2 size={18} className="text-gray-400" />
                          <div className="flex-1">
                            <div className="font-medium text-gray-900 text-sm">
                              {business.name}
                            </div>
                            <div className="text-xs text-gray-500">
                              {business.user_count} {business.user_count === 1 ? 'member' : 'members'}
                            </div>
                          </div>
                          <span className={`text-xs px-2 py-1 rounded ${getRoleBadgeColor(business.role.slug)}`}>
                            {business.role.name}
                          </span>
                        </button>
                      ))}
                  </div>
                </div>
              );
            })}

            {/* Actions */}
            <div className="p-3 bg-gray-50 border-t border-gray-100">
              <a
                href="/business"
                className="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition-colors mb-1"
              >
                <Settings size={16} />
                <span>Manage Businesses</span>
              </a>
              <a
                href="/business/create"
                className="flex items-center gap-2 px-3 py-2 text-sm text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors font-medium"
              >
                <Plus size={16} />
                <span>Create New Business</span>
              </a>
            </div>
          </div>
        </>
      )}
    </div>
  );
};

export default BusinessSwitcher;
