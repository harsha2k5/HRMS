<?php
/**
 * API: Departments - Create, Update, Delete
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

RBAC::requireRoles(['super_admin', 'hr_admin']);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'create';
$input = Validator::getJsonInput();
$pdo = Database::getConnection();

if ($action === 'create' && $method === 'POST') {
    $errors = Validator::validate($input, [
        'name' => 'required|min:2',
        'code' => 'required|min:2'
    ]);
    if (!empty($errors)) {
        Response::error('Validation failed', $errors, 422);
    }

    $name = trim($input['name']);
    $code = strtoupper(trim($input['code']));
    $desc = trim($input['description'] ?? '');
    $headId = !empty($input['head_id']) ? (int)$input['head_id'] : null;

    $stmt = $pdo->prepare("INSERT INTO departments (name, code, description, head_id, status) VALUES (:name, :code, :desc, :head, 'active')");
    $stmt->execute([':name' => $name, ':code' => $code, ':desc' => $desc, ':head' => $headId]);
    $deptId = (int)$pdo->lastInsertId();

    Audit::log('department_created', 'department', $deptId, "Created department {$name} ({$code})");
    Response::json(['id' => $deptId], "Department '{$name}' created successfully.", 201);

} elseif ($action === 'update' && $method === 'POST') {
    $id = !empty($input['id']) ? (int)$input['id'] : null;
    if (!$id) {
        Response::error('Department ID is required', [], 400);
    }

    $name = trim($input['name'] ?? '');
    $code = strtoupper(trim($input['code'] ?? ''));
    $desc = trim($input['description'] ?? '');
    $headId = !empty($input['head_id']) ? (int)$input['head_id'] : null;
    $status = $input['status'] ?? 'active';

    $stmt = $pdo->prepare("
        UPDATE departments
        SET name = :name, code = :code, description = :desc, head_id = :head, status = :status
        WHERE id = :id
    ");
    $stmt->execute([
        ':name'   => $name,
        ':code'   => $code,
        ':desc'   => $desc,
        ':head'   => $headId,
        ':status' => $status,
        ':id'     => $id
    ]);

    Audit::log('department_updated', 'department', $id, "Updated department {$name}");
    Response::json(null, "Department updated successfully.");

} elseif ($action === 'delete' && $method === 'POST') {
    $id = !empty($input['id']) ? (int)$input['id'] : null;
    if (!$id) {
        Response::error('Department ID is required', [], 400);
    }

    // Check if department has active employees
    $stmt = $pdo->prepare("SELECT count(*) as c FROM employees WHERE department_id = :id AND employment_status = 'active'");
    $stmt->execute([':id' => $id]);
    $c = (int)($stmt->fetch()['c'] ?? 0);

    if ($c > 0) {
        Response::error("Cannot delete department because it still has {$c} active employee(s) assigned. Reassign them first.", [], 400);
    }

    $del = $pdo->prepare("DELETE FROM departments WHERE id = :id");
    $del->execute([':id' => $id]);

    Audit::log('department_deleted', 'department', $id, "Deleted department #{$id}");
    Response::json(null, "Department deleted successfully.");
} else {
    Response::error('Invalid action or method', [], 405);
}
