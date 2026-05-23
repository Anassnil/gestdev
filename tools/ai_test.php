<?php
// Simple Laravel bootstrap script to call AIPlanningService::generatePlantUML
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AIPlanningService;

$service = new AIPlanningService();

$prompt = "Return ONLY PlantUML between @startuml and @enduml (no explanation); use these exact comment headers in order: ' ========================', 'CORE ENTITIES', '========================', 'MANAGEMENT FEATURES', '========================', 'AI & GENERATION', '========================', 'RELATIONS', '========================'; generate a full class diagram for a Note Management System with these entities and fields — Note(id:int,title:string,body:text,version:int,created_at:datetime,updated_at:datetime), User(id:int,name:string,email:string), Tag(id:int,name:string), Notebook(id:int,name:string,user_id:int), Comment(id:int,content:text,user_id:int,note_id:int), NoteTag(note_id:int,tag_id:int) — include relations: User 1-many Notebook; Notebook 1-many Note; Note many-many Tag via NoteTag; Note 1-many Comment; include management classes (NoteVersion, AccessControl, ShareLink) and AI & Generation classes (DiagramGenerator, ValidationService); output only valid PlantUML.";

try {
    $out = $service->generatePlantUML($prompt, ['style' => 'precise', 'temperature' => 0.1, 'top_p' => 0.9, 'max_tokens' => 1200]);
    echo $out;
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
