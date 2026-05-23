<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$svc = app(App\Services\AIPlanningService::class);

$prompt = <<<'PROMPT'
You are a senior software architect.

Generate a complete and production-ready UML Class Diagram using PlantUML for a full-featured Car Rental Management System (web application).

The system must be modular, scalable, and follow clean architecture principles.

Include the following domains and ensure proper relationships (inheritance, composition, aggregation, associations):

1. USER & AUTHENTICATION
- User (id, name, email, password, role, status)
- Roles (Admin, Customer, Employee)
- Authentication (login, register, reset_password)
- Permission (id, name, description)

2. CUSTOMER MANAGEMENT
- Customer (id, user_id, driver_license, address, phone, dob)
- CustomerHistory (id, customer_id, action, created_at)

3. VEHICLE MANAGEMENT
- Vehicle (id, brand, model, year, status, mileage, category_id)
- VehicleCategory (id, name, daily_rate, deposit)
- VehicleAvailability (id, vehicle_id, date, available)
- VehicleMaintenance (id, vehicle_id, description, scheduled_at, status)
- VehicleImage (id, vehicle_id, path, is_primary)

4. BOOKING & RESERVATION
- Reservation (id, customer_id, vehicle_id, start_date, end_date, status)
- Booking (id, reservation_id, confirmed_at, notes)
- Cancellation (id, reservation_id, reason, cancelled_at, refund_amount)

5. RENTAL PROCESS
- RentalContract (id, booking_id, pickup_date, return_date, conditions, fuel_level_out)
- Inspection (id, contract_id, type, notes, fuel_level, damage_noted, inspected_at)
- LateReturn (id, contract_id, days_late, fee)

6. PRICING & BILLING
- PricingPlan (id, category_id, daily_rate, weekly_rate, monthly_rate, discount_pct)
- Invoice (id, contract_id, total_amount, tax, status, issued_at)
- Payment (id, invoice_id, method, amount, transaction_id, status, paid_at)
- Refund (id, payment_id, amount, reason, processed_at)

7. INSURANCE & PENALTIES
- Insurance (id, contract_id, type, coverage, cost, provider)
- Penalty (id, contract_id, type, amount, reason, issued_at)

8. LOCATION & LOGISTICS
- Branch (id, name, city, address, phone, manager_id)
- VehicleAssignment (id, vehicle_id, branch_id, assigned_at, returned_at)

9. NOTIFICATIONS
- Notification (id, user_id, type, message, read_at, sent_at)
- NotificationTemplate (id, name, subject, body, channel)

10. ADMIN & REPORTING
- Report (id, type, generated_at, data, created_by)
- AuditLog (id, user_id, action, entity_type, entity_id, created_at)
PROMPT;

$result = $svc->generateUML($prompt, ['diagram_type' => 'class', 'board_id' => null]);

$lines = explode("\n", $result);
echo "Lines: " . count($lines) . "\n";
echo "Classes: " . substr_count($result, 'class ') . "\n";
echo "Enums: " . substr_count($result, 'enum ') . "\n";
echo "Relations: " . substr_count($result, '--') . "\n";
echo "Has @startuml: " . (str_contains($result, '@startuml') ? 'YES' : 'NO') . "\n";
echo "Has @enduml: "   . (str_contains($result, '@enduml')   ? 'YES' : 'NO') . "\n";
echo "\n--- FIRST 60 LINES ---\n";
echo implode("\n", array_slice($lines, 0, 60)) . "\n";
