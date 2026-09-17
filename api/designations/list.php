<?php
/**
 * API: Designations - List
 * GET /api/designations/list.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

Auth::requireAuth();
$pdo = Database::getConnection();

$deptId = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;

$sql = "
    SELECT des.*, d.name as department_name, count(e.id) as employee_count
    FROM designations des
    JOIN departments d ON des.department_id = d.id
    LEFT JOIN employees e ON des.id = e.designation_id AND e.employment_status = 'active'
";

if ($deptId) {
    $sql .= " WHERE des.department_id = :dept_id";
}

$sql .= " GROUP BY des.id, des.department_id, des.title, des.description, des.min_salary, des.max_salary, des.created_at, d.name ORDER BY d.name ASC, des.title ASC";

$stmt = $pdo->prepare($sql);
if ($deptId) {
    $stmt->execute([':dept_id' => $deptId]);
} else {
    $stmt->execute();
}
$designations = $stmt->fetchAll();

Response::json($designations);
