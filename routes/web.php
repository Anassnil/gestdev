<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('home');
});

// AI Model Management routes
use App\Http\Controllers\AI\AIModelController;
use App\Http\Controllers\AI\DatasetController;
use App\Http\Controllers\AI\ExperimentController;
use App\Http\Controllers\AI\TrainingRunController;
use App\Http\Controllers\AI\DeploymentController;
use App\Http\Controllers\AI\HelpController;

Route::middleware(['auth', \App\Http\Middleware\EnsureAiAdmin::class])->prefix('ai')->group(function () {
    // Help & Documentation
    Route::get('/help', [HelpController::class, 'guide'])->name('ai.help');

    Route::get('/models', [AIModelController::class, 'index'])->name('ai.models.index');
    Route::post('/models', [AIModelController::class, 'store'])->name('ai.models.store');
    Route::get('/models/{model}', [AIModelController::class, 'show'])->name('ai.models.show');
    Route::patch('/models/{model}', [AIModelController::class, 'update'])->name('ai.models.update');
    Route::delete('/models/{model}', [AIModelController::class, 'destroy'])->name('ai.models.destroy');

    Route::get('/datasets', [DatasetController::class, 'index'])->name('ai.datasets.index');
    Route::get('/datasets/create', [DatasetController::class, 'create'])->name('ai.datasets.create');
    Route::post('/datasets', [DatasetController::class, 'store'])->name('ai.datasets.store');
    Route::get('/datasets/{dataset}', [DatasetController::class, 'show'])->name('ai.datasets.show');
    Route::delete('/datasets/{dataset}', [DatasetController::class, 'destroy'])->name('ai.datasets.destroy');

    Route::get('/experiments', [ExperimentController::class, 'index'])->name('ai.experiments.index');
    Route::post('/experiments', [ExperimentController::class, 'store'])->name('ai.experiments.store');
    Route::get('/experiments/multi-compare', [ExperimentController::class, 'multiCompare'])->name('ai.experiments.multi_compare');
    Route::get('/experiments/{experiment}', [ExperimentController::class, 'show'])->name('ai.experiments.show');
    Route::get('/experiments/{experiment}/compare', [ExperimentController::class, 'compare'])->name('ai.experiments.compare');
    Route::get('/experiments/{experiment}/promote', function (\App\Models\Experiment $experiment) {
        return redirect()->route('ai.experiments.show', $experiment)
            ->with('error', 'Use the Promote button to submit this action.');
    });
    Route::post('/experiments/{experiment}/promote', [ExperimentController::class, 'promoteBest'])->name('ai.experiments.promote');

    Route::get('/training-runs', [TrainingRunController::class, 'index'])->name('ai.training_runs.index');
    Route::get('/training-runs/create', [TrainingRunController::class, 'create'])->name('ai.training_runs.create');
    Route::post('/training-runs', [TrainingRunController::class, 'store'])->name('ai.training_runs.store');
    Route::get('/training-runs/{training_run}', [TrainingRunController::class, 'show'])->name('ai.training_runs.show');
    Route::get('/training-runs/{training_run}/progress', [TrainingRunController::class, 'progress'])->name('ai.training_runs.progress');
    Route::post('/training-runs/{training_run}/cancel', [TrainingRunController::class, 'cancel'])->name('ai.training_runs.cancel');

    Route::get('/deployments', [DeploymentController::class, 'index'])->name('ai.deployments.index');
    Route::post('/deployments', [DeploymentController::class, 'store'])->name('ai.deployments.store');
});

