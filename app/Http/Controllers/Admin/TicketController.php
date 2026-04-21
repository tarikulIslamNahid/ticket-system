<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Events\TicketTypingBroadcast;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReplyTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'status' => $request->string('status')->toString() ?: null,
            'search' => $request->string('search')->toString() ?: null,
        ];

        $tickets = $this->ticketService->listTickets($filters);

        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => TicketResource::collection($tickets),
            'filters' => [
                'status' => $filters['status'] ?? '',
                'search' => $filters['search'] ?? '',
            ],
            'statusOptions' => [
                ['value' => '', 'label' => 'All statuses'],
                ['value' => Ticket::STATUS_OPEN, 'label' => 'Open'],
                ['value' => Ticket::STATUS_CLOSED, 'label' => 'Closed'],
            ],
        ]);
    }

    public function show(Ticket $ticket): Response
    {
        $ticket->load([
            'replies' => function ($query) {
                $query->orderBy('created_at')->with('user:id,name');
            },
        ]);

        return Inertia::render('Admin/Tickets/Show', [
            'ticket' => new TicketResource($ticket),
        ]);
    }

    public function reply(ReplyTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->ticketService->replyAsAdmin(
            $ticket,
            $request->user(),
            $request->validated('message'),
        );

        return redirect()
            ->route('admin.tickets.show', $ticket)
            ->with('success', 'Reply sent to customer.');
    }

    public function typing(Ticket $ticket): JsonResponse
    {
        TicketTypingBroadcast::dispatch($ticket->public_token, 'admin');

        return response()->json(['ok' => true]);
    }
}
