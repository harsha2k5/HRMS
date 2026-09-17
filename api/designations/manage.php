<?php
/**
 * API: Designations - Create, Update, Delete
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
        'department_id' => 'required|numeric',
        'title'         => 'required|min:2'
    ]);
    if (!empty($errors)) {
        Response::error('Validation failed', $errors, 422);
    }

    $deptId = (int)$input['department_id'];
    $title = trim($input['title']);
    $desc = trim($input['description'] ?? '');
    $minSal = !empty($input['min_salary']) ? (float)$input['min_salary'] : 0.00;
    $maxSal = !empty($input['max_salary']) ? (float)$input['max_salary'] : 0.00;

    $stmt = $pdo->prepare("
        INSERT INTO designations (department_id, title, description, min_salary, max_salary)
        VALUES (:dept, :title, :desc, :min, :max)
    ");
    $stmt->execute([
        ':dept'  => $deptId,
        ':title' => $title,
        ':desc'  => $desc,
        ':min'   => $minSal,
        ':max'   => $maxSal
    ]);
    $desigId = (int)$pdo->lastInsertId();

    Audit::log('designation_created', 'designation', $desigId, "Created designation {$title}");
    Response::json(['id' => $desigId], "Designation '{$title}' created successfully.", 201);

} elseif ($action === 'update' && $method === 'POST') {
    $id = !empty($input['id']) ? (int)$input['id'] : null;
    if (!$id) {
        Response::error('Designation ID is required', [], 400);
    }

    $deptId = (int)($input['department_id'] ?? 0);
    $title = trim($input['title'] ?? '');
    $desc = trim($input['description'] ?? '');
    $minSal = (float)($input['min_salary'] ?? 0);
    $maxSal = (float)($input['max_salary'] ?? 0);

    $stmt = $pdo->prepare("
        UPDATE designations
        SET department_id = :dept, title = :title, description = :desc, min_salary = :min, max_salary = :max
        WHERE id = :id
    ");
    $stmt->execute([
        ':dept'  => $deptId,
        ':title' => $title,
        ':desc'  => $desc,
        ':min'   => $minSal,
        ':max'   => $maxSal,
        ':id'    => $id
    ]);

    Audit::log('designation_updated', 'designation', $id, "Updated designation {$title}");
    Response::json(null, "Designation updated successfully.");

} elseif ($action === 'delete' && $method === 'POST') {
    $id = !empty($input['id']) ? (int)$input['id'] : null;
    if (!$id) {
        Response::error('Designation ID is required', [], 400);
    }

    $stmt = $pdo->prepare("SELECT count(*) as c FROM employees WHERE designation_id = :id AND employment_status = 'active'");
    $stmt->execute([':id' => $id]);
    $c = (int)($stmt->fetch()['c'] ?? 0);

    if ($c > 0) {
        Response::error("Cannot delete designation because {$c} active employee(s) hold this title.", [], 400);
    }

    $del = $pdo->prepare("DELETE FROM designations WHERE id = :id");
    $del->execute([':id' => $id]);

    Audit::log('designation_deleted', 'designation', $id, "Deleted designation #{$id}");
    Response::json(null, "Designation deleted successfully.");
} else {
    Response::error('Invalid action or method', [], 405);
}
