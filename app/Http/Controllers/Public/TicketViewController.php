<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Events\TicketTypingBroadcast;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerReplyRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TicketViewController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    public function show(string $token): Response
    {
        $ticket = $this->findTicketByToken($token);

        $ticket->load([
            'replies' => function ($query) {
                $query->orderBy('created_at')->with('user:id,name');
            },
        ]);

        return Inertia::render('Public/TicketView', [
            'ticket' => new TicketResource($ticket),
        ]);
    }

    public function reply(CustomerReplyRequest $request, string $token): RedirectResponse
    {
        $ticket = $this->findTicketByToken($token);

        $this->ticketService->replyAsCustomer(
            $ticket,
            $request->validated('message'),
        );

        return redirect()
            ->route('ticket.view', $ticket->public_token)
            ->with('success', 'Your reply has been posted.');
    }

    public function typing(string $token): JsonResponse
    {
        $ticket = $this->findTicketByToken($token);

        TicketTypingBroadcast::dispatch($ticket->public_token, 'customer');

        return response()->json(['ok' => true]);
    }

    private function findTicketByToken(string $token): Ticket
    {
        return Ticket::where('public_token', $token)->firstOrFail();
    }
}
