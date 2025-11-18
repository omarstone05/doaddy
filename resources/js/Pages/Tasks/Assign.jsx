import { Head, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Badge } from '@/Components/ui/Badge';
import { 
    CheckSquare, 
    UserPlus, 
    Users, 
    X,
    Save,
    ArrowLeft,
} from 'lucide-react';
import { useState } from 'react';
import axios from 'axios';

export default function TaskAssign({ task, users, assignedUsers = [] }) {
    const [selectedUsers, setSelectedUsers] = useState(
        assignedUsers.map(au => ({
            user_id: au.id,
            name: au.name,
            email: au.email,
            can_edit: au.pivot?.can_edit ?? true,
            can_delete: au.pivot?.can_delete ?? false,
            can_assign: au.pivot?.can_assign ?? false,
            can_view_time: au.pivot?.can_view_time ?? true,
            can_manage_subtasks: au.pivot?.can_manage_subtasks ?? false,
            can_change_status: au.pivot?.can_change_status ?? true,
            can_change_priority: au.pivot?.can_change_priority ?? false,
        }))
    );
    const [loading, setLoading] = useState(false);
    const [showAddUser, setShowAddUser] = useState(false);
    const [selectedUserId, setSelectedUserId] = useState('');

    const availableUsers = users.filter(
        user => !selectedUsers.some(su => su.user_id === user.id)
    );

    const addUser = () => {
        if (!selectedUserId) return;

        const user = users.find(u => u.id === selectedUserId);
        if (!user) return;

        setSelectedUsers([
            ...selectedUsers,
            {
                user_id: user.id,
                name: user.name,
                email: user.email,
                can_edit: true,
                can_delete: false,
                can_assign: false,
                can_view_time: true,
                can_manage_subtasks: false,
                can_change_status: true,
                can_change_priority: false,
            },
        ]);

        setSelectedUserId('');
        setShowAddUser(false);
    };

    const removeUser = (userId) => {
        setSelectedUsers(selectedUsers.filter(su => su.user_id !== userId));
    };

    const updatePrivilege = (userId, privilege, value) => {
        setSelectedUsers(
            selectedUsers.map(su =>
                su.user_id === userId ? { ...su, [privilege]: value } : su
            )
        );
    };

    const handleSubmit = async () => {
        setLoading(true);
        try {
            const usersData = selectedUsers.map(su => ({
                user_id: su.user_id,
                can_edit: su.can_edit,
                can_delete: su.can_delete,
                can_assign: su.can_assign,
                can_view_time: su.can_view_time,
                can_manage_subtasks: su.can_manage_subtasks,
                can_change_status: su.can_change_status,
                can_change_priority: su.can_change_priority,
            }));

            await axios.post(
                `/api/projects/${task.project_id}/tasks/${task.id}/assign-users`,
                { users: usersData }
            );

            router.visit(`/projects/${task.project_id}/tasks/${task.id}`);
        } catch (error) {
            console.error('Error assigning users:', error);
            alert('Failed to assign users. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    const PrivilegeCheckbox = ({ userId, privilege, label, description, value, onChange }) => (
        <div className="flex items-start gap-3">
            <input
                type="checkbox"
                id={`${userId}-${privilege}`}
                checked={value}
                onChange={(e) => onChange(userId, privilege, e.target.checked)}
                className="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <div className="flex-1">
                <label
                    htmlFor={`${userId}-${privilege}`}
                    className="text-sm font-medium text-gray-900 cursor-pointer"
                >
                    {label}
                </label>
                {description && (
                    <p className="text-xs text-gray-500 mt-0.5">{description}</p>
                )}
            </div>
        </div>
    );

    return (
        <SectionLayout sectionName="Decisions">
            <Head title={`Assign Users - ${task.title}`} />
            
            <div className="mb-6">
                <Button
                    variant="secondary"
                    onClick={() => router.visit(`/projects/${task.project_id}/tasks/${task.id}`)}
                >
                    <ArrowLeft className="h-4 w-4 mr-2" />
                    Back to Task
                </Button>
            </div>

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <CheckSquare className="h-5 w-5" />
                        Assign Users to Task
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="mb-4">
                        <h3 className="text-lg font-semibold text-gray-900 mb-2">{task.title}</h3>
                        <p className="text-sm text-gray-600">
                            Assign one or more users to this task and set their privileges.
                        </p>
                    </div>

                    {/* Add User Section */}
                    {showAddUser && availableUsers.length > 0 && (
                        <div className="mb-6 p-4 bg-gray-50 rounded-lg">
                            <div className="flex items-center gap-2 mb-3">
                                <UserPlus className="h-4 w-4 text-gray-600" />
                                <span className="font-medium text-gray-900">Add User</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <select
                                    value={selectedUserId}
                                    onChange={(e) => setSelectedUserId(e.target.value)}
                                    className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">Select a user...</option>
                                    {availableUsers.map((user) => (
                                        <option key={user.id} value={user.id}>
                                            {user.name} ({user.email})
                                        </option>
                                    ))}
                                </select>
                                <Button onClick={addUser} size="sm">
                                    Add
                                </Button>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    onClick={() => {
                                        setShowAddUser(false);
                                        setSelectedUserId('');
                                    }}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    )}

                    {!showAddUser && availableUsers.length > 0 && (
                        <div className="mb-6">
                            <Button
                                variant="secondary"
                                onClick={() => setShowAddUser(true)}
                            >
                                <UserPlus className="h-4 w-4 mr-2" />
                                Add User
                            </Button>
                        </div>
                    )}

                    {availableUsers.length === 0 && selectedUsers.length > 0 && (
                        <div className="mb-6 p-3 bg-blue-50 rounded-lg">
                            <p className="text-sm text-blue-800">
                                All available users have been assigned to this task.
                            </p>
                        </div>
                    )}

                    {/* Assigned Users List */}
                    {selectedUsers.length === 0 ? (
                        <div className="text-center py-8 text-gray-500">
                            <Users className="h-12 w-12 mx-auto mb-3 text-gray-400" />
                            <p>No users assigned yet. Click "Add User" to assign users to this task.</p>
                        </div>
                    ) : (
                        <div className="space-y-6">
                            {selectedUsers.map((user) => (
                                <Card key={user.user_id}>
                                    <CardContent className="pt-6">
                                        <div className="flex items-start justify-between mb-4">
                                            <div>
                                                <h4 className="font-semibold text-gray-900">{user.name}</h4>
                                                <p className="text-sm text-gray-500">{user.email}</p>
                                            </div>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => removeUser(user.user_id)}
                                            >
                                                <X className="h-4 w-4" />
                                            </Button>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <PrivilegeCheckbox
                                                userId={user.user_id}
                                                privilege="can_edit"
                                                label="Can Edit Task"
                                                description="User can edit task details"
                                                value={user.can_edit}
                                                onChange={updatePrivilege}
                                            />
                                            <PrivilegeCheckbox
                                                userId={user.user_id}
                                                privilege="can_delete"
                                                label="Can Delete Task"
                                                description="User can delete this task"
                                                value={user.can_delete}
                                                onChange={updatePrivilege}
                                            />
                                            <PrivilegeCheckbox
                                                userId={user.user_id}
                                                privilege="can_assign"
                                                label="Can Assign Users"
                                                description="User can assign other users to this task"
                                                value={user.can_assign}
                                                onChange={updatePrivilege}
                                            />
                                            <PrivilegeCheckbox
                                                userId={user.user_id}
                                                privilege="can_view_time"
                                                label="Can View Time"
                                                description="User can view time entries for this task"
                                                value={user.can_view_time}
                                                onChange={updatePrivilege}
                                            />
                                            <PrivilegeCheckbox
                                                userId={user.user_id}
                                                privilege="can_manage_subtasks"
                                                label="Can Manage Subtasks"
                                                description="User can create and manage subtasks"
                                                value={user.can_manage_subtasks}
                                                onChange={updatePrivilege}
                                            />
                                            <PrivilegeCheckbox
                                                userId={user.user_id}
                                                privilege="can_change_status"
                                                label="Can Change Status"
                                                description="User can change task status"
                                                value={user.can_change_status}
                                                onChange={updatePrivilege}
                                            />
                                            <PrivilegeCheckbox
                                                userId={user.user_id}
                                                privilege="can_change_priority"
                                                label="Can Change Priority"
                                                description="User can change task priority"
                                                value={user.can_change_priority}
                                                onChange={updatePrivilege}
                                            />
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}

                    {/* Submit Button */}
                    {selectedUsers.length > 0 && (
                        <div className="mt-6 flex justify-end gap-3">
                            <Button
                                variant="secondary"
                                onClick={() => router.visit(`/projects/${task.project_id}/tasks/${task.id}`)}
                            >
                                Cancel
                            </Button>
                            <Button onClick={handleSubmit} disabled={loading}>
                                <Save className="h-4 w-4 mr-2" />
                                {loading ? 'Saving...' : 'Save Assignments'}
                            </Button>
                        </div>
                    )}
                </CardContent>
            </Card>
        </SectionLayout>
    );
}

