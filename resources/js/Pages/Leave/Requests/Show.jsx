import { Head, Link, router, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, CheckCircle, XCircle, Calendar, User, Clock } from 'lucide-react';

export default function LeaveRequestsShow({ leaveRequest }) {
    const { data, setData, post, processing } = useForm({
        comments: '',
    });

    const handleApprove = () => {
        if (confirm('Are you sure you want to approve this leave request?')) {
            post(`/leave/requests/${leaveRequest.id}/approve`);
        }
    };

    const handleReject = () => {
        if (confirm('Are you sure you want to reject this leave request?')) {
            post(`/leave/requests/${leaveRequest.id}/reject`);
        }
    };

    const getStatusBadge = (status) => {
        const badges = {
            pending: 'bg-yellow-100 text-yellow-700',
            approved: 'bg-green-100 text-green-700',
            rejected: 'bg-red-100 text-red-700',
            cancelled: 'bg-gray-100 text-gray-700',
        };
        return badges[status] || 'bg-gray-100 text-gray-700';
    };

    return (
        <SectionLayout sectionName="People">
            <Head title="Leave Request Details" />
            <div className="max-w-4xl mx-auto">
                <Link href="/leave/requests">
                    <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Leave Requests
                    </button>
                </Link>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="flex items-center justify-between mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Leave Request Details</h1>
                        <span className={`px-3 py-1 text-sm font-medium rounded-full ${getStatusBadge(leaveRequest.status)}`}>
                            {leaveRequest.status.charAt(0).toUpperCase() + leaveRequest.status.slice(1)}
                        </span>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <User className="h-4 w-4" />
                                <span className="text-sm font-medium">Team Member</span>
                            </div>
                            <p className="text-gray-900 font-medium">
                                {leaveRequest.team_member?.first_name} {leaveRequest.team_member?.last_name}
                            </p>
                        </div>

                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <span className="text-sm font-medium">Leave Type</span>
                            </div>
                            <p className="text-gray-900 font-medium">{leaveRequest.leave_type?.name}</p>
                        </div>

                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <Calendar className="h-4 w-4" />
                                <span className="text-sm font-medium">Start Date</span>
                            </div>
                            <p className="text-gray-900 font-medium">
                                {new Date(leaveRequest.start_date).toLocaleDateString()}
                            </p>
                        </div>

                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <Calendar className="h-4 w-4" />
                                <span className="text-sm font-medium">End Date</span>
                            </div>
                            <p className="text-gray-900 font-medium">
                                {new Date(leaveRequest.end_date).toLocaleDateString()}
                            </p>
                        </div>

                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <Clock className="h-4 w-4" />
                                <span className="text-sm font-medium">Number of Days</span>
                            </div>
                            <p className="text-gray-900 font-medium">{leaveRequest.number_of_days} days</p>
                        </div>

                        {leaveRequest.approved_by && (
                            <div>
                                <div className="flex items-center gap-2 text-gray-600 mb-2">
                                    <span className="text-sm font-medium">Approved By</span>
                                </div>
                                <p className="text-gray-900 font-medium">
                                    {leaveRequest.approved_by?.name}
                                    {leaveRequest.approved_at && (
                                        <span className="text-sm text-gray-500 ml-2">
                                            ({new Date(leaveRequest.approved_at).toLocaleDateString()})
                                        </span>
                                    )}
                                </p>
                            </div>
                        )}
                    </div>

                    {leaveRequest.reason && (
                        <div className="mb-6">
                            <div className="text-sm font-medium text-gray-700 mb-2">Reason</div>
                            <p className="text-gray-900">{leaveRequest.reason}</p>
                        </div>
                    )}

                    {leaveRequest.comments && (
                        <div className="mb-6">
                            <div className="text-sm font-medium text-gray-700 mb-2">Comments</div>
                            <p className="text-gray-900">{leaveRequest.comments}</p>
                        </div>
                    )}

                    {leaveRequest.status === 'pending' && (
                        <div className="border-t border-gray-200 pt-6">
                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2">Comments</label>
                                <textarea
                                    value={data.comments}
                                    onChange={(e) => setData('comments', e.target.value)}
                                    rows={3}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="Add comments..."
                                />
                            </div>
                            <div className="flex gap-3">
                                <Button
                                    onClick={handleApprove}
                                    disabled={processing}
                                    className="bg-green-500 hover:bg-green-600 text-white"
                                >
                                    <CheckCircle className="h-4 w-4 mr-2" />
                                    Approve
                                </Button>
                                <Button
                                    onClick={handleReject}
                                    disabled={processing}
                                    variant="secondary"
                                    className="bg-red-500 hover:bg-red-600 text-white"
                                >
                                    <XCircle className="h-4 w-4 mr-2" />
                                    Reject
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </SectionLayout>
    );
}

