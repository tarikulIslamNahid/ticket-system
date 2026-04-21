<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;

/**
 * Stub implementation. Will be replaced with OpenAI-backed classifier
 * in a follow-up commit. Keeps the TicketService contract stable.
 */
class AiCategorizationService
{
    /**
     * @return array{category: string, suggested_reply: ?string}
     */
    public function analyze(string $message, ?string $subject = null): array
    {
        $combined = strtolower(trim(($subject ?? '').' '.$message));

        $category = match (true) {
            str_contains($combined, 'invoice')
                || str_contains($combined, 'payment')
                || str_contains($combined, 'refund')
                || str_contains($combined, 'billing')
                || str_contains($combined, 'charge')
                => Ticket::CATEGORY_BILLING,
            str_contains($combined, 'bug')
                || str_contains($combined, 'error')
                || str_contains($combined, 'issue')
                || str_contains($combined, 'problem')
                || str_contains($combined, 'help')
                || str_contains($combined, 'support')
                => Ticket::CATEGORY_SUPPORT,
            default => Ticket::CATEGORY_OTHER,
        };

        return [
            'category' => $category,
            'suggested_reply' => null,
        ];
    }
}
