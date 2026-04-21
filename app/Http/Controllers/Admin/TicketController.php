<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
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
}
