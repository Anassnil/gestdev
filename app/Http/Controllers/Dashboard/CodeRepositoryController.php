<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Repository;
use App\Models\RepositoryCollaborator;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CodeRepositoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $repositories = Repository::query()
            ->with(['owner:id,name', 'defaultBranch:id,repository_id,name'])
            ->withCount(['branches', 'collaborators'])
            ->where(function ($query) use ($userId) {
                $query->where('owner_id', $userId)
                    ->orWhereHas('collaborators', fn ($q) => $q->where('user_id', $userId));
            })
            ->orderByDesc('updated_at')
            ->paginate(10, ['*'], 'repos_page')
            ->withQueryString();

        $tasks = Task::query()
            ->with(['board:id,name,user_id', 'assignee:id,name'])
            ->whereHas('board', fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc('updated_at')
            ->paginate(12, ['*'], 'tasks_page')
            ->withQueryString();

        $allTasksQuery = Task::query()->whereHas('board', fn ($q) => $q->where('user_id', $userId));
        $allReposQuery = Repository::query()->where('owner_id', $userId);

        $stats = [
            'repositories_total' => (clone $allReposQuery)->count(),
            'repositories_public' => (clone $allReposQuery)->where('visibility', 'public')->count(),
            'repositories_private' => (clone $allReposQuery)->where('visibility', 'private')->count(),
            'branches_total' => Branch::query()->whereHas('repository', fn ($q) => $q->where('owner_id', $userId))->count(),
            'total_tasks' => (clone $allTasksQuery)->count(),
            'with_pr' => (clone $allTasksQuery)->whereNotNull('pr_url')->where('pr_url', '!=', '')->count(),
            'done_with_pr' => (clone $allTasksQuery)->where('status', 'done')->whereNotNull('pr_url')->where('pr_url', '!=', '')->count(),
            'boards_covered' => (clone $allTasksQuery)->distinct('board_id')->count('board_id'),
        ];

        return view('dashboard.code_repository.index', compact('repositories', 'tasks', 'stats'));
    }

    public function storeRepository(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in(['public', 'private'])],
        ]);

        $ownerId = $request->user()->id;
        $baseSlug = $data['slug'] ? Str::slug($data['slug']) : Str::slug($data['name']);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'repository';
        $slug = $baseSlug;
        $suffix = 1;

        while (Repository::query()->where('owner_id', $ownerId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        $repository = Repository::create([
            'owner_id' => $ownerId,
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'visibility' => $data['visibility'],
            'is_archived' => false,
        ]);

        $defaultBranch = Branch::create([
            'repository_id' => $repository->id,
            'name' => 'main',
            'is_default' => true,
            'is_protected' => true,
            'protection_rules' => ['required_approvals' => 1],
            'created_by' => $ownerId,
        ]);

        $repository->update(['default_branch_id' => $defaultBranch->id]);

        return redirect()
            ->route('dashboard.code_repository.show', $repository)
            ->with('success', 'Repository created successfully.');
    }

    public function show(Request $request, Repository $repository)
    {
        $this->authorize('view', $repository);

        $repository->load([
            'owner:id,name,email',
            'defaultBranch:id,repository_id,name',
            'branches' => fn ($q) => $q->orderBy('name'),
            'collaborators.user:id,name,email',
        ]);

        $availableUsers = User::query()
            ->where('id', '!=', $repository->owner_id)
            ->whereNotIn('id', $repository->collaborators->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $canManage = $this->canManage($request->user()->id, $repository);

        return view('dashboard.code_repository.show', compact('repository', 'availableUsers', 'canManage'));
    }

    public function updateRepository(Request $request, Repository $repository): RedirectResponse
    {
        $this->authorize('update', $repository);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'is_archived' => ['nullable', 'boolean'],
        ]);

        $repository->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'visibility' => $data['visibility'],
            'is_archived' => (bool) ($data['is_archived'] ?? false),
        ]);

        return redirect()
            ->route('dashboard.code_repository.show', $repository)
            ->with('success', 'Repository settings updated.');
    }

    public function destroyRepository(Repository $repository): RedirectResponse
    {
        $this->authorize('delete', $repository);
        $repository->delete();

        return redirect()
            ->route('dashboard.code_repository.index')
            ->with('success', 'Repository deleted.');
    }

    public function storeBranch(Request $request, Repository $repository): RedirectResponse
    {
        $this->authorize('update', $repository);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9._\/-]+$/',
                Rule::unique('branches', 'name')->where(fn ($q) => $q->where('repository_id', $repository->id)),
            ],
        ]);

        Branch::create([
            'repository_id' => $repository->id,
            'name' => $data['name'],
            'is_default' => false,
            'is_protected' => false,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('dashboard.code_repository.show', $repository)
            ->with('success', 'Branch created.');
    }

    public function setDefaultBranch(Repository $repository, Branch $branch): RedirectResponse
    {
        $this->authorize('update', $repository);
        abort_unless((int) $branch->repository_id === (int) $repository->id, 404);

        Branch::query()
            ->where('repository_id', $repository->id)
            ->update(['is_default' => false]);

        $branch->update(['is_default' => true]);
        $repository->update(['default_branch_id' => $branch->id]);

        return redirect()
            ->route('dashboard.code_repository.show', $repository)
            ->with('success', 'Default branch updated.');
    }

    public function toggleBranchProtection(Repository $repository, Branch $branch): RedirectResponse
    {
        $this->authorize('update', $repository);
        abort_unless((int) $branch->repository_id === (int) $repository->id, 404);

        $branch->update(['is_protected' => ! $branch->is_protected]);

        return redirect()
            ->route('dashboard.code_repository.show', $repository)
            ->with('success', 'Branch protection updated.');
    }

    public function destroyBranch(Repository $repository, Branch $branch): RedirectResponse
    {
        $this->authorize('update', $repository);
        abort_unless((int) $branch->repository_id === (int) $repository->id, 404);

        if ($branch->is_default || (int) $repository->default_branch_id === (int) $branch->id) {
            return redirect()
                ->route('dashboard.code_repository.show', $repository)
                ->with('error', 'Default branch cannot be deleted.');
        }

        $branch->delete();

        return redirect()
            ->route('dashboard.code_repository.show', $repository)
            ->with('success', 'Branch deleted.');
    }

    public function storeCollaborator(Request $request, Repository $repository): RedirectResponse
    {
        $this->authorize('update', $repository);

        $data = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('repository_collaborators', 'user_id')->where(fn ($q) => $q->where('repository_id', $repository->id)),
            ],
            'role' => ['required', Rule::in(['admin', 'maintainer', 'developer'])],
        ]);

        RepositoryCollaborator::create([
            'repository_id' => $repository->id,
            'user_id' => $data['user_id'],
            'role' => $data['role'],
            'invited_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('dashboard.code_repository.show', $repository)
            ->with('success', 'Collaborator added.');
    }

    public function removeCollaborator(Repository $repository, RepositoryCollaborator $collaborator): RedirectResponse
    {
        $this->authorize('update', $repository);
        abort_unless((int) $collaborator->repository_id === (int) $repository->id, 404);

        $collaborator->delete();

        return redirect()
            ->route('dashboard.code_repository.show', $repository)
            ->with('success', 'Collaborator removed.');
    }

    public function updatePr(Request $request, Task $task): RedirectResponse
    {
        $request->validate([
            'pr_url' => ['nullable', 'url', 'max:2000'],
        ]);

        $userId = $request->user()->id;
        abort_unless($task->board && (int) $task->board->user_id === (int) $userId, 403);

        $task->update([
            'pr_url' => $request->input('pr_url') ?: null,
        ]);

        return redirect()
            ->route('dashboard.code_repository.index')
            ->with('success', 'PR link updated successfully.');
    }

    private function canManage(int $userId, Repository $repository): bool
    {
        if ((int) $repository->owner_id === $userId) {
            return true;
        }

        return $repository->collaborators()
            ->where('user_id', $userId)
            ->whereIn('role', ['admin', 'maintainer'])
            ->exists();
    }
}
