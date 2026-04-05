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

        $replyText = $this->requestReplyText($ticket, $incomingMessage);

        if (!$replyText) {
            return null;
        }

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
        if (!$this->enabled()) {
            return null;
        }

        $latestCustomerMessage = SupportMessage::query()
            ->where('support_ticket_id', $ticket->id)
            ->where('admin_id', 0)
            ->orderByDesc('id')
            ->first();

        if (!$latestCustomerMessage) {
            return null;
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
            report(new \RuntimeException('OpenAI support ticket draft failed: ' . $response->body()));
            return null;
        }

        return $this->extractText($response->json());
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
}
