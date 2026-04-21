<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /** @var list<string> */
    private const array SUBJECTS_SUPPORT = [
        'Cannot log in to my account',
        'Getting a 500 error on checkout',
        'Password reset link not arriving',
        'App freezes after latest update',
        'Two-factor code never arrives',
    ];

    /** @var list<string> */
    private const array SUBJECTS_BILLING = [
        'Charged twice for last month',
        'Invoice amount looks incorrect',
        'Please refund my annual plan',
        'Question about upgrade pricing',
        'Cannot update payment method',
    ];

    /** @var list<string> */
    private const array SUBJECTS_OTHER = [
        'Feature request — dark mode',
        'How do I export my data?',
        'Is there an API we can use?',
        'Business hours question',
        'Partnership inquiry',
    ];

    /** @var list<string> */
    private const array MESSAGES_SUPPORT = [
        'I have been trying to log in for the last hour but keep getting an authentication error. Can you help?',
        'Whenever I reach the checkout page I see a 500 error and cannot complete my purchase. Please look into this.',
        'I requested a password reset email 30 minutes ago but nothing has shown up in my inbox or spam folder.',
        'After updating the app this morning, it crashes every time I try to open it. Running on iOS 17.',
        'The 2FA code never reaches my phone. I have waited several minutes and tried twice already.',
    ];

    /** @var list<string> */
    private const array MESSAGES_BILLING = [
        'My credit card was charged twice for my subscription this month. Please refund the duplicate.',
        'The total on my latest invoice is higher than what I signed up for. Can you explain the extra charges?',
        'I would like to cancel and get a refund for my unused annual plan. I have not used it since upgrading.',
        'How much would it cost to upgrade from the Pro plan to the Business plan mid-cycle? Is it prorated?',
        'When I try to change my credit card I get an error that says "payment method invalid". What should I do?',
    ];

    /** @var list<string> */
    private const array MESSAGES_OTHER = [
        'Any chance you will add a dark theme soon? Using the app at night is rough on my eyes.',
        'I would like to export all my data as CSV. Is that possible through the dashboard?',
        'Do you offer an API? We would like to integrate your platform with our internal tools.',
        'Just a quick question — are you open this weekend? Your website does not show business hours.',
        'We are an agency exploring partnership opportunities. Who should we speak with about resellers?',
    ];

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $category = $this->faker->randomElement([
            Ticket::CATEGORY_SUPPORT,
            Ticket::CATEGORY_BILLING,
            Ticket::CATEGORY_OTHER,
        ]);

        $source = $this->faker->randomElement([
            Ticket::SOURCE_FORM,
            Ticket::SOURCE_CHAT,
        ]);

        [$subject, $message] = $this->subjectAndMessageForCategory($category);

        $createdAt = Carbon::now()->subMinutes($this->faker->numberBetween(5, 60 * 48));

        return [
            'ticket_number' => 'TKT-'.$this->faker->unique()->numberBetween(50000, 99999),
            'public_token' => Str::random(64),
            'source' => $source,
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->optional(0.3)->e164PhoneNumber(),
            'subject' => $subject,
            'message' => $message,
            'category' => $category,
            'status' => Ticket::STATUS_OPEN,
            'ai_suggested_reply' => $this->faker->optional(0.6)->randomElement([
                'Thank you for reaching out. Our team is looking into this and will follow up shortly.',
                'Thanks for the details. We are investigating the issue and will update you as soon as we have more information.',
                'Appreciate you letting us know. We will review your account and get back to you.',
            ]),
            'notification_email_sent_at' => $createdAt->copy()->addSeconds(3),
            'closed_at' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    public function form(): static
    {
        return $this->state(fn (): array => ['source' => Ticket::SOURCE_FORM]);
    }

    public function chat(): static
    {
        return $this->state(fn (): array => ['source' => Ticket::SOURCE_CHAT]);
    }

    public function closed(): static
    {
        return $this->state(function (array $attributes): array {
            $createdAt = $attributes['created_at'] ?? Carbon::now();

            return [
                'status' => Ticket::STATUS_CLOSED,
                'closed_at' => Carbon::parse($createdAt)->copy()->addHours(
                    $this->faker->numberBetween(1, 48)
                ),
            ];
        });
    }

    public function support(): static
    {
        return $this->state(fn (): array => $this->stateForCategory(Ticket::CATEGORY_SUPPORT));
    }

    public function billing(): static
    {
        return $this->state(fn (): array => $this->stateForCategory(Ticket::CATEGORY_BILLING));
    }

    public function other(): static
    {
        return $this->state(fn (): array => $this->stateForCategory(Ticket::CATEGORY_OTHER));
    }

    /** @return array<string, string> */
    private function stateForCategory(string $category): array
    {
        [$subject, $message] = $this->subjectAndMessageForCategory($category);

        return [
            'category' => $category,
            'subject' => $subject,
            'message' => $message,
        ];
    }

    /** @return array{0: string, 1: string} */
    private function subjectAndMessageForCategory(string $category): array
    {
        [$subjects, $messages] = match ($category) {
            Ticket::CATEGORY_SUPPORT => [self::SUBJECTS_SUPPORT, self::MESSAGES_SUPPORT],
            Ticket::CATEGORY_BILLING => [self::SUBJECTS_BILLING, self::MESSAGES_BILLING],
            default => [self::SUBJECTS_OTHER, self::MESSAGES_OTHER],
        };

        return [
            $this->faker->randomElement($subjects),
            $this->faker->randomElement($messages),
        ];
    }
}
