<?php

namespace App\Services;

use App\Models\AIPlan;
use Illuminate\Support\Facades\Log;

class AIPlanningService
{
    protected $llm;
    protected LLMPipeline $pipeline;

    public function __construct(LLMService $llm = null)
    {
        $this->llm = $llm ?: new LLMService();
        $this->pipeline = new LLMPipeline($this->llm);
    }

    protected function extractJson(string $text): ?array
    {
        // Try to extract a JSON object from the LLM reply
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        $json = substr($text, $start, $end - $start + 1);
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    protected function callModel(string $instruction, array $opts = []): array
    {
        $system = $opts['system'] ?? "You are an expert software architect. Given an idea, return a JSON object with keys: features, modules, database_entities, api_endpoints, architecture_overview, improvements. Keep responses concise and return only valid JSON when requested.";

        $searchRadius = $opts['search_radius'] ?? 1;
        if ($searchRadius && $searchRadius > 1) {
            $system .= "\nConsider a wide search radius: explore broader architectural patterns, related domains, and alternative solutions. When useful, propose multiple valid approaches and trade-offs.";
        }

        $maxTokens = $opts['max_tokens'] ?? config('services.llm.max_tokens', 512);
        if ($searchRadius && $searchRadius > 1) {
            $scale = min(4, (int)$searchRadius);
            $maxTokens = max($maxTokens, min(4096, 512 * $scale));
        }

        // ── Run through the formal pipeline ──
        $results = $this->pipeline->run($instruction, [
            'n' => 1,
            'temperature' => $opts['temperature'] ?? config('services.llm.temperature', 0.6),
            'top_p' => $opts['top_p'] ?? config('services.llm.top_p', 0.95),
            'system' => $system,
            'max_tokens' => $maxTokens,
        ], [
            'format' => 'json',
            'max_retries' => $opts['attempts'] ?? 3,
            'validator' => function (string $output) {
                $data = json_decode($output, true);
                return is_array($data) && $this->validateSchema($data);
            },
        ]);

        if (!empty($results[0])) {
            $parsed = json_decode($results[0], true);
            if (is_array($parsed) && $this->validateSchema($parsed)) {
                return $parsed;
            }
        }

        // Pipeline exhausted — fallback to empty structure
        Log::warning('AIPlanningService: pipeline could not produce valid JSON schema');
        return [
            'features' => [],
            'modules' => [],
            'database_entities' => [],
            'api_endpoints' => [],
            'architecture_overview' => trim($instruction),
            'improvements' => [],
        ];
    }

    protected function validateSchema(array $data): bool
    {
        // Minimal schema validation: required keys and types
        $required = ['features','modules','database_entities','api_endpoints','architecture_overview','improvements'];
        foreach ($required as $k) {
            if (!array_key_exists($k, $data)) return false;
        }
        if (!is_array($data['features'])) return false;
        if (!is_array($data['modules'])) return false;
        if (!is_array($data['database_entities'])) return false;
        if (!is_array($data['api_endpoints'])) return false;
        if (!is_string($data['architecture_overview'])) return false;
        if (!is_array($data['improvements'])) return false;
        return true;
    }

    // Public helper so callers can check whether a returned structure matches schema
    public function isValidSchema(array $data): bool
    {
        return $this->validateSchema($data);
    }

    public function analyzeIdea(string $text, $meta = []): array
    {
        // Backward compat: delegates to the dedicated endpoint
        return $this->convertIdeaToTasks($text, $meta);
    }

    // ════════════════════════════════════════════════
    //  DEDICATED ENDPOINT: convertIdeaToTasks
    // ════════════════════════════════════════════════

    /**
     * Convert a project idea into a structured task breakdown.
     *
     * Prompt template : task-extraction focused
     * System prompt   : product-manager / tech-lead persona
     * Post-processing : validates tasks schema, normalises priorities
     */
    public function convertIdeaToTasks(string $text, $meta = []): array
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

        $instruction = "Break down the following project idea into concrete engineering tasks, entities, and API endpoints.\n\n"
                     . "Idea:\n" . $text;

        $results = $this->pipeline->run($instruction, [
            'n' => 1,
            'temperature' => 0.3,
            'system' => $system,
            'max_tokens' => $meta['max_tokens'] ?? 1024,
        ], [
            'format' => 'json',
            'max_retries' => 3,
            'validator' => function (string $output) {
                $data = json_decode($output, true);
                return is_array($data) && $this->validateSchema($data);
            },
            'normalizer' => function (string $output) {
                // Normalise: ensure arrays are clean, trim strings
                $data = json_decode($output, true);
                if (isset($data['features'])) {
                    $data['features'] = array_values(array_filter(array_map('trim', $data['features'])));
                }
                if (isset($data['improvements'])) {
                    $data['improvements'] = array_values(array_filter(array_map('trim', $data['improvements'])));
                }
                return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            },
        ]);

        $result = !empty($results[0]) ? json_decode($results[0], true) : null;

        if (!$result || !$this->validateSchema($result)) {
            $result = [
                'features' => [], 'modules' => [], 'database_entities' => [],
                'api_endpoints' => [], 'architecture_overview' => trim($text),
                'improvements' => [],
            ];
        }

        AIPlan::create([
            'board_id' => $meta['board_id'] ?? null,
            'title' => $meta['title'] ?? 'convertIdeaToTasks',
            'input_text' => $text,
            'result_json' => $result,
        ]);

        return $result;
    }

    // ════════════════════════════════════════════════
    //  DEDICATED ENDPOINT: improveArchitecture
    // ════════════════════════════════════════════════

    /**
     * Analyze existing code/architecture and return improvement suggestions.
     *
     * Prompt template : code-review / architecture-audit focused
     * System prompt   : senior architect persona
     * Post-processing : validates improvement categories exist
     */
    public function improveArchitecture(string $codeOrDescription, $meta = []): array
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

        $instruction = "Review the following architecture or code and provide specific, actionable improvement recommendations.\n\n"
                     . "Input:\n" . $codeOrDescription;

        $results = $this->pipeline->run($instruction, [
            'n' => 1,
            'temperature' => 0.4,
            'system' => $system,
            'max_tokens' => $meta['max_tokens'] ?? 1024,
        ], [
            'format' => 'json',
            'max_retries' => 3,
            'validator' => function (string $output) {
                $data = json_decode($output, true);
                if (!is_array($data)) return false;
                // Must have at least one improvement category
                return isset($data['refactoring']) || isset($data['security'])
                    || isset($data['performance']) || isset($data['scaling']);
            },
            'normalizer' => function (string $output) {
                $data = json_decode($output, true);
                // Ensure all expected keys exist
                $defaults = [
                    'refactoring' => [], 'security' => [], 'performance' => [],
                    'scaling' => [], 'migrations' => [], 'architecture_overview' => '',
                ];
                $data = array_merge($defaults, $data);
                return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            },
        ]);

        $result = !empty($results[0]) ? json_decode($results[0], true) : null;

        if (!$result) {
            $result = [
                'refactoring' => [], 'security' => [], 'performance' => [],
                'scaling' => [], 'migrations' => [],
                'architecture_overview' => trim($codeOrDescription),
            ];
        }

        AIPlan::create([
            'board_id' => $meta['board_id'] ?? null,
            'title' => $meta['title'] ?? 'improveArchitecture',
            'input_text' => $codeOrDescription,
            'result_json' => $result,
        ]);

