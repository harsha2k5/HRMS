<?php
/**
 * API: Attendance - List / Monthly View
 * GET /api/attendance/list.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$role = Auth::role();
$pdo = Database::getConnection();

// Scope restriction
$scope = RBAC::getEmployeeScopeCondition('e');
$whereClauses = [$scope['where']];
$params = $scope['params'];

// Optional filters
$month = !empty($_GET['month']) ? (int)$_GET['month'] : null;
$year  = !empty($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$specificEmpId = !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
$date = trim($_GET['date'] ?? '');

if ($specificEmpId) {
    if (!RBAC::canAccessEmployee($specificEmpId)) {
        Response::forbidden('Cannot view attendance for this employee.');
    }
    $whereClauses[] = "a.employee_id = :filter_eid";
    $params[':filter_eid'] = $specificEmpId;
}

if ($date !== '') {
    $whereClauses[] = "a.date = :filter_date";
    $params[':filter_date'] = $date;
} elseif ($month) {
    $monthStr = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
    $whereClauses[] = "a.date LIKE :filter_my";
    $params[':filter_my'] = "{$year}-{$monthStr}%";
}

$whereSql = implode(' AND ', $whereClauses);

$sql = "
    SELECT a.*, e.employee_code, e.first_name, e.last_name,
           d.name as department_name, des.title as designation_title
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN designations des ON e.designation_id = des.id
    WHERE {$whereSql}
    ORDER BY a.date DESC, a.check_in ASC
    LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendance = $stmt->fetchAll();

Response::json($attendance);
