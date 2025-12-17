import { useState, useEffect } from 'react';
import { X, Calendar } from 'lucide-react';
import { Button } from '@/Components/ui/Button';
import axios from 'axios';
import { router } from '@inertiajs/react';

export default function LeaveTypeModal({ isOpen, onClose, onLeaveTypeCreated }) {
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        maximum_days_per_year: 0,
        can_carry_forward: false,
        max_carry_forward_days: null,
        is_active: true,
    });
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        if (isOpen) {
            // Reset form when modal opens
            setFormData({
                name: '',
                description: '',
                maximum_days_per_year: 0,
                can_carry_forward: false,
                max_carry_forward_days: null,
                is_active: true,
            });
        }
    }, [isOpen]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            await axios.post('/leave/types', formData);
            setFormData({
                name: '',
                description: '',
                maximum_days_per_year: 0,
                can_carry_forward: false,
                max_carry_forward_days: null,
                is_active: true,
            });
            onClose();
            if (onLeaveTypeCreated) {
                onLeaveTypeCreated();
            }
            router.reload();
        } catch (error) {
            console.error('Failed to create leave type:', error);
            if (error.response?.data?.errors) {
                const errors = error.response.data.errors;
                const errorMessages = Object.values(errors).flat().join(', ');
                alert(`Failed to create leave type: ${errorMessages}`);
            } else {
                alert('Failed to create leave type. Please try again.');
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
                                <Calendar className="h-5 w-5 text-teal-500" />
                            </div>
                            <div>
                                <h3 className="text-xl font-bold text-teal-700">Add Leave Type</h3>
                                <p className="text-xs text-teal-600/70">Create a new leave type</p>
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
                                    placeholder="e.g., Annual Leave, Sick Leave, Maternity Leave"
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
                                    placeholder="Describe this leave type..."
                                    disabled={submitting}
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Maximum Days Per Year <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    value={formData.maximum_days_per_year}
                                    onChange={(e) => setFormData({ ...formData, maximum_days_per_year: parseInt(e.target.value) || 0 })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white/80 backdrop-blur-sm"
                                    placeholder="e.g., 21"
                                    required
                                    disabled={submitting}
                                />
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="can_carry_forward"
                                    checked={formData.can_carry_forward}
                                    onChange={(e) => setFormData({ ...formData, can_carry_forward: e.target.checked })}
                                    className="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                                    disabled={submitting}
                                />
                                <label htmlFor="can_carry_forward" className="text-sm font-medium text-gray-700">
                                    Can Carry Forward
                                </label>
                            </div>

                            {formData.can_carry_forward && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Maximum Carry Forward Days
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={formData.max_carry_forward_days || ''}
                                        onChange={(e) => setFormData({ ...formData, max_carry_forward_days: e.target.value ? parseInt(e.target.value) : null })}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white/80 backdrop-blur-sm"
                                        placeholder="e.g., 5"
                                        disabled={submitting}
                                    />
                                </div>
                            )}

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
                                    {submitting ? 'Creating...' : 'Create Leave Type'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}




