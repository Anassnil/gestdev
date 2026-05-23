<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$svc = new \App\Services\AIPlanningService();
$result = $svc->generatePlantUML('electric cars chargers managing systeme', ['board_id' => null, 'title' => 'test']);
echo $result;
