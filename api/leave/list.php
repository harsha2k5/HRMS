<?php
/**
 * API: Leave - Requests List
 * GET /api/leave/list.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$pdo = Database::getConnection();

$status = trim($_GET['status'] ?? '');
$scope = RBAC::getEmployeeScopeCondition('e');

$whereClauses = [$scope['where']];
$params = $scope['params'];

if ($status !== '') {
    $whereClauses[] = "lr.status = :status";
    $params[':status'] = $status;
}

$whereSql = implode(' AND ', $whereClauses);

$sql = "
    SELECT lr.*, lt.name as leave_type_name, lt.code as leave_type_code, lt.is_paid,
           e.employee_code, e.first_name, e.last_name,
           d.name as department_name, des.title as designation_title,
           u.username as reviewer_name
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    JOIN leave_types lt ON lr.leave_type_id = lt.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN designations des ON e.designation_id = des.id
    LEFT JOIN users u ON lr.reviewed_by = u.id
    WHERE {$whereSql}
    ORDER BY CASE WHEN lr.status = 'pending' THEN 1 ELSE 2 END, lr.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

Response::json($requests);
