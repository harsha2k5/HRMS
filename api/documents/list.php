<?php
/**
 * API: Documents - List
 * GET /api/documents/list.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$role = Auth::role();
$pdo = Database::getConnection();

$category = trim($_GET['category'] ?? '');
$scope = RBAC::getEmployeeScopeCondition('e');

$whereClauses = [$scope['where']];
$params = $scope['params'];

// Policies are visible to all employees
if ($category === 'policies') {
    $whereClauses = ["doc.category = 'policies'"];
    $params = [];
} elseif ($category !== '') {
    $whereClauses[] = "doc.category = :cat";
    $params[':cat'] = $category;
}

$whereSql = implode(' AND ', $whereClauses);

$sql = "
    SELECT doc.*, e.employee_code, e.first_name, e.last_name, u.username as uploader_username
    FROM documents doc
    JOIN employees e ON doc.employee_id = e.id
    JOIN users u ON doc.uploaded_by = u.id
    WHERE {$whereSql}
    ORDER BY doc.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll();

Response::json($documents);
