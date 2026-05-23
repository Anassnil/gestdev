<?php

namespace App\Providers;

use App\Models\AIPlan;
use App\Models\AIModel;
use App\Models\Board;
use App\Models\Dataset;
use App\Models\Experiment;
use App\Models\TrainingRun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Scope board route model binding to the authenticated owner OR collaborator.
        Route::bind('board', function ($value) {
            $query = Board::query()->whereKey($value);
            $user = Auth::user();

            if ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhereHas('collaborators', fn($c) => $c->where('user_id', $user->id));
                });
            } else {
                $query->whereRaw('1 = 0');
            }

            return $query->firstOrFail();
        });

        // Scope AI model binding to the authenticated owner.
        Route::bind('model', function ($value) {
            $user = Auth::user();
            $query = AIModel::query()->whereKey($value);
            if ($user) {
                $query->where('user_id', $user->id);
            } else {
                $query->whereRaw('1 = 0');
            }
            return $query->firstOrFail();
        });

        // Scope dataset binding to the authenticated owner.
        Route::bind('dataset', function ($value) {
            $user = Auth::user();
            $query = Dataset::query()->whereKey($value);
            if ($user) {
                $query->where('user_id', $user->id);
            } else {
                $query->whereRaw('1 = 0');
            }
            return $query->firstOrFail();
        });

        // Scope AI plan binding to the authenticated owner.
        Route::bind('plan', function ($value) {
            $user = Auth::user();
            $query = AIPlan::query()->whereKey($value);
            if ($user) {
                $query->where('user_id', $user->id);
            } else {
                $query->whereRaw('1 = 0');
            }
            return $query->firstOrFail();
        });

        // Scope experiment binding through the owning AI model.
        Route::bind('experiment', function ($value) {
            $user = Auth::user();
            $query = Experiment::query()->whereKey($value);
            if ($user) {
                $query->whereHas('model', fn($q) => $q->where('user_id', $user->id));
            } else {
                $query->whereRaw('1 = 0');
            }
            return $query->firstOrFail();
        });

        // Scope training run binding through its experiment's owning AI model.
        Route::bind('training_run', function ($value) {
            $user = Auth::user();
            $query = TrainingRun::query()->whereKey($value);
            if ($user) {
                $query->whereHas('experiment.model', fn($q) => $q->where('user_id', $user->id));
            } else {
                $query->whereRaw('1 = 0');
            }
            return $query->firstOrFail();
        });

        // Define manage-ai gate as a fallback if AuthServiceProvider is not registered.
        Gate::define('manage-ai', function ($user = null) {
            if (! $user) return false;
            $admins = array_filter(array_map('trim', explode(',', env('AI_ADMINS', ''))));
            if (in_array($user->email, $admins)) return true;
            if (app()->environment('local')) return true;
            return false;
        });
    }
}
