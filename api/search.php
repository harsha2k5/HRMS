<?php
/**
 * API: Global Search Across Permitted Modules
 * GET /api/search.php?q={term}
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/rbac.php';

Auth::requireAuth();
$role = Auth::role();
$pdo = Database::getConnection();

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    Response::json([
        'employees'     => [],
        'departments'   => [],
        'announcements' => [],
        'jobs'          => []
    ]);
}

$like = "%{$q}%";
$results = [];

// 1. Employees (scoped by RBAC)
$scope = RBAC::getEmployeeScopeCondition('e');
$empSql = "
    SELECT e.id, e.employee_code, e.first_name, e.last_name, e.email, d.name as department_name, des.title as designation_title
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN designations des ON e.designation_id = des.id
    WHERE ({$scope['where']}) AND (e.first_name LIKE :q OR e.last_name LIKE :q OR e.email LIKE :q OR e.employee_code LIKE :q)
    LIMIT 6
";
$empStmt = $pdo->prepare($empSql);
$params = array_merge($scope['params'], [':q' => $like]);
$empStmt->execute($params);
$results['employees'] = $empStmt->fetchAll();

// 2. Departments
$deptStmt = $pdo->prepare("SELECT id, name, code FROM departments WHERE name LIKE :q OR code LIKE :q LIMIT 5");
$deptStmt->execute([':q' => $like]);
$results['departments'] = $deptStmt->fetchAll();

// 3. Announcements
$annStmt = $pdo->prepare("SELECT id, title, created_at FROM announcements WHERE title LIKE :q OR content LIKE :q LIMIT 5");
$annStmt->execute([':q' => $like]);
$results['announcements'] = $annStmt->fetchAll();

// 4. Jobs
$jobStmt = $pdo->prepare("SELECT id, title, job_type FROM job_openings WHERE status = 'open' AND (title LIKE :q OR description LIKE :q) LIMIT 5");
$jobStmt->execute([':q' => $like]);
$results['jobs'] = $jobStmt->fetchAll();

Response::json($results);
