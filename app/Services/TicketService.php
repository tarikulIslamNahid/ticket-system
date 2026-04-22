<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\TicketReplyBroadcast;
use App\Events\TicketStatusBroadcast;
use App\Mail\TicketCreatedMail;
use App\Mail\TicketReplyMail;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

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

        $ticket = DB::transaction(function () use ($data, $source, $analysis): Ticket {
            return Ticket::create([
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
        });

        $this->sendCreatedNotification($ticket);

        return $ticket;
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

        $this->sendReplyNotification($ticket, $reply);

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

    /**
     * Aggregated counts for the admin dashboard.
     *
     * @return array{
     *     total: int,
     *     open: int,
     *     closed: int,
     *     last_24h: int,
     *     by_source: array<string, int>,
     *     by_category: array<string, int>
     * }
     */
    public function getDashboardStats(): array
    {
        $total = Ticket::count();
        $open = Ticket::where('status', Ticket::STATUS_OPEN)->count();
        $closed = Ticket::where('status', Ticket::STATUS_CLOSED)->count();
        $last24h = Ticket::where('created_at', '>=', Carbon::now()->subHours(24))->count();

        $bySource = Ticket::query()
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->map(fn ($c): int => (int) $c)
            ->toArray();

        $byCategory = Ticket::query()
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->map(fn ($c): int => (int) $c)
            ->toArray();

        return [
            'total' => $total,
            'open' => $open,
            'closed' => $closed,
            'last_24h' => $last24h,
            'by_source' => $bySource,
            'by_category' => $byCategory,
        ];
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getRecentTickets(int $limit = 5): Collection
    {
        return Ticket::query()
            ->withCount('replies')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function changeStatus(Ticket $ticket, string $status): Ticket
    {
        $ticket->update([
            'status' => $status,
            'closed_at' => $status === Ticket::STATUS_CLOSED ? Carbon::now() : null,
        ]);

        $ticket = $ticket->refresh();

        TicketStatusBroadcast::dispatch(
            $ticket->public_token,
            $ticket->status,
            $ticket->closed_at?->toISOString(),
        );

        return $ticket;
    }

    /**
     * Queue the "ticket received" email for the customer.
     *
     * The Mailable implements ShouldQueue, so this returns immediately after
     * dispatching the job. notification_email_sent_at records when we queued
     * the mail — actual delivery happens on the queue worker.
     * Queue-dispatch failures are logged; SMTP-level failures during worker
     * execution land in the failed_jobs table with Laravel's auto-retry.
     */
    private function sendCreatedNotification(Ticket $ticket): void
    {
        if (! $ticket->email) {
            return;
        }

        try {
            Mail::to($ticket->email)->send(new TicketCreatedMail($ticket));

            $ticket->forceFill(['notification_email_sent_at' => Carbon::now()])->save();
        } catch (Throwable $e) {
            Log::warning('Failed to queue ticket-created email', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Queue the admin-replied email for the customer.
     */
    private function sendReplyNotification(Ticket $ticket, TicketReply $reply): void
    {
        if (! $ticket->email) {
            return;
        }

        try {
            Mail::to($ticket->email)->send(new TicketReplyMail($ticket, $reply));

            $reply->forceFill([
                'email_sent' => true,
                'email_sent_at' => Carbon::now(),
            ])->save();
        } catch (Throwable $e) {
            Log::warning('Failed to queue ticket-reply email', [
                'ticket_id' => $ticket->id,
                'reply_id' => $reply->id,
                'error' => $e->getMessage(),
            ]);
        }
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
