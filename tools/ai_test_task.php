<?php
// Simple Laravel bootstrap script to call AIPlanningService::generatePlantUML for Task Management
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AIPlanningService;

$service = new AIPlanningService();

$prompt = "Return ONLY PlantUML between @startuml and @enduml (no explanation); use these exact comment headers in order: ' ========================', 'CORE ENTITIES', '========================', 'MANAGEMENT FEATURES', '========================', 'AI & GENERATION', '========================', 'RELATIONS', '========================'; generate a full class diagram for a Task Management System with entities and fields — User(id:int,name:string,email:string), Project(id:int,name:string,description:text), Task(id:int,title:string,state:string,estimate:int), Sprint(id:int,name:string,start_date:datetime,end_date:datetime), Requirement(id:int,title:string), Tag(id:int,name:string), Comment(id:int,content:text,user_id:int,task_id:int), include relations: User 1-many Project; Project 1-many Task; Sprint 1-many Task; Requirement 1-many Task; Task many-many Tag via TaskTag; Task 1-many Comment; include management classes (Release, AccessControl, ShareLink) and AI & Generation classes (DiagramGenerator, ValidationService); output only valid PlantUML.";

try {
    $out = $service->generatePlantUML($prompt, ['style' => 'precise', 'temperature' => 0.1, 'top_p' => 0.9, 'max_tokens' => 1200, 'search_radius' => 2]);
    echo $out;
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
