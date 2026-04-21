<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ticket_number',
    'public_token',
    'source',
    'name',
    'email',
    'phone',
    'subject',
    'message',
    'category',
    'status',
    'ai_suggested_reply',
    'notification_email_sent_at',
    'closed_at',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    public const string SOURCE_EMAIL = 'email';
    public const string SOURCE_CHAT = 'chat';
    public const string SOURCE_FORM = 'form';

    public const string CATEGORY_SUPPORT = 'support';
    public const string CATEGORY_BILLING = 'billing';
    public const string CATEGORY_OTHER = 'other';

    public const string STATUS_OPEN = 'open';
    public const string STATUS_CLOSED = 'closed';

    /** @return HasMany<TicketReply, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'notification_email_sent_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
