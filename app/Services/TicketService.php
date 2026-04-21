<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
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
     * Record an admin reply to a ticket.
     */
    public function replyAsAdmin(Ticket $ticket, User $admin, string $message): TicketReply
    {
        return TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'sender_type' => TicketReply::SENDER_ADMIN,
            'message' => $message,
        ]);
    }

    /**
     * Record a customer reply via the public ticket page.
     */
    public function replyAsCustomer(Ticket $ticket, string $message): TicketReply
    {
        return TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => null,
            'sender_type' => TicketReply::SENDER_CUSTOMER,
            'message' => $message,
        ]);
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
