<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\TicketTypingBroadcast;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerReplyRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\TicketReplyResource;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    public function start(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->createTicket(
            $request->validated(),
            Ticket::SOURCE_CHAT,
        );

        return response()->json([
            'ticket' => (new TicketResource($ticket))->resolve(),
        ], 201);
    }

    public function show(string $token): JsonResponse
    {
        $ticket = $this->findTicket($token);

        $ticket->load([
            'replies' => function ($query) {
                $query->orderBy('created_at')->with('user:id,name');
            },
        ]);

        return response()->json([
            'ticket' => (new TicketResource($ticket))->resolve(),
        ]);
    }

    public function reply(CustomerReplyRequest $request, string $token): JsonResponse
    {
        $ticket = $this->findTicket($token);

        if ($ticket->status === Ticket::STATUS_CLOSED) {
            return response()->json([
                'message' => 'This ticket is closed.',
            ], 422);
        }

        $reply = $this->ticketService->replyAsCustomer(
            $ticket,
            $request->validated('message'),
        );

        return response()->json([
            'reply' => (new TicketReplyResource($reply))->resolve(),
        ], 201);
    }

    public function typing(string $token): JsonResponse
    {
        $ticket = $this->findTicket($token);

        TicketTypingBroadcast::dispatch($ticket->public_token, 'customer');

        return response()->json(['ok' => true]);
    }

    private function findTicket(string $token): Ticket
    {
        return Ticket::where('public_token', $token)->firstOrFail();
    }
}
