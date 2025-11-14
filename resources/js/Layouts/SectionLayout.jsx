import React from 'react';
import { usePage } from '@inertiajs/react';
import { Navigation } from '@/Components/layout/Navigation';
import { TabNavigation } from '@/Components/layout/TabNavigation';
import FlashMessages from '@/Components/FlashMessages';
import { navigation } from './navigation';

export default function SectionLayout({ children, sectionName }) {
  const { url } = usePage().props;
  const currentPath = (url || (typeof window !== 'undefined' ? window.location.pathname : '')).replace(/\/$/, '') || '/';

  // Find the section in navigation
  const section = navigation.find(s => s.name === sectionName);
  
  if (!section) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Navigation />
        <div className="max-w-[1600px] mx-auto px-6 py-8">
          {children}
        </div>
      </div>
    );
  }

  // Create tabs from section items
  const tabs = section.items.map(item => ({
    name: item.name,
    href: item.href,
  }));

  return (
    <div className="min-h-screen bg-gray-50">
      <Navigation />
      <FlashMessages />
      <div className="max-w-[1600px] mx-auto px-6 py-8">
        {/* Section Header with Tabs */}
        {tabs.length > 0 && (
          <div className="mb-8">
            <TabNavigation tabs={tabs} currentPath={currentPath} />
          </div>
        )}
        {children}
      </div>
    </div>
  );
}

