<?php

namespace App\Services\AI;

/**
 * Centralised prompt templates for every AI endpoint.
 *
 * Each static method returns ['system' => string, 'user' => string]
 * ready to feed into LLMPipeline::run().
 */
class PromptBuilder
{
    // ══════════════════════════════════════════════════
    //  convertIdeaToTasks
    // ══════════════════════════════════════════════════

    public static function forTasks(string $text): array
    {
        $system = "You are an experienced tech lead who converts project ideas into actionable engineering tasks. "
                . "Return ONLY a valid JSON object with these exact keys:\n"
                . "- features: array of short feature bullets\n"
                . "- modules: array of module names\n"
                . "- database_entities: array of {name, fields: [{name, type}]}\n"
                . "- api_endpoints: array of {method, path, description}\n"
                . "- architecture_overview: string (2-3 sentences)\n"
                . "- improvements: array of suggested improvements\n"
                . "Focus on practical, shippable tasks. Be specific about field types and API contracts.";

        $user = "Break down the following project idea into concrete engineering tasks, entities, and API endpoints.\n\n"
              . "Idea:\n" . $text;

        return compact('system', 'user');
    }

    // ══════════════════════════════════════════════════
    //  improveArchitecture
    // ══════════════════════════════════════════════════

    public static function forArchitectureImprovement(string $codeOrDescription): array
    {
        $system = "You are a senior software architect conducting an architecture review. "
                . "Return ONLY a valid JSON object with these exact keys:\n"
                . "- refactoring: array of {area, suggestion, priority} — code refactoring opportunities\n"
                . "- security: array of {vulnerability, severity, fix} — security issues and fixes\n"
                . "- performance: array of {bottleneck, suggestion} — performance improvements\n"
                . "- scaling: array of strings — scaling recommendations\n"
                . "- migrations: array of strings — migration steps needed\n"
                . "- architecture_overview: string — summary of current state\n"
                . "Be specific. Reference concrete patterns (CQRS, event sourcing, caching layers, etc).";

        $user = "Review the following architecture or code and provide specific, actionable improvement recommendations.\n\n"
              . "Input:\n" . $codeOrDescription;

        return compact('system', 'user');
    }

    // ══════════════════════════════════════════════════
    //  generatePlantUML
    // ══════════════════════════════════════════════════

    public static function forPlantUML(string $text, array $meta = []): array
    {
        $system = "You are an expert software architect. Given an idea, return a JSON object with keys: features, modules, database_entities, api_endpoints, architecture_overview, improvements. Keep responses concise and return only valid JSON when requested.";

        // Base instruction with template and few-shot example
        $user = "Produce a PlantUML diagram (text only, no commentary, no markdown fences) that represents the system described below. Follow this exact structure and section headings. When listing entities include fields and types where possible. Use PlantUML class diagram syntax and relationships.\n\nExample template to follow exactly (replace example content with actual generated content):\n@startuml\n' ========================\n' CORE ENTITIES\n' ========================\n\nclass User {\n  +id: int\n  +name: string\n  +email: string\n  +password: string\n}\n\nclass Project {\n  +id: int\n  +name: string\n  +description: text\n  +created_at: datetime\n}\n\n' ========================\n' MANAGEMENT FEATURES\n' ========================\n\nclass DiagramVersion {\n  +id: int\n  +diagram_id: int\n  +content: text\n  +version_number: int\n  +created_at: datetime\n}\n\n' ========================\n' RELATIONS\n' ========================\n\nUser \"1\" -- \"many\" Project\nProject \"1\" -- \"many\" Diagram\n\n@enduml\n\n";

        $searchRadius = $meta['search_radius'] ?? 1;
        if ($searchRadius && $searchRadius > 1) {
            $user .= "Consider exploring broader architectural patterns, related domains, alternative designs, and trade-offs. Provide richer entity suggestions and helper classes if appropriate.\n\n";
        }

        $style = strtolower($meta['style'] ?? ($meta['mode'] ?? 'precise'));

        if (in_array($style, ['diverse', 'powerful', 'strange'])) {
            $user .= "Be more creative: when useful, propose unconventional but valid entity names, additional helper classes, and interesting relations. Keep PlantUML valid.\n\n";

            if ($style === 'strange') {
                $user .= "For 'strange' mode: introduce at least one unusual helper class (e.g., 'Chronicle', 'EntropyTracker') and one metaphorical relation (as a valid PlantUML association) while keeping types and syntax correct.\n\n";
            }

            $user .= "Example (few-shot):\n@startuml\n' ========================\n' CORE ENTITIES\n' ========================\nclass User {\n  +id: int\n  +handle: string\n}\nclass Task {\n  +id: int\n  +title: string\n  +state: string\n}\n' ========================\n' MANAGEMENT FEATURES\n' ========================\nclass Comment {\n  +id: int\n  +task_id: int\n  +content: text\n}\n' ========================\n' RELATIONS\n' ========================\nUser \"1\" -- \"many\" Task\nTask \"1\" -- \"many\" Comment\n@enduml\n\n";
        }

        $user .= "Now produce PlantUML by extracting entities, modules and relations from the input below. Output only valid PlantUML that follows the template. Do NOT include any extra text.\n\nInput:\n" . $text;

        return compact('system', 'user');
    }

