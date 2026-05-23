<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AIModelSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Datasets ──────────────────────────────────────────────
        $datasets = [
            [
                'name' => 'Customer Churn Dataset',
                'type' => 'csv',
                'path' => 'datasets/customer_churn.csv',
                'metadata' => json_encode([
                    'rows'     => 14000,
                    'features' => 20,
                    'size'     => 3407872,
                    'preview'  => [
                        ['customer_id' => 'C001', 'tenure' => 12, 'monthly_charges' => 65.4, 'churn' => 0],
                        ['customer_id' => 'C002', 'tenure' => 2,  'monthly_charges' => 95.2, 'churn' => 1],
                        ['customer_id' => 'C003', 'tenure' => 45, 'monthly_charges' => 42.0, 'churn' => 0],
                        ['customer_id' => 'C004', 'tenure' => 8,  'monthly_charges' => 78.9, 'churn' => 1],
                        ['customer_id' => 'C005', 'tenure' => 30, 'monthly_charges' => 55.0, 'churn' => 0],
                    ],
                ]),
                'created_at' => Carbon::now()->subDays(30),
                'updated_at' => Carbon::now()->subDays(30),
            ],
            [
                'name' => 'Sales Forecast Data',
                'type' => 'csv',
                'path' => 'datasets/sales_forecast.csv',
                'metadata' => json_encode([
                    'rows'     => 8500,
                    'features' => 15,
                    'size'     => 1843200,
                    'preview'  => [
                        ['date' => '2025-01-01', 'product' => 'A', 'region' => 'EU', 'sales' => 1200],
                        ['date' => '2025-01-02', 'product' => 'B', 'region' => 'US', 'sales' => 950],
                        ['date' => '2025-01-03', 'product' => 'A', 'region' => 'US', 'sales' => 1400],
                        ['date' => '2025-01-04', 'product' => 'C', 'region' => 'EU', 'sales' => 700],
                        ['date' => '2025-01-05', 'product' => 'B', 'region' => 'APAC', 'sales' => 820],
                    ],
                ]),
                'created_at' => Carbon::now()->subDays(25),
                'updated_at' => Carbon::now()->subDays(25),
            ],
            [
                'name' => 'Product Review Sentiment',
                'type' => 'json',
                'path' => 'datasets/product_reviews.json',
                'metadata' => json_encode([
                    'rows'     => 22000,
                    'features' => 8,
                    'size'     => 6291456,
                    'preview'  => [
                        ['id' => 1, 'review' => 'Great product!',       'rating' => 5, 'sentiment' => 'positive'],
                        ['id' => 2, 'review' => 'Not worth the price.', 'rating' => 2, 'sentiment' => 'negative'],
                        ['id' => 3, 'review' => 'Average quality.',     'rating' => 3, 'sentiment' => 'neutral'],
                        ['id' => 4, 'review' => 'Highly recommend.',    'rating' => 5, 'sentiment' => 'positive'],
                        ['id' => 5, 'review' => 'Would not buy again.', 'rating' => 1, 'sentiment' => 'negative'],
                    ],
                ]),
                'created_at' => Carbon::now()->subDays(20),
                'updated_at' => Carbon::now()->subDays(20),
            ],
            [
                'name' => 'Fraud Detection Transactions',
                'type' => 'csv',
                'path' => 'datasets/fraud_transactions.csv',
                'metadata' => json_encode([
                    'rows'     => 284807,
                    'features' => 31,
                    'size'     => 71303168,
                    'preview'  => [
                        ['time' => 0,   'v1' => -1.36, 'v2' => -0.07, 'amount' => 149.62, 'class' => 0],
                        ['time' => 0,   'v1' =>  1.19, 'v2' =>  0.27, 'amount' => 2.69,   'class' => 0],
                        ['time' => 1,   'v1' => -1.36, 'v2' => -1.34, 'amount' => 378.66, 'class' => 0],
                        ['time' => 1,   'v1' =>  1.23, 'v2' =>  0.08, 'amount' => 123.50, 'class' => 0],
                        ['time' => 2,   'v1' => -0.33, 'v2' =>  1.12, 'amount' => 69.99,  'class' => 1],
                    ],
                ]),
                'created_at' => Carbon::now()->subDays(18),
                'updated_at' => Carbon::now()->subDays(18),
            ],
        ];

        DB::table('datasets')->insert($datasets);

        $datasetIds = DB::table('datasets')->orderBy('id')->pluck('id')->toArray();

        // ── 2. AI Models ─────────────────────────────────────────────
        $models = [
            [
                'name'        => 'Churn Predictor v2',
                'type'        => 'classification',
                'status'      => 'active',
                'description' => 'Predicts customer churn probability using historical usage patterns and billing data.',
                'created_at'  => Carbon::now()->subDays(28),
                'updated_at'  => Carbon::now()->subDays(2),
            ],
            [
                'name'        => 'Revenue Forecast Engine',
                'type'        => 'regression',
                'status'      => 'active',
                'description' => 'Time-series regression model for monthly revenue forecasting across regions.',
                'created_at'  => Carbon::now()->subDays(22),
                'updated_at'  => Carbon::now()->subDays(5),
            ],
            [
                'name'        => 'Sentiment Analyzer',
                'type'        => 'nlp',
                'status'      => 'created',
                'description' => 'NLP model for classifying product review sentiment into positive/neutral/negative.',
                'created_at'  => Carbon::now()->subDays(15),
                'updated_at'  => Carbon::now()->subDays(15),
            ],
            [
                'name'        => 'Fraud Detection Model',
                'type'        => 'classification',
                'status'      => 'active',
                'description' => 'Binary classifier for real-time credit card fraud detection.',
                'created_at'  => Carbon::now()->subDays(12),
                'updated_at'  => Carbon::now()->subDays(1),
            ],
        ];

        DB::table('ai_models')->insert($models);

        $modelIds = DB::table('ai_models')->orderBy('id')->pluck('id')->toArray();

        // ── 3. Model Versions ─────────────────────────────────────────
        $versions = [
            // Churn Predictor
            ['ai_model_id' => $modelIds[0], 'version' => 'v1.0', 'status' => 'deprecated', 'config' => json_encode(['epochs' => 20, 'batch_size' => 64, 'learning_rate' => 0.01]),  'created_at' => Carbon::now()->subDays(25), 'updated_at' => Carbon::now()->subDays(25)],
            ['ai_model_id' => $modelIds[0], 'version' => 'v1.1', 'status' => 'deprecated', 'config' => json_encode(['epochs' => 30, 'batch_size' => 64, 'learning_rate' => 0.005]), 'created_at' => Carbon::now()->subDays(18), 'updated_at' => Carbon::now()->subDays(18)],
            ['ai_model_id' => $modelIds[0], 'version' => 'v2.0', 'status' => 'production',  'config' => json_encode(['epochs' => 50, 'batch_size' => 32, 'learning_rate' => 0.001]), 'created_at' => Carbon::now()->subDays(5),  'updated_at' => Carbon::now()->subDays(2)],

            // Revenue Forecast
            ['ai_model_id' => $modelIds[1], 'version' => 'v1.0', 'status' => 'deprecated', 'config' => json_encode(['epochs' => 15, 'batch_size' => 128, 'learning_rate' => 0.01]),  'created_at' => Carbon::now()->subDays(20), 'updated_at' => Carbon::now()->subDays(20)],
            ['ai_model_id' => $modelIds[1], 'version' => 'v1.2', 'status' => 'production',  'config' => json_encode(['epochs' => 40, 'batch_size' => 64,  'learning_rate' => 0.002]), 'created_at' => Carbon::now()->subDays(8),  'updated_at' => Carbon::now()->subDays(5)],

            // Fraud Detection
            ['ai_model_id' => $modelIds[3], 'version' => 'v1.0', 'status' => 'staging',    'config' => json_encode(['epochs' => 25, 'batch_size' => 256, 'learning_rate' => 0.003]), 'created_at' => Carbon::now()->subDays(9),  'updated_at' => Carbon::now()->subDays(1)],
        ];

        DB::table('model_versions')->insert($versions);

        $versionIds = DB::table('model_versions')->orderBy('id')->pluck('id')->toArray();

        // ── 4. Experiments ────────────────────────────────────────────
        $experiments = [
            // Churn Predictor — 3 experiments
            ['ai_model_id' => $modelIds[0], 'dataset_id' => $datasetIds[0], 'status' => 'completed', 'started_at' => Carbon::now()->subDays(26), 'ended_at' => Carbon::now()->subDays(25), 'created_at' => Carbon::now()->subDays(27), 'updated_at' => Carbon::now()->subDays(25)],
            ['ai_model_id' => $modelIds[0], 'dataset_id' => $datasetIds[0], 'status' => 'completed', 'started_at' => Carbon::now()->subDays(16), 'ended_at' => Carbon::now()->subDays(15), 'created_at' => Carbon::now()->subDays(17), 'updated_at' => Carbon::now()->subDays(15)],
            ['ai_model_id' => $modelIds[0], 'dataset_id' => $datasetIds[0], 'status' => 'completed', 'started_at' => Carbon::now()->subDays(6),  'ended_at' => Carbon::now()->subDays(2),  'created_at' => Carbon::now()->subDays(7),  'updated_at' => Carbon::now()->subDays(2)],

            // Revenue Forecast — 2 experiments
            ['ai_model_id' => $modelIds[1], 'dataset_id' => $datasetIds[1], 'status' => 'completed', 'started_at' => Carbon::now()->subDays(21), 'ended_at' => Carbon::now()->subDays(20), 'created_at' => Carbon::now()->subDays(22), 'updated_at' => Carbon::now()->subDays(20)],
            ['ai_model_id' => $modelIds[1], 'dataset_id' => $datasetIds[1], 'status' => 'completed', 'started_at' => Carbon::now()->subDays(9),  'ended_at' => Carbon::now()->subDays(5),  'created_at' => Carbon::now()->subDays(10), 'updated_at' => Carbon::now()->subDays(5)],

            // Sentiment Analyzer — 1 experiment (running)
            ['ai_model_id' => $modelIds[2], 'dataset_id' => $datasetIds[2], 'status' => 'running',   'started_at' => Carbon::now()->subHours(3),  'ended_at' => null,                        'created_at' => Carbon::now()->subDays(2),  'updated_at' => Carbon::now()->subHours(3)],

            // Fraud Detection — 2 experiments
            ['ai_model_id' => $modelIds[3], 'dataset_id' => $datasetIds[3], 'status' => 'completed', 'started_at' => Carbon::now()->subDays(11), 'ended_at' => Carbon::now()->subDays(10), 'created_at' => Carbon::now()->subDays(12), 'updated_at' => Carbon::now()->subDays(10)],
            ['ai_model_id' => $modelIds[3], 'dataset_id' => $datasetIds[3], 'status' => 'completed', 'started_at' => Carbon::now()->subDays(3),  'ended_at' => Carbon::now()->subDays(1),  'created_at' => Carbon::now()->subDays(4),  'updated_at' => Carbon::now()->subDays(1)],
        ];

        DB::table('experiments')->insert($experiments);

        $experimentIds = DB::table('experiments')->orderBy('id')->pluck('id')->toArray();

        // ── 5. Training Runs & 6. Metrics ─────────────────────────────
        // Helper: build realistic accuracy/loss curves
        $this->seedTrainingRuns($experimentIds);

        // ── 7. Deployments ────────────────────────────────────────────
        $deployments = [
            // Churn Predictor v2.0 → production
            ['model_version_id' => $versionIds[2], 'environment' => 'production', 'status' => 'active',   'endpoint_url' => 'https://api.example.com/v2/churn/predict',       'created_at' => Carbon::now()->subDays(4), 'updated_at' => Carbon::now()->subDays(2)],
            ['model_version_id' => $versionIds[2], 'environment' => 'staging',    'status' => 'inactive', 'endpoint_url' => 'https://staging.example.com/v2/churn/predict',   'created_at' => Carbon::now()->subDays(6), 'updated_at' => Carbon::now()->subDays(4)],

            // Revenue Forecast v1.2 → production
            ['model_version_id' => $versionIds[4], 'environment' => 'production', 'status' => 'active',   'endpoint_url' => 'https://api.example.com/v1/forecast/predict',    'created_at' => Carbon::now()->subDays(7), 'updated_at' => Carbon::now()->subDays(5)],

            // Fraud Detection v1.0 → staging
            ['model_version_id' => $versionIds[5], 'environment' => 'staging',    'status' => 'active',   'endpoint_url' => 'https://staging.example.com/v1/fraud/predict',  'created_at' => Carbon::now()->subDays(8), 'updated_at' => Carbon::now()->subDays(1)],
        ];

        DB::table('deployments')->insert($deployments);

        $deploymentIds = DB::table('deployments')->orderBy('id')->pluck('id')->toArray();

        // ── 8. Deployment Logs ────────────────────────────────────────
        $logs = [
            // Churn production deployment
            ['deployment_id' => $deploymentIds[0], 'message' => 'Deployment initiated. Pulling model artifact v2.0.',              'level' => 'info',    'created_at' => Carbon::now()->subDays(4)->addMinutes(0)],
            ['deployment_id' => $deploymentIds[0], 'message' => 'Model artifact downloaded successfully (48 MB).',                 'level' => 'info',    'created_at' => Carbon::now()->subDays(4)->addMinutes(1)],
            ['deployment_id' => $deploymentIds[0], 'message' => 'Container built and pushed to registry.',                        'level' => 'info',    'created_at' => Carbon::now()->subDays(4)->addMinutes(3)],
            ['deployment_id' => $deploymentIds[0], 'message' => 'Health check passed — endpoint responding at 200ms avg.',        'level' => 'info',    'created_at' => Carbon::now()->subDays(4)->addMinutes(5)],
            ['deployment_id' => $deploymentIds[0], 'message' => 'Production traffic switched. Old version decommissioned.',        'level' => 'info',    'created_at' => Carbon::now()->subDays(4)->addMinutes(6)],
            ['deployment_id' => $deploymentIds[0], 'message' => 'High latency spike detected (850ms). Auto-scaled to 3 replicas.', 'level' => 'warning', 'created_at' => Carbon::now()->subDays(2)->addHours(14)],
            ['deployment_id' => $deploymentIds[0], 'message' => 'Latency normalized after scaling. Avg response 210ms.',          'level' => 'info',    'created_at' => Carbon::now()->subDays(2)->addHours(14)->addMinutes(15)],

            // Revenue Forecast production deployment
            ['deployment_id' => $deploymentIds[2], 'message' => 'Deployment started for Revenue Forecast v1.2.',                  'level' => 'info',    'created_at' => Carbon::now()->subDays(7)->addMinutes(0)],
            ['deployment_id' => $deploymentIds[2], 'message' => 'Model artifact verified. Checksum OK.',                          'level' => 'info',    'created_at' => Carbon::now()->subDays(7)->addMinutes(2)],
            ['deployment_id' => $deploymentIds[2], 'message' => 'Deployment successful. Serving on production endpoint.',         'level' => 'info',    'created_at' => Carbon::now()->subDays(7)->addMinutes(8)],

            // Fraud Detection staging deployment
            ['deployment_id' => $deploymentIds[3], 'message' => 'Staging deployment for Fraud Detection v1.0 initiated.',         'level' => 'info',    'created_at' => Carbon::now()->subDays(8)->addMinutes(0)],
            ['deployment_id' => $deploymentIds[3], 'message' => 'Running integration tests against staging endpoint.',            'level' => 'info',    'created_at' => Carbon::now()->subDays(8)->addMinutes(5)],
            ['deployment_id' => $deploymentIds[3], 'message' => 'Test suite passed (47/47). False-positive rate: 0.8%.',          'level' => 'info',    'created_at' => Carbon::now()->subDays(8)->addMinutes(12)],
            ['deployment_id' => $deploymentIds[3], 'message' => 'Memory usage above threshold (2.1 GB / 2 GB limit).',            'level' => 'error',   'created_at' => Carbon::now()->subDays(1)->addHours(9)],
            ['deployment_id' => $deploymentIds[3], 'message' => 'OOM issue resolved. Batch size reduced to 128. Redeployed.',     'level' => 'warning', 'created_at' => Carbon::now()->subDays(1)->addHours(9)->addMinutes(30)],
        ];

        DB::table('deployment_logs')->insert($logs);
    }

    private function seedTrainingRuns(array $experimentIds): void
    {
        // Define run configs per experiment — simulates increasingly good results
        $runConfigs = [
            // Exp 0 — Churn, early attempt
            $experimentIds[0] => [
                ['epochs' => 20, 'batch' => 64,  'lr' => 0.01,  'start_acc' => 0.65, 'end_acc' => 0.78, 'start_loss' => 0.62, 'end_loss' => 0.44, 'days_ago_start' => 26, 'days_ago_end' => 25],
            ],
            // Exp 1 — Churn, improved
            $experimentIds[1] => [
                ['epochs' => 30, 'batch' => 64,  'lr' => 0.005, 'start_acc' => 0.77, 'end_acc' => 0.83, 'start_loss' => 0.46, 'end_loss' => 0.38, 'days_ago_start' => 16, 'days_ago_end' => 15],
                ['epochs' => 30, 'batch' => 32,  'lr' => 0.005, 'start_acc' => 0.79, 'end_acc' => 0.85, 'start_loss' => 0.43, 'end_loss' => 0.35, 'days_ago_start' => 15, 'days_ago_end' => 14],
            ],
            // Exp 2 — Churn, best run
            $experimentIds[2] => [
                ['epochs' => 50, 'batch' => 32,  'lr' => 0.001, 'start_acc' => 0.81, 'end_acc' => 0.913, 'start_loss' => 0.41, 'end_loss' => 0.22, 'days_ago_start' => 6, 'days_ago_end' => 2],
                ['epochs' => 50, 'batch' => 16,  'lr' => 0.001, 'start_acc' => 0.82, 'end_acc' => 0.887, 'start_loss' => 0.39, 'end_loss' => 0.26, 'days_ago_start' => 5, 'days_ago_end' => 2],
            ],

            // Exp 3 — Revenue Forecast
            $experimentIds[3] => [
                ['epochs' => 15, 'batch' => 128, 'lr' => 0.01,  'start_acc' => 0.70, 'end_acc' => 0.81, 'start_loss' => 0.55, 'end_loss' => 0.41, 'days_ago_start' => 21, 'days_ago_end' => 20],
            ],
            // Exp 4 — Revenue Forecast, tuned
            $experimentIds[4] => [
                ['epochs' => 40, 'batch' => 64,  'lr' => 0.002, 'start_acc' => 0.80, 'end_acc' => 0.879, 'start_loss' => 0.42, 'end_loss' => 0.29, 'days_ago_start' => 9, 'days_ago_end' => 5],
            ],

            // Exp 5 — Sentiment (running — only partial epochs)
            $experimentIds[5] => [
                ['epochs' => 25, 'batch' => 32,  'lr' => 0.003, 'start_acc' => 0.55, 'end_acc' => 0.68, 'start_loss' => 0.70, 'end_loss' => 0.51, 'days_ago_start' => 0, 'days_ago_end' => 0, 'status' => 'running', 'partial' => true],
            ],

            // Exp 6 — Fraud Detection
            $experimentIds[6] => [
                ['epochs' => 25, 'batch' => 256, 'lr' => 0.003, 'start_acc' => 0.88, 'end_acc' => 0.942, 'start_loss' => 0.35, 'end_loss' => 0.18, 'days_ago_start' => 11, 'days_ago_end' => 10],
            ],
            // Exp 7 — Fraud Detection, refined
            $experimentIds[7] => [
                ['epochs' => 25, 'batch' => 128, 'lr' => 0.002, 'start_acc' => 0.93, 'end_acc' => 0.971, 'start_loss' => 0.19, 'end_loss' => 0.09, 'days_ago_start' => 3, 'days_ago_end' => 1],
                ['epochs' => 20, 'batch' => 128, 'lr' => 0.001, 'start_acc' => 0.94, 'end_acc' => 0.963, 'start_loss' => 0.18, 'end_loss' => 0.11, 'days_ago_start' => 2, 'days_ago_end' => 1],
            ],
        ];

        foreach ($runConfigs as $expId => $runs) {
            foreach ($runs as $run) {
                $isRunning = ($run['status'] ?? 'completed') === 'running';
                $partial   = $run['partial'] ?? false;

                $startedAt = Carbon::now()->subDays($run['days_ago_start']);
                $endedAt   = $isRunning ? null : Carbon::now()->subDays($run['days_ago_end']);

                $runId = DB::table('training_runs')->insertGetId([
                    'experiment_id' => $expId,
                    'parameters'    => json_encode([
                        'epochs'        => $run['epochs'],
                        'batch_size'    => $run['batch'],
                        'learning_rate' => $run['lr'],
                    ]),
                    'status'     => $isRunning ? 'running' : 'completed',
                    'started_at' => $startedAt,
                    'ended_at'   => $endedAt,
                    'created_at' => $startedAt,
                    'updated_at' => $endedAt ?? Carbon::now(),
                ]);

                // Generate per-epoch metrics (accuracy + loss curve)
                $totalEpochs    = $run['epochs'];
                $reportedEpochs = $partial ? (int) ($totalEpochs * 0.45) : $totalEpochs;

                $metrics = [];
                for ($epoch = 1; $epoch <= $reportedEpochs; $epoch++) {
                    $progress = $epoch / $totalEpochs;

                    // Smooth S-curve for accuracy
                    $accuracy = $run['start_acc'] + ($run['end_acc'] - $run['start_acc'])
                        * (1 / (1 + exp(-10 * ($progress - 0.5))));
                    // Add slight noise
                    $accuracy = round($accuracy + (mt_rand(-20, 20) / 2000), 4);

                    // Exponential decay for loss
                    $loss = $run['start_loss'] * exp(-$progress * log($run['start_loss'] / $run['end_loss']));
                    $loss = round($loss + (mt_rand(-15, 15) / 2000), 4);

                    $epochTime = $startedAt->copy()->addMinutes((int) ($epoch * ($run['days_ago_start'] - $run['days_ago_end']) * 1440 / $totalEpochs));

                    $metrics[] = ['training_run_id' => $runId, 'name' => 'accuracy', 'value' => $accuracy, 'created_at' => $epochTime, 'updated_at' => $epochTime];
                    $metrics[] = ['training_run_id' => $runId, 'name' => 'loss',     'value' => max(0.01, $loss), 'created_at' => $epochTime, 'updated_at' => $epochTime];
                }

                // Insert in chunks to avoid query size limits
                foreach (array_chunk($metrics, 200) as $chunk) {
                    DB::table('metrics')->insert($chunk);
                }
            }
        }
    }
}
