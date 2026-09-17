<?php
/**
 * API: Leave - Apply for Leave
 * POST /api/leave/apply.php
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
$errors = Validator::validate($input, [
    'leave_type_id' => 'required|numeric',
    'start_date'    => 'required|date',
    'end_date'      => 'required|date',
    'reason'        => 'required|min:5'
]);

if (!empty($errors)) {
    Response::error('Validation failed', $errors, 422);
}

$startDate = $input['start_date'];
$endDate = $input['end_date'];
$leaveTypeId = (int)$input['leave_type_id'];
$reason = trim($input['reason']);

if ($startDate > $endDate) {
    Response::error('End date cannot be prior to start date.', ['end_date' => ['Invalid date range']], 422);
}

$dStart = new DateTime($startDate);
$dEnd   = new DateTime($endDate);
$totalDays = $dStart->diff($dEnd)->days + 1;

$year = (int)$dStart->format('Y');
$pdo = Database::getConnection();

// Check leave balance
$bStmt = $pdo->prepare("
    SELECT * FROM leave_balances
    WHERE employee_id = :eid AND leave_type_id = :ltid AND year = :year
    LIMIT 1
");
$bStmt->execute([':eid' => $empId, ':ltid' => $leaveTypeId, ':year' => $year]);
$balance = $bStmt->fetch();

if (!$balance) {
    Response::error('No leave balance assigned for this category in ' . $year, [], 400);
}

$availableDays = $balance['total_days'] - $balance['used_days'] - $balance['pending_days'];
if ($totalDays > $availableDays) {
    Response::error("Insufficient leave balance. You requested {$totalDays} day(s), but have only {$availableDays} day(s) available.", [
        'total_days' => ["Available balance: {$availableDays}"]
    ], 422);
}

try {
    $pdo->beginTransaction();

    // 1. Insert leave request
    $ins = $pdo->prepare("
        INSERT INTO leave_requests (employee_id, leave_type_id, start_date, end_date, total_days, reason, status)
        VALUES (:eid, :ltid, :sd, :ed, :td, :reason, 'pending')
    ");
    $ins->execute([
        ':eid'    => $empId,
        ':ltid'   => $leaveTypeId,
        ':sd'     => $startDate,
        ':ed'     => $endDate,
        ':td'     => $totalDays,
        ':reason' => $reason
    ]);
    $requestId = (int)$pdo->lastInsertId();

    // 2. Increment pending days in balance
    $updBalance = $pdo->prepare("
        UPDATE leave_balances
        SET pending_days = pending_days + :td
        WHERE id = :id
    ");
    $updBalance->execute([':td' => $totalDays, ':id' => $balance['id']]);

    // 3. Notify manager / HR
    $mgrStmt = $pdo->prepare("SELECT manager_id FROM employees WHERE id = :eid");
    $mgrStmt->execute([':eid' => $empId]);
    $mgrId = $mgrStmt->fetch()['manager_id'] ?? null;

    if ($mgrId) {
        $uStmt = $pdo->prepare("SELECT user_id FROM employees WHERE id = :mid");
        $uStmt->execute([':mid' => $mgrId]);
        $targetUserId = $uStmt->fetch()['user_id'] ?? null;
        if ($targetUserId) {
            $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type, link)
                VALUES (:uid, 'New Leave Application', 'A team member applied for leave awaiting review.', 'warning', '#/leave')
            ")->execute([':uid' => $targetUserId]);
        }
    }

    $pdo->commit();

    Audit::log('leave_applied', 'leave_request', $requestId, "Applied for {$totalDays} day(s) leave ({$startDate} to {$endDate})");

    Response::json([
        'request_id' => $requestId,
        'total_days' => $totalDays,
        'status'     => 'pending'
    ], "Leave request for {$totalDays} day(s) submitted successfully.", 201);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    Response::error('Failed to submit leave request: ' . $e->getMessage(), [], 500);
}