use App\Http\Controllers\AuthController;

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register.form');
Route::post('/register', [AuthController::class, 'register'])->name('register');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/dashboard', function () {
    $user = Auth::user();

    // Gather recent activity from multiple sources
    $activities = collect();

    // Recent tasks (created or updated)
    $tasks = \App\Models\Task::with('board')
        ->whereHas('board', fn($q) => $q->where('user_id', $user->id))
        ->latest('updated_at')
        ->take(10)
        ->get()
        ->map(fn($t) => [
            'icon'  => 'task',
            'title' => $t->title,
            'desc'  => $t->status === 'done'
                ? 'Task completed'
                : ($t->created_at->eq($t->updated_at) ? 'Task created' : 'Task updated to ' . $t->status),
            'board' => $t->board->name ?? null,
            'time'  => $t->updated_at,
            'color' => match($t->status) {
                'done'        => 'green',
                'in_progress' => 'yellow',
                default       => 'blue',
            },
        ]);
    $activities = $activities->merge($tasks);

    // Recent requirements
    $reqs = \App\Models\Requirement::with('board')
        ->whereHas('board', fn($q) => $q->where('user_id', $user->id))
        ->latest('updated_at')
        ->take(5)
        ->get()
        ->map(fn($r) => [
            'icon'  => 'requirement',
            'title' => $r->title,
            'desc'  => $r->created_at->eq($r->updated_at) ? 'Requirement created' : 'Requirement updated',
            'board' => $r->board->name ?? null,
            'time'  => $r->updated_at,
            'color' => 'purple',
        ]);
    $activities = $activities->merge($reqs);

    // Recent boards
    $boards = \App\Models\Board::where('user_id', $user->id)
        ->latest('updated_at')
        ->take(3)
        ->get()
        ->map(fn($b) => [
            'icon'  => 'board',
            'title' => $b->name,
            'desc'  => $b->created_at->eq($b->updated_at) ? 'Board created' : 'Board updated',
            'board' => null,
            'time'  => $b->updated_at,
            'color' => 'blue',
        ]);
    $activities = $activities->merge($boards);

    // Recent diagrams
    $diagrams = \App\Models\Diagram::with('board')
        ->whereHas('board', fn($q) => $q->where('user_id', $user->id))
        ->latest('updated_at')
        ->take(5)
        ->get()
        ->map(fn($d) => [
            'icon'  => 'diagram',
            'title' => $d->title,
            'desc'  => $d->created_at->eq($d->updated_at) ? 'Diagram created' : 'Diagram updated',
            'board' => $d->board->name ?? null,
            'time'  => $d->updated_at,
            'color' => 'teal',
        ]);
    $activities = $activities->merge($diagrams);

    // Sort by time descending, take latest 10
    $activities = $activities->sortByDesc('time')->take(10)->values();

    return view('dashboard', compact('activities'));
})->middleware('auth')->name('dashboard');

use App\Http\Controllers\PlanningController;
use App\Http\Controllers\Dashboard\PlanningToolsController;
use App\Http\Controllers\Dashboard\RoadmapController;
use App\Http\Controllers\Dashboard\CodeRepositoryController;
use App\Http\Controllers\Dashboard\AccountSettingsController;
use App\Http\Controllers\Dashboard\PeopleController;
use App\Http\Controllers\AI\AIPlanningController;

