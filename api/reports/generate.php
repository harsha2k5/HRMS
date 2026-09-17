<?php
/**
 * API: Reports - Data Aggregation & CSV Export
 * GET /api/reports/generate.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$role = Auth::role();
$pdo = Database::getConnection();

// Reports access requires privileged role or manager scope
RBAC::requireRoles(['super_admin', 'hr_admin', 'manager']);

$type = trim($_GET['type'] ?? 'employees');
$format = trim($_GET['format'] ?? 'json');
$deptId = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');

$scope = RBAC::getEmployeeScopeCondition('e');
$whereClauses = [$scope['where']];
$params = $scope['params'];

if ($deptId) {
    $whereClauses[] = "e.department_id = :dept_id";
    $params[':dept_id'] = $deptId;
}

$whereSql = implode(' AND ', $whereClauses);
$data = [];
$filename = "hrms_{$type}_report_" . date('Ymd_His') . ".csv";

if ($type === 'employees') {
    $sql = "
        SELECT e.employee_code, e.first_name, e.last_name, e.email, e.phone,
               d.name as department, des.title as designation, e.joining_date,
               e.employment_type, e.employment_status, e.basic_salary
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN designations des ON e.designation_id = des.id
        WHERE {$whereSql}
        ORDER BY e.employee_code ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

} elseif ($type === 'attendance') {
    $attWhere = [$whereSql];
    if ($startDate !== '') {
        $attWhere[] = "a.date >= :sd";
        $params[':sd'] = $startDate;
    }
    if ($endDate !== '') {
        $attWhere[] = "a.date <= :ed";
        $params[':ed'] = $endDate;
    }
    $attWhereSql = implode(' AND ', $attWhere);

    $sql = "
        SELECT a.date, e.employee_code, e.first_name, e.last_name, d.name as department,
               a.check_in, a.check_out, a.working_hours, a.overtime_hours, a.status
        FROM attendance a
        JOIN employees e ON a.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE {$attWhereSql}
        ORDER BY a.date DESC, e.employee_code ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

} elseif ($type === 'leave') {
    $sql = "
        SELECT lr.id, e.employee_code, e.first_name, e.last_name, d.name as department,
               lt.name as leave_type, lr.start_date, lr.end_date, lr.total_days, lr.status, lr.created_at
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        JOIN leave_types lt ON lr.leave_type_id = lt.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE {$whereSql}
        ORDER BY lr.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

} elseif ($type === 'payroll') {
    // Only Super Admin and HR Admin can view full payroll reports
    RBAC::requireRoles(['super_admin', 'hr_admin']);

    $sql = "
        SELECT p.month, p.year, e.employee_code, e.first_name, e.last_name, d.name as department,
               p.basic_salary, p.total_allowances, p.total_deductions, p.net_salary, p.payment_status, p.payment_date
        FROM payroll p
        JOIN employees e ON p.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        ORDER BY p.year DESC, p.month DESC, e.employee_code ASC
    ";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();
}

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    if (!empty($data)) {
        // Output column headers
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    } else {
        fputcsv($output, ['No records matching criteria']);
    }
    fclose($output);
    exit;
}

Response::json([
    'type'        => $type,
    'total_count' => count($data),
    'records'     => $data
]);
