<?php
/**
 * API: Settings - Audit Logs
 * GET /api/settings/audit_logs.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

RBAC::requireRoles(['super_admin']);

$pdo = Database::getConnection();
$limit = min(100, max(10, (int)($_GET['limit'] ?? 50)));

$sql = "
    SELECT a.*, u.username, u.email
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT {$limit}
";
$logs = $pdo->query($sql)->fetchAll();

Response::json($logs);
