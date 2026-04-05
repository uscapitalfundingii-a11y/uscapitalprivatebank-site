<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SupportTicketAiResponder
{
    public function enabled(): bool
    {
        return config('openai.support_ticket.enabled') && filled(config('openai.api_key'));
    }

    public function autoReply(SupportTicket $ticket, SupportMessage $incomingMessage): ?SupportMessage
    {
        if (!$this->enabled() || !$this->schemaReady()) {
            return null;
        }

        if ($incomingMessage->is_ai_response || (int) $incomingMessage->admin_id !== 0) {
            return null;
        }

        $existingReply = SupportMessage::query()
            ->where('support_ticket_id', $ticket->id)
            ->where('is_ai_response', 1)
            ->where('ai_reply_to_message_id', $incomingMessage->id)
            ->first();

        if ($existingReply) {
            return $existingReply;
        }

        $replyText = $this->requestReplyText($ticket, $incomingMessage)
            ?: $this->fallbackAutoReply($ticket, $incomingMessage);

        $reply = new SupportMessage();
        $reply->support_ticket_id = $ticket->id;
        $reply->admin_id = 0;
        $reply->message = $replyText;
        $reply->is_ai_response = 1;
        $reply->ai_reply_to_message_id = $incomingMessage->id;
        $reply->ai_model = config('openai.support_ticket.model');
        $reply->save();

        $ticket->status = Status::TICKET_ANSWER;
        $ticket->last_reply = Carbon::now();
        $ticket->save();

        $recipient = $ticket->user_id ? $ticket->user : $ticket;
        $sendVia = $ticket->user_id ? null : ['email'];
        $createLog = (bool) $ticket->user_id;

        notify($recipient, 'ADMIN_SUPPORT_REPLY', [
            'ticket_id' => $ticket->ticket,
            'ticket_subject' => $ticket->subject,
            'reply' => $replyText,
            'link' => route('ticket.view', $ticket->ticket),
        ], $sendVia, $createLog);

        return $reply;
    }

    public function draftForAdmin(SupportTicket $ticket): ?string
    {
        $latestCustomerMessage = SupportMessage::query()
            ->where('support_ticket_id', $ticket->id)
            ->where('admin_id', 0)
            ->orderByDesc('id')
            ->first();

        if (!$latestCustomerMessage) {
            throw new \RuntimeException('There is no customer message available to draft a reply from.');
        }

        if (!$this->enabled()) {
            return $this->fallbackAdminDraft($ticket, $latestCustomerMessage);
        }

        $response = Http::baseUrl(rtrim(config('openai.base_url'), '/'))
            ->acceptJson()
            ->withToken(config('openai.api_key'))
            ->timeout(config('openai.support_ticket.timeout'))
            ->post('/responses', [
                'model' => config('openai.support_ticket.model'),
                'instructions' => $this->adminDraftInstructions(),
                'input' => $this->buildAdminDraftInput($ticket),
                'max_output_tokens' => config('openai.support_ticket.max_output_tokens'),
                'metadata' => [
                    'feature' => 'support_ticket_admin_draft',
                    'ticket_id' => (string) $ticket->ticket,
                    'support_ticket_id' => (string) $ticket->id,
                ],
            ]);

        if (!$response->successful()) {
            report(new \RuntimeException($this->apiErrorMessage($response->json(), 'AI draft could not be generated right now.')));
            return $this->fallbackAdminDraft($ticket, $latestCustomerMessage);
        }

        return $this->extractText($response->json()) ?: $this->fallbackAdminDraft($ticket, $latestCustomerMessage);
    }

    public function polishAdminDraft(SupportTicket $ticket, string $draft): string
    {
        $draft = trim($draft);

        if ($draft === '') {
            throw new \RuntimeException('Please enter or dictate a reply before asking AI to revise it.');
        }

        if (!$this->enabled()) {
            return $this->fallbackPolishDraft($ticket, $draft);
        }

        $response = Http::baseUrl(rtrim(config('openai.base_url'), '/'))
            ->acceptJson()
            ->withToken(config('openai.api_key'))
            ->timeout(config('openai.support_ticket.timeout'))
            ->post('/responses', [
                'model' => config('openai.support_ticket.model'),
                'instructions' => $this->adminPolishInstructions(),
                'input' => [[
                    'role' => 'user',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => trim(implode("\n", [
                            'Ticket subject: ' . $ticket->subject,
                            'Customer name: ' . $ticket->name,
                            'Customer email: ' . $ticket->email,
                            'Draft reply to revise:',
                            $draft,
                        ])),
                    ]],
                ]],
                'max_output_tokens' => config('openai.support_ticket.max_output_tokens'),
                'metadata' => [
                    'feature' => 'support_ticket_admin_polish',
                    'ticket_id' => (string) $ticket->ticket,
                    'support_ticket_id' => (string) $ticket->id,
                ],
            ]);

        if (!$response->successful()) {
            report(new \RuntimeException($this->apiErrorMessage($response->json(), 'AI revision could not be generated right now.')));
            return $this->fallbackPolishDraft($ticket, $draft);
        }

        return $this->extractText($response->json()) ?: $this->fallbackPolishDraft($ticket, $draft);
    }

    protected function schemaReady(): bool
    {
        return Schema::hasTable('support_messages')
            && Schema::hasColumn('support_messages', 'is_ai_response')
            && Schema::hasColumn('support_messages', 'ai_reply_to_message_id')
            && Schema::hasColumn('support_messages', 'ai_model');
    }

    protected function requestReplyText(SupportTicket $ticket, SupportMessage $incomingMessage): ?string
    {
        $response = Http::baseUrl(rtrim(config('openai.base_url'), '/'))
            ->acceptJson()
            ->withToken(config('openai.api_key'))
            ->timeout(config('openai.support_ticket.timeout'))
            ->post('/responses', [
                'model' => config('openai.support_ticket.model'),
                'instructions' => config('openai.support_ticket.system_prompt'),
                'input' => $this->buildInput($ticket, $incomingMessage),
                'max_output_tokens' => config('openai.support_ticket.max_output_tokens'),
                'metadata' => [
                    'feature' => 'support_ticket_auto_reply',
                    'ticket_id' => (string) $ticket->ticket,
                    'support_ticket_id' => (string) $ticket->id,
                ],
            ]);

        if (!$response->successful()) {
            report(new \RuntimeException('OpenAI support ticket reply failed: ' . $response->body()));
            return null;
        }

        return $this->extractText($response->json());
    }

    protected function buildInput(SupportTicket $ticket, SupportMessage $incomingMessage): array
    {
        $customerType = $ticket->user_id ? 'registered user' : 'contact form visitor';

        return [[
            'role' => 'user',
            'content' => [[
                'type' => 'input_text',
                'text' => trim(implode("\n", [
                    'Ticket subject: ' . $ticket->subject,
                    'Ticket number: ' . $ticket->ticket,
                    'Customer type: ' . $customerType,
                    'Customer name: ' . $ticket->name,
                    'Customer email: ' . $ticket->email,
                    'Customer message:',
                    $incomingMessage->message,
                ])),
            ]],
        ]];
    }

    protected function buildAdminDraftInput(SupportTicket $ticket): array
    {
        $messages = SupportMessage::query()
            ->where('support_ticket_id', $ticket->id)
            ->with('admin')
            ->orderBy('id')
            ->limit(12)
            ->get();

        $history = $messages->map(function ($message) {
            if ($message->is_ai_response) {
                $author = 'AI assistant';
            } elseif ((int) $message->admin_id === 0) {
                $author = 'Customer';
            } else {
                $author = 'Admin ' . ($message->admin?->name ?: 'staff');
            }

            return $author . ': ' . trim($message->message);
        })->implode("\n");

        return [[
            'role' => 'user',
            'content' => [[
                'type' => 'input_text',
                'text' => trim(implode("\n", [
                    'Draft a reply for this support ticket.',
                    'Ticket subject: ' . $ticket->subject,
                    'Ticket number: ' . $ticket->ticket,
                    'Customer name: ' . $ticket->name,
                    'Customer email: ' . $ticket->email,
                    'Conversation:',
                    $history,
                ])),
            ]],
        ]];
    }

    protected function adminDraftInstructions(): string
    {
        return 'You are drafting a reply for a bank support agent. ' .
            'Write a professional, concise customer-service response that the admin can edit before sending. ' .
            'Do not claim any action has been completed unless the ticket explicitly confirms it. ' .
            'Do not mention being an AI. ' .
            'Do not ask for passwords, PINs, full card numbers, SSNs, or full account numbers. ' .
            'If the ticket asks for unsupported hardware, account opening, KYC review, fraud review, wire edits, or specialist help, ' .
            'say the matter will be reviewed by the appropriate team. ' .
            'Keep the draft under 180 words.';
    }

    protected function adminPolishInstructions(): string
    {
        return 'You are revising a support agent draft for a bank ticket reply. ' .
            'Rewrite the text to sound professional, clear, calm, and customer-friendly while keeping the meaning intact. ' .
            'Do not mention being an AI. ' .
            'Do not add promises of completed actions unless they are already stated in the draft. ' .
            'Do not ask for passwords, PINs, full card numbers, SSNs, or full account numbers. ' .
            'Return only the revised reply text, ready to send.';
    }

    protected function extractText(array $payload): ?string
    {
        $chunks = [];

        foreach (($payload['output'] ?? []) as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach (($item['content'] ?? []) as $content) {
                if (($content['type'] ?? null) !== 'output_text') {
                    continue;
                }

                $text = trim((string) ($content['text'] ?? ''));

                if ($text !== '') {
                    $chunks[] = $text;
                }
            }
        }

        $reply = trim(implode("\n\n", $chunks));

        return $reply !== '' ? $reply : null;
    }

    protected function apiErrorMessage(array $payload, string $fallback): string
    {
        $message = trim((string) data_get($payload, 'error.message', ''));
        $code = trim((string) data_get($payload, 'error.code', ''));

        if ($code === 'insufficient_quota') {
            return 'OpenAI billing quota has been exceeded for the configured API key.';
        }

        return $message !== '' ? $message : $fallback;
    }

    protected function fallbackAdminDraft(SupportTicket $ticket, SupportMessage $latestCustomerMessage): string
    {
        $summary = $this->summarizeCustomerNeed($ticket, $latestCustomerMessage);
        $requestMoreInfo = $this->needsMoreInformation($latestCustomerMessage->message);

        $lines = [
            'Hello ' . ($ticket->name ?: 'there') . ',',
            '',
            'Thank you for contacting ' . gs('site_name') . '. We have reviewed your message regarding ' . $summary . '.',
        ];

        if ($requestMoreInfo) {
            $lines[] = 'To help you properly, please reply with any additional details you can share about the request, including the exact service or device you need and how you plan to use it.';
        } else {
            $lines[] = 'Your request has been noted and will be reviewed by the appropriate team. We will follow up with the next steps and any requirements as soon as possible.';
        }

        $lines[] = '';
        $lines[] = 'Best regards,';
        $lines[] = gs('site_name') . ' Support';

        return implode("\n", $lines);
    }

    protected function fallbackAutoReply(SupportTicket $ticket, SupportMessage $incomingMessage): string
    {
        $summary = $this->summarizeCustomerNeed($ticket, $incomingMessage);

        return implode("\n", [
            'Hello ' . ($ticket->name ?: 'there') . ',',
            '',
            'Thank you for contacting ' . gs('site_name') . '. We received your message regarding ' . $summary . '.',
            'A member of our support team will review it and follow up with you as soon as possible.',
            '',
            'Best regards,',
            gs('site_name') . ' Support',
        ]);
    }

    protected function fallbackPolishDraft(SupportTicket $ticket, string $draft): string
    {
        $draft = preg_replace('/\s+/', ' ', trim($draft));

        if (!str_starts_with(strtolower($draft), 'hello') && !str_starts_with(strtolower($draft), 'hi ')) {
            $draft = 'Hello ' . ($ticket->name ?: 'there') . ",\n\n" . ucfirst($draft);
        }

        if (!str_contains(strtolower($draft), 'best regards') && !str_contains(strtolower($draft), 'sincerely')) {
            $draft .= "\n\nBest regards,\n" . gs('site_name') . ' Support';
        }

        return $draft;
    }

    protected function summarizeCustomerNeed(SupportTicket $ticket, SupportMessage $message): string
    {
        $text = strtolower(trim($ticket->subject . ' ' . $message->message));

        $map = [
            'device' => 'your device request',
            'protocol' => 'your protocol/device request',
            'pos' => 'your POS device request',
            'account' => 'your account request',
            'kyc' => 'your KYC request',
            'transfer' => 'your transfer request',
            'wire' => 'your wire transfer request',
            'card' => 'your card-related request',
            'login' => 'your login issue',
            'verification' => 'your verification request',
        ];

        foreach ($map as $needle => $summary) {
            if (str_contains($text, $needle)) {
                return $summary;
            }
        }

        return 'your recent support request';
    }

    protected function needsMoreInformation(string $message): bool
    {
        return str_word_count(trim($message)) < 18;
    }
}
