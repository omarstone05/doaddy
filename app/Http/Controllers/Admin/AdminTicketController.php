<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Services\Admin\EmailService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminTicketController extends Controller
{
    public function __construct(
        protected EmailService $emailService
    ) {}

    public function index(Request $request)
    {
        $tickets = SupportTicket::query()
            ->with(['user', 'organization', 'assignedTo'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->priority, function ($query, $priority) {
                $query->where('priority', $priority);
            })
            ->when($request->assigned_to, function ($query, $userId) {
                $query->where('assigned_to', $userId);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                      ->orWhere('ticket_number', 'like', "%{$search}%");
                });
            })
            ->orderBy($request->sort ?? 'created_at', $request->direction ?? 'desc')
            ->paginate(20);

        // Transform for frontend
        $tickets->getCollection()->transform(function ($ticket) {
            $ticket->assigned_to_user = $ticket->assignedTo;
            return $ticket;
        });

        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['status', 'priority', 'assigned_to', 'search', 'sort', 'direction']),
        ]);
    }

    public function create()
    {
        $users = \App\Models\User::with('organizations')->get();
        $organizations = \App\Models\Organization::all();

        return Inertia::render('Admin/Tickets/Create', [
            'users' => $users,
            'organizations' => $organizations,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|min:10',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'required|in:technical,billing,feature_request,bug,other',
            'user_id' => 'required|exists:users,id',
            'organization_id' => 'required|exists:organizations,id',
        ]);

        $ticket = SupportTicket::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'ticket_number' => 'TKT-' . strtoupper(uniqid()),
            'organization_id' => $validated['organization_id'],
            'user_id' => $validated['user_id'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'category' => $validated['category'],
            'status' => 'open',
            'assigned_to' => auth()->id(), // Auto-assign to admin who created it
        ]);

        return redirect()->route('admin.tickets.show', $ticket->id)
            ->with('success', 'Support ticket created successfully! Ticket number: ' . $ticket->ticket_number);
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load([
            'user',
            'organization',
            'assignedTo',
            'messages.user',
        ]);

        return Inertia::render('Admin/Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function assign(Request $request, SupportTicket $ticket)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $ticket->update(['assigned_to' => $request->assigned_to]);

        return back()->with('success', 'Ticket assigned successfully');
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,waiting_customer,resolved,closed',
        ]);

        $ticket->update(['status' => $request->status]);

        if ($request->status === 'resolved') {
            $ticket->markAsResolved();
        } elseif ($request->status === 'closed') {
            $ticket->close();
        }

        return back()->with('success', 'Ticket status updated');
    }

    public function addMessage(Request $request, SupportTicket $ticket)
    {
        $request->validate([
            'message' => 'required|string',
            'is_internal_note' => 'sometimes|boolean',
        ]);

        $message = SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $request->message,
            'is_internal_note' => $request->is_internal_note ?? false,
        ]);

        // Update ticket status if needed
        if (!$request->is_internal_note && $ticket->status === 'waiting_customer') {
            $ticket->update(['status' => 'in_progress']);
        }

        // Send email notification if not internal
        if (!$request->is_internal_note) {
            $this->emailService->sendTicketResponse($ticket, $request->message, auth()->user());
        }

        return back()->with('success', 'Message added successfully');
    }
}

