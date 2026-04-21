<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ticket_id',
    'user_id',
    'sender_type',
    'message',
    'email_sent',
    'email_sent_at',
])]
class TicketReply extends Model
{
    public const string SENDER_ADMIN = 'admin';
    public const string SENDER_CUSTOMER = 'customer';

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_sent' => 'boolean',
            'email_sent_at' => 'datetime',
        ];
    }
}
