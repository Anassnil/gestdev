<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AIPlanningService;

$service = new AIPlanningService();
$prompt = "Return ONLY PlantUML between @startuml and @enduml (no explanation); generate a Note system diagram.";
try {
    $out = $service->generatePlantUML($prompt, ['include_ai' => true, 'style' => 'precise', 'temperature' => 0.1, 'top_p' => 0.9]);
    echo $out;
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
