<?php
/**
 * API: Performance - Goals & KPIs
 * GET /api/performance/goals.php
 * POST /api/performance/goals.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $scope = RBAC::getEmployeeScopeCondition('e');
    $where = $scope['where'];
    $params = $scope['params'];

    $sql = "
        SELECT pg.*, e.employee_code, e.first_name, e.last_name,
               d.name as department_name, des.title as designation_title
        FROM performance_goals pg
        JOIN employees e ON pg.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN designations des ON e.designation_id = des.id
        WHERE {$where}
        ORDER BY pg.target_date ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $goals = $stmt->fetchAll();

    Response::json($goals);

} elseif ($method === 'POST') {
    $input = Validator::getJsonInput();
    $id = !empty($input['id']) ? (int)$input['id'] : null;

    if ($id) {
        // Update progress or status
        $progress = min(100, max(0, (int)($input['progress'] ?? 0)));
        $status = ($progress >= 100) ? 'completed' : ($input['status'] ?? 'in_progress');

        $stmt = $pdo->prepare("UPDATE performance_goals SET progress = :prog, status = :status WHERE id = :id");
        $stmt->execute([':prog' => $progress, ':status' => $status, ':id' => $id]);

        Audit::log('goal_progress_updated', 'performance', $id, "Updated goal #{$id} progress to {$progress}%");
        Response::json(null, "Goal progress updated to {$progress}%.");
    } else {
        RBAC::requireRoles(['super_admin', 'hr_admin', 'manager']);
        $errors = Validator::validate($input, [
            'employee_id' => 'required|numeric',
            'title'       => 'required|min:3',
            'start_date'  => 'required|date',
            'target_date' => 'required|date'
        ]);
        if (!empty($errors)) {
            Response::error('Validation failed', $errors, 422);
        }

        $ins = $pdo->prepare("
            INSERT INTO performance_goals (employee_id, title, description, start_date, target_date, progress, status)
            VALUES (:eid, :title, :desc, :sd, :td, :prog, 'in_progress')
        ");
        $ins->execute([
            ':eid'   => (int)$input['employee_id'],
            ':title' => trim($input['title']),
            ':desc'  => trim($input['description'] ?? ''),
            ':sd'    => $input['start_date'],
            ':td'    => $input['target_date'],
            ':prog'  => (int)($input['progress'] ?? 0)
        ]);
        $newId = (int)$pdo->lastInsertId();

        Audit::log('goal_created', 'performance', $newId, "Created performance goal: {$input['title']}");
        Response::json(['id' => $newId], "Performance goal created successfully.", 201);
    }
} else {
    Response::error('Method not allowed', [], 405);
}
