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
