<?php
/**
 * API: Attendance - Check In
 * POST /api/attendance/checkin.php
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

$input = Validator::getJsonInput();
$notes = trim($input['notes'] ?? '');
$today = date('Y-m-d');
$nowTime = date('H:i:s');
$pdo = Database::getConnection();

// Check if already checked in today
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = :eid AND date = :today LIMIT 1");
$stmt->execute([':eid' => $empId, ':today' => $today]);
$existing = $stmt->fetch();

if ($existing && !empty($existing['check_in'])) {
    Response::error("You have already checked in today at {$existing['check_in']}.", [], 400);
}

// Determine status based on standard start time (09:15 grace limit)
$status = (strtotime($nowTime) > strtotime('09:15:00')) ? 'late' : 'present';

if ($existing) {
    $upd = $pdo->prepare("
        UPDATE attendance
        SET check_in = :time, status = :status, notes = :notes
        WHERE id = :id
    ");
    $upd->execute([
        ':time'   => $nowTime,
        ':status' => $status,
        ':notes'  => $notes,
        ':id'     => $existing['id']
    ]);
    $recordId = $existing['id'];
} else {
    $ins = $pdo->prepare("
        INSERT INTO attendance (employee_id, date, check_in, status, notes)
        VALUES (:eid, :date, :time, :status, :notes)
    ");
    $ins->execute([
        ':eid'    => $empId,
        ':date'   => $today,
        ':time'   => $nowTime,
        ':status' => $status,
        ':notes'  => $notes
    ]);
    $recordId = (int)$pdo->lastInsertId();
}

Audit::log('attendance_checkin', 'attendance', $recordId, "Employee checked in at {$nowTime} (Status: {$status})");

Response::json([
    'check_in' => $nowTime,
    'status'   => $status,
    'date'     => $today
], "Successfully punched in at {$nowTime}. Have a productive day!");
