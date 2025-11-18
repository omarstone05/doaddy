import React from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import BentoDashboard from '@/Components/BentoDashboard';

export default function Dashboard({ user, stats, modularCards = [], preloadedCardData = {} }) {
  return (
    <AuthenticatedLayout
      user={user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>}
    >
      <Head title="Dashboard" />
      
      <BentoDashboard 
        stats={stats} 
        user={user} 
        modularCards={modularCards}
        preloadedCardData={preloadedCardData}
      />
    </AuthenticatedLayout>
  );
}
