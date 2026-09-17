<?php
/**
 * API: Employees - Deactivate / Delete
 * POST /api/employees/delete.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

RBAC::requireRoles(['super_admin', 'hr_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', [], 405);
}

$input = Validator::getJsonInput();
$id = !empty($input['id']) ? (int)$input['id'] : null;

if (!$id) {
    Response::error('Employee ID is required.', [], 400);
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare("SELECT e.*, u.id as uid FROM employees e JOIN users u ON e.user_id = u.id WHERE e.id = :id");
$stmt->execute([':id' => $id]);
$emp = $stmt->fetch();

if (!$emp) {
    Response::notFound('Employee record not found.');
}

// Cannot deactivate self
if ($emp['uid'] === Auth::id()) {
    Response::error('You cannot deactivate your own administrative account.', [], 400);
}

$pdo->beginTransaction();

// Set employee status to terminated
$updEmp = $pdo->prepare("UPDATE employees SET employment_status = 'terminated', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
$updEmp->execute([':id' => $id]);

// Suspend user account
$updUser = $pdo->prepare("UPDATE users SET status = 'suspended', updated_at = CURRENT_TIMESTAMP WHERE id = :uid");
$updUser->execute([':uid' => $emp['uid']]);

$pdo->commit();

Audit::log('employee_deactivated', 'employee', $id, "Deactivated employee {$emp['employee_code']}");

Response::json(null, "Employee {$emp['first_name']} {$emp['last_name']} has been deactivated.");
