<?php
/**
 * API: Announcements - Create
 * POST /api/announcements/create.php
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
$errors = Validator::validate($input, [
    'title'   => 'required|min:3',
    'content' => 'required|min:5'
]);
if (!empty($errors)) {
    Response::error('Validation failed', $errors, 422);
}

$pdo = Database::getConnection();
$title = trim($input['title']);
$content = trim($input['content']);
$target = $input['target_role'] ?? 'all';
$pinned = !empty($input['is_pinned']) ? 1 : 0;

$stmt = $pdo->prepare("
    INSERT INTO announcements (title, content, target_role, is_pinned, author_id)
    VALUES (:title, :content, :target, :pinned, :author_id)
");
$stmt->execute([
    ':title'     => $title,
    ':content'   => $content,
    ':target'    => $target,
    ':pinned'    => $pinned,
    ':author_id' => Auth::id()
]);
$newId = (int)$pdo->lastInsertId();

// Create broadcast notification for all active users
$usersStmt = $pdo->query("SELECT id FROM users WHERE status = 'active'");
$notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (:uid, :t, :m, 'info', '#/announcements')");
while ($row = $usersStmt->fetch()) {
    $notifStmt->execute([':uid' => $row['id'], ':t' => 'New Announcement', ':m' => $title]);
}

Audit::log('announcement_published', 'announcement', $newId, "Published announcement '{$title}'");

Response::json(['id' => $newId], "Announcement published successfully.", 201);
