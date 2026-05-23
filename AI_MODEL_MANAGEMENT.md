# AI Model Management (scaffold)

This folder adds a scaffolded AI Model Management subsystem for full lifecycle management.

Included features (scaffold):
- DB migrations for models, model_versions, datasets, experiments, training_runs, metrics, deployments, deployment_logs
- Eloquent models with relationships in `app/Models`
- Controllers (web CRUD) in `app/Http/Controllers/AI`
- Services: `ModelRegistry`, `EvaluationService`, `MonitoringService` in `app/Services`
- Artisan commands: `ai:start-training`, `ai:monitor-deployments`
- Minimal Blade views under `resources/views/ai_*`
- Routes added to `routes/web.php` under prefix `/ai`

Next steps to enable full functionality:
- Run migrations: `php artisan migrate`
- Wire a training backend or queue job to actually run training (call `ai:start-training` or dispatch jobs)
- Implement EvaluationService logic to compute/compare metrics
- Implement MonitoringService integration with your deployment endpoints
- Add access control policies (`app/Policies`) for fine-grained permissions
- Add tests and CI integration

If you want, I can:
- Implement a job/queue integration for training runs
- Add API endpoints and token-based access
- Create a model registry UI with versioning and automated promotion
