<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\AIPlanningService;
use App\Models\AIRequest;

class AIPlanningController extends Controller
{
    protected $service;

    public function __construct(AIPlanningService $service)
    {
        $this->service = $service;
    }

    // ── Dedicated endpoint: Convert Idea to Tasks ────────────────────

    public function convertIdeaToTasks(Request $request)
    {
        $data = $request->validate([
            'text'     => 'required|string',
            'board_id' => 'nullable|integer',
        ]);
        $res = $this->service->convertIdeaToTasks(
            $data['text'],
            ['board_id' => $data['board_id'] ?? null, 'title' => 'convertIdeaToTasks']
        );
        return response()->json(['ok' => true, 'result' => $res]);
    }

    // ── Dedicated endpoint: Generate UML ─────────────────────────────

    public function generateUML(Request $request)
    {
        $data = $request->validate([
            'input'         => 'required|string',
            'board_id'      => 'nullable|integer',
            'temperature'   => 'nullable|numeric',
            'top_p'         => 'nullable|numeric',
            'style'         => 'nullable|string',
            'include_ai'    => 'nullable|boolean',
            'search_radius' => 'nullable|integer|min:1|max:10',
            'diagram_type'  => 'nullable|string|in:class,sequence,usecase,er,activity,state,component',
        ]);

        try {
            $opts = ['board_id' => $data['board_id'] ?? null, 'title' => 'generateUML'];
            foreach (['temperature', 'top_p', 'style', 'include_ai', 'search_radius', 'diagram_type'] as $k) {
                if (isset($data[$k])) $opts[$k] = $data[$k];
            }

            $text = $this->service->generateUML($data['input'], $opts);
            return response()->json(['ok' => true, 'result' => ['plantuml' => $text]]);
        } catch (\Throwable $e) {
            \Log::error('AIPlanningController::generateUML error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── Streaming endpoint: Generate UML with SSE progress ───────────

    public function generateUMLStream(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'input'         => 'required|string',
            'board_id'      => 'nullable|integer',
            'temperature'   => 'nullable|numeric',
            'top_p'         => 'nullable|numeric',
            'style'         => 'nullable|string',
            'include_ai'    => 'nullable|boolean',
            'search_radius' => 'nullable|integer|min:1|max:10',
            'diagram_type'  => 'nullable|string|in:class,sequence,usecase,er,activity,state,component',
        ]);

        $opts = ['board_id' => $data['board_id'] ?? null, 'title' => 'generateUML'];
        foreach (['temperature', 'top_p', 'style', 'include_ai', 'search_radius', 'diagram_type'] as $k) {
            if (isset($data[$k])) $opts[$k] = $data[$k];
        }

        $service = $this->service;
        $input   = $data['input'];

        return new StreamedResponse(function () use ($service, $input, $opts) {
            // SSE helper
            $send = function (string $event, array $payload) {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";
                if (ob_get_level()) ob_flush();
                flush();
            };

            // Wire progress callback
            $opts['on_progress'] = function (string $step, string $detail) use ($send) {
                $send('progress', ['step' => $step, 'message' => $detail]);
            };

            try {
                $text = $service->generateUML($input, $opts);
                $send('complete', ['plantuml' => $text]);
            } catch (\Throwable $e) {
                \Log::error('AIPlanningController::generateUMLStream error: ' . $e->getMessage());
                $send('error', ['message' => $e->getMessage()]);
            }
        }, 200, [
            'Content-Type'  => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection'    => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    // ── Dedicated endpoint: Improve Architecture ─────────────────────

    public function improveArchitecture(Request $request)
    {
        $data = $request->validate([
            'code'     => 'required|string',
            'board_id' => 'nullable|integer',
        ]);
        $res = $this->service->improveArchitecture(
            $data['code'],
            ['board_id' => $data['board_id'] ?? null, 'title' => 'improveArchitecture']
        );
        return response()->json(['ok' => true, 'result' => $res]);
    }

    // ── Dedicated endpoint: Generate Test Cases ──────────────────────

    public function generateTestCases(Request $request)
    {
        $data = $request->validate([
            'feature'  => 'required|string',
            'board_id' => 'nullable|integer',
        ]);
        $res = $this->service->generateTestCases(
            $data['feature'],
            ['board_id' => $data['board_id'] ?? null, 'title' => 'generateTestCases']
        );
        return response()->json(['ok' => true, 'result' => $res]);
    }

    // ── History: chat-style AI interaction log ─────────────────────

    public function history(Request $request)
    {
        $data = $request->validate([
            'type'   => 'nullable|string|in:uml,architecture,tasks,test_cases,improvements,plantuml,text,json',
            'limit'  => 'nullable|integer|min:1|max:100',
        ]);

        $query = AIRequest::forUser(auth()->id())
            ->successful()
            ->orderByDesc('created_at');

        if (! empty($data['type'])) {
            $query->ofType($data['type']);
        }

        $items = $query->limit($data['limit'] ?? 30)->get([
            'id', 'type', 'input', 'output', 'status', 'retries', 'duration_ms', 'created_at',
        ]);

        return response()->json(['ok' => true, 'history' => $items]);
    }

    // ── Legacy endpoints (backward compatibility) ────────────────────

    /** @deprecated Use convertIdeaToTasks() */
    public function analyzeIdea(Request $request)
    {
        $data = $request->validate(['idea' => 'required|string', 'board_id' => 'nullable|integer']);
        $res = $this->service->analyzeIdea($data['idea'], ['board_id' => $data['board_id'] ?? null, 'title' => 'analyzeIdea']);
        return response()->json(['ok' => true, 'result' => $res]);
    }

    /** @deprecated Use generateUML() or improveArchitecture() */
    public function generateArchitecture(Request $request)
    {
        $data = $request->validate([
            'input' => 'required|string',
            'board_id' => 'nullable|integer',
            'format' => 'nullable|string',
            'temperature' => 'nullable|numeric',
            'top_p' => 'nullable|numeric',
            'style' => 'nullable|string',
            'include_ai' => 'nullable|boolean',
            'search_radius' => 'nullable|integer|min:1|max:10',
        ]);

        try {
            if (isset($data['format']) && $data['format'] === 'plantuml') {
                $opts = [
                    'board_id' => $data['board_id'] ?? null,
                    'title' => 'generatePlantUML',
                ];
                if (isset($data['temperature'])) $opts['temperature'] = $data['temperature'];
                if (isset($data['top_p'])) $opts['top_p'] = $data['top_p'];
                if (isset($data['style'])) $opts['style'] = $data['style'];
                if (isset($data['include_ai'])) $opts['include_ai'] = $data['include_ai'];
                if (isset($data['search_radius'])) $opts['search_radius'] = $data['search_radius'];

                $text = $this->service->generatePlantUML($data['input'], $opts);
                return response()->json(['ok' => true, 'result' => ['plantuml' => $text]]);
            }

            $res = $this->service->generateArchitecture($data['input'], ['board_id' => $data['board_id'] ?? null, 'title' => 'generateArchitecture']);
            return response()->json(['ok' => true, 'result' => $res]);
        } catch (\Throwable $e) {
            \Log::error('AIPlanningController::generateArchitecture error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /** @deprecated Use improveArchitecture() */
    public function suggestImprovements(Request $request)
    {
        $data = $request->validate(['architecture' => 'required|string', 'board_id' => 'nullable|integer']);
        $res = $this->service->suggestImprovements($data['architecture'], ['board_id' => $data['board_id'] ?? null, 'title' => 'suggestImprovements']);
        return response()->json(['ok' => true, 'result' => $res]);
    }
}
