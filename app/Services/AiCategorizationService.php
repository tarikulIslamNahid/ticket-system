<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AiCategorizationService
{
    /** @var list<string> */
    private const array VALID_CATEGORIES = [
        Ticket::CATEGORY_SUPPORT,
        Ticket::CATEGORY_BILLING,
        Ticket::CATEGORY_OTHER,
    ];

    /**
     * Classify a ticket message and generate a suggested reply.
     *
     * Uses Google Gemini (via the Generative Language API) when configured.
     * Falls back to a keyword classifier when the API is unavailable so
     * ticket creation never blocks.
     *
     * @return array{category: string, suggested_reply: ?string}
     */
    public function analyze(string $message, ?string $subject = null): array
    {
        $apiKey = config('services.gemini.key');

        if (! is_string($apiKey) || $apiKey === '') {
            return $this->keywordFallback($message, $subject);
        }

        try {
            return $this->callGemini($message, $subject, $apiKey);
        } catch (Throwable $e) {
            Log::warning('Gemini categorization failed, falling back to keywords', [
                'error' => $e->getMessage(),
            ]);

            return $this->keywordFallback($message, $subject);
        }
    }

    /**
     * @return array{category: string, suggested_reply: ?string}
     */
    private function callGemini(string $message, ?string $subject, string $apiKey): array
    {
        $model = config('services.gemini.model');
        $endpoint = rtrim((string) config('services.gemini.endpoint'), '/');
        $timeout = (int) config('services.gemini.timeout', 10);

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->post("{$endpoint}/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [[
                    'parts' => [['text' => $this->buildPrompt($message, $subject)]],
                ]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'category' => [
                                'type' => 'STRING',
                                'enum' => self::VALID_CATEGORIES,
                            ],
                            'suggested_reply' => [
                                'type' => 'STRING',
                            ],
                        ],
                        'required' => ['category', 'suggested_reply'],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Gemini API HTTP {$response->status()}: ".substr((string) $response->body(), 0, 300)
            );
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            throw new RuntimeException('Gemini returned no usable text');
        }

        $parsed = json_decode($text, true);

        if (! is_array($parsed) || ! isset($parsed['category'])) {
            throw new RuntimeException('Gemini returned invalid JSON');
        }

        $category = in_array($parsed['category'], self::VALID_CATEGORIES, true)
            ? (string) $parsed['category']
            : Ticket::CATEGORY_OTHER;

        $suggestedReply = null;
        if (isset($parsed['suggested_reply']) && is_string($parsed['suggested_reply'])) {
            $trimmed = trim($parsed['suggested_reply']);
            $suggestedReply = $trimmed !== '' ? $trimmed : null;
        }

        return [
            'category' => $category,
            'suggested_reply' => $suggestedReply,
        ];
    }

    private function buildPrompt(string $message, ?string $subject): string
    {
        $subjectLine = $subject !== null && $subject !== ''
            ? "Subject: {$subject}\n"
            : '';

        return <<<PROMPT
You are a customer support triage assistant. Analyze the ticket below and respond with a JSON object.

1. category — exactly one of:
   - "support": technical issues, bugs, errors, login problems, product usage questions
   - "billing": invoices, payments, refunds, subscriptions, pricing questions
   - "other": anything that does not fit the above

2. suggested_reply — a polite 2-3 sentence draft reply that acknowledges the issue, thanks the customer, and sets expectations (e.g., "our team will investigate"). Do not promise timelines or resolutions.

{$subjectLine}Message: {$message}
PROMPT;
    }

    /**
     * @return array{category: string, suggested_reply: ?string}
     */
    private function keywordFallback(string $message, ?string $subject): array
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
