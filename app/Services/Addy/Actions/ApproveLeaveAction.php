<?php

namespace App\Services\Addy\Actions;

use App\Models\LeaveRequest;
use Illuminate\Support\Collection;

class ApproveLeaveAction extends BaseAction
{
    public function validate(): bool
    {
        return $this->getPendingRequests(1)->isNotEmpty();
    }

    public function preview(): array
    {
        $limit = (int) ($this->parameters['limit'] ?? 3);
        $requests = $this->getPendingRequests($limit);

        if ($requests->isEmpty()) {
            throw new \Exception('No pending leave requests found to approve.');
        }

        return [
            'title' => 'Approve Leave Request',
            'description' => $requests->count() > 1
                ? "Ready to approve {$requests->count()} pending leave requests."
                : 'Approve pending time-off request.',
            'items' => $requests->map(fn(LeaveRequest $request) => [
                'id' => $request->id,
                'team_member' => optional($request->teamMember)->full_name ?? 'Team member',
                'date_range' => $request->start_date->format('Y-m-d') . ' → ' . $request->end_date->format('Y-m-d'),
                'days' => $request->number_of_days,
                'type' => optional($request->leaveType)->name ?? 'Leave',
                'status' => $request->status,
            ])->toArray(),
            'impact' => 'medium',
            'warnings' => [],
        ];
    }

    public function execute(): array
    {
        $limit = (int) ($this->parameters['limit'] ?? 3);
        $requests = $this->getPendingRequests($limit);

        if ($requests->isEmpty()) {
            throw new \Exception('No pending leave requests to approve.');
        }

        $comments = $this->parameters['comments'] ?? 'Approved via Addy';

        foreach ($requests as $request) {
            $request->update([
                'status' => 'approved',
                'approved_by_id' => $this->user->id,
                'approved_at' => now(),
                'comments' => $comments,
            ]);
        }

        return [
            'success' => true,
            'message' => "Approved {$requests->count()} leave request(s).",
            'approved_count' => $requests->count(),
            'request_ids' => $requests->pluck('id'),
        ];
    }

    protected function getPendingRequests(int $limit = 3): Collection
    {
        $query = LeaveRequest::with(['teamMember', 'leaveType'])
            ->where('organization_id', $this->organization->id)
            ->where('status', 'pending');

        if (!empty($this->parameters['leave_request_id'])) {
            $query->where('id', $this->parameters['leave_request_id']);
        }

        if (!empty($this->parameters['team_member_name'])) {
            $name = trim($this->parameters['team_member_name']);
            $query->whereHas('teamMember', function ($q) use ($name) {
                $q->whereRaw("LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?", ['%' . strtolower($name) . '%']);
            });
        }

        if (!empty($this->parameters['starting_after'])) {
            $query->whereDate('start_date', '>=', $this->parameters['starting_after']);
        }

        return $query
            ->orderBy('start_date')
            ->limit(max(1, $limit))
            ->get();
    }
}
