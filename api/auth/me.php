<?php
/**
 * API: Authentication - Current User Profile
 * GET /api/auth/me.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

if (!Auth::check()) {
    Response::unauthorized();
}

$user = Auth::user();
$pdo = Database::getConnection();

// Refresh employee record
$stmt = $pdo->prepare("
    SELECT e.*, d.name as department_name, des.title as designation_title,
           m.first_name as manager_first_name, m.last_name as manager_last_name
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN designations des ON e.designation_id = des.id
    LEFT JOIN employees m ON e.manager_id = m.id
    WHERE e.user_id = :user_id
    LIMIT 1
");
$stmt->execute([':user_id' => $user['id']]);
$employee = $stmt->fetch();
$_SESSION['employee'] = $employee ?: null;

// Get unread notifications count
$stmt = $pdo->prepare("SELECT count(*) as c FROM notifications WHERE user_id = :user_id AND is_read = 0");
$stmt->execute([':user_id' => $user['id']]);
$unreadNotifs = (int)($stmt->fetch()['c'] ?? 0);

// Get role permissions list
$stmt = $pdo->prepare("
    SELECT p.module, p.action
    FROM permissions p
    JOIN role_permissions rp ON p.id = rp.permission_id
    WHERE rp.role_id = :role_id
");
$stmt->execute([':role_id' => $user['role_id']]);
$permissions = $stmt->fetchAll();

Response::json([
    'user'                 => $user,
    'employee'             => $employee,
    'unread_notifications' => $unreadNotifs,
    'permissions'          => $permissions
]);
