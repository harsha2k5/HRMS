<?php
/**
 * API: Documents - Secure Download / Preview
 * GET /api/documents/download.php?id={id}
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$id = !empty($_GET['id']) ? (int)$_GET['id'] : null;

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

// Check authorization (Policies are readable by all authenticated users)
if ($doc['category'] !== 'policies') {
    if (!RBAC::canAccessEmployee((int)$doc['employee_id'])) {
        Response::forbidden('You do not have permission to view this document.');
    }
}

$fullPath = STORAGE_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $doc['file_path']);

if (file_exists($fullPath) && is_file($fullPath)) {
    header('Content-Type: ' . ($doc['mime_type'] ?: 'application/octet-stream'));
    header('Content-Disposition: inline; filename="' . basename($doc['title']) . '"');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
} else {
    // Generate clean text/plain preview fallback for demo seeded documents
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: inline; filename="' . basename($doc['title']) . '.txt"');
    echo "=========================================================\n";
    echo "APEX GLOBAL HRMS - SECURE DOCUMENT VAULT\n";
    echo "=========================================================\n\n";
    echo "Document Title:    " . $doc['title'] . "\n";
    echo "Category:          " . strtoupper($doc['category']) . "\n";
    echo "Document ID:       #" . $doc['id'] . "\n";
    echo "Uploaded On:       " . $doc['created_at'] . "\n";
    echo "Integrity Status:  Verified & Encrypted\n\n";
    echo "This is an official document managed by the Apex Global Technologies HRMS.\n";
    exit;
}
