<?php
/**
 * API: Leave - Balances & Types
 * GET /api/leave/balances.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$pdo = Database::getConnection();

$targetEmpId = !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : Auth::employeeId();
$year = !empty($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

if (!RBAC::canAccessEmployee($targetEmpId)) {
    Response::forbidden('Cannot access leave balances for this employee.');
}

// Fetch all available leave types
$typesStmt = $pdo->query("SELECT * FROM leave_types ORDER BY id ASC");
$leaveTypes = $typesStmt->fetchAll();

// Fetch balances for this employee
$bStmt = $pdo->prepare("
    SELECT lb.*, lt.name as leave_type_name, lt.code as leave_type_code, lt.is_paid
    FROM leave_balances lb
    JOIN leave_types lt ON lb.leave_type_id = lt.id
    WHERE lb.employee_id = :eid AND lb.year = :year
");
$bStmt->execute([':eid' => $targetEmpId, ':year' => $year]);
$balances = $bStmt->fetchAll();

Response::json([
    'year'        => $year,
    'leave_types' => $leaveTypes,
    'balances'    => $balances
]);
