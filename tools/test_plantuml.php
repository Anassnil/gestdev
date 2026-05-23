<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$svc = new \App\Services\AIPlanningService();

$prompts = [
    // Original prompts
    'owner managing systeme',
    'hospital patient management',
    'e-commerce online store',
    'school student grades',
    'hotel booking system',
    'bank account management',
    'restaurant food ordering',
    'social network platform',
    'flight airline booking',
    'gym fitness membership system',
    'pizza delivery tracking',
    // New domain prompts
    'pharmacy medication stock',
    'veterinary clinic for pets',
    'law firm case management',
    'construction project planning',
    'telecom subscriber billing',
    'CRM sales pipeline',
    'recruitment hiring process',
    'e-learning online courses platform',
    'beauty salon appointments',
    'car rental booking system',
    'taxi ride sharing app',
    'courier parcel delivery tracking',
    'charity donation management',
    'dental clinic patient treatment',
    'supermarket grocery management',
    'helpdesk support ticket system',
    'survey questionnaire platform',
    'freelance marketplace gig economy',
    'blood bank donation management',
    'emergency ambulance dispatch',
    'wedding planning organisation',
    'museum art exhibition',
    'zoo animal management',
    'bakery pastry shop',
    'laundry dry cleaning service',
    'electricity utility billing',
    'police criminal investigation',
    'daycare nursery children',
    'cryptocurrency blockchain wallet',
    'smart home IoT devices',
    'recycling waste management',
];

foreach ($prompts as $p) {
    echo "\n========== PROMPT: $p ==========\n";
    $result = $svc->generatePlantUML($p, ['board_id' => null, 'title' => 'test']);
    $classCount = substr_count($result, 'class ');
    $hasStartuml = str_contains($result, '@startuml');
    echo "Classes: $classCount | Valid: " . ($hasStartuml ? 'YES' : 'NO') . "\n";
    if (!$hasStartuml || $classCount < 2) {
        echo "*** FAILED ***\n";
        echo $result . "\n";
    } else {
        echo "OK\n";
    }
}
echo "\n--- ALL DONE ---\n";
