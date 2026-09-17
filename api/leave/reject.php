<?php
/**
 * API: Leave - Reject Leave Request
 * POST /api/leave/reject.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

RBAC::requireRoles(['super_admin', 'hr_admin', 'manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', [], 405);
}

$input = Validator::getJsonInput();
$requestId = !empty($input['request_id']) ? (int)$input['request_id'] : null;
$comment = trim($input['comment'] ?? 'Rejected');

if (!$requestId) {
    Response::error('Request ID is required.', [], 400);
}

$pdo = Database::getConnection();

// Fetch request
$stmt = $pdo->prepare("
    SELECT lr.*, e.user_id as employee_user_id, e.manager_id, e.department_id, e.first_name, e.last_name
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    WHERE lr.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $requestId]);
$leaveReq = $stmt->fetch();

if (!$leaveReq) {
    Response::notFound('Leave request not found.');
}

if ($leaveReq['status'] !== 'pending') {
    Response::error("This leave request is already {$leaveReq['status']}.", [], 400);
}

// Check authorization if manager
$role = Auth::role();
$currentEmp = Auth::employee();
if ($role === 'manager') {
    if ($currentEmp['id'] !== $leaveReq['manager_id'] && $currentEmp['department_id'] !== $leaveReq['department_id']) {
        Response::forbidden('You can only reject leave for your own team members.');
    }
}

try {
    $pdo->beginTransaction();

    $reviewerId = Auth::id();
    $year = (int)date('Y', strtotime($leaveReq['start_date']));

    // 1. Update request status to rejected
    if (Database::getDriver() === 'mysql') {
        $updReq = $pdo->prepare("
            UPDATE leave_requests
            SET status = 'rejected', manager_comment = :comment, reviewed_by = :rev_id, reviewed_at = NOW()
            WHERE id = :id
        ");
    } else {
        $updReq = $pdo->prepare("
            UPDATE leave_requests
            SET status = 'rejected', manager_comment = :comment, reviewed_by = :rev_id, reviewed_at = datetime('now')
            WHERE id = :id
        ");
    }
    $updReq->execute([
        ':comment' => $comment,
        ':rev_id'  => $reviewerId,
        ':id'      => $requestId
    ]);

    // 2. Release pending days from balance
    $updBal = $pdo->prepare("
        UPDATE leave_balances
        SET pending_days = max(0, pending_days - :td)
        WHERE employee_id = :eid AND leave_type_id = :ltid AND year = :year
    ");
    $updBal->execute([
        ':td'   => $leaveReq['total_days'],
        ':eid'  => $leaveReq['employee_id'],
        ':ltid' => $leaveReq['leave_type_id'],
        ':year' => $year
    ]);

    // 3. Notify employee
    $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type, link)
        VALUES (:uid, 'Leave Request Rejected', :msg, 'danger', '#/leave')
    ")->execute([
        ':uid' => $leaveReq['employee_user_id'],
        ':msg' => "Your leave request for {$leaveReq['start_date']} was rejected. Reason: {$comment}"
    ]);

    $pdo->commit();

    Audit::log('leave_rejected', 'leave_request', $requestId, "Rejected leave for {$leaveReq['first_name']} {$leaveReq['last_name']}: {$comment}");

    Response::json(null, "Leave request #{$requestId} has been rejected.");
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    Response::error('Failed to reject leave request: ' . $e->getMessage(), [], 500);
}
