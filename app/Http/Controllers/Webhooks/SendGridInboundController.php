<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SendGridInboundController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    /**
     * Handle the multipart/form-data payload that SendGrid's Inbound
     * Parse sends when an email arrives at a configured address.
     *
     * @see https://www.twilio.com/docs/sendgrid/for-developers/parsing-email/setting-up-the-inbound-parse-webhook
     */
    public function handle(Request $request, string $secret): JsonResponse
    {
        $expected = (string) config('services.sendgrid.inbound_secret');

        if ($expected === '' || ! hash_equals($expected, $secret)) {
            return response()->json(['error' => 'Invalid secret'], Response::HTTP_FORBIDDEN);
        }

        $from = (string) $request->input('from', '');
        $subject = trim((string) $request->input('subject', ''));
        $bodyText = trim((string) $request->input('text', ''));
        $bodyHtml = (string) $request->input('html', '');

        [$senderName, $senderEmail] = $this->parseFromField($from);

        if ($senderEmail === '') {
            Log::warning('SendGrid inbound webhook: missing sender email', [
                'from' => $from,
            ]);

            return response()->json(['error' => 'Missing sender email'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $message = $bodyText !== '' ? $bodyText : $this->stripTags($bodyHtml);

        if ($message === '') {
            return response()->json(['error' => 'Empty message'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ticket = $this->ticketService->createTicket([
            'name' => $senderName !== '' ? $senderName : $senderEmail,
            'email' => $senderEmail,
            'phone' => null,
            'subject' => $subject !== '' ? $subject : null,
            'message' => $message,
        ], Ticket::SOURCE_EMAIL);

        return response()->json([
            'ticket_number' => $ticket->ticket_number,
        ], Response::HTTP_CREATED);
    }

    /**
     * Parse sender field like "Jane Doe <jane@example.com>" or bare
     * "jane@example.com" into [name, email].
     *
     * @return array{0: string, 1: string}
     */
    private function parseFromField(string $from): array
    {
        $from = trim($from);

        if ($from === '') {
            return ['', ''];
        }

        if (preg_match('/^(.*?)\s*<([^>]+)>\s*$/', $from, $matches) === 1) {
            $name = trim($matches[1], " \t\n\r\0\x0B\"'");
            $email = trim($matches[2]);

            return [$name, filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : ''];
        }

        return ['', filter_var($from, FILTER_VALIDATE_EMAIL) ? $from : ''];
    }

    private function stripTags(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }
}
