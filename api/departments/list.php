<?php
/**
 * API: Departments - List
 * GET /api/departments/list.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

Auth::requireAuth();
$pdo = Database::getConnection();

$sql = "
    SELECT d.*, count(e.id) as employee_count,
           h.first_name as head_first_name, h.last_name as head_last_name, h.email as head_email
    FROM departments d
    LEFT JOIN employees e ON d.id = e.department_id AND e.employment_status = 'active'
    LEFT JOIN employees h ON d.head_id = h.id
    GROUP BY d.id, d.name, d.code, d.description, d.head_id, d.status, d.created_at, h.first_name, h.last_name, h.email
    ORDER BY d.name ASC
";

$stmt = $pdo->query($sql);
$departments = $stmt->fetchAll();

Response::json($departments);
