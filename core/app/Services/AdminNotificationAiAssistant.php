<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AdminNotificationAiAssistant
{
    public function enabled(): bool
    {
        return config('openai.support_ticket.enabled') && filled(config('openai.api_key'));
    }

    public function polishDraft(string $message, ?string $subject = null, string $via = 'email'): string
    {
        $message = trim($message);
        $subject = trim((string) $subject);
        $via = trim($via) ?: 'email';

        if ($message === '') {
            throw new \RuntimeException('Please enter or dictate a notification before asking AI to revise it.');
        }

        if (!$this->enabled()) {
            return $this->fallbackPolishDraft($message, $subject, $via);
        }

        $response = Http::baseUrl(rtrim(config('openai.base_url'), '/'))
            ->acceptJson()
            ->withToken(config('openai.api_key'))
            ->timeout(config('openai.support_ticket.timeout'))
            ->post('/responses', [
                'model' => config('openai.support_ticket.model'),
                'instructions' => $this->polishInstructions(),
                'input' => [[
                    'role' => 'user',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => trim(implode("\n", array_filter([
                            'Notification channel: ' . strtoupper($via),
                            $subject !== '' ? 'Notification subject: ' . $subject : null,
                            'Draft notification to revise:',
                            $message,
                        ]))),
                    ]],
                ]],
                'max_output_tokens' => config('openai.support_ticket.max_output_tokens'),
                'metadata' => [
                    'feature' => 'admin_notification_polish',
                    'via' => $via,
                ],
            ]);

        if (!$response->successful()) {
            report(new \RuntimeException($this->apiErrorMessage($response->json(), 'AI revision could not be generated right now.')));
            return $this->fallbackPolishDraft($message, $subject, $via);
        }

        return $this->extractText($response->json()) ?: $this->fallbackPolishDraft($message, $subject, $via);
    }

    protected function polishInstructions(): string
    {
        return 'You are revising an outbound bank notification draft for an admin user. ' .
            'Rewrite the draft so it sounds professional, clear, organized, and customer-friendly. ' .
            'Preserve paragraph breaks, bullet structure, greeting/signoff structure, and important punctuation. ' .
            'Do not mention being an AI. ' .
            'Do not add promises of completed actions unless they are already stated in the draft. ' .
            'Do not ask for passwords, PINs, full card numbers, SSNs, or full account numbers. ' .
            'If the channel is SMS or push, keep the wording concise while preserving the meaning. ' .
            'Return only the revised notification text, ready to send.';
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

    protected function fallbackPolishDraft(string $message, ?string $subject = null, string $via = 'email'): string
    {
        $message = preg_replace("/\r\n?/", "\n", trim($message));
        $paragraphs = array_values(array_filter(array_map('trim', preg_split("/\n{2,}/", $message))));

        if (empty($paragraphs)) {
            return $message;
        }

        $paragraphs = array_map(function ($paragraph) {
            return preg_replace('/[ \t]+/', ' ', $paragraph);
        }, $paragraphs);

        if (in_array($via, ['sms', 'push'], true)) {
            return implode("\n\n", $paragraphs);
        }

        $firstParagraph = $paragraphs[0];
        $firstParagraphLower = strtolower($firstParagraph);

        if (!str_starts_with($firstParagraphLower, 'hello') && !str_starts_with($firstParagraphLower, 'dear') && !str_starts_with($firstParagraphLower, 'hi ')) {
            array_unshift($paragraphs, 'Hello,');
        }

        $combined = implode("\n\n", $paragraphs);

        if (!preg_match('/best regards|kind regards|sincerely/i', $combined)) {
            $combined .= "\n\nBest regards,\n" . gs('site_name');
        }

        return $combined;
    }
}
