<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\TicketResource;
use App\Services\TicketService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => $this->ticketService->getDashboardStats(),
            'recentTickets' => TicketResource::collection(
                $this->ticketService->getRecentTickets()
            ),
        ]);
    }
}
