<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LLMService
{
    protected $config;

    /** Maximum retry attempts for transient failures */
    protected int $maxRetries = 2;

    /** Delay between retries in milliseconds */
    protected int $retryDelayMs = 500;

    public function __construct(array $config = [])
    {
        $this->config = array_merge(config('services.llm', []), $config);
    }

    /**
     * Check whether the LLM service is available and enabled.
     */
    public function isEnabled(): bool
    {
        return !empty($this->config['enabled']);
    }

    /**
     * Generate one or more completions from the configured LLM provider.
     * Returns an array of cleaned strings (one per completion).
     *
     * Features:
     *  - Retries transient failures (5xx, timeouts) up to $maxRetries times
     *  - Validates and sanitises every completion before returning
     *  - Returns [] on permanent failure so callers can use their fallback
     */
    public function generate(string $prompt, array $options = []): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $provider = $this->config['provider'] ?? 'openai';
        $n = $options['n'] ?? 3;
        $temperature = $options['temperature'] ?? $this->config['temperature'] ?? 0.6;
        $max_tokens = $options['max_tokens'] ?? $this->config['max_tokens'] ?? 512;

        if ($provider === 'openai') {
            return $this->callOpenAI($prompt, $options, $n, $temperature, $max_tokens);
        }

        Log::warning('LLMService: unknown provider ' . $provider);
        return [];
    }

    /**
     * OpenAI-specific call with retry logic.
     */
    protected function callOpenAI(string $prompt, array $options, int $n, float $temperature, int $max_tokens): array
    {
        $key = $this->config['key'] ?? env('LLM_API_KEY');
        if (empty($key)) {
            Log::warning('LLMService: openai key not configured');
            return [];
        }

        $base = rtrim($this->config['base'] ?? env('LLM_API_BASE', ''), '/');
        $url = $base ? $base . '/v1/chat/completions' : 'https://api.openai.com/v1/chat/completions';

        $payload = [
            'model' => $this->config['model'] ?? env('LLM_MODEL', 'gpt-4o-mini'),
            'messages' => [
                ['role' => 'system', 'content' => $options['system'] ?? "You are a helpful, concise engineering assistant that produces technical, actionable task descriptions, acceptance criteria, and implementation notes."],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => (float) $temperature,
            'n' => (int) $n,
            'max_tokens' => (int) $max_tokens,
        ];

        // ── Retry loop for transient failures ──
        $lastError = null;
        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                if ($attempt > 0) {
                    usleep($this->retryDelayMs * 1000 * $attempt); // exponential-ish back-off
                    Log::info("LLMService: retry attempt {$attempt}");
                }

                $resp = Http::timeout(30)->withToken($key)->post($url, $payload);

                // Permanent client errors (4xx) — no point retrying
                if ($resp->clientError()) {
                    Log::error('LLMService: client error (not retryable)', [
                        'status' => $resp->status(),
                        'body' => mb_substr($resp->body(), 0, 500),
                    ]);
                    return [];
                }

                // Server errors (5xx) — retry
                if ($resp->serverError()) {
                    $lastError = 'HTTP ' . $resp->status();
                    Log::warning("LLMService: server error {$resp->status()}, will retry", [
                        'attempt' => $attempt,
                    ]);
                    continue;
                }

                // Success path
                $body = $resp->json();
                return $this->extractAndClean($body);

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastError = $e->getMessage();
                Log::warning("LLMService: connection error, will retry", [
                    'attempt' => $attempt,
                    'error' => $lastError,
                ]);
                continue;
            } catch (\Exception $e) {
                Log::error('LLMService: unexpected exception', ['exception' => $e]);
                return [];
            }
        }

        Log::error('LLMService: all retries exhausted', ['last_error' => $lastError]);
        return [];
    }

    /**
     * Extract completions from the API response and sanitise each one.
     */
    protected function extractAndClean(array $body): array
    {
        $out = [];
        if (!isset($body['choices']) || !is_array($body['choices'])) {
            Log::warning('LLMService: response missing choices', ['keys' => array_keys($body)]);
            return [];
        }

        foreach ($body['choices'] as $choice) {
            $raw = $choice['message']['content'] ?? $choice['text'] ?? null;
            if ($raw === null) {
                continue;
            }
            $cleaned = $this->sanitiseOutput($raw);
            if ($cleaned !== '') {
                $out[] = $cleaned;
            }
        }

        return $out;
    }

    /**
     * Clean & validate a single completion string.
     *  - Trims whitespace
     *  - Strips null bytes and control chars (except newlines/tabs)
     *  - Rejects empty or suspiciously short outputs
     */
    protected function sanitiseOutput(string $raw): string
    {
        // Remove null bytes and non-printable control characters (keep \n \r \t)
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $raw);
        $clean = trim($clean);

        // Reject if only whitespace/punctuation remains
        if ($clean === '' || mb_strlen(preg_replace('/[\s\p{P}]/u', '', $clean)) < 3) {
            return '';
        }

        return $clean;
    }
}
