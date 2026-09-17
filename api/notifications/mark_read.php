<?php
/**
 * API: Notifications - Mark Read
 * POST /api/notifications/mark_read.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/auth.php';

Auth::requireAuth();
$userId = Auth::id();
$input = Validator::getJsonInput();
$id = !empty($input['id']) ? (int)$input['id'] : null;

$pdo = Database::getConnection();

if ($id) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => $id, ':uid' => $userId]);
} else {
    // Mark all as read for this user
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid");
    $stmt->execute([':uid' => $userId]);
}

Response::json(null, "Notifications marked as read.");
