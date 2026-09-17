<?php
/**
 * API: Documents - Delete
 * POST /api/documents/delete.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$role = Auth::role();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', [], 405);
}

$input = Validator::getJsonInput();
$id = !empty($input['id']) ? (int)$input['id'] : null;

if (!$id) {
    Response::error('Document ID is required', [], 400);
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$doc = $stmt->fetch();

if (!$doc) {
    Response::notFound('Document record not found.');
}

// Only Super Admin, HR Admin, or the user who uploaded can delete
$canDelete = in_array($role, ['super_admin', 'hr_admin'], true) || (Auth::id() === (int)$doc['uploaded_by']);
if (!$canDelete) {
    Response::forbidden('You do not have permission to delete this document.');
}

// Delete file on disk if exists
$fullPath = STORAGE_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $doc['file_path']);
if (file_exists($fullPath) && is_file($fullPath)) {
    @unlink($fullPath);
}

$del = $pdo->prepare("DELETE FROM documents WHERE id = :id");
$del->execute([':id' => $id]);

Audit::log('document_deleted', 'document', $id, "Deleted document '{$doc['title']}'");

Response::json(null, "Document '{$doc['title']}' has been removed.");