        return $result;
    }

    /**
     * @deprecated Use improveArchitecture() instead. Kept for backward compatibility.
     */
    public function suggestImprovements(string $text, $meta = []): array
    {
        return $this->improveArchitecture($text, $meta);
    }

    public function generateArchitecture(string $text, $meta = []): array
    {
        $instruction = "Given the idea or existing description below, produce a system architecture JSON object with components (frontend, backend, database), relationships, suggested patterns (MVC, modular, microservices if applicable), and concrete component responsibilities. Output valid JSON only.\n\nInput:\n" . $text;
        $result = $this->callModel($instruction);

        AIPlan::create([
            'board_id' => $meta['board_id'] ?? null,
            'title' => $meta['title'] ?? 'generateArchitecture',
            'input_text' => $text,
            'result_json' => $result,
        ]);

        return $result;
    }

    // ════════════════════════════════════════════════
    //  DEDICATED ENDPOINT: generateUML
    // ════════════════════════════════════════════════

    /**
     * Generate a PlantUML class diagram from a natural-language prompt.
     *
     * Prompt template : PlantUML-specific with section headings + few-shot examples
     * System prompt   : architect persona, PlantUML-only output
     * Post-processing : validates @startuml/@enduml, requires class defs, auto-wraps
     */
    public function generateUML(string $prompt, $meta = []): string
    {
        return $this->generatePlantUML($prompt, $meta);
    }

    public function generatePlantUML(string $text, $meta = []): string
    {
        $onProgress = $meta['on_progress'] ?? null;

        // Diagram type overrides: when a specific diagram_type is requested, use type-specific instructions
        $diagramType = strtolower($meta['diagram_type'] ?? 'class');
        if ($diagramType !== 'class') {
            // Prepend domain context to enrich the prompt for all diagram types
            $domainHintPrefix = $this->extractDomainHints($text);

            $typeInstructions = [
                'sequence'  => [
                    'system' => "You are an expert software architect specializing in UML sequence diagrams. Produce ONLY valid PlantUML sequence diagram code starting with @startuml and ending with @enduml. Use 'actor', 'participant', 'boundary', 'control', 'entity', '->', '-->', '<-', '<--', 'activate', 'deactivate', 'alt', 'opt', 'loop', 'group'. Show realistic message labels with parameters. No markdown fences, no prose.",
                    'suffix' => "Generate a PlantUML SEQUENCE diagram showing the interactions and message flows for the system described below. Show all actors/participants, synchronous (->) and asynchronous (->>) messages, return arrows, activation bars, and relevant groupings (alt/loop/opt).\n\n",
                ],
                'usecase'   => [
                    'system' => "You are an expert software architect specializing in UML use case diagrams. Produce ONLY valid PlantUML use case diagram code starting with @startuml and ending with @enduml. Use 'actor', '(use case)', '<<include>>', '<<extend>>', 'rectangle' for system boundary. No markdown fences, no prose.",
                    'suffix' => "Generate a PlantUML USE CASE diagram for the system below. Include all primary and secondary actors, all main use cases, <<include>> and <<extend>> relationships, and draw a system boundary rectangle.\n\n",
                ],
                'er'        => [
                    'system' => "You are an expert database architect specializing in entity-relationship diagrams. Produce ONLY valid PlantUML ER diagram code starting with @startuml and ending with @enduml. Use 'entity' keyword, list attributes with types inside entity blocks, and define relationships with proper ER cardinality notation (||--o{, }o--||, etc.). No markdown fences, no prose.",
                    'suffix' => "Generate a PlantUML ENTITY-RELATIONSHIP (ER) diagram for the system below. Show all entities with their full attribute lists (include data types and key markers), and define all relationships with cardinality (one-to-one, one-to-many, many-to-many). Use Crow's foot notation.\n\n",
                ],
                'activity'  => [
                    'system' => "You are an expert software architect specializing in UML activity diagrams. Produce ONLY valid PlantUML activity diagram code starting with @startuml and ending with @enduml. Use 'start', 'stop', ':action;', 'if (...) then (yes)', 'else (no)', 'endif', 'fork', 'fork again', 'end fork', 'partition \"Name\"'. No markdown fences, no prose.",
                    'suffix' => "Generate a PlantUML ACTIVITY diagram showing the complete workflow and process flow for the system described below. Include all major steps, decision points with conditions, parallel flows using fork/join, and use partitions to group activities by actor or component.\n\n",
                ],
                'state'     => [
                    'system' => "You are an expert software architect specializing in UML state machine diagrams. Produce ONLY valid PlantUML state diagram code starting with @startuml and ending with @enduml. Use '[*]' for initial/final states, 'state \"Label\" as X', 'X --> Y : trigger [guard] / action', 'state X { [*] --> A }' for composite states. No markdown fences, no prose.",
                    'suffix' => "Generate a PlantUML STATE MACHINE diagram for the system below. Show all possible states, all transitions with trigger events and guard conditions, entry/exit actions where relevant, and composite states if needed.\n\n",
                ],
                'component' => [
                    'system' => "You are an expert software architect specializing in UML component and deployment diagrams. Produce ONLY valid PlantUML component diagram code starting with @startuml and ending with @enduml. Use 'component', 'interface', 'package', 'node', '-->', '..>', 'database', 'cloud', 'queue'. Show realistic dependency arrows with labels. No markdown fences, no prose.",
                    'suffix' => "Generate a PlantUML COMPONENT diagram for the system below. Show all components, interfaces they expose/require, packages or layers they belong to, and all dependencies between them with appropriate arrow types (realization, dependency, usage).\n\n",
                ],
            ];

            if (isset($typeInstructions[$diagramType])) {
                $info = $typeInstructions[$diagramType];
                // Build prompt with domain hints + type-specific suffix + user input
                $prompt = ($domainHintPrefix ? $domainHintPrefix : '') . $info['suffix'] . "Input:\n" . $text;
                if ($onProgress) { $onProgress('generating', 'Building ' . strtoupper($diagramType) . ' diagram…'); }
                $results = $this->pipeline->run($prompt, [
                    'n' => 1,
                    'temperature' => $meta['temperature'] ?? 0.3,
                    'top_p' => $meta['top_p'] ?? 0.95,
                    'system' => $info['system'],
                    'max_tokens' => $meta['max_tokens'] ?? config('services.llm.max_tokens', 3000),
                ], [
                    'format' => 'plantuml',
                    'max_retries' => 2,
                    'on_progress' => $onProgress,
                ]);
                $textOut = trim($results[0] ?? '');

                if ($textOut && !$this->looksLikeInstruction($textOut)) {
                    AIPlan::create([
                        'board_id' => $meta['board_id'] ?? null,
                        'title' => $meta['title'] ?? 'generatePlantUML(' . $diagramType . ')',
                        'input_text' => $text,
                        'result_json' => ['plantuml' => $textOut],
                    ]);
                    if ($onProgress) { $onProgress('complete', 'Done'); }
                    return $textOut;
                }
                // fall through to class diagram if type-specific generation failed
            }
        }

        // Domain keyword mapping: extract seed entities to enrich the LLM prompt (do NOT bypass LLM)
        $domainStructure   = $this->detectDomainStructure($text);
        $domainSeedContext = '';

        // For heavy structured prompts, pre-parse inline specs → use as LLM seed
        if ($this->isDetailedStructuredPrompt($text)) {
            if ($onProgress) { $onProgress('analyzing', 'Parsing structured specification…'); }
            $preParsed = $this->parseDetailedPromptToPlantUML($text);
            if ($preParsed) {
                // If LLM is disabled, return the pre-parsed result immediately
                if (!$this->pipeline->isLLMEnabled()) {
                    AIPlan::create([
                        'board_id'    => $meta['board_id'] ?? null,
                        'title'       => $meta['title'] ?? 'generatePlantUML',
                        'input_text'  => $text,
                        'result_json' => ['plantuml' => $preParsed],
                    ]);
                    if ($onProgress) { $onProgress('complete', 'Done'); }
                    return $preParsed;
                }
                // Otherwise, pass pre-parsed PlantUML as seed context to the LLM
                $domainSeedContext = "PARSED SPECIFICATION (expand, enrich, and refine this into a complete diagram):\n"
                    . $preParsed . "\n\n";
            }
        } elseif (!empty($domainStructure['database_entities'])) {
            if ($onProgress) { $onProgress('analyzing', 'Domain detected — generating rich diagram…'); }
            $seedLines = [];
            foreach ($domainStructure['database_entities'] as $ent) {
                $name = $ent['name'] ?? '';
                if (!$name) continue;
                $fieldPairs = [];
                foreach ($ent['fields'] ?? [] as $f) {
                    $fn = is_array($f) ? ($f['name'] ?? '') : $f;
                    $ft = is_array($f) ? ($f['type'] ?? 'string') : 'string';
                    if ($fn) $fieldPairs[] = "{$fn}: {$ft}";
                }
                $seedLines[] = "- {$name} (" . implode(', ', $fieldPairs) . ")";
            }
            if (!empty($seedLines)) {
                $domainSeedContext = "DOMAIN SEED ENTITIES (include ALL of these and add MANY MORE related classes, enumerations, interfaces, methods, and relationships):\n"
                    . implode("\n", $seedLines) . "\n\n";
            }
        }

        // Determine style and sampling parameters
        $style = $meta['style'] ?? ($meta['mode'] ?? 'precise');
        $style = strtolower($style);
        $temperature = $meta['temperature'] ?? null;
        $top_p = $meta['top_p'] ?? null;

        if ($temperature === null) {
            switch ($style) {
                case 'precise':
                    $temperature = 0.3; $top_p = 0.95; break;
                case 'diverse':
                    $temperature = 0.6; $top_p = 0.95; break;
                case 'powerful':
                    $temperature = 0.75; $top_p = 0.95; break;
                case 'strange':
                    $temperature = 0.9; $top_p = 0.9; break;
                default:
                    $temperature = config('services.llm.temperature', 0.6); $top_p = config('services.llm.top_p', 0.95);
            }
        }

        // Base instruction — demands comprehensive, highly-detailed output
        $richFewShot = <<<'FEWSHOT'
@startuml
' ========================
' ENUMERATIONS
' ========================

enum UserStatus {
  ACTIVE
  INACTIVE
  SUSPENDED
  PENDING_VERIFICATION
}

enum OrderStatus {
  DRAFT
  CONFIRMED
  PROCESSING
  SHIPPED
  DELIVERED
  CANCELLED
  REFUNDED
}

enum PaymentMethod {
  CREDIT_CARD
  DEBIT_CARD
  BANK_TRANSFER
  DIGITAL_WALLET
  CRYPTO
}

' ========================
' INTERFACES & ABSTRACT
' ========================

interface Auditable {
  +getCreatedAt(): datetime
  +getUpdatedAt(): datetime
  +getCreatedBy(): int
}

abstract class BaseEntity {
  +id: int
  +created_at: datetime
  +updated_at: datetime
  +deleted_at: datetime
  +{abstract} validate(): bool
}

' ========================
' CORE ENTITIES
' ========================

class User {
  +id: int
  +name: string
  +email: string
  +password_hash: string
  +status: UserStatus
  +phone: string
  +avatar_url: string
  +email_verified_at: datetime
  +last_login_at: datetime
  +created_at: datetime
  +login(): bool
  +logout(): void
  +updateProfile(data: array): bool
}

class Address {
  +id: int
  +user_id: int
  +label: string
  +street: string
  +city: string
  +state: string
  +country: string
  +zip_code: string
  +is_default: bool
}

class Product {
  +id: int
  +name: string
  +slug: string
  +description: text
  +sku: string
  +price: decimal
  +sale_price: decimal
  +stock: int
  +low_stock_threshold: int
  +weight_kg: decimal
  +category_id: int
  +brand_id: int
  +is_active: bool
  +created_at: datetime
  +isInStock(): bool
  +applyDiscount(pct: float): decimal
}

class Category {
  +id: int
  +name: string
  +slug: string
  +parent_id: int
  +description: text
  +image_url: string
  +sort_order: int
}

class Brand {
  +id: int
  +name: string
  +logo_url: string
  +website: string
}

class Order {
  +id: int
  +user_id: int
  +status: OrderStatus
  +subtotal: decimal
  +discount_amount: decimal
  +tax_amount: decimal
  +shipping_amount: decimal
  +total: decimal
  +shipping_address_id: int
  +coupon_id: int
  +notes: text
  +placed_at: datetime
  +calculateTotal(): decimal
  +cancel(): bool
}

class OrderItem {
  +id: int
  +order_id: int
  +product_id: int
  +quantity: int
  +unit_price: decimal
  +discount: decimal
  +subtotal: decimal
}

class Cart {
  +id: int
  +user_id: int
  +session_id: string
  +expires_at: datetime
  +total(): decimal
  +clear(): void
}

class CartItem {
  +id: int
  +cart_id: int
  +product_id: int
  +quantity: int
  +saved_price: decimal
}

' ========================
' MANAGEMENT FEATURES
' ========================

class Payment {
  +id: int
  +order_id: int
  +method: PaymentMethod
  +amount: decimal
  +currency: string
  +transaction_id: string
  +gateway_response: json
  +status: string
  +paid_at: datetime
  +refund(amount: decimal): bool
}

class Coupon {
  +id: int
  +code: string
  +type: string
  +value: decimal
  +min_order_amount: decimal
  +max_uses: int
  +used_count: int
  +valid_from: date
  +valid_until: date
  +is_active: bool
  +isValid(): bool
  +getDiscount(amount: decimal): decimal
}

class Review {
  +id: int
  +product_id: int
  +user_id: int
  +rating: int
  +title: string
  +body: text
  +verified_purchase: bool
  +helpful_count: int
  +created_at: datetime
}

class Notification {
  +id: int
  +user_id: int
  +type: string
  +title: string
  +body: text
  +data: json
  +read_at: datetime
  +created_at: datetime
}

class AuditLog {
  +id: int
  +user_id: int
  +action: string
  +model_type: string
  +model_id: int
  +old_values: json
  +new_values: json
  +ip_address: string
  +created_at: datetime
}

' ========================
' RELATIONS
' ========================

User "1" -- "0..*" Address : has
User "1" -- "0..*" Order : places
User "1" -- "0..1" Cart : owns
User "1" -- "0..*" Review : writes
User "1" -- "0..*" Notification : receives

Order "1" *-- "1..*" OrderItem : contains
Order "1" -- "0..1" Payment : paid via
Order "1" -- "0..1" Coupon : applies
Order "1" -- "1" Address : ships to

Cart "1" *-- "0..*" CartItem : holds

Product "1" -- "0..*" OrderItem
Product "1" -- "0..*" CartItem
Product "1" -- "0..*" Review
Product "*" --> "1" Category
Product "*" --> "1" Brand

Category "0..1" --> "0..*" Category : parent

User ..|> Auditable
Order ..|> Auditable
Product --|> BaseEntity

@enduml
FEWSHOT;

        $instruction = "You are a senior software architect. Produce a COMPLETE, COMPREHENSIVE, HIGHLY-DETAILED PlantUML CLASS diagram for the system described below.\n\nRULES:\n- Include ALL realistic entities for the domain — aim for 8-20+ classes\n- Every class must have a full list of attributes with proper data types\n- Include enumerations (enum) for status, type, role, priority, and other categorical fields\n- Define interfaces or abstract classes where the domain suggests them\n- Include methods on key classes (e.g. validate(), calculate(), cancel(), isActive())\n- Add multiplicity labels on ALL associations (\"1\", \"0..1\", \"1..*\", \"0..*\")\n- Use proper UML relationship types: '--' (association), '*--' (composition), 'o--' (aggregation), '--|>' (inheritance), '..|>' (realization), '..>' (dependency)\n- Add section comments (' ===) to organise: ENUMERATIONS, INTERFACES & ABSTRACT, CORE ENTITIES, MANAGEMENT FEATURES, RELATIONS\n- The more domain-relevant detail the better — include audit, notification, address, config, log, and helper entities when appropriate\n- Text only, no markdown fences, no prose outside the @startuml block\n\nFEW-SHOT EXAMPLE (this shows the required level of complexity and detail — produce something equally or more detailed for the actual input):\n\n{$richFewShot}\n\n";

        // Inject domain hints to steer the LLM toward domain-appropriate entities
        $domainHints = $this->extractDomainHints($text);
        if ($domainHints) {
            $instruction = $domainHints . $instruction;
        }
        // Inject seed entities extracted from knowledge-base domain detection
        if ($domainSeedContext) {
            $instruction = $domainSeedContext . $instruction;
        }

        // If user requested a larger search radius, ask model to consider broader possibilities
        $searchRadius = $meta['search_radius'] ?? 1;
        if ($searchRadius && $searchRadius > 1) {
            $instruction .= "Consider exploring broader architectural patterns, related domains, alternative designs, and trade-offs. Provide richer entity suggestions and helper classes if appropriate.\n\n";
        }

        // Add style modifiers for creative modes
        if (in_array($style, ['diverse','powerful','strange'])) {
            $instruction .= "Be more creative: when useful, propose unconventional but valid entity names, additional helper classes, and interesting relations. Keep PlantUML valid.\n\n";

            if ($style === 'strange') {
                $instruction .= "For 'strange' mode: introduce at least one unusual helper class (e.g., 'Chronicle', 'EntropyTracker') and one metaphorical relation (as a valid PlantUML association) while keeping types and syntax correct.\n\n";
            }
        }

        $instruction .= "Now produce the COMPLETE and DETAILED PlantUML for the input below. Follow all the rules above. Output only valid PlantUML starting with @startuml. Do NOT include any extra text.\n\nInput:\n" . $text;

        // ── Run through the pipeline (pre-process → call → post-process → retry) ──
        $llmOpts = [
            'n' => 1,
            'temperature' => $temperature,
            'top_p' => $top_p,
            'max_tokens' => $meta['max_tokens'] ?? config('services.llm.max_tokens', 3000),
        ];

        $results = $this->pipeline->run($instruction, $llmOpts, [
            'format' => 'plantuml',
            'max_retries' => 2,
            'on_progress' => $onProgress,
        ]);
        $textOut = $results[0] ?? '';

        // Backend-only: if configured, perform additional internal exploration via pipeline
        $searchRadius = $meta['search_radius'] ?? config('services.llm.search_radius', 2);
        $autoExplore = config('services.llm.auto_explore', true);
        if (($searchRadius && $searchRadius > 1) || $autoExplore) {
            $extraStyles = ['diverse', 'powerful'];
            $collected = [$textOut];
            if ($onProgress) { $onProgress('exploring', 'Exploring alternative designs…'); }
            foreach ($extraStyles as $es) {
                try {
                    $promptForStyle = $instruction . "\n' style: {$es}\n";
                    $extra = $this->pipeline->run($promptForStyle, $llmOpts, [
                        'format' => 'plantuml',
                        'max_retries' => 0,
                    ]);
                    if (!empty($extra[0])) $collected[] = $extra[0];
                } catch (\Throwable $e) {
                    Log::debug('AIPlanningService: extra explore failed: ' . $e->getMessage());
                }
            }
            $textOut = $this->mergePlantUmlVariants($collected, $textOut);
        }

        // If the pipeline returned nothing useful, fallback to building PlantUML from structured architecture
        $textOut = trim($textOut);
        if (!$textOut || $this->looksLikeInstruction($textOut)) {

            // For non-class diagrams, use a type-specific offline generator
            if ($diagramType !== 'class') {
                $textOut = $this->generateOfflineDiagram($text, $diagramType);
            }

            if (!$textOut) {
                // Heavy structured prompt (numbered sections + inline field lists)?
                if ($this->isDetailedStructuredPrompt($text)) {
                    if ($onProgress) { $onProgress('analyzing', 'Parsing structured specification…'); }
                    $parsed = $this->parseDetailedPromptToPlantUML($text);
                    if ($parsed) {
                        $textOut = $parsed;
                        AIPlan::create([
                            'board_id'    => $meta['board_id'] ?? null,
                            'title'       => $meta['title'] ?? 'generatePlantUML',
                            'input_text'  => $text,
                            'result_json' => ['plantuml' => $textOut],
                        ]);
                        if ($onProgress) { $onProgress('complete', 'Done'); }
                        return $textOut;
                    }
                }

                // Prefer the already-computed domain structure (zero LLM calls needed).
                if (!empty($domainStructure['database_entities'])) {
                    $textOut = $this->buildPlantUmlFromStructure($domainStructure, $text);
                } else {
                    $structured = $this->generateArchitecture($text, $meta);
                    if (empty($structured['database_entities'])) {
                        $structured = $this->parsePromptToStructure($text);
                    }
                    $textOut = $this->buildPlantUmlFromStructure($structured, $text);
                }
            }

            if (!empty($meta['include_ai'])) {
                $textOut = $this->appendAiSection($textOut);
            }
        }

        AIPlan::create([
            'board_id' => $meta['board_id'] ?? null,
            'title' => $meta['title'] ?? 'generatePlantUML',
            'input_text' => $text,
            'result_json' => ['plantuml' => $textOut],
        ]);

        return $textOut;
    }

    /**
     * Append the AI classes & relations section into an existing PlantUML document.
     */
    protected function appendAiSection(string $plantuml): string
    {
        // remove trailing @enduml if present
        $trim = rtrim($plantuml);
        if (substr($trim, -7) === '@enduml') {
            $trim = substr($trim, 0, -7);
        }

        $section = "\n' ========================\n' AI & GENERATION\n' ========================\n\n";
        $section .= "class DiagramGenerator {\n  +generateFromText(input: string): Diagram\n  +generateFromCode(code: string): Diagram\n}\n\n";
        $section .= "class ValidationService {\n  +validateSyntax(diagram: Diagram): bool\n  +checkConsistency(diagram: Diagram): bool\n}\n\n";
        $section .= "' AI Relations\n";
        $section .= "DiagramGenerator ..> Diagram\n";
        $section .= "ValidationService ..> Diagram\n";

        return $trim . "\n" . $section . "@enduml";
    }

    protected function looksLikeInstruction(string $s): bool
    {
        $low = strtolower($s);
        return str_contains($low, 'return only') || str_contains($low, 'original instruction') || str_contains($low, 'previous response') || str_contains($low, 'do not include');
    }

    // ════════════════════════════════════════════════
    //  OFFLINE DIAGRAM GENERATOR (non-class types)
    // ════════════════════════════════════════════════

    /**
     * Generate a plausible PlantUML diagram for the given type without an LLM,
     * by extracting nouns/verbs from the user's text to build actors, flows, states, etc.
     */
    protected function generateOfflineDiagram(string $text, string $type): string
    {
        $low = strtolower($text);

        // ── Extract useful tokens ────────────────────────────────────────────
        // Capitalised nouns → potential actors / entities
        preg_match_all('/\b([A-Z][a-z]{2,}(?:\s[A-Z][a-z]{2,})?)\b/', $text, $nounM);
        $nouns = array_values(array_unique($nounM[1] ?? []));
        // Remove common English stop words
        $stop = ['The','This','That','With','For','And','But','Generate','Create','Show','Using','System','Diagram','Complete','Based','Include','Each'];
        $nouns = array_values(array_filter($nouns, fn($n) => !in_array($n, $stop) && strlen($n) > 2));
        $nouns = array_slice($nouns, 0, 14);

        // Verb phrases for actions
        preg_match_all('/\b(create|add|update|delete|send|receive|request|respond|login|logout|register|book|pay|cancel|confirm|approve|reject|process|generate|validate|notify|upload|download|search|list|view|manage|assign|submit)\b/i', $text, $verbM);
        $verbs = array_values(array_unique(array_map('strtolower', $verbM[1] ?? [])));
        $verbs = array_slice($verbs, 0, 10);
        if (empty($verbs)) $verbs = ['request', 'process', 'respond', 'confirm'];

        // Derive a system title from the text
        $title = 'System';
        if (preg_match('/\bfor\s+(?:a\s+|an\s+)?(.{3,40}?)(?:\.|,|$)/i', $text, $tm)) {
            $title = ucwords(trim($tm[1]));
        } elseif (!empty($nouns)) {
            $title = implode(' ', array_slice($nouns, 0, 3));
        }

        switch ($type) {
            case 'sequence':
                return $this->buildOfflineSequence($text, $nouns, $verbs, $title, $low);
            case 'usecase':
                return $this->buildOfflineUseCase($text, $nouns, $verbs, $title, $low);
            case 'er':
                return $this->buildOfflineER($text, $nouns, $title, $low);
            case 'activity':
                return $this->buildOfflineActivity($text, $verbs, $title, $low);
            case 'state':
                return $this->buildOfflineState($text, $nouns, $verbs, $title, $low);
            case 'component':
                return $this->buildOfflineComponent($text, $nouns, $title, $low);
            default:
                return '';
        }
    }

    // ── Sequence ─────────────────────────────────────────────────────────────
    private function buildOfflineSequence(string $text, array $nouns, array $verbs, string $title, string $low): string
    {
        // Identify actors vs systems from the nouns
        $actorKeywords = ['user','customer','admin','client','employee','manager','guest','staff','doctor','patient','vendor','buyer','seller'];
        $actors  = [];
        $systems = [];
        foreach ($nouns as $n) {
            if (in_array(strtolower($n), $actorKeywords)) $actors[]  = $n;
            else                                           $systems[] = $n;
        }
        if (empty($actors))  $actors  = ['User'];
        if (empty($systems)) $systems = [$title . 'System'];
        $actors  = array_slice($actors,  0, 3);
        $systems = array_slice($systems, 0, 5);

        $L = [];
        $L[] = '@startuml';
        $L[] = "title {$title} — Sequence Diagram";
        $L[] = '';
        foreach ($actors  as $a) { $L[] = "actor \"{$a}\" as " . preg_replace('/\s+/', '', $a); }
        foreach ($systems as $s) { $L[] = "participant \"{$s}\" as " . preg_replace('/\s+/', '', $s); }
        $L[] = '';

        $actorId  = preg_replace('/\s+/', '', $actors[0]);
        $systemId = preg_replace('/\s+/', '', $systems[0]);
        $dbId     = count($systems) > 1 ? preg_replace('/\s+/', '', $systems[1]) : 'Database';
        if (count($systems) === 1) {
            $L[] = "participant \"Database\" as Database";
        }

        // Build realistic flows from detected verbs
        $flows = [];
        $actionMap = [
            'login'    => ["{$actorId} -> {$systemId}: login(username, password)", "{$systemId} -> Database: validateCredentials()", "Database --> {$systemId}: user record", "alt valid credentials", "  {$systemId} --> {$actorId}: JWT token", "else invalid", "  {$systemId} --> {$actorId}: 401 Unauthorized", "end"],
            'register' => ["{$actorId} -> {$systemId}: register(data)", "{$systemId} -> {$systemId}: validateInput(data)", "{$systemId} -> Database: createUser(data)", "Database --> {$systemId}: userId", "{$systemId} --> {$actorId}: 201 Created"],
            'create'   => ["{$actorId} -> {$systemId}: create(data)", "{$systemId} -> {$systemId}: validate(data)", "{$systemId} -> Database: insert(data)", "Database --> {$systemId}: newId", "{$systemId} --> {$actorId}: 201 Created { id }"],
            'update'   => ["{$actorId} -> {$systemId}: update(id, data)", "{$systemId} -> Database: findById(id)", "Database --> {$systemId}: record", "{$systemId} -> Database: save(data)", "Database --> {$systemId}: ok", "{$systemId} --> {$actorId}: 200 Updated"],
            'delete'   => ["{$actorId} -> {$systemId}: delete(id)", "{$systemId} -> Database: findById(id)", "alt record exists", "  {$systemId} -> Database: softDelete(id)", "  {$systemId} --> {$actorId}: 200 Deleted", "else not found", "  {$systemId} --> {$actorId}: 404 Not Found", "end"],
            'search'   => ["{$actorId} -> {$systemId}: search(query, filters)", "{$systemId} -> Database: query(filters)", "Database --> {$systemId}: results[]", "{$systemId} --> {$actorId}: 200 { results, total, page }"],
            'list'     => ["{$actorId} -> {$systemId}: list(page, perPage)", "{$systemId} -> Database: paginate(page, perPage)", "Database --> {$systemId}: items[]", "{$systemId} --> {$actorId}: 200 { items, meta }"],
            'pay'      => ["{$actorId} -> {$systemId}: pay(orderId, method)", "activate {$systemId}", "{$systemId} -> {$dbId}: getOrder(orderId)", "{$dbId} --> {$systemId}: order", "{$systemId} -> {$dbId}: processPayment(method, amount)", "{$dbId} --> {$systemId}: transactionId", "{$systemId} --> {$actorId}: 200 { transactionId }", "deactivate {$systemId}"],
            'book'     => ["{$actorId} -> {$systemId}: book(resourceId, dates)", "{$systemId} -> Database: checkAvailability(resourceId, dates)", "Database --> {$systemId}: available", "alt available", "  {$systemId} -> Database: createBooking(data)", "  {$systemId} --> {$actorId}: bookingId", "else unavailable", "  {$systemId} --> {$actorId}: 409 Conflict", "end"],
            'send'     => ["{$actorId} -> {$systemId}: send(payload)", "{$systemId} -> {$systemId}: validate(payload)", "{$systemId} ->> {$dbId}: asyncProcess(payload)", "{$systemId} --> {$actorId}: 202 Accepted"],
            'notify'   => ["{$systemId} -> {$dbId}: getSubscribers()", "{$dbId} --> {$systemId}: subscribers[]", "loop each subscriber", "  {$systemId} ->> {$actorId}: notify(message)", "end"],
            'approve'  => ["{$actorId} -> {$systemId}: approve(requestId)", "{$systemId} -> Database: updateStatus(requestId, 'approved')", "Database --> {$systemId}: ok", "{$systemId} ->> {$actorId}: notification(approved)", "{$systemId} --> {$actorId}: 200 Approved"],
            'validate' => ["{$actorId} -> {$systemId}: submit(data)", "{$systemId} -> {$systemId}: validate(data)", "alt valid", "  {$systemId} -> Database: save(data)", "  {$systemId} --> {$actorId}: 200 OK", "else invalid", "  {$systemId} --> {$actorId}: 422 Validation Error", "end"],
        ];

        foreach ($verbs as $v) {
            if (isset($actionMap[$v]) && !in_array($v, array_keys($flows))) {
                $flows[$v] = $actionMap[$v];
                if (count($flows) >= 4) break;
            }
        }
        if (empty($flows)) {
            $flows['request'] = ["{$actorId} -> {$systemId}: request(data)", "{$systemId} -> Database: process(data)", "Database --> {$systemId}: result", "{$systemId} --> {$actorId}: response"];
        }

        foreach ($flows as $verb => $lines) {
            $L[] = "== " . ucfirst($verb) . " ==";
            foreach ($lines as $line) { $L[] = $line; }
            $L[] = '';
        }

        $L[] = '@enduml';
        return implode("\n", $L);
    }

    // ── Use Case ──────────────────────────────────────────────────────────────
    private function buildOfflineUseCase(string $text, array $nouns, array $verbs, string $title, string $low): string
    {
        $actorKeywords = ['user','customer','admin','client','employee','manager','guest','staff','doctor','patient','vendor','buyer','seller','operator'];
        $actors = [];
        foreach ($nouns as $n) {
            if (in_array(strtolower($n), $actorKeywords)) $actors[] = $n;
        }
        if (empty($actors)) $actors = ['User', 'Admin'];
        $actors = array_slice($actors, 0, 4);

        $useCases = [];
        $actionLabels = ['login'=>'Login','register'=>'Register','create'=>'Create Record','update'=>'Update Record','delete'=>'Delete Record','view'=>'View Details','list'=>'Browse List','search'=>'Search','book'=>'Make Booking','pay'=>'Process Payment','cancel'=>'Cancel','confirm'=>'Confirm','approve'=>'Approve Request','reject'=>'Reject Request','upload'=>'Upload File','download'=>'Download File','send'=>'Send Message','notify'=>'Receive Notification','manage'=>'Manage Settings','assign'=>'Assign Task','submit'=>'Submit Form','generate'=>'Generate Report','validate'=>'Validate Data'];
        foreach ($verbs as $v) {
            if (isset($actionLabels[$v])) $useCases[] = $actionLabels[$v];
        }
        if (empty($useCases)) $useCases = ['Manage ' . $title, 'View Records', 'Generate Report'];
        $useCases = array_slice(array_unique($useCases), 0, 10);

        $L = [];
        $L[] = '@startuml';
        $L[] = "left to right direction";
        $L[] = "title {$title} — Use Case Diagram";
        $L[] = '';
        foreach ($actors as $a) { $L[] = "actor \"" . $a . "\" as " . preg_replace('/\s+/', '', $a); }
        $L[] = '';
        $L[] = "rectangle \"{$title}\" {";
        foreach ($useCases as $uc) {
            $ucId = preg_replace('/[^A-Za-z0-9]/', '', $uc);
            $L[] = "  usecase \"{$uc}\" as UC_{$ucId}";
        }
        $L[] = '}';
        $L[] = '';

        // Primary actor gets all use cases; last actor (if admin) gets management ones
        $primaryActor = preg_replace('/\s+/', '', $actors[0]);
        foreach ($useCases as $uc) {
            $ucId = preg_replace('/[^A-Za-z0-9]/', '', $uc);
            $L[] = "{$primaryActor} --> UC_{$ucId}";
        }
        if (count($actors) > 1) {
            $adminActor = preg_replace('/\s+/', '', $actors[count($actors) - 1]);
            $adminCases = array_filter($useCases, fn($u) => preg_match('/Manage|Approve|Reject|Generate|Assign/i', $u));
            foreach ($adminCases as $uc) {
                $ucId = preg_replace('/[^A-Za-z0-9]/', '', $uc);
                $L[] = "{$adminActor} --> UC_{$ucId}";
            }
        }
        $L[] = '';
        $L[] = '@enduml';
        return implode("\n", $L);
    }

    // ── ER Diagram ────────────────────────────────────────────────────────────
    private function buildOfflineER(string $text, array $nouns, string $title, string $low): string
    {
        // Use domain structure for ER — it gives us typed fields
        $domain = $this->detectDomainStructure($text);
        $entities = $domain['database_entities'] ?? [];

        // Fall back: use extracted nouns as entity stubs
        if (empty($entities)) {
            foreach (array_slice($nouns, 0, 8) as $n) {
                $entities[] = ['name' => preg_replace('/\s+/', '', $n), 'fields' => [
                    ['name' => 'id',         'type' => 'INT'],
                    ['name' => 'name',        'type' => 'VARCHAR(255)'],
                    ['name' => 'created_at',  'type' => 'DATETIME'],
                ]];
            }
        }
        if (empty($entities)) { $entities = [['name'=>$title,'fields'=>[['name'=>'id','type'=>'INT'],['name'=>'name','type'=>'VARCHAR(255)']]]];}

        $L = [];
        $L[] = '@startuml';
        $L[] = "!define TABLE(x) entity x << (T,#FFAAAA) >>";
        $L[] = "hide empty methods";
        $L[] = "title {$title} — ER Diagram";
        $L[] = '';

        $names = [];
        foreach ($entities as $ent) {
            $name = $ent['name'] ?? 'Entity';
            $names[] = $name;
            $L[] = "entity \"{$name}\" {";
            $hasPk = false;
            foreach ($ent['fields'] ?? [] as $f) {
                $fn = is_array($f) ? ($f['name'] ?? 'field') : $f;
                $ft = is_array($f) ? strtoupper($f['type'] ?? 'VARCHAR') : 'VARCHAR';
                if ($fn === 'id') { $L[] = "  * {$fn} : {$ft} <<PK>>"; $hasPk = true; }
                else               { $L[] = "  {$fn} : {$ft}"; }
            }
            if (!$hasPk) { array_splice($L, count($L) - count($ent['fields'] ?? []), 0, ["  * id : INT <<PK>>"]); }
            $L[] = '}';
            $L[] = '';
        }

        // Relations from FK fields
        foreach ($entities as $ent) {
            $src = $ent['name'] ?? '';
            foreach ($ent['fields'] ?? [] as $f) {
                $fn = is_array($f) ? ($f['name'] ?? '') : $f;
                if (preg_match('/^(.+)_id$/', $fn, $m)) {
                    $target = implode('', array_map('ucfirst', explode('_', $m[1])));
                    if (in_array($target, $names)) {
                        $L[] = "{$src} }o--|| {$target} : \"belongs to\"";
                    }
                }
            }
        }
        $L[] = '';
        $L[] = '@enduml';
        return implode("\n", $L);
    }

    // ── Activity ──────────────────────────────────────────────────────────────
    private function buildOfflineActivity(string $text, array $verbs, string $title, string $low): string
    {
        $L = [];
        $L[] = '@startuml';
        $L[] = "skinparam ActivityBackgroundColor #f0f4ff";
        $L[] = "skinparam ActivityBorderColor #6366f1";
        $L[] = "skinparam ArrowColor #6366f1";
        $L[] = "title {$title} — Activity Diagram";
        $L[] = '';
        $L[] = 'start';
        $L[] = '';

        // ── 1. Extract numbered steps ──────────────────────────────────────────
        preg_match_all('/^\s*\d+[\.\)]\s+(.+)$/m', $text, $stepMatches);
        $rawSteps = array_map('trim', $stepMatches[1] ?? []);

        if (count($rawSteps) >= 3) {
            // ── 2. Classify each step ──────────────────────────────────────────
            // Type: 'normal' | 'if_fail' | 'if_success' | 'if_reject' | 'if_confirm'
            $classified = [];
            foreach ($rawSteps as $step) {
                $type = 'normal';
                if (preg_match('/^\s*if\b/i', $step)) {
                    // Order matters: check reject/confirm before generic success/fail
                    if (preg_match('/\breject\w*/i', $step)) {
                        $type = 'if_reject';
                    } elseif (preg_match('/\bconfirm\w*/i', $step)) {
                        $type = 'if_confirm';
                    } elseif (preg_match('/\b(fail|error|invalid|unavailable|not.found|declined|denied|timeout)\w*/i', $step)) {
                        $type = 'if_fail';
                    } elseif (preg_match('/\b(succeed|success|valid|available|found|approved)\w*/i', $step)) {
                        $type = 'if_success';
                    }
                }
                $classified[] = ['step' => $step, 'type' => $type];
            }

            // ── 3. Pair consecutive if_fail/if_success and if_reject/if_confirm ──
            // Strategy: when we see if_fail immediately followed by if_success (or vice-versa),
            // wrap them in if/else/endif around the decision node before them.
            // Build an output plan first, then emit.
            $plan = []; // [ ['kind'=>'action'|'decision', ...] ]
            $i = 0;
            $n = count($classified);
            while ($i < $n) {
                $cur  = $classified[$i];
                $next = $classified[$i + 1] ?? null;

                // Pair: if_fail + if_success  (or reversed)
                $isPair = $next && (
                    ($cur['type'] === 'if_fail'    && in_array($next['type'], ['if_success','if_confirm'])) ||
                    ($cur['type'] === 'if_success' && in_array($cur['type'],  ['if_fail','if_reject']))     ||
                    ($cur['type'] === 'if_reject'  && in_array($next['type'], ['if_confirm','if_success'])) ||
                    ($cur['type'] === 'if_confirm' && in_array($next['type'], ['if_reject','if_fail']))
                );

                if ($isPair) {
                    // Determine which is success/fail branch
                    if (in_array($cur['type'], ['if_fail', 'if_reject'])) {
                        $failStep    = $cur['step'];
                        $successStep = $next['step'];
                        $condLabel   = in_array($cur['type'], ['if_reject']) ? 'Order confirmed' : 'Payment valid';
                    } else {
                        $successStep = $cur['step'];
                        $failStep    = $next['step'];
                        $condLabel   = in_array($cur['type'], ['if_confirm']) ? 'Order confirmed' : 'Payment valid';
                    }
                    $plan[] = ['kind' => 'decision', 'cond' => $condLabel, 'yes' => $successStep, 'no' => $failStep];
                    $i += 2;
                    continue;
                }

                // Lone conditional step — treat as plain action
                $plan[] = ['kind' => 'action', 'step' => $cur['step'], 'type' => $cur['type']];
                $i++;
            }

            // ── 4. Emit PlantUML ───────────────────────────────────────────────
            foreach ($plan as $item) {
                if ($item['kind'] === 'decision') {
                    $cond = $item['cond'];
                    $yes  = $this->cleanActivityLabel($item['yes']);
                    $no   = $this->cleanActivityLabel($item['no']);
                    $L[] = "if ({$cond}?) then (yes)";
                    $L[] = "  :{$yes};";
                    $L[] = "else (no)";
                    $L[] = "  :{$no};";
                    $L[] = "endif";
                } else {
                    $label    = $this->cleanActivityLabel($item['step']);
                    $actor    = $this->detectActivityActor($item['step']);
                    $L[] = ":{$label};";
                }
                $L[] = '';
            }

        } else {
            // ── Fallback: verb-based steps ─────────────────────────────────────
            $actionLabels = [
                'login'=>'Authenticate User','register'=>'Register Account',
                'create'=>'Create Record','update'=>'Update Record',
                'delete'=>'Delete Record','view'=>'View Details',
                'search'=>'Search Records','book'=>'Make Booking',
                'pay'=>'Process Payment','cancel'=>'Cancel Request',
                'confirm'=>'Confirm Action','approve'=>'Approve Request',
                'reject'=>'Reject Request','upload'=>'Upload File',
                'send'=>'Send Notification','validate'=>'Validate Input',
                'submit'=>'Submit Form','assign'=>'Assign Resource',
                'notify'=>'Notify User','process'=>'Process Data',
            ];
            $steps = [];
            foreach ($verbs as $v) {
                if (isset($actionLabels[$v])) $steps[] = $actionLabels[$v];
            }
            if (empty($steps)) $steps = ['Validate Input','Process Request','Return Result'];
            $steps = array_slice(array_unique($steps), 0, 8);

            $hasValidate = in_array('Validate Input', $steps);
            foreach ($steps as $i => $step) {
                if ($step === 'Validate Input') {
                    $L[] = ":{$step};";
                    $L[] = "if (Valid?) then (yes)";
                } elseif ($i === count($steps) - 1) {
                    $L[] = "  :{$step};";
                    if ($hasValidate) { $L[] = "else (no)"; $L[] = "  :Return Validation Error;"; $L[] = "endif"; }
                } else {
                    $L[] = ($hasValidate && $i > 0) ? "  :{$step};" : ":{$step};";
                }
            }
        }

        $L[] = 'stop';
        $L[] = '@enduml';
        return implode("\n", $L);
    }

    private function cleanActivityLabel(string $step): string
    {
        // Strip leading "If X [fails|succeeds], " prefix from conditional steps
        $label = preg_replace('/^\s*if\s+\w[\w\s]*?,\s*/i', '', $step);
        // Strip leading actor mentions
        $label = preg_replace('/^(customer|user|system|restaurant|delivery agent|agent|driver|admin)\s+/i', '', $label);
        $label = ucfirst(trim($label));
        // Truncate very long labels
        if (strlen($label) > 80) $label = substr($label, 0, 77) . '...';
        return $label;
    }

    private function detectActivityActor(string $step): string
    {
        if (preg_match('/\b(customer|user|client)\b/i', $step)) return 'Customer';
        if (preg_match('/\b(restaurant|vendor|merchant)\b/i', $step)) return 'Restaurant';
        if (preg_match('/\b(delivery|agent|driver|courier)\b/i', $step)) return 'Delivery';
        if (preg_match('/\b(payment|bank|gateway)\b/i', $step)) return 'Payment';
        if (preg_match('/\b(system|server|app|platform)\b/i', $step)) return 'System';
        if (preg_match('/\b(admin|manager|staff)\b/i', $step)) return 'Admin';
        return '';
    }

    // ── State Machine ─────────────────────────────────────────────────────────
    private function buildOfflineState(string $text, array $nouns, array $verbs, string $title, string $low): string
    {
        // Detect common status / state words in text
        preg_match_all('/\b(pending|active|inactive|approved|rejected|cancelled|completed|draft|published|archived|processing|shipped|delivered|open|closed|suspended|expired|paid|unpaid|confirmed|failed|running|stopped|idle)\b/i', $text, $stateM);
        $states = array_values(array_unique(array_map('ucfirst', array_map('strtolower', $stateM[1] ?? []))));

        if (count($states) < 3) {
            // Use nouns as state labels fallback
            $domainStates = ['Pending','Active','Processing','Completed','Cancelled'];
            $states = $domainStates;
        }
        $states = array_slice($states, 0, 8);

        $L = [];
        $L[] = '@startuml';
        $L[] = "title {$title} — State Diagram";
        $L[] = '';
        $L[] = "[*] --> {$states[0]}";
        $L[] = '';

        for ($i = 0; $i < count($states); $i++) {
            $cur  = $states[$i];
            $next = $states[$i + 1] ?? null;
            if ($next) {
                $trigger = $verbs[$i] ?? 'proceed';
                $L[] = "{$cur} --> {$next} : " . ucfirst($trigger);
            }
            // Add backwards/cancel transition
            if ($i > 0 && isset($states[$i - 1]) && in_array(strtolower($cur), ['cancelled','rejected','failed','closed'])) {
                $L[] = "{$cur} --> [*]";
            }
        }
        $L[] = '';
        $last = end($states);
        $L[] = "{$last} --> [*]";
        $L[] = '';
        $L[] = '@enduml';
        return implode("\n", $L);
    }

    // ── Component ─────────────────────────────────────────────────────────────
    private function buildOfflineComponent(string $text, array $nouns, string $title, string $low): string
    {
        // Group nouns into layers
        $frontendWords = ['ui','frontend','web','mobile','app','client','browser','dashboard','interface','portal'];
        $backendWords  = ['api','service','server','backend','controller','handler','engine','processor','gateway','queue'];
        $dataWords     = ['database','db','cache','storage','repository','store','redis','mysql','mongo','postgres','elasticsearch'];
        $externalWords = ['email','sms','payment','stripe','paypal','twilio','aws','s3','firebase','oauth','jwt','ldap'];

        $front = $back = $data = $ext = [];
        foreach ($nouns as $n) {
            $nl = strtolower($n);
            if (array_filter($frontendWords, fn($w) => str_contains($nl, $w))) $front[] = $n;
            elseif (array_filter($dataWords,    fn($w) => str_contains($nl, $w))) $data[]  = $n;
            elseif (array_filter($externalWords,fn($w) => str_contains($nl, $w))) $ext[]   = $n;
            else                                                                    $back[]  = $n;
        }

        // Ensure at minimum one item per layer
        if (empty($front)) $front = ['WebClient'];
        if (empty($back))  $back  = [$title . 'API', $title . 'Service'];
        if (empty($data))  $data  = ['Database', 'Cache'];
        $back  = array_slice($back,  0, 5);
        $front = array_slice($front, 0, 3);
        $ext   = array_slice($ext,   0, 3);

        $L = [];
        $L[] = '@startuml';
        $L[] = "title {$title} — Component Diagram";
        $L[] = '';

        $L[] = 'package "Frontend" {';
        foreach ($front as $c) { $L[] = "  [" . preg_replace('/\s+/', '', $c) . "]"; }
        $L[] = '}';
        $L[] = '';
        $L[] = 'package "Backend" {';
        foreach ($back as $c) { $L[] = "  [" . preg_replace('/\s+/', '', $c) . "]"; }
        $L[] = '}';
        $L[] = '';
        $L[] = 'package "Data Layer" {';
        foreach ($data as $c) { $L[] = "  [" . preg_replace('/\s+/', '', $c) . "]"; }
        $L[] = '}';
        if (!empty($ext)) {
            $L[] = '';
            $L[] = 'package "External Services" {';
            foreach ($ext as $c) { $L[] = "  [" . preg_replace('/\s+/', '', $c) . "]"; }
            $L[] = '}';
        }
        $L[] = '';

        // Connections: front → back → data
        $frontId = preg_replace('/\s+/', '', $front[0]);
        $backId  = preg_replace('/\s+/', '', $back[0]);
        $dataId  = preg_replace('/\s+/', '', $data[0]);
        $L[] = "[{$frontId}] --> [{$backId}] : HTTP/REST";
        if (count($back) > 1) {
            $back2 = preg_replace('/\s+/', '', $back[1]);
            $L[] = "[{$backId}] --> [{$back2}] : internal";
            $L[] = "[{$back2}] --> [{$dataId}] : query";
        } else {
            $L[] = "[{$backId}] --> [{$dataId}] : query";
        }
        if (count($data) > 1) {
            $cache = preg_replace('/\s+/', '', $data[1]);
            $L[] = "[{$backId}] --> [{$cache}] : cache";
        }
        foreach ($ext as $e) {
            $eId = preg_replace('/\s+/', '', $e);
            $L[] = "[{$backId}] --> [{$eId}] : API call";
        }

        $L[] = '';
        $L[] = '@enduml';
        return implode("\n", $L);
    }

    /**
     * Merge multiple PlantUML variant outputs into a single PlantUML text.
     * This keeps the primary output and appends any class blocks or relations
     * from other variants that are not already present.
     */
    protected function mergePlantUmlVariants(array $variants, string $primary): string
    {
        // collect existing classes and relations from primary
        $primaryClasses = $this->extractPlantUmlClassNames($primary);
        $primaryRelations = $this->extractPlantUmlRelations($primary);

        $appendClasses = [];
        $appendRelations = [];

        foreach ($variants as $v) {
            if (!$v || $v === $primary) continue;
            // extract class blocks
            preg_match_all('/class\s+([A-Za-z0-9_]+)\s*\{([^}]*)\}/s', $v, $m, PREG_SET_ORDER);
            foreach ($m as $blk) {
                $name = $blk[1];
                $block = trim($blk[0]);
                if (!in_array($name, $primaryClasses) && !isset($appendClasses[$name])) {
                    $appendClasses[$name] = $block;
                }
            }
            // extract relation lines (simple heuristic: lines not starting with 'class' or comment)
            $lines = preg_split('/\r?\n/', $v);
            foreach ($lines as $ln) {
                $l = trim($ln);
                if ($l === '' || str_starts_with($l, "'") || preg_match('/^class\s+/', $l)) continue;
                // relation-like lines often contain -- or ..>
                if (str_contains($l, '--') || str_contains($l, '..>') || str_contains($l, '->')) {
                    if (!in_array($l, $primaryRelations) && !in_array($l, $appendRelations)) {
                        $appendRelations[] = $l;
                    }
                }
            }
        }

        // Insert appended classes before the MANAGEMENT FEATURES or RELATIONS section if possible
        $insertPos = strpos($primary, "' MANAGEMENT FEATURES");
        if ($insertPos === false) $insertPos = strrpos($primary, "@enduml");
        if ($insertPos === false) $insertPos = strlen($primary);

        $toInsert = "\n";
        foreach ($appendClasses as $blk) {
            $toInsert .= $blk . "\n\n";
        }

        // append relations before @enduml
        $final = substr($primary, 0, $insertPos) . $toInsert . substr($primary, $insertPos);

        // ensure relations appended near end but before @enduml
        $endPos = strrpos($final, "@enduml");
        if ($endPos === false) $endPos = strlen($final);
        $relationsText = "\n" . implode("\n", $appendRelations) . "\n";
        $final = substr($final, 0, $endPos) . $relationsText . substr($final, $endPos);

        // basic cleanup: remove duplicated blank lines
        $final = preg_replace('/\n{3,}/', "\n\n", $final);

        return $final;
    }

    protected function extractPlantUmlClassNames(string $text): array
    {
        $names = [];
        preg_match_all('/class\s+([A-Za-z0-9_]+)\s*\{/', $text, $m);
        if (!empty($m[1])) {
            foreach ($m[1] as $n) $names[] = $n;
        }
        return $names;
    }

    protected function extractPlantUmlRelations(string $text): array
    {
        $rels = [];
        $lines = preg_split('/\r?\n/', $text);
        foreach ($lines as $ln) {
            $l = trim($ln);
            if ($l === '' || str_starts_with($l, "'")) continue;
            if (str_contains($l, '--') || str_contains($l, '..>') || str_contains($l, '->')) {
                $rels[] = $l;
            }
        }
        return $rels;
    }

    protected function buildPlantUmlFromStructure(array $s, string $fallbackTitle = ''): string
    {
        $lines = [];
        $lines[] = '@startuml';

        $hasRealEntities = !empty($s['database_entities']) && is_array($s['database_entities']) && count($s['database_entities']) > 0;

        // ── ENUMERATIONS (from domain structure) ──
        if (!empty($s['enums']) && is_array($s['enums'])) {
            $lines[] = "' ========================";
            $lines[] = "' ENUMERATIONS";
            $lines[] = "' ========================\n";
            foreach ($s['enums'] as $enumName => $values) {
                if (!is_string($enumName) || !is_array($values)) continue;
                $lines[] = "enum {$enumName} {";
                foreach ($values as $val) {
                    $lines[] = "  {$val}";
                }
                $lines[] = "}\n";
            }
        }

        $lines[] = "' ========================";
        $lines[] = "' CORE ENTITIES";
        $lines[] = "' ========================\n";

        // Emit core entities from structured database_entities, avoiding duplicates
        $defined = [];
        if ($hasRealEntities) {
            foreach ($s['database_entities'] as $ent) {
                $name = preg_replace('/[^A-Za-z0-9_]/', '', ($ent['name'] ?? ($ent[0] ?? 'Entity')));
                if (in_array($name, $defined)) continue;
                $defined[] = $name;
                $lines[] = "class {$name} {";
                $fields = $ent['fields'] ?? [];
                if (is_array($fields) && count($fields)) {
                    foreach ($fields as $f) {
                        if (is_string($f)) {
                            $lines[] = '  +' . trim($f);
                        } elseif (is_array($f)) {
                            $fn = $f['name'] ?? ($f[0] ?? 'field');
                            $ft = $f['type'] ?? ($f['datatype'] ?? 'string');
                            $lines[] = '  +' . trim($fn) . ': ' . trim($ft);
                        }
                    }
                } else {
                    // minimal placeholder
                    $lines[] = '  +id: int';
                }
                // Emit methods when present
                if (!empty($ent['methods']) && is_array($ent['methods'])) {
                    foreach ($ent['methods'] as $method) {
                        $lines[] = '  +' . trim($method);
                    }
                }
                $lines[] = "}\n";
            }
        } else {
            // default sample entities if none parsed
            $lines[] = 'class User {';
            $lines[] = '  +id: int';
            $lines[] = '  +name: string';
            $lines[] = '  +email: string';
            $lines[] = '  +password: string';
            $lines[] = "}\n";

            $lines[] = 'class Project {';
            $lines[] = '  +id: int';
            $lines[] = '  +name: string';
            $lines[] = '  +description: text';
            $lines[] = '  +created_at: datetime';
            $lines[] = "}\n";

            $lines[] = 'class Diagram {';
            $lines[] = '  +id: int';
            $lines[] = '  +title: string';
            $lines[] = '  +type: string';
            $lines[] = '  +content: text';
            $lines[] = '  +version: int';
            $lines[] = '  +created_at: datetime';
            $lines[] = '  +updated_at: datetime';
            $lines[] = "}\n";
        }

        // MANAGEMENT FEATURES section - emit classes based on provided modules or sensible defaults
        $lines[] = "' ========================";
        $lines[] = "' MANAGEMENT FEATURES";
        $lines[] = "' ========================\n";

        $moduleTemplates = [
            'DiagramVersion' => [
                'class DiagramVersion {',
                '  +id: int',
                '  +diagram_id: int',
                '  +content: text',
                '  +version_number: int',
                '  +created_at: datetime',
                '}',
            ],
            'NoteVersion' => [
                'class NoteVersion {',
                '  +id: int',
                '  +note_id: int',
                '  +content: text',
                '  +version_number: int',
                '  +created_at: datetime',
                '}',
            ],
            'Comment' => [
                'class Comment {',
                '  +id: int',
                '  +content: text',
                '  +user_id: int',
                '  +note_id: int',
                '  +task_id: int',
                '  +created_at: datetime',
                '}',
            ],
            'Tag' => [
                'class Tag {',
                '  +id: int',
                '  +name: string',
                '}',
            ],
            'DiagramTag' => [
                'class DiagramTag {',
                '  +diagram_id: int',
                '  +tag_id: int',
                '}',
            ],
            'NoteTag' => [
                'class NoteTag {',
                '  +note_id: int',
                '  +tag_id: int',
                '}',
            ],
            'AccessControl' => [
                'class AccessControl {',
                '  +id: int',
                '  +resource_id: int',
                '  +user_id: int',
                '  +role: string',
                '}',
            ],
            'ShareLink' => [
                'class ShareLink {',
                '  +id: int',
                '  +resource_id: int',
                '  +token: string',
                '  +expires_at: datetime',
                '}',
            ],
            'Release' => [
                'class Release {',
                '  +id: int',
                '  +version: string',
                '  +released_at: datetime',
                '  +notes: text',
                '}',
            ],
        ];

        // Emit provided modules (handled below with deduplication)

        // If common management classes weren't provided, emit sensible defaults
        $emitted = [];
        if (!empty($s['modules']) && is_array($s['modules'])) {
            foreach ($s['modules'] as $mod) {
                $mn = is_string($mod) ? $mod : ($mod['name'] ?? null);
                if (!$mn) continue;
                if (in_array($mn, $emitted)) continue;
                if (isset($moduleTemplates[$mn])) {
                    foreach ($moduleTemplates[$mn] as $line) $lines[] = $line;
                    $lines[] = "\n";
                    $emitted[] = $mn;
                    $defined[] = $mn;
                } else {
                    if (!in_array($mn, $defined)) {
                        $lines[] = "class {$mn} {";
                        $lines[] = "  +...";
                        $lines[] = "}\n";
                        $emitted[] = $mn;
                        $defined[] = $mn;
                    }
                }
            }
        }

        // If common management classes weren't provided, emit sensible defaults (only if not already defined)
        // ONLY add generic defaults when there are no real domain entities (pure fallback mode).
        if (!$hasRealEntities) {
            $defaults = ['Comment','Tag','AccessControl','ShareLink'];
            foreach ($defaults as $dflt) {
                if (!in_array($dflt, $defined)) {
                    if (isset($moduleTemplates[$dflt])) {
                        foreach ($moduleTemplates[$dflt] as $line) $lines[] = $line;
                        $lines[] = "\n";
                        $defined[] = $dflt;
                    }
                }
            }
        }

        // AI & GENERATION section intentionally omitted from fallback output

        // RELATIONS section - derive relations from present entities when possible
        $lines[] = "' ========================";
        $lines[] = "' RELATIONS";
        $lines[] = "' ========================\n";

        // Use explicit relations when provided by domain structure (highest fidelity)
        if (!empty($s['relations']) && is_array($s['relations'])) {
            foreach ($s['relations'] as $rel) {
                if (is_string($rel)) $lines[] = $rel;
            }
            $lines[] = "\n@enduml";
            return implode("\n", $lines);
        }

        $hasUser = $this->entityExists($s, 'User');
        $hasNotebook = $this->entityExists($s, 'Notebook');
        $hasNote = $this->entityExists($s, 'Note');
        $hasTag = $this->entityExists($s, 'Tag');
        $hasComment = $this->entityExists($s, 'Comment');
        $hasDiagram = $this->entityExists($s, 'Diagram');
        $hasProject = $this->entityExists($s, 'Project');

        if ($hasUser && $hasNotebook) {
            $lines[] = "User \"1\" -- \"many\" Notebook";
        }
        if ($hasNotebook && $hasNote) {
            $lines[] = "Notebook \"1\" -- \"many\" Note\n";
        }
        if ($hasNote && $hasTag) {
            $lines[] = "Note \"many\" -- \"many\" Tag : NoteTag\n";
        }
        if ($hasNote && $hasComment) {
            $lines[] = "Note \"1\" -- \"many\" Comment";
        }

        // Fallbacks for project/diagram-style entities
        if ($hasUser && $hasProject) {
            $lines[] = "User \"1\" -- \"many\" Project";
        }
        if ($hasProject && $this->entityExists($s, 'Task')) {
            $lines[] = "Project \"1\" -- \"many\" Task";
        }
        if ($hasProject && $hasDiagram) {
            $lines[] = "Project \"1\" -- \"many\" Diagram\n";
        }

        if ($this->entityExists($s, 'Sprint') && $this->entityExists($s, 'Task')) {
            $lines[] = "Sprint \"1\" -- \"many\" Task";
        }
        if ($this->entityExists($s, 'Requirement') && $this->entityExists($s, 'Task')) {
            $lines[] = "Requirement \"1\" -- \"many\" Task";
        }
        if ($this->entityExists($s, 'Task') && $this->entityExists($s, 'Tag')) {
            $lines[] = "Task \"many\" -- \"many\" Tag : TaskTag\n";
        }
        if ($this->entityExists($s, 'Task') && $this->entityExists($s, 'Comment')) {
            $lines[] = "Task \"1\" -- \"many\" Comment";
        }

        if ($hasDiagram) {
            $lines[] = "Diagram \"1\" -- \"many\" DiagramVersion";
            if ($hasComment) $lines[] = "Diagram \"1\" -- \"many\" Comment";
            if ($this->entityExists($s, 'Tag')) $lines[] = "Diagram \"many\" -- \"many\" Tag : DiagramTag\n";
            $lines[] = "Diagram \"1\" -- \"many\" ShareLink";
            $lines[] = "Diagram \"1\" -- \"many\" AccessControl\n";
        }

        if ($hasUser && $hasComment) {
            $lines[] = "User \"1\" -- \"many\" Comment";
        }
        if ($hasUser && $this->entityExists($s, 'AccessControl')) {
            $lines[] = "User \"1\" -- \"many\" AccessControl\n";
        }

        // User/Role/Permission/Profile/Session relations
        if ($hasUser && $this->entityExists($s, 'Role')) {
            $lines[] = "User \"many\" -- \"many\" Role";
        }
        if ($this->entityExists($s, 'Role') && $this->entityExists($s, 'Permission')) {
            $lines[] = "Role \"many\" -- \"many\" Permission";
        }
        if ($hasUser && $this->entityExists($s, 'Profile')) {
            $lines[] = "User \"1\" -- \"1\" Profile";
        }
        if ($hasUser && $this->entityExists($s, 'Session')) {
            $lines[] = "User \"1\" -- \"many\" Session\n";
        }

        // Owner management system relations
        $hasOwner = $this->entityExists($s, 'Owner');
        $hasProperty = $this->entityExists($s, 'Property');
        $hasTenant = $this->entityExists($s, 'Tenant');
        $hasContract = $this->entityExists($s, 'Contract');
        $hasPayment = $this->entityExists($s, 'Payment');
        $hasMaintenance = $this->entityExists($s, 'MaintenanceRequest');

        if ($hasOwner && $hasProperty) {
            $lines[] = "Owner \"1\" -- \"many\" Property";
        }
        if ($hasProperty && $hasTenant) {
            $lines[] = "Property \"1\" -- \"many\" Contract";
        }
        if ($hasTenant && $hasContract) {
            $lines[] = "Tenant \"1\" -- \"many\" Contract";
        }
        if ($hasContract && $hasPayment) {
            $lines[] = "Contract \"1\" -- \"many\" Payment";
        }
        if ($hasProperty && $hasMaintenance) {
            $lines[] = "Property \"1\" -- \"many\" MaintenanceRequest";
        }
        if ($hasTenant && $hasMaintenance) {
            $lines[] = "Tenant \"1\" -- \"many\" MaintenanceRequest\n";
        }

        // AI & GENERATION relations omitted

        // Generic relation inference: infer foreign-key-based relations for
        // any entity that wasn't covered by the hardcoded checks above.
        $genericRels = $this->inferGenericRelations($s['database_entities'] ?? []);
        $existingLines = implode("\n", $lines);
        foreach ($genericRels as $rel) {
            // Only add if the relation line doesn't already exist
            if (!str_contains($existingLines, $rel)) {
                $lines[] = $rel;
            }
        }

        $lines[] = "\n@enduml";

        return implode("\n", $lines);
    }

    protected function entityExists(array $s, string $name): bool
    {
        if (empty($s['database_entities']) || !is_array($s['database_entities'])) return false;
        foreach ($s['database_entities'] as $ent) {
            $n = $ent['name'] ?? ($ent[0] ?? null);
            if (!$n) continue;
            if (strcasecmp($n, $name) === 0) return true;
        }
        return false;
    }

    // ════════════════════════════════════════════════
    //  HEAVY / STRUCTURED PROMPT PARSER
    // ════════════════════════════════════════════════

    /**
     * Detect whether a prompt is a rich structured specification
     * (numbered sections + inline field lists).  If so we can parse
     * it directly without an LLM.
     */
    protected function isDetailedStructuredPrompt(string $text): bool
    {
        // At least 2 numbered sections  (1. TITLE  /  1) TITLE)
        $sectionCount = preg_match_all('/^\s*\d+[\.\)]\s+[A-Z][A-Z &\/\-]+/m', $text);
        // At least 3 inline field specs  like  ClassName (field1, field2, ...)
        $classCount = preg_match_all('/\b[A-Z][a-zA-Z]+\s*\([a-z_][^)]{5,}\)/m', $text);
        return $sectionCount >= 2 && $classCount >= 3;
    }

    /**
     * Parse a rich, explicitly structured prompt and return valid PlantUML
     * without calling any LLM.  Handles:
     *   - Numbered sections  → @package / namespace blocks
     *   - `ClassName (field, field, ...)`  → class with typed fields
     *   - Parenthesised UPPER_CASE / short values  → enums
     *   - Foreign-key fields (`xxx_id`)  → associations
     *   - "inheritance / extends / implements" keywords → extends / implements
     *   - Explicit method patterns like  methodName()
     */
    protected function parseDetailedPromptToPlantUML(string $text): string
    {
        $lines       = [];
        $allPackages = [];   // [ 'PackageName' => [ classNames ] ]
        $allClasses  = [];   // [ name => [ 'fields'=>[], 'methods'=>[], 'abstract'=>bool ] ]
        $allEnums    = [];   // [ name => [ values ] ]
        $relations   = [];   // raw strings of PlantUML relation lines

        // ── 1. Normalise line endings ──────────────────────────────────────────
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // ── 2. Split into sections by "N. HEADING" or "N) HEADING" ──────────────
        $sectionPattern = '/^[ \t]*\d+[\.\)]\s+(.+)$/m';
        $sectionStarts  = [];
        preg_match_all($sectionPattern, $text, $sectionMatches, PREG_OFFSET_CAPTURE);

        if (!empty($sectionMatches[1])) {
            foreach ($sectionMatches[1] as $idx => $m) {
                $rawTitle = trim($m[0]);
                // Normalise title → PascalCase package name
                $pkg  = preg_replace('/[^A-Za-z0-9]/', '_', $rawTitle);
                $pkg  = preg_replace('/_+/', '_', trim($pkg, '_'));
                $pkg  = implode('', array_map('ucfirst', array_map('strtolower', explode('_', $pkg))));
                $sectionStarts[$idx] = [
                    'title'  => $rawTitle,
                    'pkg'    => $pkg,
                    'offset' => $sectionMatches[0][$idx][1],
                ];
            }
        }

        // Build per-section text slices
        $sectionBodies = [];
        $keys = array_keys($sectionStarts);
        foreach ($keys as $i => $idx) {
            $start  = $sectionStarts[$idx]['offset'];
            $end    = isset($keys[$i + 1]) ? $sectionStarts[$keys[$i + 1]]['offset'] : strlen($text);
            $sectionBodies[$idx] = [
                'pkg'  => $sectionStarts[$idx]['pkg'],
                'title'=> $sectionStarts[$idx]['title'],
                'body' => substr($text, $start, $end - $start),
            ];
        }

        // If no sections found, treat the whole text as one block
        if (empty($sectionBodies)) {
            $sectionBodies[] = ['pkg' => 'System', 'title' => 'System', 'body' => $text];
        }

        // ── 3. Helper closures ───────────────────────────────────────────────────

        // Infer PHP-style type from field name
        $inferType = function (string $field): string {
            $f = strtolower($field);
            if (preg_match('/\b(id|_id)$/', $f))                         return 'int';
            if (preg_match('/\b(count|qty|quantity|number|num|amount|year|age|size|capacity|port|limit|max|min)$/', $f)) return 'int';
            if (preg_match('/\b(rate|price|cost|salary|fee|discount|tax|balance|total|amount|gross|net|wage)/', $f))   return 'decimal';
            if (preg_match('/(_at|_date|date_|date$|_time|time$|_on$|_start|_end|expires|issued|pickup|return|joined)/', $f)) return 'datetime';
            if (preg_match('/\b(status|state|type|kind|mode|level|priority|phase|stage|category|gender|role)$/', $f))  return 'string';
            if (preg_match('/\b(is_|has_|can_|allow|enabled|verified|active|available|paid|complete|archived)/', $f)) return 'bool';
            if (preg_match('/\b(description|content|notes|body|bio|text|detail|message|comment|address|info)/', $f))   return 'text';
            if (preg_match('/\b(email|url|link|path|slug|token|hash|password|secret|key|code|ref|uuid|sku)/', $f))     return 'string';
            if (preg_match('/\b(json|data|meta|payload|config|settings|options|extra|attributes)/', $f))               return 'json';
            if (preg_match('/\b(image|photo|avatar|icon|thumb|file|attachment|document|logo)/', $f))                   return 'string';
            return 'string';
        };

        // Check if parenthesised list looks like enum values (short, UPPER or Title, no spaces in values)
        $looksLikeEnum = function (array $values): bool {
            if (count($values) < 2) return false;
            $upper = 0; $short = 0;
            foreach ($values as $v) {
                $v = trim($v);
                if (strlen($v) < 20 && !str_contains($v, ' ')) $short++;
                if ($v === strtoupper($v) || preg_match('/^[A-Z][a-z]*$/', $v)) $upper++;
            }
            return $short >= count($values) * 0.7 && $upper >= count($values) * 0.5;
        };

        // Parse a raw parenthesised field list into typed field arrays
        $parseFields = function (string $raw) use ($inferType): array {
            $items  = preg_split('/,\s*/', $raw);
            $fields = [];
            foreach ($items as $item) {
                $item = trim($item);
                if ($item === '' || strtolower($item) === 'etc.' || $item === '...') continue;
                // "field_name: Type" or "field_name (type)" or just "field_name"
                if (preg_match('/^(\w+)\s*:\s*(\w+)$/', $item, $m)) {
                    $fields[] = ['name' => $m[1], 'type' => $m[2]];
                } elseif (preg_match('/^(\w+)$/', $item, $m)) {
                    $fields[] = ['name' => $m[1], 'type' => $inferType($m[1])];
                }
            }
            return $fields;
        };

        // ── 4. Parse each section ────────────────────────────────────────────────
        foreach ($sectionBodies as $sec) {
            $pkg  = $sec['pkg'];
            $body = $sec['body'];
            $pkgClasses = [];

            // Find all bullet/dash items
            preg_match_all('/^[ \t]*[-*•]\s+(.+)$/m', $body, $bulletMatches);
            $bullets = $bulletMatches[1] ?? [];

            foreach ($bullets as $bullet) {
                $bullet = trim($bullet);

                // Pattern: "ClassName (field1, field2, ...)"
                if (preg_match('/^([A-Z][A-Za-z0-9_]+(?:\s+[A-Z][A-Za-z0-9_]+)?)\s*\(([^)]+)\)(.*)$/', $bullet, $m)) {
                    $className = preg_replace('/\s+/', '', $m[1]); // remove spaces in name
                    $inner     = $m[2];
                    $values    = preg_split('/,\s*/', $inner);
                    $suffix    = strtolower($m[3] ?? '');

                    // Decide: enum or class?
                    if ($looksLikeEnum($values) && !preg_match('/\b(id|_id|date|price|email|phone|status)\b/i', $inner)) {
                        // Clean values
                        $enumVals = [];
                        foreach ($values as $v) {
                            $v = preg_replace('/[^A-Za-z0-9_]/', '', trim($v));
                            if ($v && strtolower($v) !== 'etc') $enumVals[] = strtoupper($v);
                        }
                        if ($enumVals) $allEnums[$className] = $enumVals;
                    } else {
                        $fields = $parseFields($inner);
                        if (!isset($allClasses[$className])) {
                            $allClasses[$className] = ['fields' => $fields, 'methods' => [], 'abstract' => false];
                        } else {
                            // Merge fields if already seen
                            $allClasses[$className]['fields'] = array_merge($allClasses[$className]['fields'], $fields);
                        }
                        $pkgClasses[] = $className;
                    }
                } elseif (preg_match('/^([A-Z][A-Za-z0-9_]+(?:\s+[A-Z][A-Za-z0-9_]+)?)$/', $bullet, $m)) {
                    // Plain "ClassName" bullet — add as stub class
                    $className = preg_replace('/\s+/', '', $m[1]);
                    if (!isset($allClasses[$className]) && !ctype_upper(str_replace('_', '', $className))) {
                        $allClasses[$className] = ['fields' => [], 'methods' => [], 'abstract' => false];
                        $pkgClasses[] = $className;
                    }
                } else {
                    // Free-text bullet — look for "ClassName (field, ...)" embedded anywhere
                    preg_match_all('/\b([A-Z][A-Za-z0-9]+)\s*\(([^)]{3,})\)/', $bullet, $embedded);
                    foreach ($embedded[1] as $ei => $className) {
                        $inner  = $embedded[2][$ei];
                        $values = preg_split('/,\s*/', $inner);
                        if ($looksLikeEnum($values) && !preg_match('/\b(id|_id|date|price)\b/i', $inner)) {
                            $enumVals = [];
                            foreach ($values as $v) {
                                $v = preg_replace('/[^A-Za-z0-9_]/', '', trim($v));
                                if ($v && strtolower($v) !== 'etc') $enumVals[] = strtoupper($v);
                            }
                            if ($enumVals) $allEnums[$className] = $enumVals;
                        } else {
                            $fields = $parseFields($inner);
                            if (!isset($allClasses[$className])) {
                                $allClasses[$className] = ['fields' => $fields, 'methods' => [], 'abstract' => false];
                                $pkgClasses[] = $className;
                            }
                        }
                    }
                }
            }

            // Detect method stubs in the section body: "methodName()" or "methodName(param)"
            preg_match_all('/\b([a-z][A-Za-z0-9]+)\(([^)]*)\)/', $body, $methodMatches);
            // Only attach methods to the last class mentioned in this section
            if (!empty($pkgClasses) && !empty($methodMatches[1])) {
                $lastClass = end($pkgClasses);
                foreach ($methodMatches[1] as $mi => $mname) {
                    $sig = $mname . '(' . $methodMatches[2][$mi] . '): void';
                    if (!in_array($sig, $allClasses[$lastClass]['methods'] ?? [])) {
                        $allClasses[$lastClass]['methods'][] = $sig;
                    }
                }
            }

            if (!empty($pkgClasses)) {
                $allPackages[$pkg] = array_unique($pkgClasses);
            }
        }

        // ── 5. Auto-infer relationships from foreign-key fields ──────────────────
        $knownNames = array_merge(array_keys($allClasses), array_keys($allEnums));
        foreach ($allClasses as $srcName => $srcDef) {
            foreach ($srcDef['fields'] as $field) {
                $fn = $field['name'] ?? '';
                // field named xxx_id → look for class Xxx
                if (preg_match('/^(.+)_id$/', $fn, $m)) {
                    $target = implode('', array_map('ucfirst', explode('_', $m[1])));
                    if (in_array($target, $knownNames)) {
                        $relations[] = "{$srcName} }o--|| {$target} : \"belongs to\"";
                    }
                }
                // enum fields — link to enum type if matches a known enum
                if (isset($allEnums[ucfirst($fn)])) {
                    $relations[] = "{$srcName} ..> " . ucfirst($fn) . " : <<use>>";
                }
            }
        }

        // Detect explicit inheritance / interface hints in the original text
        if (preg_match_all('/\b([A-Z][A-Za-z0-9]+)\s+(?:extends|inherits?|is\s+a)\s+([A-Z][A-Za-z0-9]+)/i', $text, $inhMatches)) {
            foreach ($inhMatches[1] as $ii => $child) {
                $parent = $inhMatches[2][$ii];
                if (isset($allClasses[$child]) || isset($allClasses[$parent])) {
                    $relations[] = "{$child} --|> {$parent}";
                }
            }
        }
        if (preg_match_all('/\b([A-Z][A-Za-z0-9]+)\s+implements\s+([A-Z][A-Za-z0-9]+)/i', $text, $implMatches)) {
            foreach ($implMatches[1] as $ii => $cls) {
                $iface = $implMatches[2][$ii];
                $relations[] = "{$cls} ..|> {$iface}";
            }
        }

        // ── 6. If parsing produced nothing useful, bail early ────────────────────
        if (empty($allClasses) && empty($allEnums)) {
            return '';
        }

        // ── 7. Render PlantUML ───────────────────────────────────────────────────
        $lines[] = '@startuml';
        $lines[] = "' Auto-generated from structured specification";
        $lines[] = "hide empty members";
        $lines[] = '';

        // Enums first
        if (!empty($allEnums)) {
            $lines[] = "' ========================";
            $lines[] = "' ENUMERATIONS";
            $lines[] = "' ========================";
            $lines[] = '';
            foreach ($allEnums as $enumName => $values) {
                $lines[] = "enum {$enumName} {";
                foreach ($values as $v) { $lines[] = "  {$v}"; }
                $lines[] = '}';
                $lines[] = '';
            }
        }

        // Classes grouped by package
        $emittedClasses = [];
        foreach ($allPackages as $pkgName => $classNames) {
            // Derive a readable label from the package name
            $pkgLabel = preg_replace('/([A-Z])/', ' $1', $pkgName);
            $pkgLabel = strtoupper(trim($pkgLabel));
            $lines[] = "' ========================";
            $lines[] = "' {$pkgLabel}";
            $lines[] = "' ========================";
            $lines[] = '';
            $lines[] = "package \"{$pkgLabel}\" {";
            foreach ($classNames as $cn) {
                if (!isset($allClasses[$cn]) || in_array($cn, $emittedClasses)) continue;
                $emittedClasses[] = $cn;
                $def = $allClasses[$cn];
                $lines[] = "  class {$cn} {";
                // Ensure id field exists
                $hasId = false;
                foreach ($def['fields'] as $f) { if (($f['name'] ?? '') === 'id') { $hasId = true; break; } }
                if (!$hasId) $lines[] = "    +id: int";
                foreach ($def['fields'] as $f) {
                    $lines[] = "    +" . ($f['name'] ?? 'field') . ": " . ($f['type'] ?? 'string');
                }
                if (!empty($def['methods'])) {
                    $lines[] = "    --";
                    foreach ($def['methods'] as $method) {
                        $lines[] = "    +" . $method;
                    }
                }
                $lines[] = "  }";
                $lines[] = '';
            }
            $lines[] = '}';
            $lines[] = '';
        }

        // Any classes not assigned to a package
        $leftover = array_diff(array_keys($allClasses), $emittedClasses);
        if (!empty($leftover)) {
            $lines[] = "' ========================";
            $lines[] = "' ADDITIONAL CLASSES";
            $lines[] = "' ========================";
            $lines[] = '';
            foreach ($leftover as $cn) {
                $def = $allClasses[$cn];
                $lines[] = "class {$cn} {";
                $hasId = false;
                foreach ($def['fields'] as $f) { if (($f['name'] ?? '') === 'id') { $hasId = true; break; } }
                if (!$hasId) $lines[] = "  +id: int";
                foreach ($def['fields'] as $f) {
                    $lines[] = "  +" . ($f['name'] ?? 'field') . ": " . ($f['type'] ?? 'string');
                }
                if (!empty($def['methods'])) {
                    $lines[][] = "  --";
                    foreach ($def['methods'] as $method) { $lines[] = "  +" . $method; }
                }
                $lines[] = '}';
                $lines[] = '';
            }
        }

        // Relationships
        if (!empty($relations)) {
            $lines[] = "' ========================";
            $lines[] = "' RELATIONSHIPS";
            $lines[] = "' ========================";
            $lines[] = '';
            foreach (array_unique($relations) as $rel) {
                $lines[] = $rel;
            }
            $lines[] = '';
        }

        $lines[] = '@enduml';

        return implode("\n", $lines);
    }

    /**
     * Detect known domain keywords and return a prepared structured array
     * compatible with buildPlantUmlFromStructure().
     */
    protected function detectDomainStructure(string $text): array
    {
        $low = strtolower($text);
        // Note management mapping
        if (str_contains($low, 'note') || str_contains($low, 'notebook')) {
            return [
                'database_entities' => [
                    [
                        'name' => 'User',
                        'fields' => [
                            ['name' => 'id', 'type' => 'int'],
                            ['name' => 'name', 'type' => 'string'],
                            ['name' => 'email', 'type' => 'string'],
                        ],
                    ],
                    [
                        'name' => 'Notebook',
                        'fields' => [
                            ['name' => 'id', 'type' => 'int'],
                            ['name' => 'name', 'type' => 'string'],
                            ['name' => 'user_id', 'type' => 'int'],
                        ],
                    ],
                    [
                        'name' => 'Note',
                        'fields' => [
                            ['name' => 'id', 'type' => 'int'],
                            ['name' => 'title', 'type' => 'string'],
                            ['name' => 'body', 'type' => 'text'],
                            ['name' => 'version', 'type' => 'int'],
                            ['name' => 'created_at', 'type' => 'datetime'],
                            ['name' => 'updated_at', 'type' => 'datetime'],
                        ],
                    ],
                    [
                        'name' => 'Tag',
                        'fields' => [
                            ['name' => 'id', 'type' => 'int'],
                            ['name' => 'name', 'type' => 'string'],
                        ],
                    ],
                    [
                        'name' => 'Comment',
                        'fields' => [
                            ['name' => 'id', 'type' => 'int'],
                            ['name' => 'content', 'type' => 'text'],
                            ['name' => 'user_id', 'type' => 'int'],
                            ['name' => 'note_id', 'type' => 'int'],
                        ],
                    ],
                ],
                'modules' => ['NoteVersion','AccessControl','ShareLink'],
            ];
        }

        // Task management mapping
        if (str_contains($low, 'task') || str_contains($low, 'task management')) {
            return [
                'database_entities' => [
                    ['name' => 'User', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string']]],
                    ['name' => 'Project', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text']]],
                    ['name' => 'Task', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'state','type'=>'string'],['name'=>'estimate','type'=>'int']]],
                    ['name' => 'Sprint', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'start_date','type'=>'datetime'],['name'=>'end_date','type'=>'datetime']]],
                    ['name' => 'Requirement', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string']]],
                    ['name' => 'Tag', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string']]],
                    ['name' => 'Comment', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'content','type'=>'text'],['name'=>'user_id','type'=>'int'],['name'=>'task_id','type'=>'int'],['name'=>'created_at','type'=>'datetime']]],
                ],
                'modules' => ['Sprint','Release','Requirement','AccessControl','ShareLink'],
            ];
        }

        // Roadmap mapping
        if (str_contains($low, 'roadmap') || str_contains($low, 'road map')) {
            return [
                'database_entities' => [
                    ['name' => 'Roadmap', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text']]],
                    ['name' => 'Milestone', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'due_date','type'=>'datetime']]],
                    ['name' => 'Release', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'version','type'=>'string'],['name'=>'released_at','type'=>'datetime']]],
                ],
                'modules' => ['Milestone','Release','AccessControl','ShareLink'],
            ];
        }

        // Sprint mapping
        if (str_contains($low, 'sprint') || str_contains($low, 'scrum')) {
            return [
                'database_entities' => [
                    ['name' => 'Sprint', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'start_date','type'=>'datetime'],['name'=>'end_date','type'=>'datetime']]],
                    ['name' => 'Task', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'state','type'=>'string']]],
                    ['name' => 'SprintBacklog', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'sprint_id','type'=>'int'],['name'=>'task_id','type'=>'int']]],
                ],
                'modules' => ['Sprint','SprintBacklog','AccessControl'],
            ];
        }

        // Check specific system domain BEFORE the generic property/manage catch-all
        $specificDomain = $this->detectSpecificSystemDomain($low);
        if (!empty($specificDomain)) {
            return $specificDomain;
        }

        // Owner / property management mapping (only when specifically about property/real-estate)
        if (
            str_contains($low, 'owner') ||
            str_contains($low, 'property') ||
            str_contains($low, 'real estate') ||
            str_contains($low, 'landlord') ||
            str_contains($low, 'tenant') ||
            str_contains($low, 'immobilier') ||
            (str_contains($low, 'manag') && !preg_match('/\b(car|vehicle|electric|charger|hospital|school|hotel|restaurant|bank|library|gym|fleet|stock|warehouse|inventory|blood|waste|salon|museum|zoo|courier|ticket|recruit|freelance|survey|dental|clinic|pharmacy|farm|music|game|cinema|airline|park|event|laundry|bakery|daycare|police|fire|wedding|crypto|iot|smart|recycl|emergency|ambulance|helpdesk|support|crm|sale|telecom|construc|legal|law|vet|pet|taxi|ride|charity|ngo)/i', $low))
        ) {
            return [
                'database_entities' => [
                    ['name' => 'Owner', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'created_at','type'=>'datetime']]],
                    ['name' => 'Property', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'owner_id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'status','type'=>'string']]],
                    ['name' => 'Tenant', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string']]],
                    ['name' => 'Contract', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'property_id','type'=>'int'],['name'=>'tenant_id','type'=>'int'],['name'=>'start_date','type'=>'date'],['name'=>'end_date','type'=>'date'],['name'=>'rent_amount','type'=>'decimal'],['name'=>'status','type'=>'string']]],
                    ['name' => 'Payment', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'contract_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'paid_at','type'=>'datetime'],['name'=>'method','type'=>'string'],['name'=>'status','type'=>'string']]],
                    ['name' => 'MaintenanceRequest', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'property_id','type'=>'int'],['name'=>'tenant_id','type'=>'int'],['name'=>'description','type'=>'text'],['name'=>'priority','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'created_at','type'=>'datetime']]],
                ],
                'modules' => ['AccessControl','ShareLink'],
            ];
        }

        // User / account / profile mapping
        if (str_contains($low, 'user') || str_contains($low, 'account') || str_contains($low, 'profile') || str_contains($low, 'member')) {
            return [
                'database_entities' => [
                    ['name' => 'User', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'password','type'=>'string'],['name'=>'email_verified_at','type'=>'datetime'],['name'=>'created_at','type'=>'datetime']]],
                    ['name' => 'Role', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'string']]],
                    ['name' => 'Permission', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'string']]],
                    ['name' => 'Session', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'ip_address','type'=>'string'],['name'=>'last_activity','type'=>'datetime']]],
                    ['name' => 'Profile', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'avatar','type'=>'string'],['name'=>'bio','type'=>'text'],['name'=>'phone','type'=>'string']]],
                ],
                'modules' => ['AccessControl','ShareLink'],
            ];
        }

        // Authentication / login / registration mapping
        if (str_contains($low, 'auth') || str_contains($low, 'authentication') || str_contains($low, 'login') || str_contains($low, 'register') || str_contains($low, 'signup')) {
            return [
                'database_entities' => [
                    ['name' => 'User', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'password','type'=>'string']]],
                    ['name' => 'Session', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'token','type'=>'string'],['name'=>'expires_at','type'=>'datetime']]],
                    ['name' => 'Registration', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'method','type'=>'string'],['name'=>'created_at','type'=>'datetime']]],
                    ['name' => 'Audit', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'action','type'=>'string'],['name'=>'user_id','type'=>'int'],['name'=>'created_at','type'=>'datetime']]],
                ],
                'modules' => ['AccessControl','ShareLink'],
            ];
        }

        // ── GENERIC FALLBACK: parse any prompt into entities ──
        return $this->parsePromptToStructure($text);
    }

    /**
     * Detect specific system domains (car rental, hotel, restaurant, bank, etc.)
     * Returns a structured array for buildPlantUmlFromStructure(), or empty array.
     */
    protected function detectSpecificSystemDomain(string $low): array
    {
        // ── CAR RENTAL / VEHICLE ──
        if (preg_match('/\b(car[\s_-]*rent|vehicle[\s_-]*rent|auto[\s_-]*rent|car[\s_-]*hire|rental[\s_-]*car|location\s*voiture|voiture)\w*/i', $low)
            || (str_contains($low, 'car') && preg_match('/\brent/i', $low))) {
            return ['database_entities' => [
                ['name' => 'Customer',   'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'license_number','type'=>'string'],['name'=>'created_at','type'=>'datetime']]],
                ['name' => 'Vehicle',    'fields' => [['name'=>'id','type'=>'int'],['name'=>'make','type'=>'string'],['name'=>'model','type'=>'string'],['name'=>'year','type'=>'int'],['name'=>'plate','type'=>'string'],['name'=>'category','type'=>'string'],['name'=>'daily_rate','type'=>'decimal'],['name'=>'status','type'=>'string']]],
                ['name' => 'Rental',     'fields' => [['name'=>'id','type'=>'int'],['name'=>'customer_id','type'=>'int'],['name'=>'vehicle_id','type'=>'int'],['name'=>'start_date','type'=>'date'],['name'=>'end_date','type'=>'date'],['name'=>'total_cost','type'=>'decimal'],['name'=>'status','type'=>'string'],['name'=>'created_at','type'=>'datetime']]],
                ['name' => 'Payment',    'fields' => [['name'=>'id','type'=>'int'],['name'=>'rental_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'method','type'=>'string'],['name'=>'paid_at','type'=>'datetime'],['name'=>'status','type'=>'string']]],
                ['name' => 'VehicleCategory', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'daily_rate','type'=>'decimal']]],
                ['name' => 'Branch',     'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'string'],['name'=>'city','type'=>'string'],['name'=>'phone','type'=>'string']]],
                ['name' => 'Reservation','fields' => [['name'=>'id','type'=>'int'],['name'=>'customer_id','type'=>'int'],['name'=>'vehicle_id','type'=>'int'],['name'=>'pickup_date','type'=>'datetime'],['name'=>'return_date','type'=>'datetime'],['name'=>'status','type'=>'string']]],
            ], 'modules' => ['AccessControl', 'Notifications']];
        }

        // ── HOTEL / ACCOMMODATION ──
        if (preg_match('/\b(hotel|motel|hostel|resort|inn|accommodation|booking|chambre|reservation)\b/', $low)) {
            return ['database_entities' => [
                ['name' => 'Guest',       'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'passport','type'=>'string']]],
                ['name' => 'Room',        'fields' => [['name'=>'id','type'=>'int'],['name'=>'number','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'floor','type'=>'int'],['name'=>'price_per_night','type'=>'decimal'],['name'=>'status','type'=>'string']]],
                ['name' => 'Booking',     'fields' => [['name'=>'id','type'=>'int'],['name'=>'guest_id','type'=>'int'],['name'=>'room_id','type'=>'int'],['name'=>'check_in','type'=>'date'],['name'=>'check_out','type'=>'date'],['name'=>'total','type'=>'decimal'],['name'=>'status','type'=>'string']]],
                ['name' => 'Payment',     'fields' => [['name'=>'id','type'=>'int'],['name'=>'booking_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'method','type'=>'string'],['name'=>'paid_at','type'=>'datetime']]],
                ['name' => 'Service',     'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'price','type'=>'decimal'],['name'=>'category','type'=>'string']]],
                ['name' => 'RoomType',    'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'capacity','type'=>'int'],['name'=>'base_price','type'=>'decimal'],['name'=>'amenities','type'=>'json']]],
            ], 'modules' => ['AccessControl', 'Notifications']];
        }

        // ── RESTAURANT / FOOD ──
        if (preg_match('/\b(restaurant|menu|food|order|cuisine|meal|dine|cafe|bistro|waitress|waiter|kitchen)\b/', $low)) {
            return ['database_entities' => [
                ['name' => 'MenuItem',  'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'price','type'=>'decimal'],['name'=>'category','type'=>'string'],['name'=>'available','type'=>'bool']]],
                ['name' => 'Order',     'fields' => [['name'=>'id','type'=>'int'],['name'=>'table_id','type'=>'int'],['name'=>'customer_id','type'=>'int'],['name'=>'status','type'=>'string'],['name'=>'total','type'=>'decimal'],['name'=>'created_at','type'=>'datetime']]],
                ['name' => 'OrderItem', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'order_id','type'=>'int'],['name'=>'menu_item_id','type'=>'int'],['name'=>'quantity','type'=>'int'],['name'=>'unit_price','type'=>'decimal']]],
                ['name' => 'Table',     'fields' => [['name'=>'id','type'=>'int'],['name'=>'number','type'=>'int'],['name'=>'capacity','type'=>'int'],['name'=>'status','type'=>'string'],['name'=>'location','type'=>'string']]],
                ['name' => 'Customer',  'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'email','type'=>'string']]],
                ['name' => 'Payment',   'fields' => [['name'=>'id','type'=>'int'],['name'=>'order_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'method','type'=>'string'],['name'=>'paid_at','type'=>'datetime']]],
            ], 'modules' => ['AccessControl', 'Kitchen']];
        }

        // ── BANK / FINANCE ──
        if (preg_match('/\b(bank|banking|account|transaction|transfer|loan|credit|debit|finance|atm|swift)\b/', $low)) {
            return ['database_entities' => [
                ['name' => 'Customer',    'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'national_id','type'=>'string'],['name'=>'address','type'=>'text']]],
                ['name' => 'Account',     'fields' => [['name'=>'id','type'=>'int'],['name'=>'customer_id','type'=>'int'],['name'=>'number','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'balance','type'=>'decimal'],['name'=>'currency','type'=>'string'],['name'=>'status','type'=>'string']]],
                ['name' => 'Transaction', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'from_account_id','type'=>'int'],['name'=>'to_account_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'type','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'created_at','type'=>'datetime']]],
                ['name' => 'Loan',        'fields' => [['name'=>'id','type'=>'int'],['name'=>'customer_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'interest_rate','type'=>'decimal'],['name'=>'term_months','type'=>'int'],['name'=>'status','type'=>'string']]],
                ['name' => 'Card',        'fields' => [['name'=>'id','type'=>'int'],['name'=>'account_id','type'=>'int'],['name'=>'number','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'expiry_date','type'=>'date'],['name'=>'status','type'=>'string']]],
            ], 'modules' => ['AccessControl', 'Audit', 'Notifications']];
        }

        // ── LIBRARY ──
        if (preg_match('/\b(library|book|biblioth|borrow|lending|isbn|author|catalogue)\b/', $low)) {
            return ['database_entities' => [
                ['name' => 'Book',    'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'isbn','type'=>'string'],['name'=>'author_id','type'=>'int'],['name'=>'category','type'=>'string'],['name'=>'published_at','type'=>'date'],['name'=>'copies','type'=>'int']]],
                ['name' => 'Author',  'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'bio','type'=>'text'],['name'=>'nationality','type'=>'string']]],
                ['name' => 'Member',  'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'membership_date','type'=>'date'],['name'=>'status','type'=>'string']]],
                ['name' => 'Loan',    'fields' => [['name'=>'id','type'=>'int'],['name'=>'member_id','type'=>'int'],['name'=>'book_id','type'=>'int'],['name'=>'issued_at','type'=>'datetime'],['name'=>'due_date','type'=>'date'],['name'=>'returned_at','type'=>'datetime'],['name'=>'status','type'=>'string']]],
                ['name' => 'Fine',    'fields' => [['name'=>'id','type'=>'int'],['name'=>'loan_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'paid','type'=>'bool'],['name'=>'issued_at','type'=>'datetime']]],
            ], 'modules' => ['AccessControl', 'Notifications']];
        }

        // ── GYM / FITNESS ──
        if (preg_match('/\b(gym|fitness|workout|exercise|membership|trainer|coach|salle\s*de\s*sport)\b/', $low)) {
            return ['database_entities' => [
                ['name' => 'Member',      'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'joined_at','type'=>'date'],['name'=>'status','type'=>'string']]],
                ['name' => 'Membership',  'fields' => [['name'=>'id','type'=>'int'],['name'=>'member_id','type'=>'int'],['name'=>'plan_id','type'=>'int'],['name'=>'start_date','type'=>'date'],['name'=>'end_date','type'=>'date'],['name'=>'status','type'=>'string']]],
                ['name' => 'Plan',        'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'duration_months','type'=>'int'],['name'=>'price','type'=>'decimal'],['name'=>'features','type'=>'json']]],
                ['name' => 'Trainer',     'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'specialization','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'hourly_rate','type'=>'decimal']]],
                ['name' => 'Session',     'fields' => [['name'=>'id','type'=>'int'],['name'=>'trainer_id','type'=>'int'],['name'=>'member_id','type'=>'int'],['name'=>'scheduled_at','type'=>'datetime'],['name'=>'duration_min','type'=>'int'],['name'=>'status','type'=>'string']]],
                ['name' => 'Payment',     'fields' => [['name'=>'id','type'=>'int'],['name'=>'membership_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'paid_at','type'=>'datetime'],['name'=>'method','type'=>'string']]],
            ], 'modules' => ['AccessControl', 'Notifications']];
        }

        // ── HOSPITAL / CLINIC (more specific than the KB trigger) ──
        if (preg_match('/\b(hospital|clinic|pharmacy|dental|medical\s*center|health\s*center|urgent\s*care|ambulance)\b/', $low)) {
            return ['database_entities' => [
                ['name' => 'Patient',     'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'dob','type'=>'date'],['name'=>'gender','type'=>'string'],['name'=>'blood_type','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'address','type'=>'text']]],
                ['name' => 'Doctor',      'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'specialization','type'=>'string'],['name'=>'license','type'=>'string'],['name'=>'department_id','type'=>'int']]],
                ['name' => 'Appointment', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'patient_id','type'=>'int'],['name'=>'doctor_id','type'=>'int'],['name'=>'scheduled_at','type'=>'datetime'],['name'=>'type','type'=>'string'],['name'=>'status','type'=>'string']]],
                ['name' => 'Prescription','fields' => [['name'=>'id','type'=>'int'],['name'=>'appointment_id','type'=>'int'],['name'=>'medication','type'=>'string'],['name'=>'dosage','type'=>'string'],['name'=>'duration','type'=>'string'],['name'=>'issued_at','type'=>'datetime']]],
                ['name' => 'Department',  'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'head_doctor_id','type'=>'int'],['name'=>'floor','type'=>'int']]],
                ['name' => 'MedicalRecord','fields' => [['name'=>'id','type'=>'int'],['name'=>'patient_id','type'=>'int'],['name'=>'diagnosis','type'=>'text'],['name'=>'treatment','type'=>'text'],['name'=>'visit_date','type'=>'datetime']]],
            ], 'modules' => ['AccessControl', 'Notifications']];
        }

        // ── INVENTORY / WAREHOUSE ──
        if (preg_match('/\b(inventory|warehouse|stock|stockroom|shelf|sku|warehouse|storage|supply\s*chain)\b/', $low)) {
            return ['database_entities' => [
                ['name' => 'Product',       'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'sku','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'category_id','type'=>'int'],['name'=>'unit_cost','type'=>'decimal']]],
                ['name' => 'Warehouse',     'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'location','type'=>'string'],['name'=>'capacity','type'=>'int']]],
                ['name' => 'StockItem',     'fields' => [['name'=>'id','type'=>'int'],['name'=>'product_id','type'=>'int'],['name'=>'warehouse_id','type'=>'int'],['name'=>'quantity','type'=>'int'],['name'=>'min_stock','type'=>'int'],['name'=>'last_updated','type'=>'datetime']]],
                ['name' => 'StockMovement', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'product_id','type'=>'int'],['name'=>'quantity','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'reason','type'=>'string'],['name'=>'moved_at','type'=>'datetime']]],
                ['name' => 'Supplier',      'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'address','type'=>'text']]],
                ['name' => 'PurchaseOrder', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'supplier_id','type'=>'int'],['name'=>'status','type'=>'string'],['name'=>'total','type'=>'decimal'],['name'=>'ordered_at','type'=>'datetime']]],
            ], 'modules' => ['AccessControl', 'Notifications']];
        }

        // ── TAXI / RIDE SHARING ──
        if (preg_match('/\b(taxi|ride|uber|driver|passenger|trip|cab|transport)\b/', $low)) {
            return ['database_entities' => [
                ['name' => 'Passenger', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'rating','type'=>'decimal']]],
                ['name' => 'Driver',    'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'license','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'rating','type'=>'decimal'],['name'=>'status','type'=>'string']]],
                ['name' => 'Vehicle',   'fields' => [['name'=>'id','type'=>'int'],['name'=>'driver_id','type'=>'int'],['name'=>'plate','type'=>'string'],['name'=>'model','type'=>'string'],['name'=>'year','type'=>'int'],['name'=>'status','type'=>'string']]],
                ['name' => 'Trip',      'fields' => [['name'=>'id','type'=>'int'],['name'=>'passenger_id','type'=>'int'],['name'=>'driver_id','type'=>'int'],['name'=>'origin','type'=>'string'],['name'=>'destination','type'=>'string'],['name'=>'fare','type'=>'decimal'],['name'=>'status','type'=>'string'],['name'=>'started_at','type'=>'datetime']]],
                ['name' => 'Payment',   'fields' => [['name'=>'id','type'=>'int'],['name'=>'trip_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'method','type'=>'string'],['name'=>'paid_at','type'=>'datetime']]],
            ], 'modules' => ['AccessControl', 'Geolocation', 'Notifications']];
        }

        // ── AIRLINE / FLIGHT ──
        if (preg_match('/\b(airline|flight|airport|boarding|passenger|ticket|seat|aviation|plane)\b/', $low)) {
            return ['database_entities' => [
                ['name' => 'Passenger', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'passport','type'=>'string'],['name'=>'nationality','type'=>'string'],['name'=>'email','type'=>'string']]],
                ['name' => 'Flight',    'fields' => [['name'=>'id','type'=>'int'],['name'=>'flight_number','type'=>'string'],['name'=>'origin','type'=>'string'],['name'=>'destination','type'=>'string'],['name'=>'departure_at','type'=>'datetime'],['name'=>'arrival_at','type'=>'datetime'],['name'=>'status','type'=>'string']]],
                ['name' => 'Booking',   'fields' => [['name'=>'id','type'=>'int'],['name'=>'passenger_id','type'=>'int'],['name'=>'flight_id','type'=>'int'],['name'=>'seat','type'=>'string'],['name'=>'class','type'=>'string'],['name'=>'price','type'=>'decimal'],['name'=>'status','type'=>'string']]],
                ['name' => 'Aircraft',  'fields' => [['name'=>'id','type'=>'int'],['name'=>'model','type'=>'string'],['name'=>'capacity','type'=>'int'],['name'=>'registration','type'=>'string'],['name'=>'status','type'=>'string']]],
                ['name' => 'Crew',      'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'role','type'=>'string'],['name'=>'license','type'=>'string']]],
            ], 'modules' => ['AccessControl', 'Notifications']];
        }

        // ── EVENT / TICKET ──
        if (preg_match('/\b(event|ticket|concert|conference|venue|attendee|speaker|exhib)\b/', $low)) {
            return ['database_entities' => [
                ['name' => 'Event',    'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'venue_id','type'=>'int'],['name'=>'start_at','type'=>'datetime'],['name'=>'end_at','type'=>'datetime'],['name'=>'capacity','type'=>'int']]],
                ['name' => 'Ticket',   'fields' => [['name'=>'id','type'=>'int'],['name'=>'event_id','type'=>'int'],['name'=>'attendee_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'price','type'=>'decimal'],['name'=>'status','type'=>'string'],['name'=>'purchased_at','type'=>'datetime']]],
                ['name' => 'Attendee', 'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string']]],
                ['name' => 'Venue',    'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'string'],['name'=>'capacity','type'=>'int'],['name'=>'contact','type'=>'string']]],
                ['name' => 'Speaker',  'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'bio','type'=>'text'],['name'=>'topic','type'=>'string'],['name'=>'event_id','type'=>'int']]],
            ], 'modules' => ['AccessControl', 'Notifications', 'QRCode']];
        }

        // ── HR / RECRUITMENT ──
        if (preg_match('/\b(hr|human\s*resources|employee|staff|recruit|payroll|attendance|leave|salary|hire)\b/', $low)) {
            return ['database_entities' => [
                ['name' => 'Employee',    'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'department_id','type'=>'int'],['name'=>'position','type'=>'string'],['name'=>'salary','type'=>'decimal'],['name'=>'hired_at','type'=>'date'],['name'=>'status','type'=>'string']]],
                ['name' => 'Department',  'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'manager_id','type'=>'int'],['name'=>'budget','type'=>'decimal']]],
                ['name' => 'Attendance',  'fields' => [['name'=>'id','type'=>'int'],['name'=>'employee_id','type'=>'int'],['name'=>'date','type'=>'date'],['name'=>'check_in','type'=>'time'],['name'=>'check_out','type'=>'time'],['name'=>'status','type'=>'string']]],
                ['name' => 'LeaveRequest','fields' => [['name'=>'id','type'=>'int'],['name'=>'employee_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'start_date','type'=>'date'],['name'=>'end_date','type'=>'date'],['name'=>'status','type'=>'string']]],
                ['name' => 'Payroll',     'fields' => [['name'=>'id','type'=>'int'],['name'=>'employee_id','type'=>'int'],['name'=>'month','type'=>'date'],['name'=>'gross','type'=>'decimal'],['name'=>'deductions','type'=>'decimal'],['name'=>'net','type'=>'decimal'],['name'=>'paid_at','type'=>'datetime']]],
            ], 'modules' => ['AccessControl', 'Notifications']];
        }

        return [];
    }

    // ─────────────────────────────────────────────────────────
    //  GENERIC PROMPT → ENTITY PARSER
    // ─────────────────────────────────────────────────────────

    /**
     * Parse any free-text prompt into a structured array of entities with
     * fields and relationships.  Works without an LLM by combining keyword
     * extraction with a large built-in knowledge base.
     */
    protected function parsePromptToStructure(string $text): array
    {
        $low = strtolower($text);

        // 1. Extract candidate keywords (strip stop words / filler)
        $candidates = $this->extractCandidateNouns($low);
        if (empty($candidates)) {
            // absolute fallback: nothing meaningful in the prompt
            return [];
        }

        // 2. Match candidates against the knowledge base
        $kb = $this->entityKnowledgeBase();
        $matched = [];
        $matchedKeys = [];

        foreach ($candidates as $word) {
            foreach ($kb as $key => $def) {
                if (in_array($key, $matchedKeys)) continue;
                $triggers = $def['triggers'] ?? [$key];
                foreach ($triggers as $trigger) {
                    if (str_contains($low, $trigger)) {
                        $matched[$key] = $def;
                        $matchedKeys[] = $key;
                        break 2;
                    }
                }
            }
        }

        // 3. If no KB match, synthesise entities from the extracted nouns
        if (empty($matched)) {
            $matched = $this->synthesiseEntitiesFromNouns($candidates);
        }

        // 4. Build the structured array
        $entities = [];
        $modules  = ['AccessControl'];

        foreach ($matched as $name => $def) {
            $entities[] = [
                'name'   => $name,
                'fields' => $def['fields'] ?? [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
            ];
            // pull in related entities that weren't explicitly matched
            foreach (($def['related'] ?? []) as $rel) {
                if (!isset($matched[$rel]) && !$this->entityInList($entities, $rel)) {
                    $relDef = $kb[$rel] ?? null;
                    $entities[] = [
                        'name'   => $rel,
                        'fields' => $relDef['fields'] ?? [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string']],
                    ];
                }
            }
        }

        return empty($entities) ? [] : [
            'database_entities' => $entities,
            'modules' => $modules,
        ];
    }

    protected function entityInList(array $entities, string $name): bool
    {
        foreach ($entities as $e) {
            if (($e['name'] ?? '') === $name) return true;
        }
        return false;
    }

    /**
     * Extract candidate nouns by removing common stop/filler words.
     */
    protected function extractCandidateNouns(string $text): array
    {
        $stop = ['a','an','the','of','for','and','or','in','on','to','is','are',
                 'was','were','be','been','with','this','that','it','its','my',
                 'me','i','we','you','your','he','she','they','them','their',
                 'has','have','had','do','does','did','will','would','can','could',
                 'shall','should','may','might','must','need','about','from','by',
                 'at','as','but','not','no','so','if','then','than','too','very',
                 'give','make','create','generate','build','show','get','code',
                 'diagram','class','uml','plantuml','plan','please','want','like',
                 'system','systeme','application','app','structure','model','example',
                 'hello','hi','hey','thanks','thank','ok','just','also','some','all',
                 'how','what','which','where','when','who','why','every','each',
                 'one','two','three','many','much','more','most','other','another',
                 'new','old','first','last','next','free','open','full','main',
                 'simple','basic','complete','based','using','use','describe',
                 'design','implement','add','include','manage','managing','management'];

        // normalise: keep only letters and spaces
        $clean = preg_replace('/[^a-z\s]/', ' ', $text);
        $words = preg_split('/\s+/', trim($clean));
        $words = array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stop));
        return array_values(array_unique($words));
    }

    /**
     * When nothing in the KB matches, create generic entities from the
     * extracted nouns. Each noun becomes a class with sensible defaults.
     */
    protected function synthesiseEntitiesFromNouns(array $nouns): array
    {
        $result = [];
        // Take up to 8 nouns as entity names
        $nouns = array_slice($nouns, 0, 8);
        foreach ($nouns as $noun) {
            $name = ucfirst($noun);
            $fields = [
                ['name'=>'id','type'=>'int'],
                ['name'=>'name','type'=>'string'],
                ['name'=>'description','type'=>'text'],
                ['name'=>'status','type'=>'string'],
                ['name'=>'created_at','type'=>'datetime'],
                ['name'=>'updated_at','type'=>'datetime'],
            ];
            $result[$name] = ['fields' => $fields, 'related' => []];
        }
        return $result;
    }

    /**
     * Large knowledge base mapping entity names to fields, triggers
     * (keywords that activate them), and related entities to pull in.
     */
    protected function entityKnowledgeBase(): array
    {
        return [
            // ── E-COMMERCE ──
            'Customer' => [
                'triggers' => ['customer','buyer','shopper','ecommerce','e-commerce','shop','store','boutique'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['Product','Order','Cart','Payment','Review'],
            ],
            'Product' => [
                'triggers' => ['product','item','merchandise','goods','catalog','catalogue'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'price','type'=>'decimal'],['name'=>'stock','type'=>'int'],['name'=>'category_id','type'=>'int'],['name'=>'sku','type'=>'string']],
                'related' => ['Category','Order','Review','Cart'],
            ],
            'Order' => [
                'triggers' => ['order','purchase','checkout','buy'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'customer_id','type'=>'int'],['name'=>'total','type'=>'decimal'],['name'=>'status','type'=>'string'],['name'=>'shipping_address','type'=>'text'],['name'=>'ordered_at','type'=>'datetime']],
                'related' => ['OrderItem','Payment','Customer'],
            ],
            'OrderItem' => [
                'triggers' => ['order_item','orderitem','line_item'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'order_id','type'=>'int'],['name'=>'product_id','type'=>'int'],['name'=>'quantity','type'=>'int'],['name'=>'unit_price','type'=>'decimal']],
                'related' => [],
            ],
            'Cart' => [
                'triggers' => ['cart','basket','shopping_cart'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'customer_id','type'=>'int'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['CartItem'],
            ],
            'CartItem' => [
                'triggers' => ['cart_item','cartitem'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'cart_id','type'=>'int'],['name'=>'product_id','type'=>'int'],['name'=>'quantity','type'=>'int']],
                'related' => [],
            ],
            'Category' => [
                'triggers' => ['category','section','department'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'parent_id','type'=>'int'],['name'=>'description','type'=>'text']],
                'related' => [],
            ],
            'Review' => [
                'triggers' => ['review','rating','feedback','testimonial'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'product_id','type'=>'int'],['name'=>'customer_id','type'=>'int'],['name'=>'rating','type'=>'int'],['name'=>'comment','type'=>'text'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],
            'Payment' => [
                'triggers' => ['payment','pay','invoice','billing','bill','transaction'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'order_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'method','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'paid_at','type'=>'datetime']],
                'related' => [],
            ],
            'Coupon' => [
                'triggers' => ['coupon','discount','promo','voucher'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'code','type'=>'string'],['name'=>'discount_percent','type'=>'decimal'],['name'=>'valid_from','type'=>'date'],['name'=>'valid_until','type'=>'date'],['name'=>'max_uses','type'=>'int']],
                'related' => [],
            ],

            // ── SCHOOL / EDUCATION ──
            'Student' => [
                'triggers' => ['student','pupil','learner','school','education','university','college','academy','class','classroom'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'date_of_birth','type'=>'date'],['name'=>'enrollment_date','type'=>'date'],['name'=>'grade_level','type'=>'string']],
                'related' => ['Teacher','Course','Enrollment','Grade','Classroom'],
            ],
            'Teacher' => [
                'triggers' => ['teacher','instructor','professor','lecturer','tutor'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'department','type'=>'string'],['name'=>'specialization','type'=>'string'],['name'=>'hired_at','type'=>'date']],
                'related' => ['Course','Classroom'],
            ],
            'Course' => [
                'triggers' => ['course','subject','module','curriculum','program','programme','lesson'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'code','type'=>'string'],['name'=>'credits','type'=>'int'],['name'=>'description','type'=>'text'],['name'=>'teacher_id','type'=>'int']],
                'related' => ['Enrollment','Grade'],
            ],
            'Enrollment' => [
                'triggers' => ['enrollment','enrolment','registration','subscribe'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'student_id','type'=>'int'],['name'=>'course_id','type'=>'int'],['name'=>'enrolled_at','type'=>'date'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'Grade' => [
                'triggers' => ['grade','score','mark','result','exam','examination','test','assessment'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'student_id','type'=>'int'],['name'=>'course_id','type'=>'int'],['name'=>'score','type'=>'decimal'],['name'=>'grade','type'=>'string'],['name'=>'graded_at','type'=>'datetime']],
                'related' => [],
            ],
            'Classroom' => [
                'triggers' => ['classroom','room','hall','auditorium'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'building','type'=>'string'],['name'=>'capacity','type'=>'int']],
                'related' => [],
            ],

            // ── HOSPITAL / HEALTHCARE ──
            'Patient' => [
                'triggers' => ['patient','hospital','clinic','medical','healthcare','health','sante'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'date_of_birth','type'=>'date'],['name'=>'gender','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'blood_type','type'=>'string'],['name'=>'address','type'=>'text']],
                'related' => ['Doctor','Appointment','MedicalRecord','Prescription','Department'],
            ],
            'Doctor' => [
                'triggers' => ['doctor','physician','surgeon','medecin','specialist','practitioner'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'specialization','type'=>'string'],['name'=>'license_number','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'department_id','type'=>'int']],
                'related' => ['Appointment','Prescription','Department'],
            ],
            'Appointment' => [
                'triggers' => ['appointment','consultation','visit','rendez-vous','rendezvous','rdv','booking','reservation','reserve'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'patient_id','type'=>'int'],['name'=>'doctor_id','type'=>'int'],['name'=>'scheduled_at','type'=>'datetime'],['name'=>'status','type'=>'string'],['name'=>'notes','type'=>'text']],
                'related' => [],
            ],
            'MedicalRecord' => [
                'triggers' => ['medical_record','record','dossier','diagnosis','chart'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'patient_id','type'=>'int'],['name'=>'doctor_id','type'=>'int'],['name'=>'diagnosis','type'=>'text'],['name'=>'treatment','type'=>'text'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],
            'Prescription' => [
                'triggers' => ['prescription','medication','medicine','drug','ordonnance','pharmacy','pharmacie'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'patient_id','type'=>'int'],['name'=>'doctor_id','type'=>'int'],['name'=>'medication','type'=>'string'],['name'=>'dosage','type'=>'string'],['name'=>'duration','type'=>'string'],['name'=>'prescribed_at','type'=>'datetime']],
                'related' => [],
            ],
            'Department' => [
                'triggers' => ['department','service','ward','unit'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'floor','type'=>'int'],['name'=>'head_doctor_id','type'=>'int']],
                'related' => [],
            ],

            // ── LIBRARY ──
            'Book' => [
                'triggers' => ['book','library','bibliotheque','livre','reading','publication'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'isbn','type'=>'string'],['name'=>'author_id','type'=>'int'],['name'=>'category_id','type'=>'int'],['name'=>'published_year','type'=>'int'],['name'=>'copies','type'=>'int']],
                'related' => ['Author','Borrowing','Member','Category'],
            ],
            'Author' => [
                'triggers' => ['author','writer','auteur','ecrivain'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'bio','type'=>'text'],['name'=>'nationality','type'=>'string']],
                'related' => [],
            ],
            'Member' => [
                'triggers' => ['member','subscriber','adherent'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'membership_type','type'=>'string'],['name'=>'joined_at','type'=>'date']],
                'related' => ['Borrowing'],
            ],
            'Borrowing' => [
                'triggers' => ['borrow','loan','lending','emprunt','checkout'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'book_id','type'=>'int'],['name'=>'member_id','type'=>'int'],['name'=>'borrowed_at','type'=>'date'],['name'=>'due_at','type'=>'date'],['name'=>'returned_at','type'=>'date'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],

            // ── RESTAURANT / FOOD ──
            'Restaurant' => [
                'triggers' => ['restaurant','food','dining','cafe','cafeteria','canteen','bistro'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'cuisine_type','type'=>'string'],['name'=>'rating','type'=>'decimal']],
                'related' => ['Menu','TableReservation','Staff','OrderFood'],
            ],
            'Menu' => [
                'triggers' => ['menu','dish','meal','recipe','plat'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'price','type'=>'decimal'],['name'=>'category','type'=>'string'],['name'=>'available','type'=>'boolean']],
                'related' => [],
            ],
            'TableReservation' => [
                'triggers' => ['table','reservation','seating'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'customer_name','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'table_number','type'=>'int'],['name'=>'guest_count','type'=>'int'],['name'=>'reserved_at','type'=>'datetime'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'Staff' => [
                'triggers' => ['staff','waiter','chef','cook','employee','employe','worker','personnel'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'role','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'salary','type'=>'decimal'],['name'=>'hired_at','type'=>'date']],
                'related' => [],
            ],
            'OrderFood' => [
                'triggers' => ['order_food'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'table_number','type'=>'int'],['name'=>'staff_id','type'=>'int'],['name'=>'total','type'=>'decimal'],['name'=>'status','type'=>'string'],['name'=>'ordered_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── HOTEL ──
            'Hotel' => [
                'triggers' => ['hotel','hostel','motel','lodging','accommodation','auberge','resort'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'stars','type'=>'int'],['name'=>'phone','type'=>'string']],
                'related' => ['Room','Guest','Booking','HotelPayment'],
            ],
            'Room' => [
                'triggers' => ['room','chambre','suite'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'hotel_id','type'=>'int'],['name'=>'number','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'price_per_night','type'=>'decimal'],['name'=>'capacity','type'=>'int'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'Guest' => [
                'triggers' => ['guest','visitor','client','traveler','voyageur'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'identity_doc','type'=>'string'],['name'=>'nationality','type'=>'string']],
                'related' => ['Booking'],
            ],
            'Booking' => [
                'triggers' => ['booking','book','reserve'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'guest_id','type'=>'int'],['name'=>'room_id','type'=>'int'],['name'=>'check_in','type'=>'date'],['name'=>'check_out','type'=>'date'],['name'=>'status','type'=>'string'],['name'=>'total_price','type'=>'decimal']],
                'related' => [],
            ],
            'HotelPayment' => [
                'triggers' => ['hotel_payment'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'booking_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'method','type'=>'string'],['name'=>'paid_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── BANK / FINANCE ──
            'BankAccount' => [
                'triggers' => ['bank','banking','account','finance','financial','banque','compte'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'account_number','type'=>'string'],['name'=>'holder_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'balance','type'=>'decimal'],['name'=>'currency','type'=>'string'],['name'=>'opened_at','type'=>'date']],
                'related' => ['AccountHolder','BankTransaction','Loan','Card'],
            ],
            'AccountHolder' => [
                'triggers' => ['holder','account_holder'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'identity_number','type'=>'string']],
                'related' => [],
            ],
            'BankTransaction' => [
                'triggers' => ['bank_transaction','transfer','virement','withdraw','deposit'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'from_account_id','type'=>'int'],['name'=>'to_account_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'type','type'=>'string'],['name'=>'reference','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],
            'Loan' => [
                'triggers' => ['loan','credit','mortgage','pret','emprunt'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'account_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'interest_rate','type'=>'decimal'],['name'=>'start_date','type'=>'date'],['name'=>'end_date','type'=>'date'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'Card' => [
                'triggers' => ['card','credit_card','debit_card','carte'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'account_id','type'=>'int'],['name'=>'card_number','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'expires_at','type'=>'date'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],

            // ── HR / EMPLOYEES ──
            'Employee' => [
                'triggers' => ['employee','employe','hr','human_resource','rh','ressource','salaire','salary','payroll','workforce'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'position','type'=>'string'],['name'=>'department_id','type'=>'int'],['name'=>'salary','type'=>'decimal'],['name'=>'hired_at','type'=>'date']],
                'related' => ['Department','LeaveRequest','Attendance','Payroll'],
            ],
            'LeaveRequest' => [
                'triggers' => ['leave','vacation','conge','absence','time_off'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'employee_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'start_date','type'=>'date'],['name'=>'end_date','type'=>'date'],['name'=>'status','type'=>'string'],['name'=>'reason','type'=>'text']],
                'related' => [],
            ],
            'Attendance' => [
                'triggers' => ['attendance','pointage','check_in','presence'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'employee_id','type'=>'int'],['name'=>'date','type'=>'date'],['name'=>'check_in','type'=>'time'],['name'=>'check_out','type'=>'time'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'Payroll' => [
                'triggers' => ['payroll','paie','pay_slip','fiche_de_paie'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'employee_id','type'=>'int'],['name'=>'month','type'=>'string'],['name'=>'gross_salary','type'=>'decimal'],['name'=>'deductions','type'=>'decimal'],['name'=>'net_salary','type'=>'decimal'],['name'=>'paid_at','type'=>'date']],
                'related' => [],
            ],

            // ── BLOG / CMS ──
            'Post' => [
                'triggers' => ['blog','post','article','cms','content','publication','magazine','journal'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'body','type'=>'text'],['name'=>'author_id','type'=>'int'],['name'=>'category_id','type'=>'int'],['name'=>'status','type'=>'string'],['name'=>'published_at','type'=>'datetime']],
                'related' => ['Author','Category','Comment','Tag'],
            ],
            'Comment' => [
                'triggers' => ['comment','commentaire','reply','response'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'post_id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'body','type'=>'text'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],
            'Tag' => [
                'triggers' => ['tag','label','keyword','etiquette'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'slug','type'=>'string']],
                'related' => [],
            ],

            // ── VEHICLE / FLEET / TRANSPORT ──
            'Vehicle' => [
                'triggers' => ['vehicle','voiture','car','truck','camion','fleet','transport','logistics','logistique','parking','garage'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'plate_number','type'=>'string'],['name'=>'brand','type'=>'string'],['name'=>'model','type'=>'string'],['name'=>'year','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'status','type'=>'string']],
                'related' => ['Driver','Trip','VehicleMaintenance','FuelLog'],
            ],
            'Driver' => [
                'triggers' => ['driver','chauffeur','conducteur'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'license_number','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'Trip' => [
                'triggers' => ['trip','voyage','route','trajet','delivery','livraison','expedition','shipment','shipping'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'vehicle_id','type'=>'int'],['name'=>'driver_id','type'=>'int'],['name'=>'origin','type'=>'string'],['name'=>'destination','type'=>'string'],['name'=>'distance_km','type'=>'decimal'],['name'=>'started_at','type'=>'datetime'],['name'=>'ended_at','type'=>'datetime']],
                'related' => [],
            ],
            'VehicleMaintenance' => [
                'triggers' => ['vehicle_maintenance','maintenance','repair','reparation','entretien'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'vehicle_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'cost','type'=>'decimal'],['name'=>'date','type'=>'date']],
                'related' => [],
            ],
            'FuelLog' => [
                'triggers' => ['fuel','carburant','essence','diesel','gas'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'vehicle_id','type'=>'int'],['name'=>'liters','type'=>'decimal'],['name'=>'cost','type'=>'decimal'],['name'=>'date','type'=>'date'],['name'=>'odometer','type'=>'int']],
                'related' => [],
            ],

            // ── INVENTORY / WAREHOUSE ──
            'Warehouse' => [
                'triggers' => ['warehouse','inventory','stock','entrepot','depot','storage','magasin','stockage'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'location','type'=>'string'],['name'=>'capacity','type'=>'int']],
                'related' => ['InventoryItem','Supplier','StockMovement'],
            ],
            'InventoryItem' => [
                'triggers' => ['inventory_item'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'warehouse_id','type'=>'int'],['name'=>'product_name','type'=>'string'],['name'=>'sku','type'=>'string'],['name'=>'quantity','type'=>'int'],['name'=>'min_quantity','type'=>'int']],
                'related' => [],
            ],
            'Supplier' => [
                'triggers' => ['supplier','vendor','fournisseur','provider'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'address','type'=>'text']],
                'related' => [],
            ],
            'StockMovement' => [
                'triggers' => ['stock_movement','movement','transfer','mouvement'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'item_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'quantity','type'=>'int'],['name'=>'reference','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── REAL ESTATE ──
            'Property' => [
                'triggers' => ['property','real_estate','immobilier','apartment','appartement','house','maison','terrain','land','realty'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'type','type'=>'string'],['name'=>'price','type'=>'decimal'],['name'=>'area_sqm','type'=>'decimal'],['name'=>'status','type'=>'string'],['name'=>'owner_id','type'=>'int']],
                'related' => ['Owner','Tenant','Contract'],
            ],
            'Owner' => [
                'triggers' => ['owner','proprietaire','landlord','bailleur'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['Property','Contract'],
            ],
            'Tenant' => [
                'triggers' => ['tenant','locataire','renter'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string']],
                'related' => ['Contract'],
            ],
            'Contract' => [
                'triggers' => ['contract','contrat','lease','bail','agreement'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'property_id','type'=>'int'],['name'=>'tenant_id','type'=>'int'],['name'=>'start_date','type'=>'date'],['name'=>'end_date','type'=>'date'],['name'=>'rent_amount','type'=>'decimal'],['name'=>'status','type'=>'string']],
                'related' => ['Payment'],
            ],

            // ── EVENT MANAGEMENT ──
            'Event' => [
                'triggers' => ['event','evenement','conference','seminar','seminaire','concert','festival','workshop','atelier','meetup','gala'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'venue_id','type'=>'int'],['name'=>'start_at','type'=>'datetime'],['name'=>'end_at','type'=>'datetime'],['name'=>'max_attendees','type'=>'int'],['name'=>'status','type'=>'string']],
                'related' => ['Venue','Ticket','Attendee','Sponsor'],
            ],
            'Venue' => [
                'triggers' => ['venue','lieu','location','salle'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'capacity','type'=>'int']],
                'related' => [],
            ],
            'Ticket' => [
                'triggers' => ['ticket','billet','pass','admission','entry','entree'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'event_id','type'=>'int'],['name'=>'attendee_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'price','type'=>'decimal'],['name'=>'status','type'=>'string'],['name'=>'purchased_at','type'=>'datetime']],
                'related' => [],
            ],
            'Attendee' => [
                'triggers' => ['attendee','participant','inscrit'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string']],
                'related' => [],
            ],
            'Sponsor' => [
                'triggers' => ['sponsor','partenaire','partner','sponsoring'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'contribution','type'=>'decimal'],['name'=>'tier','type'=>'string']],
                'related' => [],
            ],

            // ── FITNESS / GYM ──
            'GymMember' => [
                'triggers' => ['gym','fitness','sport','salle_de_sport','musculation','workout','exercise','training','entrainement'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'membership_type','type'=>'string'],['name'=>'joined_at','type'=>'date']],
                'related' => ['Subscription','GymClass','Trainer'],
            ],
            'Subscription' => [
                'triggers' => ['subscription','abonnement','membership','plan'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'member_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'price','type'=>'decimal'],['name'=>'start_date','type'=>'date'],['name'=>'end_date','type'=>'date'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'GymClass' => [
                'triggers' => ['gym_class','session','seance','yoga','pilates','cours'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'trainer_id','type'=>'int'],['name'=>'schedule','type'=>'datetime'],['name'=>'max_participants','type'=>'int'],['name'=>'duration_minutes','type'=>'int']],
                'related' => [],
            ],
            'Trainer' => [
                'triggers' => ['trainer','coach','entraineur'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'specialization','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'email','type'=>'string']],
                'related' => [],
            ],

            // ── SOCIAL NETWORK ──
            'SocialUser' => [
                'triggers' => ['social','network','reseau','friend','follower','following','facebook','twitter','instagram','tiktok','social_media'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'username','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'avatar','type'=>'string'],['name'=>'bio','type'=>'text'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['SocialPost','Friendship','Message','Notification'],
            ],
            'SocialPost' => [
                'triggers' => ['social_post','status','tweet','story','reel','feed'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'content','type'=>'text'],['name'=>'media_url','type'=>'string'],['name'=>'likes_count','type'=>'int'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['Comment','Like'],
            ],
            'Friendship' => [
                'triggers' => ['friendship','friend','follow','ami','relation'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'friend_id','type'=>'int'],['name'=>'status','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],
            'Message' => [
                'triggers' => ['message','chat','conversation','dm','inbox','messagerie'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'sender_id','type'=>'int'],['name'=>'receiver_id','type'=>'int'],['name'=>'body','type'=>'text'],['name'=>'read_at','type'=>'datetime'],['name'=>'sent_at','type'=>'datetime']],
                'related' => [],
            ],
            'Notification' => [
                'triggers' => ['notification','alert','alerte','push'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'title','type'=>'string'],['name'=>'body','type'=>'text'],['name'=>'read_at','type'=>'datetime'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],
            'Like' => [
                'triggers' => ['like','reaction','favoris','favorite','love'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'likeable_id','type'=>'int'],['name'=>'likeable_type','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── INSURANCE ──
            'PolicyHolder' => [
                'triggers' => ['insurance','assurance','policy','police','sinistre','claim'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'address','type'=>'text']],
                'related' => ['InsurancePolicy','Claim'],
            ],
            'InsurancePolicy' => [
                'triggers' => ['insurance_policy'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'holder_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'premium','type'=>'decimal'],['name'=>'coverage','type'=>'decimal'],['name'=>'start_date','type'=>'date'],['name'=>'end_date','type'=>'date'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'Claim' => [
                'triggers' => ['claim','sinistre','declaration'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'policy_id','type'=>'int'],['name'=>'description','type'=>'text'],['name'=>'amount','type'=>'decimal'],['name'=>'status','type'=>'string'],['name'=>'filed_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── CINEMA / MOVIES ──
            'Movie' => [
                'triggers' => ['movie','film','cinema','theatre','theater','screening','projection'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'genre','type'=>'string'],['name'=>'duration_min','type'=>'int'],['name'=>'director','type'=>'string'],['name'=>'release_date','type'=>'date'],['name'=>'rating','type'=>'decimal']],
                'related' => ['Screening','Actor','MovieTicket'],
            ],
            'Actor' => [
                'triggers' => ['actor','acteur','actress','actrice','cast'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'nationality','type'=>'string'],['name'=>'date_of_birth','type'=>'date']],
                'related' => [],
            ],
            'Screening' => [
                'triggers' => ['screening','showtime','seance'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'movie_id','type'=>'int'],['name'=>'hall','type'=>'string'],['name'=>'starts_at','type'=>'datetime'],['name'=>'price','type'=>'decimal']],
                'related' => ['MovieTicket'],
            ],
            'MovieTicket' => [
                'triggers' => ['movie_ticket'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'screening_id','type'=>'int'],['name'=>'seat','type'=>'string'],['name'=>'price','type'=>'decimal'],['name'=>'purchased_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── TRAVEL / AIRLINE ──
            'Flight' => [
                'triggers' => ['flight','airline','avion','aviation','airport','aeroport','vol','compagnie_aerienne','plane'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'flight_number','type'=>'string'],['name'=>'origin','type'=>'string'],['name'=>'destination','type'=>'string'],['name'=>'departure_at','type'=>'datetime'],['name'=>'arrival_at','type'=>'datetime'],['name'=>'status','type'=>'string']],
                'related' => ['Passenger','FlightBooking','Aircraft'],
            ],
            'Passenger' => [
                'triggers' => ['passenger','passager','traveler'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'passport_number','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string']],
                'related' => ['FlightBooking'],
            ],
            'FlightBooking' => [
                'triggers' => ['flight_booking'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'flight_id','type'=>'int'],['name'=>'passenger_id','type'=>'int'],['name'=>'seat','type'=>'string'],['name'=>'class','type'=>'string'],['name'=>'price','type'=>'decimal'],['name'=>'booked_at','type'=>'datetime']],
                'related' => [],
            ],
            'Aircraft' => [
                'triggers' => ['aircraft','airplane'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'model','type'=>'string'],['name'=>'registration','type'=>'string'],['name'=>'capacity','type'=>'int'],['name'=>'airline','type'=>'string']],
                'related' => [],
            ],

            // ── PROJECT MANAGEMENT ──
            'Project' => [
                'triggers' => ['project','projet','pm','project_management','kanban','agile','jira','trello'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'status','type'=>'string'],['name'=>'start_date','type'=>'date'],['name'=>'end_date','type'=>'date'],['name'=>'owner_id','type'=>'int']],
                'related' => ['Task','Team','Milestone'],
            ],
            'Task' => [
                'triggers' => ['task','tache','todo','issue','ticket','backlog'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'project_id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'status','type'=>'string'],['name'=>'priority','type'=>'string'],['name'=>'assignee_id','type'=>'int'],['name'=>'due_date','type'=>'date']],
                'related' => [],
            ],
            'Team' => [
                'triggers' => ['team','equipe','group','groupe'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text']],
                'related' => ['TeamMember'],
            ],
            'TeamMember' => [
                'triggers' => ['team_member'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'team_id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'role','type'=>'string']],
                'related' => [],
            ],
            'Milestone' => [
                'triggers' => ['milestone','jalon','objectif','goal'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'project_id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'due_date','type'=>'date'],['name'=>'completed','type'=>'boolean']],
                'related' => [],
            ],

            // ── AGRICULTURE / FARM ──
            'Farm' => [
                'triggers' => ['farm','agriculture','ferme','crop','culture','harvest','recolte','elevage','livestock','betail'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'location','type'=>'string'],['name'=>'area_hectares','type'=>'decimal'],['name'=>'owner_name','type'=>'string']],
                'related' => ['Field','Crop','Livestock','Equipment'],
            ],
            'Field' => [
                'triggers' => ['field','parcelle','terrain'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'farm_id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'area_hectares','type'=>'decimal'],['name'=>'soil_type','type'=>'string']],
                'related' => [],
            ],
            'Crop' => [
                'triggers' => ['crop','culture','plante','seed','semence'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'field_id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'planted_at','type'=>'date'],['name'=>'expected_harvest','type'=>'date'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'Livestock' => [
                'triggers' => ['livestock','animal','betail','cattle','poultry','sheep','chicken','vache','mouton'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'farm_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'breed','type'=>'string'],['name'=>'count','type'=>'int'],['name'=>'health_status','type'=>'string']],
                'related' => [],
            ],
            'Equipment' => [
                'triggers' => ['equipment','materiel','tool','outil','tractor','tracteur','machine'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'purchased_at','type'=>'date']],
                'related' => [],
            ],

            // ── MUSIC / STREAMING ──
            'Song' => [
                'triggers' => ['song','music','musique','chanson','track','playlist','album','spotify','streaming','audio'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'artist_id','type'=>'int'],['name'=>'album_id','type'=>'int'],['name'=>'duration_sec','type'=>'int'],['name'=>'genre','type'=>'string']],
                'related' => ['Artist','Album','Playlist'],
            ],
            'Artist' => [
                'triggers' => ['artist','artiste','musicien','musician','singer','chanteur','band','groupe'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'genre','type'=>'string'],['name'=>'bio','type'=>'text'],['name'=>'country','type'=>'string']],
                'related' => ['Album'],
            ],
            'Album' => [
                'triggers' => ['album','disc','disque'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'artist_id','type'=>'int'],['name'=>'released_at','type'=>'date'],['name'=>'genre','type'=>'string']],
                'related' => [],
            ],
            'Playlist' => [
                'triggers' => ['playlist','queue','mix'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'user_id','type'=>'int'],['name'=>'is_public','type'=>'boolean'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── GAME / GAMING ──
            'Player' => [
                'triggers' => ['game','gaming','player','joueur','jeu','video_game','esport','leaderboard','score'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'username','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'level','type'=>'int'],['name'=>'xp','type'=>'int'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['GameSession','Achievement','Leaderboard','Inventory'],
            ],
            'GameSession' => [
                'triggers' => ['game_session','match','partie','round'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'player_id','type'=>'int'],['name'=>'game','type'=>'string'],['name'=>'score','type'=>'int'],['name'=>'duration_sec','type'=>'int'],['name'=>'played_at','type'=>'datetime']],
                'related' => [],
            ],
            'Achievement' => [
                'triggers' => ['achievement','badge','trophy','trophee','unlock','reward','recompense'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'xp_reward','type'=>'int']],
                'related' => [],
            ],
            'Leaderboard' => [
                'triggers' => ['leaderboard','ranking','classement'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'player_id','type'=>'int'],['name'=>'game','type'=>'string'],['name'=>'score','type'=>'int'],['name'=>'rank','type'=>'int']],
                'related' => [],
            ],
            'Inventory' => [
                'triggers' => ['inventory','inventaire','backpack'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'player_id','type'=>'int'],['name'=>'item_name','type'=>'string'],['name'=>'quantity','type'=>'int'],['name'=>'rarity','type'=>'string']],
                'related' => [],
            ],

            // ── ELECTION / VOTING ──
            'Election' => [
                'triggers' => ['election','vote','voting','ballot','poll','scrutin','referendum','candidate','candidat'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'start_date','type'=>'date'],['name'=>'end_date','type'=>'date'],['name'=>'status','type'=>'string']],
                'related' => ['Candidate','Voter','Ballot','ElectionResult'],
            ],
            'Candidate' => [
                'triggers' => ['candidate','candidat'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'election_id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'party','type'=>'string'],['name'=>'bio','type'=>'text']],
                'related' => [],
            ],
            'Voter' => [
                'triggers' => ['voter','electeur','citizen','citoyen'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'identity_number','type'=>'string'],['name'=>'region','type'=>'string']],
                'related' => [],
            ],
            'Ballot' => [
                'triggers' => ['ballot','bulletin'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'election_id','type'=>'int'],['name'=>'voter_id','type'=>'int'],['name'=>'candidate_id','type'=>'int'],['name'=>'cast_at','type'=>'datetime']],
                'related' => [],
            ],
            'ElectionResult' => [
                'triggers' => ['election_result','resultat'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'election_id','type'=>'int'],['name'=>'candidate_id','type'=>'int'],['name'=>'total_votes','type'=>'int'],['name'=>'percentage','type'=>'decimal']],
                'related' => [],
            ],

            // ── PARKING ──
            'ParkingLot' => [
                'triggers' => ['parking','lot','stationnement'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'string'],['name'=>'total_spots','type'=>'int'],['name'=>'hourly_rate','type'=>'decimal']],
                'related' => ['ParkingSpot','ParkingTicket'],
            ],
            'ParkingSpot' => [
                'triggers' => ['parking_spot','spot','place'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'lot_id','type'=>'int'],['name'=>'number','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'is_occupied','type'=>'boolean']],
                'related' => [],
            ],
            'ParkingTicket' => [
                'triggers' => ['parking_ticket'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'spot_id','type'=>'int'],['name'=>'plate_number','type'=>'string'],['name'=>'entered_at','type'=>'datetime'],['name'=>'exited_at','type'=>'datetime'],['name'=>'amount','type'=>'decimal']],
                'related' => [],
            ],

            // ── PHARMACY / DRUGSTORE ──
            'Pharmacy' => [
                'triggers' => ['pharmacy','pharmacie','drugstore','medicament','drug','parapharmacie'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'phone','type'=>'string'],['name'=>'license_number','type'=>'string']],
                'related' => ['Medication','PharmacyCustomer','PharmacyOrder','Pharmacist'],
            ],
            'Medication' => [
                'triggers' => ['medication','medicament','drug','remedy','pill','tablet'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'dosage','type'=>'string'],['name'=>'form','type'=>'string'],['name'=>'manufacturer','type'=>'string'],['name'=>'price','type'=>'decimal'],['name'=>'stock','type'=>'int'],['name'=>'requires_prescription','type'=>'boolean']],
                'related' => [],
            ],
            'Pharmacist' => [
                'triggers' => ['pharmacist','pharmacien'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'license_number','type'=>'string'],['name'=>'phone','type'=>'string']],
                'related' => [],
            ],
            'PharmacyCustomer' => [
                'triggers' => ['pharmacy_customer'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'insurance_id','type'=>'string']],
                'related' => [],
            ],
            'PharmacyOrder' => [
                'triggers' => ['pharmacy_order'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'customer_id','type'=>'int'],['name'=>'pharmacist_id','type'=>'int'],['name'=>'total','type'=>'decimal'],['name'=>'prescribed','type'=>'boolean'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── VETERINARY / PET ──
            'Pet' => [
                'triggers' => ['pet','animal','vet','veterinary','veterinaire','chien','chat','dog','cat','bird','oiseau'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'species','type'=>'string'],['name'=>'breed','type'=>'string'],['name'=>'date_of_birth','type'=>'date'],['name'=>'owner_id','type'=>'int'],['name'=>'weight','type'=>'decimal']],
                'related' => ['PetOwner','VetVisit','Vaccination','VetDoctor'],
            ],
            'PetOwner' => [
                'triggers' => ['pet_owner'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'address','type'=>'text']],
                'related' => [],
            ],
            'VetVisit' => [
                'triggers' => ['vet_visit','consultation'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'pet_id','type'=>'int'],['name'=>'vet_doctor_id','type'=>'int'],['name'=>'reason','type'=>'text'],['name'=>'diagnosis','type'=>'text'],['name'=>'treatment','type'=>'text'],['name'=>'visit_date','type'=>'datetime']],
                'related' => [],
            ],
            'VetDoctor' => [
                'triggers' => ['vet_doctor','veterinarian'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'specialization','type'=>'string'],['name'=>'phone','type'=>'string']],
                'related' => [],
            ],
            'Vaccination' => [
                'triggers' => ['vaccination','vaccine','vaccin','immunization'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'pet_id','type'=>'int'],['name'=>'vaccine_name','type'=>'string'],['name'=>'administered_at','type'=>'date'],['name'=>'next_due','type'=>'date']],
                'related' => [],
            ],

            // ── LEGAL / LAW FIRM ──
            'LawFirm' => [
                'triggers' => ['law','legal','lawyer','avocat','cabinet','attorney','juridique','droit','court','tribunal','justice'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'phone','type'=>'string'],['name'=>'specialization','type'=>'string']],
                'related' => ['Lawyer','LegalCase','Client','CourtHearing','LegalDocument'],
            ],
            'Lawyer' => [
                'triggers' => ['lawyer','avocat','attorney','barrister','solicitor'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'bar_number','type'=>'string'],['name'=>'specialization','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string']],
                'related' => ['LegalCase'],
            ],
            'LegalCase' => [
                'triggers' => ['case','affaire','dossier','lawsuit','proces','litige'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'case_number','type'=>'string'],['name'=>'title','type'=>'string'],['name'=>'client_id','type'=>'int'],['name'=>'lawyer_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'filed_at','type'=>'date']],
                'related' => ['CourtHearing','LegalDocument'],
            ],
            'Client' => [
                'triggers' => ['client'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'type','type'=>'string']],
                'related' => [],
            ],
            'CourtHearing' => [
                'triggers' => ['hearing','audience','seance','trial'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'case_id','type'=>'int'],['name'=>'court','type'=>'string'],['name'=>'scheduled_at','type'=>'datetime'],['name'=>'outcome','type'=>'text']],
                'related' => [],
            ],
            'LegalDocument' => [
                'triggers' => ['legal_document','document','piece','evidence','preuve'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'case_id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'file_path','type'=>'string'],['name'=>'uploaded_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── CONSTRUCTION ──
            'ConstructionProject' => [
                'triggers' => ['construction','building','batiment','chantier','btp','architect','architecte','engineering','genie_civil'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'location','type'=>'string'],['name'=>'budget','type'=>'decimal'],['name'=>'start_date','type'=>'date'],['name'=>'end_date','type'=>'date'],['name'=>'status','type'=>'string']],
                'related' => ['Contractor','Blueprint','Material','Inspection','Worker'],
            ],
            'Contractor' => [
                'triggers' => ['contractor','entrepreneur','sous_traitant','subcontractor'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'company_name','type'=>'string'],['name'=>'contact_person','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'specialization','type'=>'string']],
                'related' => [],
            ],
            'Blueprint' => [
                'triggers' => ['blueprint','plan','schema','drawing','dessin'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'project_id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'version','type'=>'int'],['name'=>'file_path','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],
            'Material' => [
                'triggers' => ['material','materiau','supply','fourniture'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'unit','type'=>'string'],['name'=>'unit_price','type'=>'decimal'],['name'=>'quantity','type'=>'int'],['name'=>'supplier_id','type'=>'int']],
                'related' => [],
            ],
            'Inspection' => [
                'triggers' => ['inspection','controle','quality_check','verification'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'project_id','type'=>'int'],['name'=>'inspector','type'=>'string'],['name'=>'date','type'=>'date'],['name'=>'result','type'=>'string'],['name'=>'notes','type'=>'text']],
                'related' => [],
            ],
            'Worker' => [
                'triggers' => ['worker','ouvrier','laborer','craftsman','artisan'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'role','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'daily_rate','type'=>'decimal']],
                'related' => [],
            ],

            // ── TELECOM ──
            'TelecomSubscriber' => [
                'triggers' => ['telecom','telephone','mobile','sim','gsm','4g','5g','operateur','operator','cellular','cellulaire','internet_provider','isp','fibre','adsl'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'phone_number','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'activated_at','type'=>'date']],
                'related' => ['TelecomPlan','SimCard','UsageRecord','TelecomInvoice'],
            ],
            'TelecomPlan' => [
                'triggers' => ['telecom_plan','forfait','bundle','package'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'data_gb','type'=>'int'],['name'=>'minutes','type'=>'int'],['name'=>'sms','type'=>'int'],['name'=>'price','type'=>'decimal']],
                'related' => [],
            ],
            'SimCard' => [
                'triggers' => ['sim_card','sim','esim'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'subscriber_id','type'=>'int'],['name'=>'iccid','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'activated_at','type'=>'date']],
                'related' => [],
            ],
            'UsageRecord' => [
                'triggers' => ['usage','cdr','call_detail','consommation'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'subscriber_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'duration_sec','type'=>'int'],['name'=>'data_mb','type'=>'decimal'],['name'=>'recorded_at','type'=>'datetime']],
                'related' => [],
            ],
            'TelecomInvoice' => [
                'triggers' => ['telecom_invoice','facture'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'subscriber_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'period','type'=>'string'],['name'=>'due_date','type'=>'date'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],

            // ── CRM ──
            'CrmContact' => [
                'triggers' => ['crm','customer_relationship','lead','prospect','pipeline','sales','vente','commercial'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'company','type'=>'string'],['name'=>'source','type'=>'string'],['name'=>'status','type'=>'string']],
                'related' => ['Deal','Activity','CrmNote'],
            ],
            'Deal' => [
                'triggers' => ['deal','opportunity','opportunite','affaire'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'contact_id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'value','type'=>'decimal'],['name'=>'stage','type'=>'string'],['name'=>'expected_close','type'=>'date'],['name'=>'assigned_to','type'=>'int']],
                'related' => [],
            ],
            'Activity' => [
                'triggers' => ['activity','activite','call','meeting','email_sent'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'contact_id','type'=>'int'],['name'=>'deal_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'scheduled_at','type'=>'datetime']],
                'related' => [],
            ],
            'CrmNote' => [
                'triggers' => ['crm_note'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'contact_id','type'=>'int'],['name'=>'content','type'=>'text'],['name'=>'created_by','type'=>'int'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── RECRUITMENT / JOBS ──
            'JobPosting' => [
                'triggers' => ['recruitment','recrutement','job','emploi','career','carriere','hiring','embauche','offre','vacancy'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'department','type'=>'string'],['name'=>'location','type'=>'string'],['name'=>'salary_range','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'posted_at','type'=>'date']],
                'related' => ['Applicant','JobApplication','Interview'],
            ],
            'Applicant' => [
                'triggers' => ['applicant','candidat','candidate'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'resume_path','type'=>'string'],['name'=>'applied_at','type'=>'date']],
                'related' => [],
            ],
            'JobApplication' => [
                'triggers' => ['application','candidature','apply'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'job_id','type'=>'int'],['name'=>'applicant_id','type'=>'int'],['name'=>'status','type'=>'string'],['name'=>'cover_letter','type'=>'text'],['name'=>'submitted_at','type'=>'datetime']],
                'related' => [],
            ],
            'Interview' => [
                'triggers' => ['interview','entretien'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'application_id','type'=>'int'],['name'=>'interviewer','type'=>'string'],['name'=>'scheduled_at','type'=>'datetime'],['name'=>'type','type'=>'string'],['name'=>'feedback','type'=>'text'],['name'=>'result','type'=>'string']],
                'related' => [],
            ],

            // ── E-LEARNING / LMS ──
            'OnlineCourse' => [
                'triggers' => ['elearning','e-learning','lms','online_course','mooc','udemy','coursera','formation','training_online','distance_learning'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'instructor_id','type'=>'int'],['name'=>'price','type'=>'decimal'],['name'=>'level','type'=>'string'],['name'=>'duration_hours','type'=>'int'],['name'=>'published_at','type'=>'date']],
                'related' => ['Instructor','Lesson','OnlineEnrollment','Quiz','Certificate'],
            ],
            'Instructor' => [
                'triggers' => ['instructor','formateur'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'bio','type'=>'text'],['name'=>'expertise','type'=>'string']],
                'related' => [],
            ],
            'Lesson' => [
                'triggers' => ['lesson','lecon','lecture','chapter','chapitre','video'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'course_id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'content_type','type'=>'string'],['name'=>'content_url','type'=>'string'],['name'=>'duration_min','type'=>'int'],['name'=>'position','type'=>'int']],
                'related' => [],
            ],
            'OnlineEnrollment' => [
                'triggers' => ['online_enrollment'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'course_id','type'=>'int'],['name'=>'student_id','type'=>'int'],['name'=>'enrolled_at','type'=>'date'],['name'=>'progress','type'=>'int'],['name'=>'completed','type'=>'boolean']],
                'related' => [],
            ],
            'Quiz' => [
                'triggers' => ['quiz','questionnaire','qcm','exam_online','test_online'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'lesson_id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'questions_count','type'=>'int'],['name'=>'passing_score','type'=>'int'],['name'=>'time_limit_min','type'=>'int']],
                'related' => [],
            ],
            'Certificate' => [
                'triggers' => ['certificate','certificat','diploma','diplome','attestation'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'student_id','type'=>'int'],['name'=>'course_id','type'=>'int'],['name'=>'issued_at','type'=>'date'],['name'=>'certificate_url','type'=>'string']],
                'related' => [],
            ],

            // ── BEAUTY SALON / BARBER ──
            'Salon' => [
                'triggers' => ['salon','beauty','coiffure','coiffeur','barber','hairdresser','spa','esthetique','nail','manucure','pedicure','maquillage','makeup'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'phone','type'=>'string'],['name'=>'opening_hours','type'=>'string']],
                'related' => ['Stylist','SalonService','SalonAppointment','SalonClient'],
            ],
            'Stylist' => [
                'triggers' => ['stylist','coiffeur','beautician','estheticienne'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'specialization','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'rating','type'=>'decimal']],
                'related' => [],
            ],
            'SalonService' => [
                'triggers' => ['salon_service','haircut','coupe','coloring','coloration','styling'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'duration_min','type'=>'int'],['name'=>'price','type'=>'decimal'],['name'=>'category','type'=>'string']],
                'related' => [],
            ],
            'SalonAppointment' => [
                'triggers' => ['salon_appointment'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'client_id','type'=>'int'],['name'=>'stylist_id','type'=>'int'],['name'=>'service_id','type'=>'int'],['name'=>'scheduled_at','type'=>'datetime'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'SalonClient' => [
                'triggers' => ['salon_client'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'preferences','type'=>'text']],
                'related' => [],
            ],

            // ── CAR RENTAL ──
            'RentalCar' => [
                'triggers' => ['car_rental','rental','location_voiture','louer','rent_a_car','hire'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'brand','type'=>'string'],['name'=>'model','type'=>'string'],['name'=>'plate_number','type'=>'string'],['name'=>'year','type'=>'int'],['name'=>'daily_rate','type'=>'decimal'],['name'=>'status','type'=>'string']],
                'related' => ['RentalCustomer','RentalBooking','RentalPayment'],
            ],
            'RentalCustomer' => [
                'triggers' => ['rental_customer'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'license_number','type'=>'string'],['name'=>'address','type'=>'text']],
                'related' => [],
            ],
            'RentalBooking' => [
                'triggers' => ['rental_booking'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'car_id','type'=>'int'],['name'=>'customer_id','type'=>'int'],['name'=>'pickup_date','type'=>'date'],['name'=>'return_date','type'=>'date'],['name'=>'total_cost','type'=>'decimal'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'RentalPayment' => [
                'triggers' => ['rental_payment'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'booking_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'method','type'=>'string'],['name'=>'paid_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── TAXI / RIDE-SHARING ──
            'RideRequest' => [
                'triggers' => ['taxi','uber','bolt','careem','ride','vtc','ride_sharing','rideshare','hailing'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'rider_id','type'=>'int'],['name'=>'driver_id','type'=>'int'],['name'=>'pickup_location','type'=>'string'],['name'=>'dropoff_location','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'requested_at','type'=>'datetime']],
                'related' => ['Rider','RideDriver','RidePayment','RideRating'],
            ],
            'Rider' => [
                'triggers' => ['rider','passager'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'rating','type'=>'decimal']],
                'related' => [],
            ],
            'RideDriver' => [
                'triggers' => ['ride_driver'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'license_number','type'=>'string'],['name'=>'vehicle_info','type'=>'string'],['name'=>'rating','type'=>'decimal'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'RidePayment' => [
                'triggers' => ['ride_payment'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'ride_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'method','type'=>'string'],['name'=>'tip','type'=>'decimal'],['name'=>'paid_at','type'=>'datetime']],
                'related' => [],
            ],
            'RideRating' => [
                'triggers' => ['ride_rating'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'ride_id','type'=>'int'],['name'=>'rated_by','type'=>'int'],['name'=>'stars','type'=>'int'],['name'=>'comment','type'=>'text']],
                'related' => [],
            ],

            // ── COURIER / DELIVERY ──
            'Parcel' => [
                'triggers' => ['courier','delivery','parcel','colis','livraison','shipping','expedition','fedex','dhl','poste','postal','envoi'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'tracking_number','type'=>'string'],['name'=>'sender_id','type'=>'int'],['name'=>'receiver_name','type'=>'string'],['name'=>'receiver_address','type'=>'text'],['name'=>'weight_kg','type'=>'decimal'],['name'=>'status','type'=>'string']],
                'related' => ['Sender','DeliveryAgent','TrackingEvent','DeliveryZone'],
            ],
            'Sender' => [
                'triggers' => ['sender','expediteur'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'address','type'=>'text']],
                'related' => [],
            ],
            'DeliveryAgent' => [
                'triggers' => ['delivery_agent','livreur','courier_agent'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'vehicle_type','type'=>'string'],['name'=>'zone_id','type'=>'int'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'TrackingEvent' => [
                'triggers' => ['tracking','suivi'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'parcel_id','type'=>'int'],['name'=>'location','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'timestamp','type'=>'datetime']],
                'related' => [],
            ],
            'DeliveryZone' => [
                'triggers' => ['delivery_zone','zone'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'city','type'=>'string'],['name'=>'delivery_fee','type'=>'decimal']],
                'related' => [],
            ],

            // ── CHARITY / NGO ──
            'CharityOrg' => [
                'triggers' => ['charity','ngo','nonprofit','ong','association','benevolat','volunteer','donation','fundraising','humanitaire'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'mission','type'=>'text'],['name'=>'website','type'=>'string'],['name'=>'founded_at','type'=>'date']],
                'related' => ['Donor','Donation','Volunteer','CharityProject','Beneficiary'],
            ],
            'Donor' => [
                'triggers' => ['donor','donateur','donneur'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'type','type'=>'string']],
                'related' => [],
            ],
            'Donation' => [
                'triggers' => ['donation','don','contribution','gift'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'donor_id','type'=>'int'],['name'=>'project_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'method','type'=>'string'],['name'=>'donated_at','type'=>'datetime']],
                'related' => [],
            ],
            'Volunteer' => [
                'triggers' => ['volunteer','benevole'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'availability','type'=>'string'],['name'=>'skills','type'=>'text']],
                'related' => [],
            ],
            'Beneficiary' => [
                'triggers' => ['beneficiary','beneficiaire'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'age','type'=>'int'],['name'=>'location','type'=>'string'],['name'=>'needs','type'=>'text']],
                'related' => [],
            ],
            'CharityProject' => [
                'triggers' => ['charity_project','cause','campaign','campagne'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'goal_amount','type'=>'decimal'],['name'=>'raised_amount','type'=>'decimal'],['name'=>'start_date','type'=>'date'],['name'=>'end_date','type'=>'date']],
                'related' => [],
            ],

            // ── DENTIST ──
            'DentalClinic' => [
                'triggers' => ['dentist','dental','dentaire','orthodont','teeth','dent','implant','root_canal'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'phone','type'=>'string']],
                'related' => ['Dentist','DentalPatient','DentalAppointment','Treatment'],
            ],
            'Dentist' => [
                'triggers' => ['dentist_doctor','dentiste'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'specialization','type'=>'string'],['name'=>'license_number','type'=>'string'],['name'=>'phone','type'=>'string']],
                'related' => [],
            ],
            'DentalPatient' => [
                'triggers' => ['dental_patient'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'date_of_birth','type'=>'date'],['name'=>'phone','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'medical_history','type'=>'text']],
                'related' => [],
            ],
            'DentalAppointment' => [
                'triggers' => ['dental_appointment'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'patient_id','type'=>'int'],['name'=>'dentist_id','type'=>'int'],['name'=>'treatment_id','type'=>'int'],['name'=>'scheduled_at','type'=>'datetime'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'Treatment' => [
                'triggers' => ['treatment','traitement','soin','procedure','intervention'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'cost','type'=>'decimal'],['name'=>'duration_min','type'=>'int']],
                'related' => [],
            ],

            // ── SUPERMARKET / GROCERY ──
            'Supermarket' => [
                'triggers' => ['supermarket','grocery','epicerie','supermarche','marche','market','hypermarket','convenience'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'phone','type'=>'string'],['name'=>'manager','type'=>'string']],
                'related' => ['GroceryProduct','GrocerySupplier','Cashier','Shelf','GroceryInvoice'],
            ],
            'GroceryProduct' => [
                'triggers' => ['grocery_product'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'barcode','type'=>'string'],['name'=>'category','type'=>'string'],['name'=>'price','type'=>'decimal'],['name'=>'stock','type'=>'int'],['name'=>'expiry_date','type'=>'date']],
                'related' => [],
            ],
            'Cashier' => [
                'triggers' => ['cashier','caissier','caisse'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'register_number','type'=>'int'],['name'=>'shift','type'=>'string']],
                'related' => [],
            ],
            'GrocerySupplier' => [
                'triggers' => ['grocery_supplier'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'contact','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'products_supplied','type'=>'text']],
                'related' => [],
            ],
            'Shelf' => [
                'triggers' => ['shelf','rayon','aisle','alley'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'category','type'=>'string'],['name'=>'floor','type'=>'int']],
                'related' => [],
            ],
            'GroceryInvoice' => [
                'triggers' => ['grocery_invoice','receipt','ticket_de_caisse','facture'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'cashier_id','type'=>'int'],['name'=>'total','type'=>'decimal'],['name'=>'payment_method','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── HELPDESK / SUPPORT ──
            'SupportTicket' => [
                'triggers' => ['helpdesk','support','ticket','assistance','help_desk','service_desk','incident','bug_report','issue_tracker'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'subject','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'priority','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'requester_id','type'=>'int'],['name'=>'assigned_to','type'=>'int'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['SupportAgent','TicketComment','KnowledgeBase'],
            ],
            'SupportAgent' => [
                'triggers' => ['support_agent','agent','technicien','technician'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'department','type'=>'string'],['name'=>'active','type'=>'boolean']],
                'related' => [],
            ],
            'TicketComment' => [
                'triggers' => ['ticket_comment','reply','reponse'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'ticket_id','type'=>'int'],['name'=>'author_id','type'=>'int'],['name'=>'body','type'=>'text'],['name'=>'is_internal','type'=>'boolean'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],
            'KnowledgeBase' => [
                'triggers' => ['knowledge_base','faq','documentation','help_article','base_de_connaissance'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'content','type'=>'text'],['name'=>'category','type'=>'string'],['name'=>'views','type'=>'int'],['name'=>'published_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── SURVEY / POLL ──
            'Survey' => [
                'triggers' => ['survey','sondage','questionnaire','poll','form','formulaire','enquete'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'status','type'=>'string'],['name'=>'created_by','type'=>'int'],['name'=>'starts_at','type'=>'datetime'],['name'=>'ends_at','type'=>'datetime']],
                'related' => ['Question','SurveyResponse','AnswerOption'],
            ],
            'Question' => [
                'triggers' => ['question'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'survey_id','type'=>'int'],['name'=>'text','type'=>'text'],['name'=>'type','type'=>'string'],['name'=>'required','type'=>'boolean'],['name'=>'position','type'=>'int']],
                'related' => ['AnswerOption'],
            ],
            'AnswerOption' => [
                'triggers' => ['answer','option','choix','reponse','answer_option'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'question_id','type'=>'int'],['name'=>'text','type'=>'string'],['name'=>'position','type'=>'int']],
                'related' => [],
            ],
            'SurveyResponse' => [
                'triggers' => ['survey_response'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'survey_id','type'=>'int'],['name'=>'respondent_id','type'=>'int'],['name'=>'submitted_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── FREELANCE / MARKETPLACE ──
            'Freelancer' => [
                'triggers' => ['freelance','freelancer','marketplace','fiverr','upwork','gig','prestation','independant','consultant'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'skills','type'=>'text'],['name'=>'hourly_rate','type'=>'decimal'],['name'=>'rating','type'=>'decimal'],['name'=>'bio','type'=>'text']],
                'related' => ['Gig','FreelanceOrder','FreelanceReview','FreelanceClient'],
            ],
            'Gig' => [
                'triggers' => ['gig','prestation','service_offer','mission'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'freelancer_id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'price','type'=>'decimal'],['name'=>'delivery_days','type'=>'int'],['name'=>'category','type'=>'string']],
                'related' => [],
            ],
            'FreelanceOrder' => [
                'triggers' => ['freelance_order','commande'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'gig_id','type'=>'int'],['name'=>'client_id','type'=>'int'],['name'=>'total','type'=>'decimal'],['name'=>'status','type'=>'string'],['name'=>'due_date','type'=>'date'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],
            'FreelanceReview' => [
                'triggers' => ['freelance_review'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'order_id','type'=>'int'],['name'=>'rating','type'=>'int'],['name'=>'comment','type'=>'text'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],
            'FreelanceClient' => [
                'triggers' => ['freelance_client'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'company','type'=>'string']],
                'related' => [],
            ],

            // ── BLOOD BANK ──
            'BloodBank' => [
                'triggers' => ['blood','sang','blood_bank','banque_de_sang','transfusion','hemoglobin','plasma'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'phone','type'=>'string']],
                'related' => ['BloodDonor','BloodUnit','BloodRequest','DonationEvent'],
            ],
            'BloodDonor' => [
                'triggers' => ['blood_donor','donneur_de_sang'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'blood_type','type'=>'string'],['name'=>'date_of_birth','type'=>'date'],['name'=>'phone','type'=>'string'],['name'=>'last_donation','type'=>'date']],
                'related' => [],
            ],
            'BloodUnit' => [
                'triggers' => ['blood_unit','poche_de_sang'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'donor_id','type'=>'int'],['name'=>'blood_type','type'=>'string'],['name'=>'volume_ml','type'=>'int'],['name'=>'collected_at','type'=>'date'],['name'=>'expiry_date','type'=>'date'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'BloodRequest' => [
                'triggers' => ['blood_request','demande_de_sang'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'hospital','type'=>'string'],['name'=>'blood_type','type'=>'string'],['name'=>'units_needed','type'=>'int'],['name'=>'urgency','type'=>'string'],['name'=>'requested_at','type'=>'datetime'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'DonationEvent' => [
                'triggers' => ['donation_event','collecte','blood_drive'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'location','type'=>'string'],['name'=>'date','type'=>'date'],['name'=>'goal_units','type'=>'int'],['name'=>'collected_units','type'=>'int']],
                'related' => [],
            ],

            // ── EMERGENCY / AMBULANCE ──
            'EmergencyCall' => [
                'triggers' => ['emergency','urgence','ambulance','samu','911','pompier','fire_department','secours','rescue'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'caller_name','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'location','type'=>'text'],['name'=>'type','type'=>'string'],['name'=>'priority','type'=>'string'],['name'=>'received_at','type'=>'datetime']],
                'related' => ['EmergencyUnit','Dispatch','Victim'],
            ],
            'EmergencyUnit' => [
                'triggers' => ['emergency_unit','unite','equipe','crew','brigade'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'vehicle_id','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'base_location','type'=>'string']],
                'related' => [],
            ],
            'Dispatch' => [
                'triggers' => ['dispatch','envoi','deploiement'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'call_id','type'=>'int'],['name'=>'unit_id','type'=>'int'],['name'=>'dispatched_at','type'=>'datetime'],['name'=>'arrived_at','type'=>'datetime'],['name'=>'resolved_at','type'=>'datetime']],
                'related' => [],
            ],
            'Victim' => [
                'triggers' => ['victim','victime','injured','blesse'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'call_id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'age','type'=>'int'],['name'=>'condition','type'=>'text'],['name'=>'transported_to','type'=>'string']],
                'related' => [],
            ],

            // ── WEDDING PLANNING ──
            'Wedding' => [
                'triggers' => ['wedding','mariage','bride','groom','nuptial','celebration'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'bride_name','type'=>'string'],['name'=>'groom_name','type'=>'string'],['name'=>'date','type'=>'date'],['name'=>'venue_id','type'=>'int'],['name'=>'budget','type'=>'decimal'],['name'=>'theme','type'=>'string']],
                'related' => ['WeddingVenue','WeddingGuest','WeddingVendor','WeddingTask'],
            ],
            'WeddingVenue' => [
                'triggers' => ['wedding_venue'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'capacity','type'=>'int'],['name'=>'price','type'=>'decimal']],
                'related' => [],
            ],
            'WeddingGuest' => [
                'triggers' => ['wedding_guest','invite','invitee'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'wedding_id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'rsvp','type'=>'string'],['name'=>'table_number','type'=>'int'],['name'=>'plus_one','type'=>'boolean']],
                'related' => [],
            ],
            'WeddingVendor' => [
                'triggers' => ['wedding_vendor','prestataire','photographer','photographe','caterer','traiteur','florist','fleuriste','dj'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'service_type','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'cost','type'=>'decimal'],['name'=>'booked','type'=>'boolean']],
                'related' => [],
            ],
            'WeddingTask' => [
                'triggers' => ['wedding_task'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'wedding_id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'due_date','type'=>'date'],['name'=>'status','type'=>'string'],['name'=>'assigned_to','type'=>'string']],
                'related' => [],
            ],

            // ── MUSEUM / GALLERY ──
            'Museum' => [
                'triggers' => ['museum','musee','gallery','galerie','exposition','exhibit','art','artwork','peinture','sculpture'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'opening_hours','type'=>'string'],['name'=>'entry_fee','type'=>'decimal']],
                'related' => ['Artwork','Exhibition','MuseumVisitor','Curator'],
            ],
            'Artwork' => [
                'triggers' => ['artwork','oeuvre','painting','sculpture_piece','artifact'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'artist','type'=>'string'],['name'=>'year','type'=>'int'],['name'=>'medium','type'=>'string'],['name'=>'value','type'=>'decimal'],['name'=>'location','type'=>'string']],
                'related' => [],
            ],
            'Exhibition' => [
                'triggers' => ['exhibition','exposition','expo'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'museum_id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'start_date','type'=>'date'],['name'=>'end_date','type'=>'date']],
                'related' => [],
            ],
            'MuseumVisitor' => [
                'triggers' => ['museum_visitor','visiteur'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'visit_date','type'=>'date'],['name'=>'ticket_type','type'=>'string']],
                'related' => [],
            ],
            'Curator' => [
                'triggers' => ['curator','conservateur'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'specialization','type'=>'string'],['name'=>'email','type'=>'string']],
                'related' => [],
            ],

            // ── ZOO / AQUARIUM ──
            'Zoo' => [
                'triggers' => ['zoo','aquarium','parc_animalier','wildlife','reserve','safari'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'area_hectares','type'=>'decimal'],['name'=>'entry_fee','type'=>'decimal']],
                'related' => ['ZooAnimal','Enclosure','Zookeeper','ZooVisitor'],
            ],
            'ZooAnimal' => [
                'triggers' => ['zoo_animal','specimen'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'species','type'=>'string'],['name'=>'date_of_birth','type'=>'date'],['name'=>'enclosure_id','type'=>'int'],['name'=>'health_status','type'=>'string'],['name'=>'diet','type'=>'text']],
                'related' => [],
            ],
            'Enclosure' => [
                'triggers' => ['enclosure','cage','habitat','bassin','tank'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'capacity','type'=>'int'],['name'=>'area_sqm','type'=>'decimal']],
                'related' => [],
            ],
            'Zookeeper' => [
                'triggers' => ['zookeeper','soigneur','keeper'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'specialization','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'assigned_enclosure','type'=>'int']],
                'related' => [],
            ],
            'ZooVisitor' => [
                'triggers' => ['zoo_visitor'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'visit_date','type'=>'date'],['name'=>'ticket_type','type'=>'string'],['name'=>'group_size','type'=>'int']],
                'related' => [],
            ],

            // ── BAKERY ──
            'Bakery' => [
                'triggers' => ['bakery','boulangerie','patisserie','pastry','bread','pain','gateau','cake','viennoiserie'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'phone','type'=>'string']],
                'related' => ['BakeryProduct','BakeryOrder','Baker','BakeryIngredient'],
            ],
            'BakeryProduct' => [
                'triggers' => ['bakery_product','baguette','croissant','tarte'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'category','type'=>'string'],['name'=>'price','type'=>'decimal'],['name'=>'available','type'=>'boolean']],
                'related' => [],
            ],
            'Baker' => [
                'triggers' => ['baker','boulanger','patissier'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'specialization','type'=>'string'],['name'=>'shift','type'=>'string']],
                'related' => [],
            ],
            'BakeryOrder' => [
                'triggers' => ['bakery_order'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'customer_name','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'items','type'=>'text'],['name'=>'total','type'=>'decimal'],['name'=>'pickup_at','type'=>'datetime'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'BakeryIngredient' => [
                'triggers' => ['ingredient','farine','flour','sugar','sucre','butter','beurre'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'unit','type'=>'string'],['name'=>'stock_quantity','type'=>'decimal'],['name'=>'min_quantity','type'=>'decimal'],['name'=>'supplier','type'=>'string']],
                'related' => [],
            ],

            // ── LAUNDRY ──
            'Laundry' => [
                'triggers' => ['laundry','pressing','laverie','dry_clean','nettoyage','linge','blanchisserie','ironing','repassage'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'phone','type'=>'string']],
                'related' => ['LaundryCustomer','LaundryOrder','LaundryService'],
            ],
            'LaundryCustomer' => [
                'triggers' => ['laundry_customer'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'address','type'=>'text']],
                'related' => [],
            ],
            'LaundryOrder' => [
                'triggers' => ['laundry_order'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'customer_id','type'=>'int'],['name'=>'service_id','type'=>'int'],['name'=>'items_count','type'=>'int'],['name'=>'weight_kg','type'=>'decimal'],['name'=>'total','type'=>'decimal'],['name'=>'status','type'=>'string'],['name'=>'dropped_at','type'=>'datetime'],['name'=>'ready_at','type'=>'datetime']],
                'related' => [],
            ],
            'LaundryService' => [
                'triggers' => ['laundry_service','wash','press','fold'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'price_per_kg','type'=>'decimal'],['name'=>'turnaround_hours','type'=>'int']],
                'related' => [],
            ],

            // ── ENERGY / UTILITY ──
            'UtilityAccount' => [
                'triggers' => ['electricity','electric','energie','energy','utility','eau','water','gas','gaz','compteur','meter','facture_electricite'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'customer_name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'meter_number','type'=>'string'],['name'=>'service_type','type'=>'string'],['name'=>'status','type'=>'string']],
                'related' => ['MeterReading','UtilityBill','ServiceRequest'],
            ],
            'MeterReading' => [
                'triggers' => ['meter_reading','releve','compteur'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'account_id','type'=>'int'],['name'=>'reading_value','type'=>'decimal'],['name'=>'read_at','type'=>'date'],['name'=>'reader','type'=>'string']],
                'related' => [],
            ],
            'UtilityBill' => [
                'triggers' => ['utility_bill','facture','bill'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'account_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'period_start','type'=>'date'],['name'=>'period_end','type'=>'date'],['name'=>'due_date','type'=>'date'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'ServiceRequest' => [
                'triggers' => ['service_request','reclamation','demande_service','outage','panne'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'account_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'priority','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── POLICE / LAW ENFORCEMENT ──
            'PoliceStation' => [
                'triggers' => ['police','gendarmerie','commissariat','law_enforcement','securite','security'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'phone','type'=>'string'],['name'=>'jurisdiction','type'=>'string']],
                'related' => ['Officer','CriminalCase','Suspect','Evidence'],
            ],
            'Officer' => [
                'triggers' => ['officer','officier','policier','agent_de_police','detective'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'badge_number','type'=>'string'],['name'=>'rank','type'=>'string'],['name'=>'department','type'=>'string']],
                'related' => [],
            ],
            'CriminalCase' => [
                'triggers' => ['criminal_case','crime','enquete','investigation'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'case_number','type'=>'string'],['name'=>'title','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'lead_officer_id','type'=>'int'],['name'=>'opened_at','type'=>'date']],
                'related' => [],
            ],
            'Suspect' => [
                'triggers' => ['suspect','accused','accuse','inculpe'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'case_id','type'=>'int'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'Evidence' => [
                'triggers' => ['evidence','preuve','indice','exhibit'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'case_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'collected_at','type'=>'datetime'],['name'=>'location','type'=>'string']],
                'related' => [],
            ],

            // ── DAYCARE / NURSERY ──
            'Daycare' => [
                'triggers' => ['daycare','nursery','creche','garderie','childcare','preschool','maternelle','kindergarten'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'capacity','type'=>'int'],['name'=>'phone','type'=>'string']],
                'related' => ['Child','Guardian','Caregiver','DaycareEnrollment','DailyReport'],
            ],
            'Child' => [
                'triggers' => ['child','enfant','bebe','baby','kid','toddler'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'date_of_birth','type'=>'date'],['name'=>'guardian_id','type'=>'int'],['name'=>'allergies','type'=>'text'],['name'=>'notes','type'=>'text']],
                'related' => [],
            ],
            'Guardian' => [
                'triggers' => ['guardian','parent','tuteur','responsable'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'relationship','type'=>'string'],['name'=>'address','type'=>'text']],
                'related' => [],
            ],
            'Caregiver' => [
                'triggers' => ['caregiver','educateur','nourrice','nounou','babysitter'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'phone','type'=>'string'],['name'=>'qualification','type'=>'string'],['name'=>'assigned_group','type'=>'string']],
                'related' => [],
            ],
            'DaycareEnrollment' => [
                'triggers' => ['daycare_enrollment','inscription_creche'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'child_id','type'=>'int'],['name'=>'daycare_id','type'=>'int'],['name'=>'start_date','type'=>'date'],['name'=>'schedule','type'=>'string'],['name'=>'monthly_fee','type'=>'decimal']],
                'related' => [],
            ],
            'DailyReport' => [
                'triggers' => ['daily_report','rapport','journal'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'child_id','type'=>'int'],['name'=>'date','type'=>'date'],['name'=>'meals','type'=>'text'],['name'=>'activities','type'=>'text'],['name'=>'nap_duration','type'=>'int'],['name'=>'notes','type'=>'text']],
                'related' => [],
            ],

            // ── CRYPTOCURRENCY / BLOCKCHAIN ──
            'CryptoWallet' => [
                'triggers' => ['crypto','cryptocurrency','bitcoin','ethereum','blockchain','wallet','portefeuille','token','nft','defi','web3'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'address','type'=>'string'],['name'=>'currency','type'=>'string'],['name'=>'balance','type'=>'decimal'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['CryptoTransaction','CryptoAsset','ExchangeAccount'],
            ],
            'CryptoTransaction' => [
                'triggers' => ['crypto_transaction'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'wallet_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'amount','type'=>'decimal'],['name'=>'fee','type'=>'decimal'],['name'=>'tx_hash','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],
            'CryptoAsset' => [
                'triggers' => ['crypto_asset','coin','token_asset'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'wallet_id','type'=>'int'],['name'=>'symbol','type'=>'string'],['name'=>'name','type'=>'string'],['name'=>'quantity','type'=>'decimal'],['name'=>'current_value','type'=>'decimal']],
                'related' => [],
            ],
            'ExchangeAccount' => [
                'triggers' => ['exchange','binance','coinbase','kraken','bourse'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'platform','type'=>'string'],['name'=>'api_key','type'=>'string'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],

            // ── IOT / SMART HOME ──
            'SmartDevice' => [
                'triggers' => ['iot','smart_home','domotique','smart','sensor','capteur','thermostat','camera','connected','connecte','arduino','raspberry'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'location','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'firmware_version','type'=>'string'],['name'=>'last_seen','type'=>'datetime']],
                'related' => ['SensorReading','Automation','DeviceGroup'],
            ],
            'SensorReading' => [
                'triggers' => ['sensor_reading','mesure','temperature','humidity','humidite'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'device_id','type'=>'int'],['name'=>'metric','type'=>'string'],['name'=>'value','type'=>'decimal'],['name'=>'unit','type'=>'string'],['name'=>'recorded_at','type'=>'datetime']],
                'related' => [],
            ],
            'Automation' => [
                'triggers' => ['automation','rule','regle','scenario','routine'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'trigger_condition','type'=>'text'],['name'=>'action','type'=>'text'],['name'=>'enabled','type'=>'boolean']],
                'related' => [],
            ],
            'DeviceGroup' => [
                'triggers' => ['device_group','piece','room_device'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'location','type'=>'string']],
                'related' => [],
            ],

            // ── RECYCLING / WASTE MANAGEMENT ──
            'WasteCollection' => [
                'triggers' => ['recycling','waste','dechet','poubelle','trash','garbage','collecte','tri','environnement','ecology','ecologie'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'zone_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'scheduled_date','type'=>'date'],['name'=>'status','type'=>'string']],
                'related' => ['CollectionZone','RecyclingCenter','WasteBin','WasteReport'],
            ],
            'CollectionZone' => [
                'triggers' => ['collection_zone'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'city','type'=>'string'],['name'=>'schedule','type'=>'string']],
                'related' => [],
            ],
            'RecyclingCenter' => [
                'triggers' => ['recycling_center','dechetterie','centre_de_tri'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'accepted_materials','type'=>'text'],['name'=>'opening_hours','type'=>'string']],
                'related' => [],
            ],
            'WasteBin' => [
                'triggers' => ['waste_bin','benne','container','conteneur'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'zone_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'capacity_liters','type'=>'int'],['name'=>'fill_level','type'=>'int'],['name'=>'location','type'=>'string']],
                'related' => [],
            ],
            'WasteReport' => [
                'triggers' => ['waste_report'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'zone_id','type'=>'int'],['name'=>'month','type'=>'string'],['name'=>'total_kg','type'=>'decimal'],['name'=>'recycled_kg','type'=>'decimal']],
                'related' => [],
            ],

            // ── AI / ML PLATFORM ──
            'MLModel' => [
                'triggers' => ['machine_learning','ml_model','artificial_intelligence','neural_network','deep_learning','ai_model','model_training','train_model','prediction','inference','llm','transformer','regression','classification','clustering','nlp','computer_vision','dataset','hyperparameter','epoch','overfitting','feature_engineering'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'version','type'=>'string'],['name'=>'framework','type'=>'string'],['name'=>'accuracy','type'=>'decimal'],['name'=>'status','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['Dataset','TrainingJob','ModelVersion','Experiment','Deployment'],
            ],
            'Dataset' => [
                'triggers' => ['dataset','training_data','data_pipeline','corpus','data_source','annotation'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'size_mb','type'=>'decimal'],['name'=>'format','type'=>'string'],['name'=>'source_url','type'=>'string'],['name'=>'labels','type'=>'text'],['name'=>'split_ratio','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['MLModel','TrainingJob'],
            ],
            'TrainingJob' => [
                'triggers' => ['training_job','training_run','train','fine_tune','finetune','checkpoint','gpu','cuda'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'model_id','type'=>'int'],['name'=>'dataset_id','type'=>'int'],['name'=>'status','type'=>'string'],['name'=>'epochs','type'=>'int'],['name'=>'batch_size','type'=>'int'],['name'=>'learning_rate','type'=>'decimal'],['name'=>'loss','type'=>'decimal'],['name'=>'started_at','type'=>'datetime'],['name'=>'finished_at','type'=>'datetime']],
                'related' => [],
            ],
            'Experiment' => [
                'triggers' => ['experiment','mlflow','wandb','tracking','hyperparameter_tuning','ablation'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'model_id','type'=>'int'],['name'=>'parameters','type'=>'json'],['name'=>'metrics','type'=>'json'],['name'=>'status','type'=>'string'],['name'=>'run_at','type'=>'datetime']],
                'related' => ['MLModel'],
            ],
            'ModelVersion' => [
                'triggers' => ['model_version','model_registry','artifact','checkpoint_file'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'model_id','type'=>'int'],['name'=>'version','type'=>'string'],['name'=>'file_path','type'=>'string'],['name'=>'metrics','type'=>'json'],['name'=>'is_production','type'=>'boolean'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── DEVOPS / CI-CD ──
            'CIPipeline' => [
                'triggers' => ['devops','ci_cd','cicd','pipeline','continuous_integration','continuous_deployment','jenkins','gitlab_ci','github_actions','travis','buildkite','circleci','workflow','automation_pipeline'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'repository_url','type'=>'string'],['name'=>'branch','type'=>'string'],['name'=>'trigger','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'last_run','type'=>'datetime']],
                'related' => ['Build','Deployment','Environment','Stage'],
            ],
            'Build' => [
                'triggers' => ['build','compilation','artifact_build','test_run'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'pipeline_id','type'=>'int'],['name'=>'commit_sha','type'=>'string'],['name'=>'branch','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'duration_sec','type'=>'int'],['name'=>'started_at','type'=>'datetime'],['name'=>'finished_at','type'=>'datetime'],['name'=>'logs_url','type'=>'string']],
                'related' => [],
            ],
            'Environment' => [
                'triggers' => ['environment','env','staging','production','development','namespace','kubernetes','k8s','cluster','infra'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'url','type'=>'string'],['name'=>'variables','type'=>'json'],['name'=>'status','type'=>'string']],
                'related' => ['Deployment'],
            ],
            'Stage' => [
                'triggers' => ['stage','step','job','task_ci'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'pipeline_id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'order','type'=>'int'],['name'=>'status','type'=>'string'],['name'=>'duration_sec','type'=>'int']],
                'related' => [],
            ],
            'Repository' => [
                'triggers' => ['repository','repo','git','github','gitlab','bitbucket','vcs','source_code','version_control'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'url','type'=>'string'],['name'=>'default_branch','type'=>'string'],['name'=>'visibility','type'=>'string'],['name'=>'owner_id','type'=>'int'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['CIPipeline','Build'],
            ],

            // ── SECURITY / IAM ──
            'Identity' => [
                'triggers' => ['iam','identity','sso','single_sign_on','oauth','openid','jwt','saml','authentication_service','identity_provider','idp','keycloak','auth0','okta'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'subject','type'=>'string'],['name'=>'provider','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['OAuthClient','APIKey','RoleAssignment','AuditLog'],
            ],
            'OAuthClient' => [
                'triggers' => ['oauth_client','oauth2','client_id','client_secret','access_token','refresh_token','scope','authorization_code'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'client_id','type'=>'string'],['name'=>'client_secret','type'=>'string'],['name'=>'redirect_uri','type'=>'string'],['name'=>'scopes','type'=>'text'],['name'=>'grant_types','type'=>'string'],['name'=>'is_trusted','type'=>'boolean']],
                'related' => ['AccessToken'],
            ],
            'AccessToken' => [
                'triggers' => ['access_token','token','bearer','jwt_token','api_token'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'client_id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'token','type'=>'string'],['name'=>'scopes','type'=>'text'],['name'=>'expires_at','type'=>'datetime'],['name'=>'revoked','type'=>'boolean']],
                'related' => [],
            ],
            'APIKey' => [
                'triggers' => ['api_key','apikey','api_secret','webhook_secret'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'key','type'=>'string'],['name'=>'permissions','type'=>'text'],['name'=>'last_used_at','type'=>'datetime'],['name'=>'expires_at','type'=>'datetime'],['name'=>'active','type'=>'boolean']],
                'related' => [],
            ],
            'AuditLog' => [
                'triggers' => ['audit_log','audit','log','event_log','activity_log','security_event','trace'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'action','type'=>'string'],['name'=>'resource','type'=>'string'],['name'=>'resource_id','type'=>'int'],['name'=>'ip_address','type'=>'string'],['name'=>'user_agent','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],
            'RoleAssignment' => [
                'triggers' => ['role_assignment','rbac','permission_grant','access_control'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'role','type'=>'string'],['name'=>'resource_type','type'=>'string'],['name'=>'resource_id','type'=>'int'],['name'=>'granted_by','type'=>'int'],['name'=>'expires_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── FINTECH / PAYMENT GATEWAY ──
            'PaymentGateway' => [
                'triggers' => ['payment_gateway','stripe','paypal','adyen','mollie','braintree','checkout_com','fintech','acquiring','merchant_payment','pos','point_of_sale'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'provider','type'=>'string'],['name'=>'api_key','type'=>'string'],['name'=>'mode','type'=>'string'],['name'=>'supported_currencies','type'=>'text'],['name'=>'webhook_url','type'=>'string']],
                'related' => ['PaymentIntent','Refund','Payout','MerchantAccount'],
            ],
            'PaymentIntent' => [
                'triggers' => ['payment_intent','charge','authorization','capture','settlement'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'gateway_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'currency','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'payment_method','type'=>'string'],['name'=>'customer_id','type'=>'int'],['name'=>'metadata','type'=>'json'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['Refund'],
            ],
            'Refund' => [
                'triggers' => ['refund','chargeback','dispute','reversal','remboursement'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'intent_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'reason','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],
            'Payout' => [
                'triggers' => ['payout','disbursement','withdrawal','virement_marchand'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'merchant_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'currency','type'=>'string'],['name'=>'bank_account','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'scheduled_at','type'=>'datetime']],
                'related' => [],
            ],
            'MerchantAccount' => [
                'triggers' => ['merchant','seller','marchand','vendor_account','marketplace_seller'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'business_name','type'=>'string'],['name'=>'email','type'=>'string'],['name'=>'iban','type'=>'string'],['name'=>'kyc_status','type'=>'string'],['name'=>'balance','type'=>'decimal'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['Payout'],
            ],
            'DigitalWallet' => [
                'triggers' => ['digital_wallet','mobile_payment','apple_pay','google_pay','wallet_app','portefeuille_numerique','contactless','nfc_payment','bnpl','buy_now_pay_later'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'balance','type'=>'decimal'],['name'=>'currency','type'=>'string'],['name'=>'pin_hash','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['WalletTransaction','LinkedCard'],
            ],
            'WalletTransaction' => [
                'triggers' => ['wallet_transaction','wallet_payment'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'wallet_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'amount','type'=>'decimal'],['name'=>'description','type'=>'string'],['name'=>'reference','type'=>'string'],['name'=>'created_at','type'=>'datetime']],
                'related' => [],
            ],
            'LinkedCard' => [
                'triggers' => ['linked_card','saved_card','carte_enregistree'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'wallet_id','type'=>'int'],['name'=>'last_four','type'=>'string'],['name'=>'brand','type'=>'string'],['name'=>'expires_at','type'=>'date'],['name'=>'is_default','type'=>'boolean']],
                'related' => [],
            ],

            // ── SUPPLY CHAIN ──
            'PurchaseOrder' => [
                'triggers' => ['supply_chain','purchase_order','procurement','sourcing','bon_de_commande','approvisionner','order_management','order_fulfillment','erp','enterprise_resource'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'supplier_id','type'=>'int'],['name'=>'warehouse_id','type'=>'int'],['name'=>'total_amount','type'=>'decimal'],['name'=>'status','type'=>'string'],['name'=>'expected_delivery','type'=>'date'],['name'=>'ordered_at','type'=>'datetime']],
                'related' => ['PurchaseOrderLine','Supplier','GoodsReceipt'],
            ],
            'PurchaseOrderLine' => [
                'triggers' => ['purchase_order_line','po_line'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'po_id','type'=>'int'],['name'=>'product_id','type'=>'int'],['name'=>'quantity','type'=>'int'],['name'=>'unit_price','type'=>'decimal'],['name'=>'received_qty','type'=>'int']],
                'related' => [],
            ],
            'GoodsReceipt' => [
                'triggers' => ['goods_receipt','reception','receiving','bon_de_reception'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'po_id','type'=>'int'],['name'=>'received_at','type'=>'datetime'],['name'=>'notes','type'=>'text'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],

            // ── TELEMEDICINE / ONLINE HEALTH ──
            'VideoConsultation' => [
                'triggers' => ['telemedicine','teleconsultation','video_consultation','online_doctor','remote_patient','ehealthcare','ehealth','digital_health','health_app','fitness_tracker','wearable','bien_etre'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'patient_id','type'=>'int'],['name'=>'doctor_id','type'=>'int'],['name'=>'scheduled_at','type'=>'datetime'],['name'=>'duration_min','type'=>'int'],['name'=>'meeting_url','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'notes','type'=>'text']],
                'related' => ['Patient','Doctor','DigitalPrescription'],
            ],
            'DigitalPrescription' => [
                'triggers' => ['digital_prescription','e_prescription','eprescription'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'consultation_id','type'=>'int'],['name'=>'patient_id','type'=>'int'],['name'=>'doctor_id','type'=>'int'],['name'=>'medications','type'=>'json'],['name'=>'issued_at','type'=>'datetime'],['name'=>'valid_until','type'=>'date'],['name'=>'qr_code','type'=>'string']],
                'related' => [],
            ],
            'HealthRecord' => [
                'triggers' => ['ehr','emr','health_record','medical_history','patient_record','dossier_medical_electronique'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'patient_id','type'=>'int'],['name'=>'allergies','type'=>'text'],['name'=>'chronic_conditions','type'=>'text'],['name'=>'blood_type','type'=>'string'],['name'=>'vaccinations','type'=>'json'],['name'=>'updated_at','type'=>'datetime']],
                'related' => [],
            ],
            'VitalSign' => [
                'triggers' => ['vital_sign','vitals','heart_rate','blood_pressure','temperature_reading','bmi','oximetry'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'patient_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'value','type'=>'decimal'],['name'=>'unit','type'=>'string'],['name'=>'measured_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── CONTENT PLATFORM (VIDEO / STREAMING) ──
            'Channel' => [
                'triggers' => ['youtube','video_platform','streaming_platform','content_creator','channel','vlog','podcast','twitch','dailymotion','content_management'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'owner_id','type'=>'int'],['name'=>'subscribers_count','type'=>'int'],['name'=>'verified','type'=>'boolean'],['name'=>'created_at','type'=>'datetime']],
                'related' => ['Video','Subscription','Comment'],
            ],
            'Video' => [
                'triggers' => ['video','clip','reel','short','media','upload','streaming'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'channel_id','type'=>'int'],['name'=>'title','type'=>'string'],['name'=>'description','type'=>'text'],['name'=>'url','type'=>'string'],['name'=>'thumbnail_url','type'=>'string'],['name'=>'duration_sec','type'=>'int'],['name'=>'views','type'=>'int'],['name'=>'status','type'=>'string'],['name'=>'published_at','type'=>'datetime']],
                'related' => ['Comment','Like'],
            ],
            'ViewHistory' => [
                'triggers' => ['view_history','watch_history','analytics','engagement'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'user_id','type'=>'int'],['name'=>'video_id','type'=>'int'],['name'=>'watched_seconds','type'=>'int'],['name'=>'watched_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── MICROSERVICES / API MANAGEMENT ──
            'Microservice' => [
                'triggers' => ['microservice','api_gateway','service_mesh','grpc','rest_api','graphql','api_management','kong','nginx','traefik','service_registry','service_discovery','consul','eureka','event_driven','message_queue','rabbitmq','kafka','nats'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'version','type'=>'string'],['name'=>'base_url','type'=>'string'],['name'=>'port','type'=>'int'],['name'=>'protocol','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'health_check_url','type'=>'string']],
                'related' => ['APIEndpoint','ServiceRoute','MessageQueue'],
            ],
            'APIEndpoint' => [
                'triggers' => ['api_endpoint','endpoint','route','controller','rest','http_method'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'service_id','type'=>'int'],['name'=>'path','type'=>'string'],['name'=>'method','type'=>'string'],['name'=>'auth_required','type'=>'boolean'],['name'=>'rate_limit','type'=>'int'],['name'=>'version','type'=>'string']],
                'related' => [],
            ],
            'ServiceRoute' => [
                'triggers' => ['service_route','gateway_route','proxy'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'gateway_id','type'=>'int'],['name'=>'service_id','type'=>'int'],['name'=>'path_prefix','type'=>'string'],['name'=>'strip_prefix','type'=>'boolean'],['name'=>'load_balance','type'=>'string']],
                'related' => [],
            ],
            'MessageQueue' => [
                'triggers' => ['message_queue','queue','topic','exchange','broker','event_bus','pub_sub','dead_letter'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'name','type'=>'string'],['name'=>'type','type'=>'string'],['name'=>'broker','type'=>'string'],['name'=>'durability','type'=>'boolean'],['name'=>'ttl_seconds','type'=>'int']],
                'related' => [],
            ],

            // ── GOVERNMENT / PUBLIC SERVICES ──
            'Citizen' => [
                'triggers' => ['government','municipality','mairie','commune','citoyen','citizen','public_service','administration','prefecture','gouvernement','etat','service_public','declaration','impot','tax'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'national_id','type'=>'string'],['name'=>'first_name','type'=>'string'],['name'=>'last_name','type'=>'string'],['name'=>'date_of_birth','type'=>'date'],['name'=>'gender','type'=>'string'],['name'=>'address','type'=>'text'],['name'=>'phone','type'=>'string'],['name'=>'email','type'=>'string']],
                'related' => ['CivilDocument','ServiceRequest','Tax'],
            ],
            'CivilDocument' => [
                'triggers' => ['civil_document','carte_nationale','passport','birth_certificate','acte_de_naissance','national_id_card','id_document'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'citizen_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'number','type'=>'string'],['name'=>'issued_at','type'=>'date'],['name'=>'expires_at','type'=>'date'],['name'=>'issuing_authority','type'=>'string'],['name'=>'status','type'=>'string']],
                'related' => [],
            ],
            'Tax' => [
                'triggers' => ['tax','impot','taxe','fiscal','taxation','irs','fisc','tva','vat','income_tax','property_tax'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'citizen_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'year','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'status','type'=>'string'],['name'=>'due_date','type'=>'date'],['name'=>'paid_at','type'=>'datetime']],
                'related' => [],
            ],

            // ── SCHOOL ERP ──
            'AcademicYear' => [
                'triggers' => ['academic_year','school_year','annee_scolaire','semester','trimestre','school_erp','school_management','school_system','sms_school'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'label','type'=>'string'],['name'=>'start_date','type'=>'date'],['name'=>'end_date','type'=>'date'],['name'=>'current','type'=>'boolean']],
                'related' => ['Timetable','FeePayment','Exam'],
            ],
            'Timetable' => [
                'triggers' => ['timetable','schedule','emploi_du_temps','horaire','planning_class'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'class_id','type'=>'int'],['name'=>'subject_id','type'=>'int'],['name'=>'teacher_id','type'=>'int'],['name'=>'day_of_week','type'=>'string'],['name'=>'start_time','type'=>'time'],['name'=>'end_time','type'=>'time'],['name'=>'room','type'=>'string']],
                'related' => [],
            ],
            'FeePayment' => [
                'triggers' => ['fee_payment','frais_scolarite','school_fee','tuition','frais','cotisation'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'student_id','type'=>'int'],['name'=>'academic_year_id','type'=>'int'],['name'=>'amount','type'=>'decimal'],['name'=>'type','type'=>'string'],['name'=>'status','type'=>'string'],['name'=>'paid_at','type'=>'datetime']],
                'related' => [],
            ],
            'Exam' => [
                'triggers' => ['exam','examen','baccalaureat','bac','concours','final_exam','mid_term'],
                'fields' => [['name'=>'id','type'=>'int'],['name'=>'academic_year_id','type'=>'int'],['name'=>'subject_id','type'=>'int'],['name'=>'type','type'=>'string'],['name'=>'date','type'=>'date'],['name'=>'duration_min','type'=>'int'],['name'=>'max_score','type'=>'int']],
                'related' => [],
            ],
        ];
    }

    /**
     * Extract domain hints from a free-text prompt and return a formatted
     * context string suitable for injecting into an LLM instruction.
     * This helps the model produce more domain-relevant entities and relations.
     */
    protected function extractDomainHints(string $text): string
    {
        $low = strtolower($text);
        $hints = [];

        $domainMap = [
            // E-commerce
            'e-commerce / online shop' => ['ecommerce','e-commerce','shop','store','cart','checkout','product','order','customer','buyer','sku','coupon','wishlist','boutique'],
            // Healthcare / Hospital
            'healthcare / hospital management' => ['patient','doctor','clinic','hospital','appointment','diagnosis','prescription','ehr','emr','medical','surgery','ward','nurse','physician','treatment','pharmacy'],
            // Banking / Finance
            'banking / financial system' => ['bank','account','transaction','loan','credit','debit','balance','interest','mortgage','investment','portfolio','brokerage','dividend','deposit','withdrawal'],
            // Payment / Fintech
            'payment gateway / fintech' => ['stripe','paypal','payment_gateway','payment_intent','refund','chargeback','merchant','payout','kyc','iban','digital_wallet','bnpl','buy_now_pay_later'],
            // Education / School
            'education / school management' => ['student','teacher','course','enrollment','grade','classroom','assignment','exam','curriculum','attendance','diploma','faculty','schedule','timetable','semester'],
            // E-learning / LMS
            'e-learning / LMS platform' => ['elearning','lms','mooc','lesson','quiz','certificate','video_course','instructor','progress','online_course','training_online'],
            // HR / Payroll
            'HR / payroll management' => ['employee','hr','payroll','salary','leave','attendance','hiring','onboarding','performance_review','department','position','recruiter'],
            // Project management
            'project / task management' => ['project','sprint','backlog','kanban','milestone','epic','story_point','velocity','retrospective','planning_poker','release'],
            // Real estate
            'real estate / property management' => ['property','real_estate','landlord','tenant','lease','rent','listing','agent','appraisal','mortgage','immobilier','apartment'],
            // Hotel / Hospitality
            'hotel / hospitality management' => ['hotel','room','booking','guest','checkin','checkout','reservation','housekeeping','minibar','reception','resort'],
            // Restaurant / Food
            'restaurant / food service' => ['restaurant','menu','dish','table','reservation','chef','waiter','kitchen','delivery','food_order','pos_restaurant'],
            // Transport / Fleet
            'transport / fleet management' => ['vehicle','driver','fleet','trip','fuel','maintenance','route','dispatch','gps','telematics','logistics'],
            // Library management
            'library / catalog management' => ['book','library','borrowing','author','isbn','catalog','member','reservation_book','late_fee','renewal'],
            // CRM / Sales
            'CRM / sales pipeline' => ['crm','lead','prospect','pipeline','deal','opportunity','quote','contract_crm','account_manager','cold_call','follow_up'],
            // Supply chain / ERP
            'supply chain / ERP' => ['supply_chain','purchase_order','procurement','warehouse','inventory','goods_receipt','bom','erp','mrp','production','assembly'],
            // AI / ML Platform
            'AI / machine learning platform' => ['machine_learning','neural_network','model_training','dataset','experiment','hyperparameter','epoch','loss_function','inference','prediction','llm','transformer','embedding','fine_tune'],
            // DevOps / CI-CD
            'DevOps / CI-CD pipeline' => ['devops','pipeline','ci_cd','build','deploy','kubernetes','docker','container','artifact','staging','environment','gitops','infrastructure'],
            // Security / IAM
            'security / identity & access management' => ['oauth','jwt','sso','identity_provider','rbac','permission','api_key','token','audit_log','mfa','two_factor','keycloak','okta','saml'],
            // Social network
            'social network / community platform' => ['social','follower','following','feed','post','like','share','comment','story','group','community','hashtag','notification'],
            // Event management
            'event management' => ['event','conference','ticket','attendee','venue','sponsor','exhibitor','booth','registration','badge','gala','seminar'],
            // Game / Gaming
            'gaming / game platform' => ['game','player','level','xp','achievement','leaderboard','inventory_game','match','score','guild','quest','item','loot'],
            // Government / Public services
            'government / public services' => ['citizen','municipality','administration','civil_document','tax','permit','registration_gov','decree','ministry','prefecture'],
            // IoT / Smart Home
            'IoT / smart home' => ['iot','sensor','smart_home','device','automation','mqtt','telemetry','gateway_iot','firmware','domotique','arduino','raspberry_pi'],
            // Content platform
            'content / video streaming platform' => ['youtube','channel','video','streaming','creator','subscriber','view','watch_history','monetization','thumbnail','playlist'],
            // Microservices / API
            'microservices / API management' => ['microservice','api_gateway','grpc','graphql','rest','kafka','rabbitmq','message_queue','service_mesh','circuit_breaker','rate_limit','swagger'],
            // Telemedicine
            'telemedicine / digital health' => ['telemedicine','video_consultation','remote_patient','digital_prescription','health_record','vital_sign','teleconsultation'],
            // Legal
            'legal / law firm management' => ['law','lawyer','case','hearing','court','legal_document','contract_law','litigation','settlement','bail'],
            // Pharmacy
            'pharmacy / medication management' => ['pharmacy','medication','prescription_pharmacy','pharmacist','drug_dispensing','stock_medication','expiry'],
        ];

        foreach ($domainMap as $domainLabel => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($low, $kw)) {
                    $hints[] = $domainLabel;
                    break; // only add each domain once
                }
            }
        }

        if (empty($hints)) {
            return '';
        }

        $unique = array_unique($hints);
        $list   = implode(', ', $unique);
        return "DOMAIN CONTEXT: The prompt relates to [{$list}]. Use this to generate appropriate, domain-specific entities, attributes, and relationships. Include industry-standard terminology.\n\n";
    }

    /**
     * Build generic relations for any set of resolved entities.
     * Called from buildPlantUmlFromStructure when specific relations
     * are not hardcoded.
     */
    protected function inferGenericRelations(array $entities): array
    {
        $names = array_map(fn($e) => $e['name'] ?? '', $entities);
        $relations = [];

        foreach ($entities as $entity) {
            $eName = $entity['name'] ?? '';
            foreach (($entity['fields'] ?? []) as $field) {
                $fName = $field['name'] ?? ($field[0] ?? '');
                // If field ends with _id, look for matching entity
                if (str_ends_with($fName, '_id')) {
                    $ref = str_replace('_id', '', $fName);
                    $ref = ucfirst($ref);
                    // camelCase conversion: e.g. head_doctor_id -> HeadDoctor? skip if not in list
                    // Try exact match first
                    if (in_array($ref, $names)) {
                        $relations[] = "{$ref} \"1\" -- \"many\" {$eName}";
                    }
                }
            }
        }

        return array_unique($relations);
    }

    // ── DEDICATED ENDPOINT: generateTestCases ──────────────────────────

    /**
     * Generate test cases for a given feature description.
     *
     * Uses a QA-engineer persona with a structured prompt that produces
     * an array of test cases, each with: name, type (unit|feature|integration),
     * description, steps, and expected_result.
     */
    public function generateTestCases(string $featureDescription, $meta = []): array
    {
        $systemPrompt = 'You are a senior QA engineer. You produce comprehensive test cases '
            . 'covering happy paths, edge cases, error handling, and security. '
            . 'Output a JSON array of test case objects. No prose, no markdown.';

        $userPrompt = "Generate test cases for the following feature:\n\n"
            . trim($featureDescription)
            . "\n\nReturn a JSON array where each element has these keys:\n"
            . "- name (string): short test name in snake_case\n"
            . "- type (string): one of unit, feature, integration\n"
            . "- description (string): what the test verifies\n"
            . "- steps (array of strings): step-by-step actions\n"
            . "- expected_result (string): the expected outcome\n\n"
            . "Return at least 5 test cases. Output valid JSON only.";

        $result = $this->pipeline->run(
            prompt: $userPrompt,
            options: [
                'system'     => $systemPrompt,
                'format'     => 'json',
                'temperature'=> 0.3,
                'max_tokens' => 1500,
                'context'    => ['feature' => $featureDescription],
                'validator'  => function (array $data): bool {
                    if (! is_array($data) || count($data) < 1) return false;
                    $first = $data[0] ?? null;
                    return is_array($first)
                        && isset($first['name'], $first['type'], $first['description']);
                },
                'normalizer' => function (array $data): array {
                    $valid = ['unit', 'feature', 'integration'];
                    return array_values(array_map(function ($tc) use ($valid) {
                        return [
                            'name'            => $tc['name'] ?? 'unnamed_test',
                            'type'            => in_array($tc['type'] ?? '', $valid) ? $tc['type'] : 'unit',
                            'description'     => $tc['description'] ?? '',
                            'steps'           => (array) ($tc['steps'] ?? []),
                            'expected_result' => $tc['expected_result'] ?? '',
                        ];
                    }, $data));
                },
            ],
        );

        AIPlan::create([
            'board_id'    => $meta['board_id'] ?? null,
            'title'       => $meta['title'] ?? 'generateTestCases',
            'input_text'  => $featureDescription,
            'result_json' => $result,
        ]);

        return is_array($result) ? $result : [];
    }

    /**
     * Generate diverse task suggestions for a board.
     *
     * Tries the LLM first; falls back to local template-based suggestions
     * if the LLM is disabled or returns nothing.
     *
     * @param  string       $baseTopic   The topic / prompt from the user
     * @param  array        $todoTitles  Existing todo task titles for context (max 5)
     * @param  array        $options     temperature, etc.
     * @return array<int, array{key: string, title: string, description: string}>
     */
    public function generateTaskSuggestions(string $baseTopic, array $todoTitles = [], array $options = []): array
    {
        $base = $baseTopic ?: (count($todoTitles) ? $todoTitles[0] : 'Improve project flow');
        $contextStr = count($todoTitles) ? implode('; ', array_slice($todoTitles, 0, 5)) : 'none';

        // ── Try LLM first ──
        if ($this->llm->isEnabled()) {
            $suggestions = $this->generateSuggestionsViaLLM($base, $contextStr, $options);
            if (count($suggestions)) {
                return $suggestions;
            }
        }

        // ── Fallback: local template-based suggestions ──
        return $this->generateSuggestionsLocal($base, $contextStr);
    }

    /**
     * Call the LLM for task suggestions and parse the response.
     */
    protected function generateSuggestionsViaLLM(string $base, string $contextStr, array $options): array
    {
        $system = "You are a senior software engineer drafting concise, technical, and actionable task suggestions. For each suggestion return a short title on the first line and a detailed description afterwards including acceptance criteria and an estimate. Provide multiple diverse approaches: implementation, investigation, design, performance, security, QA.";

        $llmPrompt = "Provide 6 distinct suggestions for: {$base}";

        $temperature = (float) ($options['temperature'] ?? config('services.llm.temperature', 0.6));

        // ── Run through the pipeline with context enrichment ──
        $results = $this->pipeline->run($llmPrompt, [
            'n' => 6,
            'temperature' => $temperature,
            'system' => $system,
        ], [
            'format' => 'text',
            'context' => ["Existing todos: {$contextStr}"],
            'max_retries' => 1,
        ]);

        $suggestions = [];
        foreach ($results as $resp) {
            $parts = preg_split('/\r?\n\-\-\-|\r?\n\r?\n/', trim($resp));
            $title = trim($parts[0] ?? 'LLM Suggestion');
            $description = trim(isset($parts[1]) ? $parts[1] : (count($parts) > 1 ? implode("\n\n", array_slice($parts, 1)) : ''));
            $key = substr(md5($title . $description), 0, 8);
            $suggestions[] = ['key' => $key, 'title' => $title, 'description' => $description];
        }

        return $suggestions;
    }

    /**
     * Produce deterministic template-based suggestions when LLM is unavailable.
     */
    protected function generateSuggestionsLocal(string $base, string $contextStr): array
    {
        $categories = [
            'Implementation' => "Deliver a runnable implementation with clear API and DB contracts.\n- Tasks: define migrations, controllers, services.\n- Tests: unit + integration.\n- Context: {$contextStr}",
            'Investigation' => "Spike to reproduce, root-cause, and propose fixes.\n- Produce a short report with logs, repro steps, and proposed patch.\n- Context: {$contextStr}",
            'Design' => "Architectural or UX design deliverable.\n- Produce sequence diagrams, data models, and API contracts.\n- Include migration and compatibility notes.\n- Context: {$contextStr}",
            'Performance' => "Identify hotspots and create benchmarks + optimizations.\n- Provide profiling steps and target metrics (p95, throughput).\n- Context: {$contextStr}",
            'Security' => "Threat modeling and remediation tasks.\n- Verify authentication, input validation, and data sanitization.\n- Provide mitigation steps and test cases.\n- Context: {$contextStr}",
            'QA' => "Create acceptance tests, edge-case scenarios, and regression suites.\n- Provide example test vectors and expected outputs.\n- Context: {$contextStr}",
        ];

        $acceptance = "\n\nAcceptance Criteria:\n- Feature works end-to-end\n- Automated tests added\n- Documentation updated";

        $suggestions = [];
        foreach ($categories as $cat => $desc) {
            $prefix = ($cat === 'Performance') ? "{$cat}: Optimize " : "{$cat}: ";
            $title = ($cat === 'QA')
                ? "QA: Test cases for " . \Illuminate\Support\Str::limit($base, 60)
                : $prefix . \Illuminate\Support\Str::limit($base, 60);
            $estimate = "\nEstimated effort: ~" . rand(1, 16) . "h";
            $key = substr(md5($base . $cat), 0, 8);

            $suggestions[] = [
                'key' => $key,
                'title' => $title,
                'description' => $desc . $acceptance . $estimate,
            ];
        }

        return $suggestions;
    }
}
