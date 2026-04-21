<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\TicketReplyBroadcast;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketService
{
    public function __construct(
        private readonly AiCategorizationService $ai,
    ) {}

    /**
     * Create a ticket from any source (form/chat/email).
     *
     * @param  array{name: string, email?: ?string, phone?: ?string, subject?: ?string, message: string}  $data
     */
    public function createTicket(array $data, string $source): Ticket
    {
        $analysis = $this->ai->analyze($data['message'], $data['subject'] ?? null);

        return DB::transaction(function () use ($data, $source, $analysis): Ticket {
            $ticket = Ticket::create([
                'ticket_number' => $this->generateTicketNumber(),
                'public_token' => $this->generatePublicToken(),
                'source' => $source,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'subject' => $data['subject'] ?? null,
                'message' => $data['message'],
                'category' => $analysis['category'],
                'status' => Ticket::STATUS_OPEN,
                'ai_suggested_reply' => $analysis['suggested_reply'],
            ]);

            return $ticket;
        });
    }

    /**
     * Record an admin reply to a ticket and broadcast it to the ticket channel.
     */
    public function replyAsAdmin(Ticket $ticket, User $admin, string $message): TicketReply
    {
        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'sender_type' => TicketReply::SENDER_ADMIN,
            'message' => $message,
        ]);

        $reply->setRelation('user', $admin);

        TicketReplyBroadcast::dispatch($reply, $ticket->public_token, $ticket->name);

        return $reply;
    }

    /**
     * Record a customer reply via the public ticket page and broadcast it.
     */
    public function replyAsCustomer(Ticket $ticket, string $message): TicketReply
    {
        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => null,
            'sender_type' => TicketReply::SENDER_CUSTOMER,
            'message' => $message,
        ]);

        TicketReplyBroadcast::dispatch($reply, $ticket->public_token, $ticket->name);

        return $reply;
    }

    /**
     * Paginated list of tickets for the admin panel with optional filters.
     *
     * @param  array{status?: ?string, search?: ?string}  $filters
     * @return LengthAwarePaginator<Ticket>
     */
    public function listTickets(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Ticket::query()
            ->when(
                ! empty($filters['status']),
                fn ($query) => $query->where('status', $filters['status'])
            )
            ->when(
                ! empty($filters['search']),
                fn ($query) => $query->where(function ($q) use ($filters): void {
                    $term = '%'.$filters['search'].'%';
                    $q->where('ticket_number', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('subject', 'like', $term);
                })
            )
            ->withCount('replies')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function changeStatus(Ticket $ticket, string $status): Ticket
    {
        $ticket->update([
            'status' => $status,
            'closed_at' => $status === Ticket::STATUS_CLOSED ? Carbon::now() : null,
        ]);

        return $ticket->refresh();
    }

    private function generateTicketNumber(): string
    {
        $nextId = (int) (Ticket::max('id') ?? 0) + 1;

        return 'TKT-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    private function generatePublicToken(): string
    {
        do {
            $token = Str::random(64);
        } while (Ticket::where('public_token', $token)->exists());

        return $token;
    }
}
