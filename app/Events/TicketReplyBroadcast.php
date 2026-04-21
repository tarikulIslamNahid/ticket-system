<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\TicketReply;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketReplyBroadcast implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly TicketReply $reply,
        public readonly string $publicToken,
        public readonly string $customerName,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel("ticket.{$this->publicToken}")];
    }

    public function broadcastAs(): string
    {
        return 'reply.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'reply' => [
                'id' => $this->reply->id,
                'sender_type' => $this->reply->sender_type,
                'message' => $this->reply->message,
                'author_name' => $this->reply->sender_type === TicketReply::SENDER_ADMIN
                    ? ($this->reply->user?->name ?? 'Support')
                    : $this->customerName,
                'created_at' => $this->reply->created_at?->toISOString(),
            ],
        ];
    }
}
