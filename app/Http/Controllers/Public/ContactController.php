<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Public/Contact');
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $this->ticketService->createTicket(
            $request->validated(),
            Ticket::SOURCE_FORM,
        );

        return redirect()
            ->route('ticket.view', $ticket->public_token)
            ->with('success', "Your ticket {$ticket->ticket_number} has been created.");
    }
}
