<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TicketReply */
class TicketReplyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender_type' => $this->sender_type,
            'message' => $this->message,
            'author_name' => $this->sender_type === TicketReply::SENDER_ADMIN
                ? $this->whenLoaded('user', fn () => $this->user?->name, 'Support')
                : $this->ticket?->name,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
