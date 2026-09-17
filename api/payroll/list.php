<?php
/**
 * API: Payroll - List
 * GET /api/payroll/list.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$role = Auth::role();
$pdo = Database::getConnection();

$month = !empty($_GET['month']) ? (int)$_GET['month'] : null;
$year  = !empty($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$status = trim($_GET['status'] ?? '');

$scope = RBAC::getEmployeeScopeCondition('e');
$whereClauses = [$scope['where']];
$params = $scope['params'];

if ($month) {
    $whereClauses[] = "p.month = :month";
    $params[':month'] = $month;
}

if ($year) {
    $whereClauses[] = "p.year = :year";
    $params[':year'] = $year;
}

if ($status !== '') {
    $whereClauses[] = "p.payment_status = :status";
    $params[':status'] = $status;
}

$whereSql = implode(' AND ', $whereClauses);

$sql = "
    SELECT p.*, e.employee_code, e.first_name, e.last_name, e.bank_name, e.bank_account_no,
           d.name as department_name, des.title as designation_title
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN designations des ON e.designation_id = des.id
    WHERE {$whereSql}
    ORDER BY p.year DESC, p.month DESC, e.id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payrolls = $stmt->fetchAll();

// Calculate total payout for this view
$totalPayout = 0;
foreach ($payrolls as $pay) {
    $totalPayout += (float)$pay['net_salary'];
}

Response::json($payrolls, 'Payroll records retrieved', 200, [
    'total_count'  => count($payrolls),
    'total_payout' => $totalPayout
]);
