<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$svc = app(App\Services\AIPlanningService::class);
$prompt = 'Generate a diagram for an e-commerce platform with users, orders, payments, products, and cart management. Include login, register, pay, create, update, delete, search operations.';

foreach (['sequence','usecase','er','activity','state','component'] as $type) {
    $r = $svc->generateUML($prompt, ['diagram_type' => $type]);
    $ok = str_contains($r, '@startuml') && str_contains($r, '@enduml');
    echo strtoupper($type) . ': ' . ($ok ? 'OK' : 'FAIL') . ' (' . strlen($r) . ' chars)' . PHP_EOL;
    if (!$ok) echo substr($r, 0, 200) . PHP_EOL;
}