Route::middleware(['auth'])->prefix('dashboard')->group(function(){
    Route::get('/settings', [AccountSettingsController::class, 'index'])->name('dashboard.settings');
    Route::patch('/settings/profile', [AccountSettingsController::class, 'updateProfile'])->name('dashboard.settings.profile.update');
    Route::patch('/settings/password', [AccountSettingsController::class, 'updatePassword'])->name('dashboard.settings.password.update');
    Route::post('/settings/avatar', [AccountSettingsController::class, 'updateAvatar'])->name('dashboard.settings.avatar.update');
    Route::delete('/settings/avatar', [AccountSettingsController::class, 'removeAvatar'])->name('dashboard.settings.avatar.remove');
    Route::patch('/settings/professional', [AccountSettingsController::class, 'updateProfessionalProfile'])->name('dashboard.settings.professional.update');
    Route::delete('/settings/account', [AccountSettingsController::class, 'destroyAccount'])->name('dashboard.settings.account.destroy');

    // People directory + messaging
    Route::get('/people', [PeopleController::class, 'index'])->name('dashboard.people.index');
    Route::get('/people/inbox', [PeopleController::class, 'inbox'])->name('dashboard.people.inbox');
    Route::get('/people/inbox-json', [PeopleController::class, 'inboxJson'])->name('dashboard.people.inbox.json');
    Route::get('/people/unread', [PeopleController::class, 'unreadCount'])->name('dashboard.people.unread');
    Route::post('/people/{user}/associate', [PeopleController::class, 'addAssociate'])->name('dashboard.people.associates.add');
    Route::delete('/people/{user}/associate', [PeopleController::class, 'removeAssociate'])->name('dashboard.people.associates.remove');
    Route::post('/people/groups', [PeopleController::class, 'createGroupChat'])->name('dashboard.people.group.create');
    Route::get('/people/groups/{groupChat}', [PeopleController::class, 'groupChat'])->name('dashboard.people.group.chat');
    Route::post('/people/groups/{groupChat}/message', [PeopleController::class, 'sendGroupMessage'])->name('dashboard.people.group.message');
    Route::get('/people/groups/{groupChat}/poll', [PeopleController::class, 'pollGroupChat'])->name('dashboard.people.group.poll');
    Route::get('/people/{user}', [PeopleController::class, 'show'])->name('dashboard.people.show');
    Route::get('/people/{user}/chat', [PeopleController::class, 'chat'])->name('dashboard.people.chat');
    Route::post('/people/{user}/message', [PeopleController::class, 'sendMessage'])->name('dashboard.people.message');
    Route::get('/people/{user}/poll', [PeopleController::class, 'poll'])->name('dashboard.people.poll');
    Route::get('/people/{user}/search', [PeopleController::class, 'searchMessages'])->name('dashboard.people.search');
    Route::get('/people/{user}/media', [PeopleController::class, 'sharedMedia'])->name('dashboard.people.media');
    Route::post('/people/{user}/typing', [PeopleController::class, 'typing'])->name('dashboard.people.typing');
    Route::patch('/people/{user}/message/{message}', [PeopleController::class, 'editMessage'])->name('dashboard.people.message.edit');
    Route::delete('/people/{user}/message/{message}', [PeopleController::class, 'deleteMessage'])->name('dashboard.people.message.delete');
    Route::post('/people/{user}/message/{message}/react', [PeopleController::class, 'toggleReaction'])->name('dashboard.people.message.react');
    Route::post('/people/{user}/message/{message}/forward', [PeopleController::class, 'forwardMessage'])->name('dashboard.people.message.forward');

    Route::get('/code-repository', [CodeRepositoryController::class, 'index'])->name('dashboard.code_repository.index');
    Route::post('/code-repository', [CodeRepositoryController::class, 'storeRepository'])->name('dashboard.code_repository.store');
    Route::get('/code-repository/{repository}', [CodeRepositoryController::class, 'show'])->name('dashboard.code_repository.show');
    Route::patch('/code-repository/{repository}', [CodeRepositoryController::class, 'updateRepository'])->name('dashboard.code_repository.update');
    Route::delete('/code-repository/{repository}', [CodeRepositoryController::class, 'destroyRepository'])->name('dashboard.code_repository.destroy');

    Route::post('/code-repository/{repository}/branches', [CodeRepositoryController::class, 'storeBranch'])->name('dashboard.code_repository.branches.store');
    Route::patch('/code-repository/{repository}/branches/{branch}/default', [CodeRepositoryController::class, 'setDefaultBranch'])->name('dashboard.code_repository.branches.default');
    Route::patch('/code-repository/{repository}/branches/{branch}/protection', [CodeRepositoryController::class, 'toggleBranchProtection'])->name('dashboard.code_repository.branches.protection');
    Route::delete('/code-repository/{repository}/branches/{branch}', [CodeRepositoryController::class, 'destroyBranch'])->name('dashboard.code_repository.branches.destroy');

    Route::post('/code-repository/{repository}/collaborators', [CodeRepositoryController::class, 'storeCollaborator'])->name('dashboard.code_repository.collaborators.store');
    Route::delete('/code-repository/{repository}/collaborators/{collaborator}', [CodeRepositoryController::class, 'removeCollaborator'])->name('dashboard.code_repository.collaborators.destroy');

    Route::patch('/code-repository/tasks/{task}/pr', [CodeRepositoryController::class, 'updatePr'])->name('dashboard.code_repository.tasks.updatePr');

    // Activity feed
    Route::get('/activity', function () {
        $user = Auth::user();
        $activities = collect();

        $tasks = \App\Models\Task::with('board')
            ->whereHas('board', fn($q) => $q->where('user_id', $user->id))
            ->latest('updated_at')->take(50)->get()
            ->map(fn($t) => [
                'icon' => 'task', 'title' => $t->title,
                'desc' => $t->status === 'done' ? 'Task completed' : ($t->created_at->eq($t->updated_at) ? 'Task created' : 'Task updated to '.$t->status),
                'board' => $t->board->name ?? null, 'time' => $t->updated_at,
                'color' => match($t->status) { 'done' => 'green', 'in_progress' => 'yellow', default => 'blue' },
            ]);
        $activities = $activities->merge($tasks);

        $reqs = \App\Models\Requirement::with('board')
            ->whereHas('board', fn($q) => $q->where('user_id', $user->id))
            ->latest('updated_at')->take(20)->get()
            ->map(fn($r) => [
                'icon' => 'requirement', 'title' => $r->title,
                'desc' => $r->created_at->eq($r->updated_at) ? 'Requirement created' : 'Requirement updated',
                'board' => $r->board->name ?? null, 'time' => $r->updated_at, 'color' => 'purple',
            ]);
        $activities = $activities->merge($reqs);

        $boards = \App\Models\Board::where('user_id', $user->id)
            ->latest('updated_at')->take(10)->get()
            ->map(fn($b) => [
                'icon' => 'board', 'title' => $b->name,
                'desc' => $b->created_at->eq($b->updated_at) ? 'Board created' : 'Board updated',
                'board' => null, 'time' => $b->updated_at, 'color' => 'blue',
            ]);
        $activities = $activities->merge($boards);

        $diagrams = \App\Models\Diagram::with('board')
            ->whereHas('board', fn($q) => $q->where('user_id', $user->id))
            ->latest('updated_at')->take(20)->get()
            ->map(fn($d) => [
                'icon' => 'diagram', 'title' => $d->title,
                'desc' => $d->created_at->eq($d->updated_at) ? 'Diagram created' : 'Diagram updated',
                'board' => $d->board->name ?? null, 'time' => $d->updated_at, 'color' => 'teal',
            ]);
        $activities = $activities->merge($diagrams);

        $activities = $activities->sortByDesc('time')->values();

        return view('dashboard.activity', compact('activities'));
    })->name('dashboard.activity');

    Route::get('/planning', [PlanningController::class, 'index'])->name('dashboard.planning.index');
    Route::post('/planning', [PlanningController::class, 'storeBoard'])->name('dashboard.planning.storeBoard');
    Route::get('/planning/{board}', [PlanningController::class, 'show'])->name('dashboard.planning.show');
    Route::patch('/planning/{board}', [PlanningController::class, 'updateBoard'])->name('dashboard.planning.update');
    Route::delete('/planning/{board}', [PlanningController::class, 'destroyBoard'])->name('dashboard.planning.destroy');
    Route::post('/planning/{board}/tasks', [PlanningController::class, 'storeTask'])->name('dashboard.planning.storeTask');

    // Board collaborators (sharing)
    Route::get('/planning/{board}/collaborators', [\App\Http\Controllers\Dashboard\BoardCollaboratorController::class, 'list'])->name('dashboard.planning.collaborators.list');
    Route::get('/planning/{board}/collaborators/search', [\App\Http\Controllers\Dashboard\BoardCollaboratorController::class, 'search'])->name('dashboard.planning.collaborators.search');
    Route::post('/planning/{board}/collaborators/invite', [\App\Http\Controllers\Dashboard\BoardCollaboratorController::class, 'invite'])->name('dashboard.planning.collaborators.invite');
    Route::patch('/planning/{board}/collaborators/role', [\App\Http\Controllers\Dashboard\BoardCollaboratorController::class, 'updateRole'])->name('dashboard.planning.collaborators.role');
    Route::delete('/planning/{board}/collaborators/remove', [\App\Http\Controllers\Dashboard\BoardCollaboratorController::class, 'remove'])->name('dashboard.planning.collaborators.remove');
    Route::patch('/planning/{board}/tasks/{task}', [PlanningController::class, 'updateTask'])->name('dashboard.planning.tasks.update');
    Route::delete('/planning/{board}/tasks/{task}', [PlanningController::class, 'destroyTask'])->name('dashboard.planning.tasks.destroy');
    Route::post('/planning/{task}/move', [PlanningController::class, 'moveTask'])->name('dashboard.planning.moveTask');

    // Board settings
    Route::patch('/planning/{board}/settings/ai', [PlanningController::class, 'setAi'])->name('dashboard.planning.settings.ai');

    // Roadmaps resource
    Route::get('/planning/{board}/roadmap', [RoadmapController::class, 'index'])->name('dashboard.planning.roadmaps.index');
    Route::get('/planning/{board}/roadmap/create', [RoadmapController::class, 'create'])->name('dashboard.planning.roadmaps.create');
    Route::post('/planning/{board}/roadmap', [RoadmapController::class, 'store'])->name('dashboard.planning.roadmaps.store');
    Route::get('/planning/{board}/roadmap/{roadmap}', [RoadmapController::class, 'show'])->name('dashboard.planning.roadmaps.show');
    Route::delete('/planning/{board}/roadmap/{roadmap}', [RoadmapController::class, 'destroy'])->name('dashboard.planning.roadmaps.destroy');

    // milestones
    Route::post('/planning/{board}/roadmap/{roadmap}/milestones', [RoadmapController::class, 'storeMilestone'])->name('dashboard.planning.roadmaps.milestones.store');
    Route::put('/planning/{board}/roadmap/{roadmap}/milestones/{milestone}', [RoadmapController::class, 'updateMilestone'])->name('dashboard.planning.roadmaps.milestones.update');
    Route::delete('/planning/{board}/roadmap/{roadmap}/milestones/{milestone}', [RoadmapController::class, 'destroyMilestone'])->name('dashboard.planning.roadmaps.milestones.destroy');
    Route::get('/planning/{board}/sprint_planning', [PlanningToolsController::class, 'sprint_planning'])->name('dashboard.planning.sprint_planning');
    Route::post('/planning/{board}/sprints', [App\Http\Controllers\Dashboard\SprintController::class, 'store'])->name('dashboard.planning.sprints.store');
    Route::put('/planning/{board}/tasks/{task}/assign-sprint', [App\Http\Controllers\Dashboard\SprintController::class, 'assignTask'])->name('dashboard.planning.tasks.assignSprint');
    // Task Matrix removed
    Route::get('/planning/{board}/backlog_grooming', [PlanningToolsController::class, 'backlog_grooming'])->name('dashboard.planning.backlog_grooming');
    Route::get('/planning/{board}/sprint_board', [PlanningToolsController::class, 'sprint_board'])->name('dashboard.planning.sprint_board');
    Route::get('/planning/{board}/retrospective', [PlanningToolsController::class, 'retrospective'])->name('dashboard.planning.retrospective');
    Route::get('/planning/{board}/release', [PlanningToolsController::class, 'release'])->name('dashboard.planning.release');
    Route::get('/planning/{board}/project-tracking', [PlanningToolsController::class, 'projectTracking'])->name('dashboard.planning.project_tracking');
    // Diagram Hub routes
    Route::get('/planning/{board}/diagrams', [App\Http\Controllers\Dashboard\DiagramHubController::class, 'index'])->name('dashboard.planning.diagrams.index');
    Route::post('/planning/{board}/diagrams', [App\Http\Controllers\Dashboard\DiagramHubController::class, 'store'])->name('dashboard.planning.diagrams.store');
    Route::patch('/planning/{board}/diagrams/{diagram}', [App\Http\Controllers\Dashboard\DiagramHubController::class, 'update'])->name('dashboard.planning.diagrams.update');
    Route::delete('/planning/{board}/diagrams/{diagram}', [App\Http\Controllers\Dashboard\DiagramHubController::class, 'destroy'])->name('dashboard.planning.diagrams.destroy');
    Route::post('/planning/{board}/project-tracking/sync-git', [PlanningToolsController::class, 'syncGit'])->name('dashboard.planning.project_tracking.syncGit');
    Route::post('/planning/{board}/project-tracking/ai-assist', [PlanningToolsController::class, 'aiAssist'])->name('dashboard.planning.project_tracking.aiAssist');
    // New AI flow: generate multiple suggestions, and select one to create
    Route::post('/planning/{board}/project-tracking/ai-generate', [PlanningToolsController::class, 'generateAiSuggestions'])->name('dashboard.planning.project_tracking.aiGenerate');
    Route::post('/planning/{board}/project-tracking/ai-select', [PlanningToolsController::class, 'selectAiSuggestion'])->name('dashboard.planning.project_tracking.aiSelect');
    Route::get('/planning/{board}/project-tracking/search', [PlanningToolsController::class, 'searchTasks'])->name('dashboard.planning.project_tracking.search');
    Route::post('/planning/{board}/project-tracking/tasks', [PlanningController::class, 'storeTask'])->name('dashboard.planning.project_tracking.tasks.store');
    Route::post('/planning/{board}/release/items', [App\Http\Controllers\Dashboard\ReleaseController::class, 'store'])->name('dashboard.planning.release.store');
    Route::patch('/planning/{board}/release/items/{release}', [App\Http\Controllers\Dashboard\ReleaseController::class, 'update'])->name('dashboard.planning.release.update');
    Route::delete('/planning/{board}/release/items/{release}', [App\Http\Controllers\Dashboard\ReleaseController::class, 'destroy'])->name('dashboard.planning.release.destroy');
    Route::post('/planning/{board}/release/items/{release}/move', [App\Http\Controllers\Dashboard\ReleaseController::class, 'move'])->name('dashboard.planning.release.move');
    
    // Requirements tool
    Route::get('/planning/{board}/requirements', [PlanningToolsController::class, 'requirements'])->name('dashboard.planning.requirements');
    // Accept POST on the base requirements URL as well (some forms may submit here)
    Route::post('/planning/{board}/requirements', [App\Http\Controllers\Dashboard\RequirementController::class, 'store']);
    Route::post('/planning/{board}/requirements/items', [App\Http\Controllers\Dashboard\RequirementController::class, 'store'])->name('dashboard.planning.requirements.store');
    Route::patch('/planning/{board}/requirements/items/{requirement}', [App\Http\Controllers\Dashboard\RequirementController::class, 'update'])->name('dashboard.planning.requirements.update');
    Route::delete('/planning/{board}/requirements/items/{requirement}', [App\Http\Controllers\Dashboard\RequirementController::class, 'destroy'])->name('dashboard.planning.requirements.destroy');
    Route::post('/planning/{board}/requirements/items/{requirement}/move', [App\Http\Controllers\Dashboard\RequirementController::class, 'move'])->name('dashboard.planning.requirements.move');
    Route::post('/planning/{board}/requirements/items/{requirement}/tasks', [App\Http\Controllers\Dashboard\RequirementController::class, 'attachTask'])->name('dashboard.planning.requirements.tasks.attach');
    Route::delete('/planning/{board}/requirements/items/{requirement}/tasks/{task}', [App\Http\Controllers\Dashboard\RequirementController::class, 'detachTask'])->name('dashboard.planning.requirements.tasks.detach');
    Route::post('/planning/{board}/requirements/export-pdf', [App\Http\Controllers\Dashboard\RequirementController::class, 'exportPdf'])->name('dashboard.planning.requirements.exportPdf');
});

