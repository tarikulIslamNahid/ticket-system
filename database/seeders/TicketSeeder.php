<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->first();

        if (! $admin instanceof User) {
            $this->command?->warn('No admin user found. Run AdminUserSeeder first.');

            return;
        }

        // 3 brand-new open tickets with no replies (fresh inbox items)
        Ticket::factory()->count(3)->form()->support()->create();

        // 2 chat tickets with a single admin reply (awaiting customer)
        Ticket::factory()
            ->count(2)
            ->chat()
            ->billing()
            ->create()
            ->each(function (Ticket $ticket) use ($admin): void {
                TicketReply::factory()
                    ->fromAdmin($admin)
                    ->create([
                        'ticket_id' => $ticket->id,
                        'created_at' => $ticket->created_at->copy()->addMinutes(15),
                        'updated_at' => $ticket->created_at->copy()->addMinutes(15),
                    ]);
            });

        // 2 tickets with a full back-and-forth conversation (active)
        Ticket::factory()
            ->count(2)
            ->form()
            ->other()
            ->create()
            ->each(function (Ticket $ticket) use ($admin): void {
                $base = $ticket->created_at->copy();

                TicketReply::factory()->fromAdmin($admin)->create([
                    'ticket_id' => $ticket->id,
                    'created_at' => $base->copy()->addMinutes(10),
                    'updated_at' => $base->copy()->addMinutes(10),
                ]);

                TicketReply::factory()->fromCustomer()->create([
                    'ticket_id' => $ticket->id,
                    'created_at' => $base->copy()->addMinutes(25),
                    'updated_at' => $base->copy()->addMinutes(25),
                ]);

                TicketReply::factory()->fromAdmin($admin)->create([
                    'ticket_id' => $ticket->id,
                    'created_at' => $base->copy()->addMinutes(40),
                    'updated_at' => $base->copy()->addMinutes(40),
                ]);
            });

        // 2 closed tickets (resolved)
        Ticket::factory()
            ->count(2)
            ->closed()
            ->support()
            ->create()
            ->each(function (Ticket $ticket) use ($admin): void {
                $base = $ticket->created_at->copy();

                TicketReply::factory()->fromAdmin($admin)->create([
                    'ticket_id' => $ticket->id,
                    'created_at' => $base->copy()->addMinutes(20),
                    'updated_at' => $base->copy()->addMinutes(20),
                ]);

                TicketReply::factory()->fromCustomer()->create([
                    'ticket_id' => $ticket->id,
                    'message' => 'Perfect, that fixed it. Thanks!',
                    'created_at' => $base->copy()->addMinutes(35),
                    'updated_at' => $base->copy()->addMinutes(35),
                ]);
            });

        // 1 high-visibility featured ticket — most recent, with AI suggestion
        Ticket::factory()
            ->form()
            ->billing()
            ->create([
                'name' => 'Demo Customer',
                'email' => 'demo@example.com',
                'subject' => 'Please explain my latest invoice',
                'message' => 'Hi, I was charged twice for my subscription this month. Can you check and refund the duplicate?',
                'created_at' => Carbon::now()->subMinutes(5),
                'updated_at' => Carbon::now()->subMinutes(5),
                'ai_suggested_reply' => "Hi Demo Customer,\n\nThanks for flagging this. We've located the duplicate charge and our billing team is processing the refund right now — it should appear on your statement within 3–5 business days.\n\nLet us know if you have any other questions.",
            ]);

        $this->command?->info('Seeded 10 demo tickets across form/chat sources.');
    }
}
