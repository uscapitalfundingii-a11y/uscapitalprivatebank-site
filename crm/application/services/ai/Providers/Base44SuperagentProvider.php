<?php

namespace app\services\ai\Providers;

use app\services\ai\Contracts\AiProviderInterface;
use RuntimeException;

defined('BASEPATH') or exit('No direct script access allowed');

class Base44SuperagentProvider implements AiProviderInterface
{
    public function getName(): string
    {
        return 'Base44 Super Agent';
    }

    public static function getModels(): array
    {
        return ['base44-superagent' => 'Base44 Super Agent'];
    }

    public function chat($prompt): string
    {
        return $this->chatForCapability((string) $prompt, 'response');
    }

    public function enhanceText(string $text, string $type): string
    {
        return $this->chatForCapability(
            "Improve the following text with a {$type} tone. Return only HTML content that is ready to insert into TinyMCE.\n\n{$text}",
            'response',
            ['enhancement_type' => $type]
        );
    }

    public function chatForCapability(string $prompt, string $capability, array $context = []): string
    {
        $request = $this->resolveRequest($capability);
        $payload = array_filter([
            'message'    => $prompt,
            'prompt'     => $prompt,
            'input'      => $prompt,
            'capability' => $capability,
            'agent'      => $request['agent'],
            'context'    => $context,
        ], static fn ($value) => $value !== null && $value !== []);

        $ch = curl_init($request['url']);

        if ($ch === false) {
            throw new RuntimeException('Unable to start the Base44 Super Agent request.');
        }

        $headers = ['Content-Type: application/json', 'Accept: application/json'];

        if ($request['api_key'] !== '') {
            $headers[] = $request['auth_header'] . ': ' . trim($request['auth_prefix'] . ' ' . $request['api_key']);
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        $body = curl_exec($ch);
        $curlError = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException($curlError !== '' ? $curlError : 'Base44 Super Agent request failed.');
        }

        if ($statusCode >= 400) {
            throw new RuntimeException($this->extractErrorMessage($body));
        }

        return $this->extractMessage($body);
    }

    private function resolveRequest(string $capability): array
    {
        $url = trim((string) $this->configValue('APP_BASE44_SUPERAGENT_' . strtoupper($capability) . '_URL', ''));
        $agent = trim((string) $this->configValue('APP_BASE44_SUPERAGENT_' . strtoupper($capability) . '_AGENT', ''));

        if ($url === '') {
            $baseUrl = rtrim((string) $this->configValue('APP_BASE44_SUPERAGENT_BASE_URL', ''), '/');
            $template = (string) $this->configValue('APP_BASE44_SUPERAGENT_ENDPOINT_TEMPLATE', '');

            if ($baseUrl !== '' && $template !== '' && $agent !== '') {
                $path = sprintf($template, rawurlencode($agent));
                $url = $baseUrl . '/' . ltrim($path, '/');
            }
        }

        if ($url === '') {
            throw new RuntimeException(
                'Base44 Super Agent is not configured. Define APP_BASE44_SUPERAGENT_' . strtoupper($capability) .
                '_URL or APP_BASE44_SUPERAGENT_BASE_URL, APP_BASE44_SUPERAGENT_ENDPOINT_TEMPLATE, and the matching agent constant.'
            );
        }

        return [
            'url'         => $url,
            'agent'       => $agent !== '' ? $agent : null,
            'api_key'     => trim((string) $this->configValue('APP_BASE44_SUPERAGENT_API_KEY', '')),
            'auth_header' => (string) $this->configValue('APP_BASE44_SUPERAGENT_AUTH_HEADER', 'Authorization'),
            'auth_prefix' => (string) $this->configValue('APP_BASE44_SUPERAGENT_AUTH_PREFIX', 'Bearer'),
        ];
    }

    private function extractMessage(string $body): string
    {
        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            $message = trim($body);
            if ($message === '') {
                throw new RuntimeException('Base44 Super Agent returned an empty response.');
            }

            return $message;
        }

        $paths = [
            ['message'],
            ['response'],
            ['reply'],
            ['content'],
            ['text'],
            ['output'],
            ['data', 'message'],
            ['data', 'response'],
            ['data', 'reply'],
            ['data', 'content'],
            ['data', 'text'],
            ['result', 'message'],
            ['result', 'response'],
            ['result', 'reply'],
            ['result', 'content'],
            ['choices', 0, 'message', 'content'],
            ['choices', 0, 'text'],
        ];

        foreach ($paths as $path) {
            $value = $this->arrayGet($decoded, $path);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        throw new RuntimeException('Base44 Super Agent returned JSON, but no response text field was found.');
    }

    private function extractErrorMessage(string $body): string
    {
        $decoded = json_decode($body, true);

        if (is_array($decoded)) {
            foreach ([['error'], ['message'], ['data', 'error'], ['data', 'message']] as $path) {
                $value = $this->arrayGet($decoded, $path);
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }
        }

        return trim($body) !== '' ? trim($body) : 'Base44 Super Agent returned an error.';
    }

    private function arrayGet(array $source, array $path): mixed
    {
        $value = $source;

        foreach ($path as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    private function configValue(string $name, mixed $default = null): mixed
    {
        if (defined($name)) {
            return constant($name);
        }

        $value = getenv($name);

        return $value !== false ? $value : $default;
    }
}