// AI planning endpoints (authenticated)
Route::middleware(['auth'])->prefix('ai')->group(function(){
    // ── Dedicated endpoints ──
    Route::post('/generate-uml',          [AIPlanningController::class, 'generateUML'])->name('ai.generateUML');
    Route::post('/generate-uml-stream',   [AIPlanningController::class, 'generateUMLStream'])->name('ai.generateUMLStream');
    Route::post('/convert-idea-to-tasks', [AIPlanningController::class, 'convertIdeaToTasks'])->name('ai.convertIdeaToTasks');
    Route::post('/improve-architecture',  [AIPlanningController::class, 'improveArchitecture'])->name('ai.improveArchitecture');
    Route::post('/generate-test-cases',   [AIPlanningController::class, 'generateTestCases'])->name('ai.generateTestCases');
    Route::get('/history',                [AIPlanningController::class, 'history'])->name('ai.history');

    // ── Legacy endpoints (backward compat) ──
    Route::post('/analyze-idea', [AIPlanningController::class, 'analyzeIdea'])->name('ai.analyzeIdea');
    Route::post('/generate-architecture', [AIPlanningController::class, 'generateArchitecture'])->name('ai.generateArchitecture');
    Route::post('/suggest-improvements', [AIPlanningController::class, 'suggestImprovements'])->name('ai.suggestImprovements');
    // Web UI for plans
    Route::get('/plans', [App\Http\Controllers\AI\AIPlanningWebController::class, 'index'])->name('ai.plans.index');
    Route::get('/plans/create', [App\Http\Controllers\AI\AIPlanningWebController::class, 'create'])->name('ai.plans.create');
    Route::post('/plans', [App\Http\Controllers\AI\AIPlanningWebController::class, 'store'])->name('ai.plans.store');
    Route::get('/plans/{plan}', [App\Http\Controllers\AI\AIPlanningWebController::class, 'show'])->name('ai.plans.show');
});
