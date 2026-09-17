<?php
/**
 * API: Settings - User Accounts & Roles Management
 * GET /api/settings/users.php
 * POST /api/settings/users.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

RBAC::requireRoles(['super_admin']);

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = "
        SELECT u.id, u.username, u.email, u.role_id, u.status, u.last_login_at, u.created_at,
               r.name as role_name, r.display_name as role_display_name,
               e.first_name, e.last_name, e.employee_code
        FROM users u
        JOIN roles r ON u.role_id = r.id
        LEFT JOIN employees e ON u.id = e.user_id
        ORDER BY u.id ASC
    ";
    $users = $pdo->query($sql)->fetchAll();

    $roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

    Response::json([
        'users' => $users,
        'roles' => $roles
    ]);

} elseif ($method === 'POST') {
    $input = Validator::getJsonInput();
    $id = !empty($input['id']) ? (int)$input['id'] : null;

    if (!$id) {
        Response::error('User ID is required.', [], 400);
    }

    $roleId = !empty($input['role_id']) ? (int)$input['role_id'] : null;
    $status = !empty($input['status']) ? $input['status'] : null;

    // Prevent de-admining oneself by accident
    if ($id === Auth::id() && $status !== 'active') {
        Response::error('You cannot change status on your own session account.', [], 400);
    }

    $updates = [];
    $params = [':id' => $id];

    if ($roleId) {
        $updates[] = 'role_id = :role_id';
        $params[':role_id'] = $roleId;
    }
    if ($status) {
        $updates[] = 'status = :status';
        $params[':status'] = $status;
    }

    if (!empty($updates)) {
        $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        Audit::log('user_account_updated', 'user', $id, "Updated user #{$id} role/status");
    }

    Response::json(null, "User account settings saved successfully.");
} else {
    Response::error('Method not allowed', [], 405);
}
