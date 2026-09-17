<?php
/**
 * API: Employees - View Profile
 * GET /api/employees/view.php?id={id}
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$role = Auth::role();
$pdo = Database::getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : Auth::employeeId();

if (!$id) {
    Response::error('Employee ID is required', [], 400);
}

// RBAC check: Manager can only access team; Employee can only access self
if (!RBAC::canAccessEmployee($id)) {
    Response::forbidden('You are not authorized to view this employee profile.');
}

// 1. Employee Core Details
$stmt = $pdo->prepare("
    SELECT e.*, d.name as department_name, des.title as designation_title,
           m.first_name as manager_first_name, m.last_name as manager_last_name,
           u.username, u.avatar
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN designations des ON e.designation_id = des.id
    LEFT JOIN employees m ON e.manager_id = m.id
    LEFT JOIN users u ON e.user_id = u.id
    WHERE e.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$employee = $stmt->fetch();

if (!$employee) {
    Response::notFound('Employee record not found.');
}

// Hide sensitive financial fields if unauthorized
$canViewFinancials = in_array($role, ['super_admin', 'hr_admin'], true) || (Auth::employeeId() === $id);
if (!$canViewFinancials) {
    unset($employee['basic_salary'], $employee['bank_name'], $employee['bank_account_no']);
}

// 2. Attendance History (Recent 15 days)
$attStmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = :id ORDER BY date DESC LIMIT 15");
$attStmt->execute([':id' => $id]);
$attendance = $attStmt->fetchAll();

// 3. Leave Balances and Recent Requests
$lbStmt = $pdo->prepare("
    SELECT lb.*, lt.name as leave_name, lt.code as leave_code
    FROM leave_balances lb
    JOIN leave_types lt ON lb.leave_type_id = lt.id
    WHERE lb.employee_id = :id AND lb.year = :year
");
$lbStmt->execute([':id' => $id, ':year' => (int)date('Y')]);
$leaveBalances = $lbStmt->fetchAll();

$lrStmt = $pdo->prepare("
    SELECT lr.*, lt.name as leave_name
    FROM leave_requests lr
    JOIN leave_types lt ON lr.leave_type_id = lt.id
    WHERE lr.employee_id = :id
    ORDER BY lr.created_at DESC
    LIMIT 10
");
$lrStmt->execute([':id' => $id]);
$leaveRequests = $lrStmt->fetchAll();

// 4. Payroll History (if allowed)
$payroll = [];
if ($canViewFinancials) {
    $payStmt = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = :id ORDER BY year DESC, month DESC LIMIT 12");
    $payStmt->execute([':id' => $id]);
    $payroll = $payStmt->fetchAll();
}

// 5. Documents
$docStmt = $pdo->prepare("SELECT * FROM documents WHERE employee_id = :id ORDER BY created_at DESC");
$docStmt->execute([':id' => $id]);
$documents = $docStmt->fetchAll();

// 6. Performance Goals & Reviews
$goalStmt = $pdo->prepare("SELECT * FROM performance_goals WHERE employee_id = :id ORDER BY target_date ASC");
$goalStmt->execute([':id' => $id]);
$goals = $goalStmt->fetchAll();

$revStmt = $pdo->prepare("
    SELECT pr.*, u.username as reviewer_username
    FROM performance_reviews pr
    LEFT JOIN users u ON pr.reviewer_id = u.id
    WHERE pr.employee_id = :id
    ORDER BY pr.created_at DESC
");
$revStmt->execute([':id' => $id]);
$reviews = $revStmt->fetchAll();

Response::json([
    'employee'       => $employee,
    'attendance'     => $attendance,
    'leave_balances' => $leaveBalances,
    'leave_requests' => $leaveRequests,
    'payroll'        => $payroll,
    'documents'      => $documents,
    'goals'          => $goals,
    'reviews'        => $reviews
]);
