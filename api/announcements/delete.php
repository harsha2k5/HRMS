<?php
/**
 * API: Announcements - Delete
 * POST /api/announcements/delete.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

RBAC::requireRoles(['super_admin', 'hr_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', [], 405);
}

$input = Validator::getJsonInput();
$id = !empty($input['id']) ? (int)$input['id'] : null;

if (!$id) {
    Response::error('Announcement ID is required.', [], 400);
}

$pdo = Database::getConnection();
$del = $pdo->prepare("DELETE FROM announcements WHERE id = :id");
$del->execute([':id' => $id]);

Audit::log('announcement_deleted', 'announcement', $id, "Deleted announcement #{$id}");

Response::json(null, "Announcement removed successfully.");
