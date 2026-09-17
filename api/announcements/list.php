<?php
/**
 * API: Announcements - List
 * GET /api/announcements/list.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

Auth::requireAuth();
$pdo = Database::getConnection();

$sql = "
    SELECT a.*, u.username as author_name
    FROM announcements a
    JOIN users u ON a.author_id = u.id
    ORDER BY a.is_pinned DESC, a.created_at DESC
";
$announcements = $pdo->query($sql)->fetchAll();

Response::json($announcements);
