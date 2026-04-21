<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<TicketReply>
 */
class TicketReplyFactory extends Factory
{
    protected $model = TicketReply::class;

    /** @var list<string> */
    private const array ADMIN_MESSAGES = [
        'Thanks for reaching out. We are looking into this now and will get back to you shortly.',
        'Got it — our team has received your report and is investigating. We will update you once we have more details.',
        'Apologies for the trouble. Could you confirm which browser and version you are using? That will help us reproduce it.',
        'We have escalated this to engineering. You should see a fix in the next release.',
        'Thanks for the patience. We have just pushed a fix — could you try again and let us know?',
    ];

    /** @var list<string> */
    private const array CUSTOMER_MESSAGES = [
        'Thanks for the quick response! I will give it another try now.',
        'Still seeing the same issue after retrying.',
        'I am using Chrome 124 on Windows 11.',
        'That worked — thanks for the help!',
        'Appreciate the update. Any ETA on when this will be resolved?',
    ];

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $senderType = $this->faker->randomElement([
            TicketReply::SENDER_ADMIN,
            TicketReply::SENDER_CUSTOMER,
        ]);

        $createdAt = Carbon::now()->subMinutes($this->faker->numberBetween(1, 60));

        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => $senderType === TicketReply::SENDER_ADMIN
                ? User::query()->value('id')
                : null,
            'sender_type' => $senderType,
            'message' => $senderType === TicketReply::SENDER_ADMIN
                ? $this->faker->randomElement(self::ADMIN_MESSAGES)
                : $this->faker->randomElement(self::CUSTOMER_MESSAGES),
            'email_sent' => $senderType === TicketReply::SENDER_ADMIN,
            'email_sent_at' => $senderType === TicketReply::SENDER_ADMIN ? $createdAt : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    public function fromAdmin(?User $admin = null): static
    {
        return $this->state(fn (): array => [
            'sender_type' => TicketReply::SENDER_ADMIN,
            'user_id' => $admin?->id ?? User::query()->value('id'),
            'message' => $this->faker->randomElement(self::ADMIN_MESSAGES),
            'email_sent' => true,
            'email_sent_at' => Carbon::now(),
        ]);
    }

    public function fromCustomer(): static
    {
        return $this->state(fn (): array => [
            'sender_type' => TicketReply::SENDER_CUSTOMER,
            'user_id' => null,
            'message' => $this->faker->randomElement(self::CUSTOMER_MESSAGES),
            'email_sent' => false,
            'email_sent_at' => null,
        ]);
    }
}
