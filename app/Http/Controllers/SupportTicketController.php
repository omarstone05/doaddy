<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $currentOrgId = session('current_organization_id') ?? Auth::user()->current_organization_id;

        $tickets = SupportTicket::query()
            ->where('organization_id', $currentOrgId)
            ->where('user_id', Auth::id())
            ->with(['assignedTo', 'messages' => function($q) {
                $q->where('is_internal_note', false)->latest()->limit(1);
            }])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->priority, function ($query, $priority) {
                $query->where('priority', $priority);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                      ->orWhere('ticket_number', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy($request->sort ?? 'created_at', $request->direction ?? 'desc')
            ->paginate(15);

        return Inertia::render('Support/Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['status', 'priority', 'search', 'sort', 'direction']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Support/Tickets/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|min:10',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'required|in:technical,billing,feature_request,bug,other',
        ]);

        $currentOrgId = session('current_organization_id') ?? Auth::user()->current_organization_id;

        $ticket = SupportTicket::create([
            'id' => (string) Str::uuid(),
            'ticket_number' => 'TKT-' . strtoupper(uniqid()),
            'organization_id' => $currentOrgId,
            'user_id' => Auth::id(),
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'category' => $validated['category'],
            'status' => 'open',
        ]);

        return redirect()->route('support.tickets.show', $ticket->id)
            ->with('success', 'Ticket created successfully. Ticket number: ' . $ticket->ticket_number);
    }

    public function show(SupportTicket $ticket)
    {
        $currentOrgId = session('current_organization_id') ?? Auth::user()->current_organization_id;

        // Ensure user can only view their own tickets
        if ($ticket->user_id !== Auth::id() || $ticket->organization_id !== $currentOrgId) {
            abort(403, 'Unauthorized access to this ticket');
        }

        $ticket->load([
            'user',
            'organization',
            'assignedTo',
            'messages.user' => function($q) {
                $q->select('id', 'name', 'email');
            },
        ]);

        // Mark messages as read (if needed)
        $ticket->messages()
            ->where('user_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return Inertia::render('Support/Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function addMessage(Request $request, SupportTicket $ticket)
    {
        $currentOrgId = session('current_organization_id') ?? Auth::user()->current_organization_id;

        // Ensure user can only add messages to their own tickets
        if ($ticket->user_id !== Auth::id() || $ticket->organization_id !== $currentOrgId) {
            abort(403, 'Unauthorized access to this ticket');
        }

        $validated = $request->validate([
            'message' => 'required|string|min:1',
        ]);

        $message = SupportTicketMessage::create([
            'id' => (string) Str::uuid(),
            'support_ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'is_internal_note' => false,
        ]);

        // Update ticket status if it was waiting for customer
        if ($ticket->status === 'waiting_customer') {
            $ticket->update(['status' => 'open']);
        }

        return back()->with('success', 'Message added successfully');
    }
}

