<?php
/**
 * API: Employees - List
 * GET /api/employees/list.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$role = Auth::role();
$pdo = Database::getConnection();

// Query parameters
$search = trim($_GET['q'] ?? '');
$departmentId = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
$status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(5, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

// Scope condition based on current user role
$scope = RBAC::getEmployeeScopeCondition('e');
$whereClauses = [$scope['where']];
$params = $scope['params'];

if ($search !== '') {
    $whereClauses[] = "(e.first_name LIKE :search OR e.last_name LIKE :search OR e.email LIKE :search OR e.employee_code LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($departmentId !== null) {
    $whereClauses[] = "e.department_id = :dept_id";
    $params[':dept_id'] = $departmentId;
}

if ($status !== '') {
    $whereClauses[] = "e.employment_status = :status";
    $params[':status'] = $status;
}

$whereSql = implode(' AND ', $whereClauses);

// Count total
$countStmt = $pdo->prepare("SELECT count(e.id) as total FROM employees e WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int)($countStmt->fetch()['total'] ?? 0);

// Fetch paginated results
$sql = "
    SELECT e.id, e.user_id, e.employee_code, e.first_name, e.last_name, e.email, e.phone,
           e.gender, e.joining_date, e.employment_type, e.employment_status,
           d.name as department_name, des.title as designation_title,
           m.first_name as manager_first_name, m.last_name as manager_last_name
           " . (in_array($role, ['super_admin', 'hr_admin'], true) ? ", e.basic_salary, e.bank_name, e.bank_account_no" : "") . "
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN designations des ON e.designation_id = des.id
    LEFT JOIN employees m ON e.manager_id = m.id
    WHERE {$whereSql}
    ORDER BY e.id ASC
    LIMIT {$limit} OFFSET {$offset}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

Response::json($employees, 'Employees retrieved successfully', 200, [
    'page'       => $page,
    'limit'      => $limit,
    'total'      => $total,
    'total_pages'=> ceil($total / $limit)
]);
