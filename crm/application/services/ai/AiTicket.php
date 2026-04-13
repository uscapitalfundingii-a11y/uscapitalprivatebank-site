<?php

namespace app\services\ai;

use app\services\ai\Contracts\AiProviderInterface;
use app\services\ai\Data\Ticket;

class AiTicket implements Contracts\AiTicketInterface
{
    public function __construct(private readonly AiProviderInterface $provider)
    {
    }

    public function summarizeTicket(Ticket $ticket): string
    {
        $prompt = "Summarize the following support ticket thread and respond with only the summary of the conversation messages/replies in a well-structured non styled HTML paragraphs. Ensure the summary is concise, ensure to clearly present the information:\n\n" . $ticket->asAIText();

        return $this->sendPrompt(
            hooks()->apply_filters('before_ai_tickets_summarize_ticket', $prompt, $ticket),
            'summary',
            $ticket
        );
    }

    public function suggestTicketReply(Ticket $ticket): string
    {
        $prompt = "Provide a well-crafted, professional response to this support ticket thread in clear and concise language. Return only the reply message in HTML format, compatible with TinyMCE 6, and exclude any salutation or closing statements:\n\n";
        $prompt .= $ticket->asAIText();

        return $this->sendPrompt(
            hooks()->apply_filters('before_ai_tickets_suggest_ticket_reply', $prompt, $ticket),
            'response',
            $ticket
        );
    }

    public function suggestTicketFollowUp(Ticket $ticket): string
    {
        $prompt = "Write a concise, professional follow-up for a client who has not responded to this ticket. Keep it polite, action-oriented, and ready to send in HTML format compatible with TinyMCE 6. Exclude any salutation or closing statements:\n\n";
        $prompt .= $ticket->asAIText();

        return $this->sendPrompt(
            hooks()->apply_filters('before_ai_tickets_suggest_ticket_followup', $prompt, $ticket),
            'followup',
            $ticket
        );
    }

    private function sendPrompt(string $prompt, string $capability, Ticket $ticket): string
    {
        if (method_exists($this->provider, 'chatForCapability')) {
            return $this->provider->chatForCapability($prompt, $capability, [
                'ticket' => [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                ],
            ]);
        }

        return $this->provider->chat($prompt);
    }
}
