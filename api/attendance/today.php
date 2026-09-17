<?php
/**
 * API: Attendance - Today's Status
 * GET /api/attendance/today.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

Auth::requireAuth();
$empId = Auth::employeeId();
$today = date('Y-m-d');
$pdo = Database::getConnection();

$record = null;
if ($empId) {
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = :eid AND date = :today LIMIT 1");
    $stmt->execute([':eid' => $empId, ':today' => $today]);
    $record = $stmt->fetch();
}

$workStart = env('WORK_START_TIME', '09:00');
$currentTime = date('H:i:s');

Response::json([
    'date'               => $today,
    'current_time'       => $currentTime,
    'standard_work_start'=> $workStart,
    'attendance'         => $record ?: null,
    'is_checked_in'      => !empty($record['check_in']) && empty($record['check_out']),
    'is_checked_out'     => !empty($record['check_out'])
]);
