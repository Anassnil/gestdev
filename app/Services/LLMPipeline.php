<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\AIRequest;

/**
 * Formal AI pipeline: pre-process → call → post-process → retry → output.
 *
 * Every AI interaction goes through this pipeline so that input cleaning,
 * output validation, and retry logic live in one place.
 */
class LLMPipeline
{
    protected LLMService $llm;

    /** Max pipeline-level retries when post-processing rejects the output */
    protected int $maxRetries = 2;

    /** Cache TTL in seconds (default 1 hour) */
    protected int $cacheTtl = 3600;

    public function __construct(LLMService $llm)
    {
        $this->llm = $llm;
    }

    /**
     * Proxy: check whether the underlying LLM service is enabled.
     */
    public function isLLMEnabled(): bool
    {
        return $this->llm->isEnabled();
    }

    // ─────────────────────────────────────────────
    //  PUBLIC API
    // ─────────────────────────────────────────────

    /**
     * Run a prompt through the full pipeline.
     *
     * @param  string   $userInput   Raw user input / prompt
     * @param  array    $options     LLM options (system, temperature, n, max_tokens …)
     * @param  array    $pipelineOpts Pipeline-specific options:
     *   - context      array   Extra context lines to prepend
     *   - validator     callable  fn(string $output): bool — return false to trigger retry
     *   - normalizer    callable  fn(string $output): string — transform valid output
     *   - format        string  Expected format: 'json' | 'plantuml' | 'text' (default 'text')
     *   - max_retries   int     Override default retry count
     * @return array  Array of cleaned, validated completion strings (may be empty)
     */
    public function run(string $userInput, array $options = [], array $pipelineOpts = []): array
    {
        $format   = $pipelineOpts['format'] ?? 'text';
        $retries  = $pipelineOpts['max_retries'] ?? $this->maxRetries;
        $context  = $pipelineOpts['context'] ?? [];
        $validator  = $pipelineOpts['validator'] ?? null;
        $normalizer = $pipelineOpts['normalizer'] ?? null;
        $noCache    = $pipelineOpts['no_cache'] ?? false;
        $type       = $pipelineOpts['type'] ?? $format;    // ai_requests.type
        $onProgress = $pipelineOpts['on_progress'] ?? null; // callable(string $step, ?string $detail)

        $startMs = (int)(microtime(true) * 1000);

        // ── 0. CACHE LOOKUP ──
        $cacheKey = $this->buildCacheKey($userInput, $options, $format);
        if (! $noCache) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                Log::debug('LLMPipeline: cache hit', ['key' => $cacheKey]);
                $this->emitProgress($onProgress, 'cached', 'Loaded from cache');
                $this->logInteraction($type, $userInput, $cached, 'cached', 0, 0, $options);
                return $cached;
            }
        }

        // ── 1. PRE-PROCESSING ──
        $this->emitProgress($onProgress, 'analyzing', 'Analyzing your prompt…');
        $prompt = $this->preProcess($userInput, $context, $format, $options);

        // ── 2–4. CALL + POST-PROCESS + RETRY LOOP ──
        $lastRaw = [];
        $rejectionReasons = [];
        $attemptCount = 0;

        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            $attemptCount = $attempt;

            // ── 2. AI CALL ──
            if ($attempt === 0) {
                $this->emitProgress($onProgress, 'generating', 'Generating ' . $format . ' output…');
            } else {
                $this->emitProgress($onProgress, 'retrying', 'Refining output (attempt ' . ($attempt + 1) . ')…');
            }

            $rawOutputs = $this->llm->generate($prompt, $options);

            if (empty($rawOutputs)) {
                Log::info('LLMPipeline: empty response from LLM', ['attempt' => $attempt]);
                break;
            }

            $lastRaw = $rawOutputs;

            // ── 3. POST-PROCESSING (with diagnostic rejection) ──
            $this->emitProgress($onProgress, 'optimizing', 'Validating and optimizing output…');
            $processed = [];
            $rejectionReasons = [];

            foreach ($rawOutputs as $raw) {
                $cleaned = $this->postProcess($raw, $format);
                if ($cleaned === null) {
                    $rejectionReasons[] = $this->diagnoseFormatRejection($raw, $format);
                    continue;
                }
                if ($validator && !$validator($cleaned)) {
                    $rejectionReasons[] = $this->diagnoseValidatorRejection($cleaned, $format);
                    continue;
                }
                if ($normalizer) {
                    $cleaned = $normalizer($cleaned);
                }
                $processed[] = $cleaned;
            }

            if (!empty($processed)) {
                // ── 5. CACHE + LOG + RETURN ──
                $this->emitProgress($onProgress, 'complete', 'Done');
                if (! $noCache) {
                    Cache::put($cacheKey, $processed, $this->cacheTtl);
                }
                $durationMs = (int)(microtime(true) * 1000) - $startMs;
                $this->logInteraction($type, $userInput, $processed, 'success', $attemptCount, $durationMs, $options);
                return $processed;
            }

            // ── 4. RETRY — build a correction prompt with diagnosis ──
            $prompt = $this->buildCorrectionPrompt(
                $userInput, $lastRaw, $format, $attempt, $rejectionReasons
            );
            Log::info('LLMPipeline: retry with correction', [
                'attempt' => $attempt + 1,
                'format'  => $format,
                'reasons' => $rejectionReasons,
            ]);
        }

        $durationMs = (int)(microtime(true) * 1000) - $startMs;
        $this->logInteraction($type, $userInput, $lastRaw, 'failed', $attemptCount, $durationMs, $options, $rejectionReasons);

        Log::warning('LLMPipeline: all retries exhausted', [
            'format' => $format,
            'input_length' => mb_strlen($userInput),
            'last_reasons' => $rejectionReasons,
        ]);
        return [];
    }

    /**
     * Emit a progress event via the optional callback.
     */
    protected function emitProgress(?callable $onProgress, string $step, string $detail): void
    {
        if ($onProgress) {
            try {
                $onProgress($step, $detail);
            } catch (\Throwable $e) {
                // Never let progress callback break the pipeline
            }
        }
    }

    /**
     * Log an AI interaction to the ai_requests table.
     */
    protected function logInteraction(
        string $type,
        string $input,
        mixed  $output,
        string $status,
        int    $retries,
        int    $durationMs,
        array  $options = [],
        array  $rejectionReasons = []
    ): void {
        try {
            $outputStr = is_array($output) ? json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $output;

            $meta = array_filter([
                'temperature'  => $options['temperature'] ?? null,
                'top_p'        => $options['top_p'] ?? null,
                'max_tokens'   => $options['max_tokens'] ?? null,
                'format'       => $options['format'] ?? $type,
                'rejections'   => $rejectionReasons ?: null,
            ]);

            AIRequest::create([
                'user_id'     => auth()->id(),
                'type'        => $type,
                'input'       => mb_substr($input, 0, 65535),
                'output'      => $outputStr,
                'status'      => $status,
                'model'       => config('services.llm.model', 'gpt-4o-mini'),
                'retries'     => $retries,
                'duration_ms' => $durationMs,
                'meta'        => $meta,
            ]);
        } catch (\Throwable $e) {
            Log::error('LLMPipeline: failed to log AI request', ['error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────
    //  1. PRE-PROCESSING
    // ─────────────────────────────────────────────

    /**
     * Clean the user input and build the enriched prompt.
     */
    protected function preProcess(string $input, array $context, string $format, array &$options): string
    {
        // ── Clean ──
        $input = $this->cleanInput($input);

        // ── Enrich context ──
        $parts = [];

        // Prepend context lines if provided
        if (!empty($context)) {
            $parts[] = "Context:\n" . implode("\n", array_map('trim', $context));
        }

        // Add format-specific system instructions if not already set
        if (empty($options['system'])) {
            $options['system'] = $this->defaultSystemPrompt($format);
        }

        // Add format constraint to prompt
        $formatInstruction = $this->formatInstruction($format);
        if ($formatInstruction) {
            $parts[] = $formatInstruction;
        }

        $parts[] = $input;

        return implode("\n\n", $parts);
    }

    /**
     * Sanitise raw user input.
     */
    protected function cleanInput(string $input): string
    {
        // Remove null bytes and dangerous control characters
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $input);

        // Collapse excessive whitespace (keep newlines)
        $input = preg_replace('/[^\S\n]+/', ' ', $input);

        // Trim per-line and overall
        $lines = array_map('trim', explode("\n", $input));
        $input = trim(implode("\n", $lines));

        // Truncate absurdly long inputs (16 KB max)
        if (mb_strlen($input) > 16384) {
            $input = mb_substr($input, 0, 16384) . "\n[input truncated]";
        }

        return $input;
    }

    /**
     * Default system prompt based on expected output format.
     */
    protected function defaultSystemPrompt(string $format): string
    {
        return match ($format) {
            'json' => "You are an expert software architect. Return ONLY valid JSON. No markdown fences, no commentary.",
            'plantuml' => "You are a senior software architect with deep expertise in UML modeling across all domains (healthcare, e-commerce, banking, DevOps, AI/ML, IoT, government, education, and more). Produce ONLY complete, comprehensive, highly-detailed PlantUML code starting with @startuml and ending with @enduml. Include as many entities, attributes, relationships, enumerations, interfaces, and abstract classes as are realistic for the described system. Use proper multiplicity labels (\"1\", \"0..*\", \"1..*\") on all relationships. No markdown fences, no prose, no explanations.",
            default => "You are a helpful, concise engineering assistant that produces technical, actionable outputs.",
        };
    }

    /**
     * Extra instruction appended to prompt for the expected format.
     */
    protected function formatInstruction(string $format): ?string
    {
        return match ($format) {
            'json' => "IMPORTANT: Output ONLY a valid JSON object. No explanation, no markdown code fences.",
            'plantuml' => "IMPORTANT: Output ONLY valid PlantUML starting with @startuml and ending with @enduml. Be comprehensive: include ALL relevant entities with full attribute lists, ALL relationships with multiplicity, enumerations for status/type fields, and meaningful comments. No explanation, no markdown code fences.",
            default => null,
        };
    }

    // ─────────────────────────────────────────────
    //  3. POST-PROCESSING
    // ─────────────────────────────────────────────

    /**
     * Validate and clean a single completion.
     * Returns the cleaned string, or null if it should be rejected.
     */
    protected function postProcess(string $raw, string $format): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Strip markdown code fences the model may wrap around the output
        $raw = $this->stripCodeFences($raw);

        return match ($format) {
            'json' => $this->postProcessJson($raw),
            'plantuml' => $this->postProcessPlantUml($raw),
            default => $this->postProcessText($raw),
        };
    }

    /**
     * Remove markdown code fences (```json ... ``` etc.)
     */
    protected function stripCodeFences(string $text): string
    {
        // Pattern: optional language tag after opening backticks
        if (preg_match('/^```[a-z]*\s*\n(.*?)```\s*$/s', $text, $m)) {
            return trim($m[1]);
        }
        return $text;
    }

    /**
     * Post-process JSON output: validate structure, fix common issues.
     */
    protected function postProcessJson(string $raw): ?string
    {
        // Try direct parse
        $data = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Try extracting JSON from surrounding text
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $slice = substr($raw, $start, $end - $start + 1);
            $data = json_decode($slice, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        // Try fixing trailing commas (common LLM mistake)
        $fixed = preg_replace('/,\s*([\]}])/', '$1', $raw);
        $data = json_decode($fixed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return null; // invalid JSON — trigger retry
    }

    /**
     * Post-process PlantUML output: validate and normalise.
     */
    protected function postProcessPlantUml(string $raw): ?string
    {
        // Must contain @startuml
        if (!str_contains($raw, '@startuml')) {
            // Try to rescue: wrap if it looks like class definitions
            if (preg_match('/class\s+\w+/', $raw)) {
                $raw = "@startuml\n" . $raw;
            } else {
                return null;
            }
        }

        // Must contain @enduml (or add it)
        if (!str_contains($raw, '@enduml')) {
            $raw = rtrim($raw) . "\n@enduml";
        }

        // Must contain at least one class definition
        if (!preg_match('/class\s+\w+/', $raw)) {
            return null;
        }

        // Strip anything before @startuml and after @enduml
        if (preg_match('/@startuml.*@enduml/s', $raw, $m)) {
            $raw = $m[0];
        }

        return $raw;
    }

    /**
     * Post-process free-text output.
     */
    protected function postProcessText(string $raw): ?string
    {
        // Reject if looks like the model echoed our instructions back
        $low = strtolower($raw);
        if (str_contains($low, 'return only') && str_contains($low, 'original instruction')) {
            return null;
        }

        // Must have meaningful content (more than just punctuation)
        $stripped = preg_replace('/[\s\p{P}]/u', '', $raw);
        if (mb_strlen($stripped) < 10) {
            return null;
        }

        return $raw;
    }

    // ─────────────────────────────────────────────
    //  4. RETRY PROMPT BUILDER
    // ─────────────────────────────────────────────

    // ─────────────────────────────────────────────
    //  CACHE
    // ─────────────────────────────────────────────

    /**
     * Build a deterministic cache key from prompt + options + format.
     */
    protected function buildCacheKey(string $input, array $options, string $format): string
    {
        // Include only keys that affect the output
        $sig = [
            'input'  => $input,
            'format' => $format,
            'temp'   => $options['temperature'] ?? null,
            'top_p'  => $options['top_p'] ?? null,
            'system' => $options['system'] ?? null,
            'n'      => $options['n'] ?? 1,
        ];
        return 'llm_pipeline:' . hash('xxh128', serialize($sig));
    }

    /**
     * Flush all pipeline cache entries (useful after config changes).
     */
    public function flushCache(): void
    {
        // Tag-based flush if driver supports it; otherwise caller clears specific keys
        try {
            Cache::forget('llm_pipeline:*');
        } catch (\Throwable $e) {
            Log::debug('LLMPipeline: cache flush not supported by driver', ['error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────
    //  DIAGNOSTIC REJECTION
    // ─────────────────────────────────────────────

    /**
     * Diagnose why postProcess() rejected the output (format-level).
     */
    protected function diagnoseFormatRejection(string $raw, string $format): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return 'Output was empty.';
        }

        return match ($format) {
            'json' => $this->diagnoseJsonRejection($raw),
            'plantuml' => $this->diagnosePlantUmlRejection($raw),
            default => 'Output did not pass text validation.',
        };
    }

    protected function diagnoseJsonRejection(string $raw): string
    {
        json_decode($raw, true);
        $err = json_last_error_msg();
        if ($err !== 'No error') {
            return "Invalid JSON: {$err}. Ensure no trailing commas, no markdown fences, and proper quoting.";
        }
        return 'JSON parsed but structure was invalid.';
    }

    protected function diagnosePlantUmlRejection(string $raw): string
    {
        $issues = [];
        if (! str_contains($raw, '@startuml')) {
            $issues[] = 'Missing @startuml tag';
        }
        if (! str_contains($raw, '@enduml')) {
            $issues[] = 'Missing @enduml tag';
        }
        if (! preg_match('/class\s+\w+/', $raw)) {
            $issues[] = 'No class definitions found';
        }
        if (str_contains($raw, '```')) {
            $issues[] = 'Contains markdown code fences (remove them)';
        }
        return $issues ? implode('; ', $issues) . '.' : 'PlantUML structure invalid.';
    }

    /**
     * Diagnose why the custom validator rejected the output.
     */
    protected function diagnoseValidatorRejection(string $cleaned, string $format): string
    {
        if ($format === 'json') {
            $data = json_decode($cleaned, true);
            if (! is_array($data)) {
                return 'Validator: parsed data is not an array/object.';
            }
            $keys = array_keys($data);
            return 'Validator: JSON schema mismatch. Got keys: [' . implode(', ', array_slice($keys, 0, 8)) . ']. Check required fields.';
        }
        return 'Validator: output did not pass domain-specific validation.';
    }

    // ─────────────────────────────────────────────
    //  CORRECTION PROMPT (replaces old buildRetryPrompt)
    // ─────────────────────────────────────────────

    /**
     * Build a correction prompt that tells the model exactly what was wrong.
     */
    protected function buildCorrectionPrompt(
        string $originalInput,
        array  $lastOutputs,
        string $format,
        int    $attempt,
        array  $rejectionReasons
    ): string {
        $parts = [];

        // ── Diagnosis ──
        $parts[] = "Your previous response was REJECTED for these reasons:";
        foreach (array_unique($rejectionReasons) as $i => $reason) {
            $parts[] = ($i + 1) . ". " . $reason;
        }

        // ── Format-specific fix instructions ──
        $parts[] = match ($format) {
            'json' => "FIX: Return ONLY a valid JSON object. No trailing commas, no markdown fences, no commentary outside the JSON. "
                    . "Ensure all required keys are present. Validate your JSON mentally before responding.",
            'plantuml' => "FIX: Return ONLY valid PlantUML. MUST start with @startuml on its own line and end with @enduml. "
                        . "MUST contain at least one 'class ClassName { ... }' block. No markdown fences, no prose.",
            default => "FIX: Return a clear, complete response with no meta-commentary.",
        };

        // ── Original request ──
        $parts[] = "Original request:\n" . $originalInput;

        // ── Show rejected output on 2nd+ retry so model can self-correct ──
        if ($attempt >= 1 && ! empty($lastOutputs[0])) {
            $snippet = mb_substr($lastOutputs[0], 0, 600);
            $parts[] = "Your rejected output was:\n" . $snippet;
        }

        return implode("\n\n", $parts);
    }
}
