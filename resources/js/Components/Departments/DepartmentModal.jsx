import { useState, useEffect } from 'react';
import { X, Building2 } from 'lucide-react';
import { Button } from '@/Components/ui/Button';
import axios from 'axios';
import { router } from '@inertiajs/react';

export default function DepartmentModal({ isOpen, onClose, onDepartmentCreated }) {
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        manager_id: '',
        is_active: true,
    });
    const [teamMembers, setTeamMembers] = useState([]);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        if (isOpen) {
            loadTeamMembers();
            // Reset form when modal opens
            setFormData({
                name: '',
                description: '',
                manager_id: '',
                is_active: true,
            });
        }
    }, [isOpen]);

    const loadTeamMembers = async () => {
        try {
            const response = await axios.get('/departments/create');
            setTeamMembers(response.data.teamMembers || []);
        } catch (error) {
            console.error('Failed to load team members:', error);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            await axios.post('/departments', formData);
            setFormData({
                name: '',
                description: '',
                manager_id: '',
                is_active: true,
            });
            onClose();
            if (onDepartmentCreated) {
                onDepartmentCreated();
            }
            router.reload();
        } catch (error) {
            console.error('Failed to create department:', error);
            if (error.response?.data?.errors) {
                const errors = error.response.data.errors;
                const errorMessages = Object.values(errors).flat().join(', ');
                alert(`Failed to create department: ${errorMessages}`);
            } else {
                alert('Failed to create department. Please try again.');
            }
        } finally {
            setSubmitting(false);
        }
    };

    if (!isOpen) return null;

    return (
        <>
            {/* Backdrop */}
            <div 
                className="fixed inset-0 bg-gradient-to-br from-teal-50/30 via-mint-50/20 to-white/40 backdrop-blur-md z-50" 
                onClick={onClose} 
            />
            
            {/* Modal */}
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div className="bg-white/90 backdrop-blur-2xl rounded-3xl shadow-2xl border border-white/60 max-w-2xl w-full max-h-[90vh] overflow-y-auto relative" style={{
                    background: 'linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(240,253,250,0.9) 50%, rgba(255,255,255,0.95) 100%)'
                }}>
                    {/* Header */}
                    <div className="sticky top-0 bg-white/40 backdrop-blur-md border-b border-mint-200/40 px-6 py-4 flex items-center justify-between z-10 rounded-t-3xl" style={{
                        background: 'linear-gradient(180deg, rgba(255,255,255,0.6) 0%, rgba(240,253,250,0.4) 100%)'
                    }}>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-full bg-white/80 backdrop-blur-sm shadow-md flex items-center justify-center border border-mint-200/30">
                                <Building2 className="h-5 w-5 text-teal-500" />
                            </div>
                            <div>
                                <h3 className="text-xl font-bold text-teal-700">Add Department</h3>
                                <p className="text-xs text-teal-600/70">Create a new department</p>
                            </div>
                        </div>
                        <button
                            onClick={onClose}
                            className="p-2 rounded-xl bg-white/60 hover:bg-white/80 backdrop-blur-sm border border-mint-200/50 text-teal-700 hover:text-teal-800 transition-all shadow-sm"
                            disabled={submitting}
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>

                    {/* Content */}
                    <div className="p-6" style={{
                        background: 'linear-gradient(180deg, rgba(255,255,255,0.5) 0%, rgba(240,253,250,0.3) 50%, rgba(255,255,255,0.5) 100%)'
                    }}>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Name <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white/80 backdrop-blur-sm"
                                    placeholder="e.g., Sales, Operations, Finance"
                                    required
                                    disabled={submitting}
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Description
                                </label>
                                <textarea
                                    value={formData.description}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                    rows={4}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white/80 backdrop-blur-sm"
                                    placeholder="Describe the department's role and responsibilities..."
                                    disabled={submitting}
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Manager
                                </label>
                                <select
                                    value={formData.manager_id}
                                    onChange={(e) => setFormData({ ...formData, manager_id: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white/80 backdrop-blur-sm"
                                    disabled={submitting}
                                >
                                    <option value="">Select manager (optional)</option>
                                    {teamMembers.map((member) => (
                                        <option key={member.id} value={member.id}>
                                            {member.first_name} {member.last_name}
                                            {member.job_title && ` - ${member.job_title}`}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    checked={formData.is_active}
                                    onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                    className="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                                    disabled={submitting}
                                />
                                <label htmlFor="is_active" className="text-sm font-medium text-gray-700">
                                    Active
                                </label>
                            </div>

                            <div className="flex gap-3 pt-4 border-t border-mint-200/40">
                                <Button
                                    variant="outline"
                                    type="button"
                                    onClick={onClose}
                                    disabled={submitting}
                                    className="flex-1 bg-white/60 backdrop-blur-sm border-mint-200/50 hover:bg-white/80 text-teal-700"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={submitting}
                                    className="flex-1 bg-gradient-to-br from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 shadow-lg"
                                >
                                    {submitting ? 'Creating...' : 'Create Department'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}