    // ══════════════════════════════════════════════════
    //  generateTestCases
    // ══════════════════════════════════════════════════

    public static function forTestCases(string $featureDescription): array
    {
        $system = 'You are a senior QA engineer. You produce comprehensive test cases '
            . 'covering happy paths, edge cases, error handling, and security. '
            . 'Output a JSON array of test case objects. No prose, no markdown.';

        $user = "Generate test cases for the following feature:\n\n"
            . trim($featureDescription)
            . "\n\nReturn a JSON array where each element has these keys:\n"
            . "- name (string): short test name in snake_case\n"
            . "- type (string): one of unit, feature, integration\n"
            . "- description (string): what the test verifies\n"
            . "- steps (array of strings): step-by-step actions\n"
            . "- expected_result (string): the expected outcome\n\n"
            . "Return at least 5 test cases. Output valid JSON only.";

        return compact('system', 'user');
    }

    // ══════════════════════════════════════════════════
    //  generateTaskSuggestions (LLM variant)
    // ══════════════════════════════════════════════════

    public static function forTaskSuggestions(string $base, string $contextStr): array
    {
        $system = "You are a senior software engineer drafting concise, technical, and actionable task suggestions. For each suggestion return a short title on the first line and a detailed description afterwards including acceptance criteria and an estimate. Provide multiple diverse approaches: implementation, investigation, design, performance, security, QA.";

        $user = "Provide 6 distinct suggestions for: {$base}";

        return compact('system', 'user');
    }

    // ══════════════════════════════════════════════════
    //  callModel (generic analysis)
    // ══════════════════════════════════════════════════

    public static function forGenericAnalysis(int $searchRadius = 1): array
    {
        $system = "You are an expert software architect. Given an idea, return a JSON object with keys: features, modules, database_entities, api_endpoints, architecture_overview, improvements. Keep responses concise and return only valid JSON when requested.";

        if ($searchRadius && $searchRadius > 1) {
            $system .= "\nConsider a wide search radius: explore broader architectural patterns, related domains, and alternative solutions. When useful, propose multiple valid approaches and trade-offs.";
        }

        return ['system' => $system, 'user' => ''];
    }

    // ══════════════════════════════════════════════════
    //  PlantUML sampling parameters by style
    // ══════════════════════════════════════════════════

    public static function plantUMLSamplingParams(string $style): array
    {
        return match ($style) {
            'precise'  => ['temperature' => 0.1, 'top_p' => 0.9],
            'diverse'  => ['temperature' => 0.6, 'top_p' => 0.95],
            'powerful' => ['temperature' => 0.75, 'top_p' => 0.95],
            'strange'  => ['temperature' => 0.9, 'top_p' => 0.9],
            default    => [
                'temperature' => config('services.llm.temperature', 0.6),
                'top_p' => config('services.llm.top_p', 0.95),
            ],
        };
    }
}
