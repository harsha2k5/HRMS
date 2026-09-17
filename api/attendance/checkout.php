<?php
/**
 * API: Attendance - Check Out
 * POST /api/attendance/checkout.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';
require_once __DIR__ . '/../../middleware/auth.php';

Auth::requireAuth();
$empId = Auth::employeeId();

if (!$empId) {
    Response::error('No employee profile associated with this account.', [], 400);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', [], 405);
}

$today = date('Y-m-d');
$nowTime = date('H:i:s');
$pdo = Database::getConnection();

// Fetch today's check-in
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = :eid AND date = :today LIMIT 1");
$stmt->execute([':eid' => $empId, ':today' => $today]);
$existing = $stmt->fetch();

if (!$existing || empty($existing['check_in'])) {
    Response::error('You have not checked in today yet.', [], 400);
}

if (!empty($existing['check_out'])) {
    Response::error("You have already checked out today at {$existing['check_out']}.", [], 400);
}

// Calculate hours
$checkInTimestamp = strtotime("{$today} {$existing['check_in']}");
$checkOutTimestamp = strtotime("{$today} {$nowTime}");
$durationSeconds = max(0, $checkOutTimestamp - $checkInTimestamp);
$workingHours = round($durationSeconds / 3600, 2);
$overtimeHours = max(0.00, round($workingHours - 8.00, 2));

$upd = $pdo->prepare("
    UPDATE attendance
    SET check_out = :time, working_hours = :wh, overtime_hours = :ot
    WHERE id = :id
");
$upd->execute([
    ':time' => $nowTime,
    ':wh'   => $workingHours,
    ':ot'   => $overtimeHours,
    ':id'   => $existing['id']
]);

Audit::log('attendance_checkout', 'attendance', $existing['id'], "Employee checked out at {$nowTime} ({$workingHours} hrs)");

Response::json([
    'check_out'     => $nowTime,
    'working_hours' => $workingHours,
    'overtime_hours'=> $overtimeHours
], "Punched out successfully at {$nowTime}. Total hours worked: {$workingHours} hrs.");
