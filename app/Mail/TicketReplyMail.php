<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketReplyMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketReply $reply,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New reply on ticket {$this->ticket->ticket_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-reply',
            with: [
                'ticket' => $this->ticket,
                'reply' => $this->reply,
                'authorName' => $this->reply->user?->name ?? 'Support',
                'viewUrl' => route('ticket.view', $this->ticket->public_token),
            ],
        );
    }
}
