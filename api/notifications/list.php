<?php
/**
 * API: Notifications - List
 * GET /api/notifications/list.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

Auth::requireAuth();
$userId = Auth::id();
$pdo = Database::getConnection();

$stmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = :uid
    ORDER BY is_read ASC, created_at DESC
    LIMIT 30
");
$stmt->execute([':uid' => $userId]);
$notifications = $stmt->fetchAll();

$countStmt = $pdo->prepare("SELECT count(*) as c FROM notifications WHERE user_id = :uid AND is_read = 0");
$countStmt->execute([':uid' => $userId]);
$unreadCount = (int)($countStmt->fetch()['c'] ?? 0);

Response::json([
    'notifications' => $notifications,
    'unread_count'  => $unreadCount
]);
