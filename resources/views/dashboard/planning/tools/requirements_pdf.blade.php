<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Specification - {{ $board->name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111827;
            font-size: 12px;
            line-height: 1.5;
            margin: 24px;
        }

        h1 {
            font-size: 22px;
            margin: 0 0 6px 0;
            color: #111827;
        }

        .meta {
            font-size: 11px;
            color: #4b5563;
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #1f2937;
            margin: 18px 0 8px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
        }

        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .summary-grid th,
        .summary-grid td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
        }

        .summary-grid th {
            background: #f3f4f6;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .requirement {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .requirement h3 {
            font-size: 14px;
            margin: 0 0 6px;
            color: #111827;
        }

        .badge {
            display: inline-block;
            border: 1px solid #9ca3af;
            border-radius: 12px;
            padding: 1px 8px;
            font-size: 10px;
            margin-right: 6px;
            margin-bottom: 6px;
        }

        .label {
            font-weight: 700;
            color: #1f2937;
        }

        .empty {
            color: #6b7280;
            font-style: italic;
        }
    </style>
</head>
<body>
    <h1>Project Specification</h1>
    <div class="meta">
        Board: {{ $board->name }}<br>
        Generated at: {{ $generatedAt->format('Y-m-d H:i:s') }}
    </div>

    @php
        $total = $requirements->count();
        $draft = $requirements->where('status', 'draft')->count();
        $highPriority = $requirements->whereIn('priority', ['high', 'critical'])->count();
        $linkedToTasks = $requirements->filter(fn($r) => $r->tasks->isNotEmpty())->count();
    @endphp

    <div class="section-title">Summary</div>
    <table class="summary-grid">
        <thead>
            <tr>
                <th>Total</th>
                <th>Draft</th>
                <th>High Priority</th>
                <th>Linked to Tasks</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $total }}</td>
                <td>{{ $draft }}</td>
                <td>{{ $highPriority }}</td>
                <td>{{ $linkedToTasks }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Requirements</div>
    @forelse($requirements as $item)
        <div class="requirement">
            <h3>{{ $item->title }}</h3>
            <div>
                <span class="badge">Type: {{ $item->type ?: 'n/a' }}</span>
                <span class="badge">Priority: {{ $item->priority ?: 'n/a' }}</span>
                <span class="badge">Status: {{ $item->status ?: 'n/a' }}</span>
                <span class="badge">Estimate: {{ $item->estimate ?? 'n/a' }}</span>
            </div>

            <p><span class="label">Description:</span>
                @if(!empty($item->description))
                    {{ $item->description }}
                @else
                    <span class="empty">No description provided.</span>
                @endif
            </p>

            <p><span class="label">Acceptance Criteria:</span>
                @if(!empty($item->acceptance_criteria))
                    {{ $item->acceptance_criteria }}
                @else
                    <span class="empty">No acceptance criteria provided.</span>
                @endif
            </p>

            <p><span class="label">Tags:</span>
                @if(is_array($item->tags) && count($item->tags))
                    {{ implode(', ', $item->tags) }}
                @else
                    <span class="empty">No tags.</span>
                @endif
            </p>

            <p><span class="label">Linked Tasks:</span>
                @if($item->tasks->isNotEmpty())
                    {{ $item->tasks->pluck('title')->implode(', ') }}
                @else
                    <span class="empty">No linked tasks.</span>
                @endif
            </p>
        </div>
    @empty
        <p class="empty">No requirements available for this board.</p>
    @endforelse
</body>
</html>
