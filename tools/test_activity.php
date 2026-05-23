<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$svc = app(App\Services\AIPlanningService::class);

$prompt = <<<'PROMPT'
Generate a detailed Activity Diagram for an Online Food Ordering System.

The flow should cover the following steps:

1. Customer opens the app and browses restaurant listings
2. Customer searches and filters by cuisine, rating, or delivery time
3. Customer selects a restaurant and views the menu
4. Customer adds items to the cart and adjusts quantities
5. System validates cart (checks item availability and minimum order amount)
6. Customer proceeds to checkout and selects delivery address
7. Customer chooses payment method (credit card, cash on delivery, wallet)
8. System processes payment and validates transaction
9. If payment fails, system prompts customer to retry or choose another method
10. If payment succeeds, system creates order and notifies the restaurant
11. Restaurant confirms or rejects the order
12. If rejected, system refunds customer and sends notification
13. If confirmed, delivery agent is assigned
14. System tracks delivery in real time and notifies customer at each step
15. Customer receives order and submits a rating and review
PROMPT;

$result = $svc->generateUML($prompt, ['diagram_type' => 'activity']);
echo $result . PHP_EOL;
