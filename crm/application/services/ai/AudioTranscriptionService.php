<?php

namespace app\services\ai;

use RuntimeException;

defined('BASEPATH') or exit('No direct script access allowed');

class AudioTranscriptionService
{
    public function isConfigured(): bool
    {
        return trim((string) $this->configValue('APP_AI_TRANSCRIPTION_URL', '')) !== '';
    }

    public function transcribe(string $filePath, string $originalName, string $mimeType = ''): string
    {
        $url = trim((string) $this->configValue('APP_AI_TRANSCRIPTION_URL', ''));

        if ($url === '') {
            throw new RuntimeException('Audio transcription is not configured.');
        }

        if (! is_file($filePath)) {
            throw new RuntimeException('Audio file not found for transcription.');
        }

        $curlFile = curl_file_create(
            $filePath,
            $mimeType !== '' ? $mimeType : 'audio/webm',
            $originalName !== '' ? $originalName : basename($filePath)
        );

        $headers = ['Accept: application/json'];
        $apiKey = trim((string) $this->configValue('APP_AI_TRANSCRIPTION_API_KEY', ''));
        $authHeader = (string) $this->configValue('APP_AI_TRANSCRIPTION_AUTH_HEADER', 'Authorization');
        $authPrefix = trim((string) $this->configValue('APP_AI_TRANSCRIPTION_AUTH_PREFIX', 'Bearer'));

        if ($apiKey !== '') {
            $headers[] = $authHeader . ': ' . trim($authPrefix . ' ' . $apiKey);
        }

        $payload = [
            'file' => $curlFile,
            'model' => (string) $this->configValue('APP_AI_TRANSCRIPTION_MODEL', 'whisper-1'),
        ];

        $language = trim((string) $this->configValue('APP_AI_TRANSCRIPTION_LANGUAGE', ''));
        if ($language !== '') {
            $payload['language'] = $language;
        }

        $prompt = trim((string) $this->configValue('APP_AI_TRANSCRIPTION_PROMPT', ''));
        if ($prompt !== '') {
            $payload['prompt'] = $prompt;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Unable to start audio transcription.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $payload,
        ]);

        $body = curl_exec($ch);
        $curlError = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException($curlError !== '' ? $curlError : 'Audio transcription request failed.');
        }

        if ($statusCode >= 400) {
            throw new RuntimeException($this->extractErrorMessage($body));
        }

        return $this->extractTranscript($body);
    }

    private function extractTranscript(string $body): string
    {
        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            $text = trim($body);
            if ($text === '') {
                throw new RuntimeException('Transcription service returned an empty response.');
            }

            return $text;
        }

        foreach ([['text'], ['transcript'], ['message'], ['data', 'text'], ['data', 'transcript']] as $path) {
            $value = $this->arrayGet($decoded, $path);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        throw new RuntimeException('Transcription service returned JSON, but no transcript text was found.');
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

        return trim($body) !== '' ? trim($body) : 'Transcription service returned an error.';
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
