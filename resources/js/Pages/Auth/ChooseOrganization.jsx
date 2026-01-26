import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Building2, Plus } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function ChooseOrganization({ organizations = [], pendaOnboardingUrl, next = '/dashboard' }) {
  const [selected, setSelected] = useState(organizations[0]?.id ?? null);
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = () => {
    if (!selected || submitting) return;
    setSubmitting(true);
    router.post('/auth/choose-organization', { organization_id: selected }, {
      onFinish: () => setSubmitting(false),
    });
  };

  return (
    <AuthenticatedLayout>
      <Head title="Choose Organization" />
      <div className="max-w-3xl mx-auto py-12 px-6">
        <div className="bg-white shadow-xl rounded-2xl border border-gray-100 p-8">
          <div className="flex items-center gap-3 mb-6">
            <div className="h-12 w-12 rounded-xl bg-teal-100 flex items-center justify-center">
              <Building2 className="h-6 w-6 text-teal-600" />
            </div>
            <div>
              <h1 className="text-2xl font-black text-gray-900">Pick an organization</h1>
              <p className="text-gray-600">Use an existing company from Penda Cloud or create a new one.</p>
            </div>
          </div>

          <div className="grid md:grid-cols-2 gap-4 mb-6">
            {organizations.map((org) => (
              <button
                key={org.id}
                onClick={() => setSelected(org.id)}
                className={`w-full text-left rounded-xl border p-4 transition-all ${
                  selected === org.id
                    ? 'border-teal-500 shadow-lg bg-teal-50'
                    : 'border-gray-200 hover:border-teal-300 hover:shadow-sm'
                }`}
              >
                <div className="flex items-center gap-3">
                  <div className="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center text-lg font-bold text-teal-700">
                    {org.logo ? (
                      <img src={org.logo} alt={org.name} className="h-10 w-10 rounded-lg object-cover" />
                    ) : (
                      org.name?.charAt(0)?.toUpperCase()
                    )}
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="font-semibold text-gray-900 truncate">{org.name}</p>
                    <p className="text-xs text-gray-500 capitalize">{org.role || 'member'}</p>
                  </div>
                  <div className={`h-4 w-4 rounded-full border ${selected === org.id ? 'border-teal-600 bg-teal-500' : 'border-gray-300'}`} />
                </div>
              </button>
            ))}
          </div>

          <div className="flex items-center justify-between gap-4">
            <a
              className="inline-flex items-center gap-2 text-sm font-semibold text-teal-700 hover:text-teal-800"
              href={pendaOnboardingUrl}
              target="_blank"
              rel="noopener noreferrer"
            >
              <Plus className="h-4 w-4" />
              Create a new organization (opens Penda Cloud)
            </a>
            <button
              disabled={!selected || submitting}
              onClick={handleSubmit}
              className="px-5 py-2.5 bg-teal-600 text-white rounded-lg font-semibold shadow hover:bg-teal-700 disabled:opacity-60 disabled:cursor-not-allowed"
            >
              {submitting ? 'Saving...' : 'Continue'}
            </button>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
