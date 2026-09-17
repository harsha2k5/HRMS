<?php
/**
 * API: Documents - Secure File Upload
 * POST /api/documents/upload.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', [], 405);
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    Response::error('No file was uploaded or an upload transmission error occurred.', [], 400);
}

$file = $_FILES['file'];
$title = trim($_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME));
$category = trim($_POST['category'] ?? 'employee_documents');
$targetEmpId = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : Auth::employeeId();

if (!$targetEmpId) {
    Response::error('Target employee ID is required.', [], 400);
}

if (!RBAC::canAccessEmployee($targetEmpId)) {
    Response::forbidden('You do not have permission to upload documents for this employee.');
}

// 1. Validate File Size (Max 10MB)
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    Response::error('File size exceeds the 10MB limit.', [], 422);
}

// 2. Validate Extension & MIME
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$disallowedExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'bat', 'cmd', 'vbs', 'js', 'py', 'pl', 'cgi', 'bin', 'jar'];
if (in_array($ext, $disallowedExtensions, true)) {
    Response::error('Executable or script files are strictly prohibited.', [], 403);
}

$allowedMimes = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/webp',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain'
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowedMimes, true)) {
    Response::error('Unsupported file format. Please upload PDF, Word, Excel, or image files.', ['mime' => [$mime]], 422);
}

// 3. Store file safely in storage/uploads/{category}
$destFolder = UPLOADS_PATH . DIRECTORY_SEPARATOR . (in_array($category, ['contracts', 'certificates', 'identity', 'policies']) ? $category : 'general');
if (!is_dir($destFolder)) {
    mkdir($destFolder, 0777, true);
}

$safeFileName = bin2hex(random_bytes(16)) . '.' . $ext;
$destPath = $destFolder . DIRECTORY_SEPARATOR . $safeFileName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    Response::error('Failed to store the uploaded file on the server.', [], 500);
}

$relativeFilePath = 'uploads/' . basename($destFolder) . '/' . $safeFileName;
$pdo = Database::getConnection();

$stmt = $pdo->prepare("
    INSERT INTO documents (employee_id, category, title, file_path, file_size, mime_type, uploaded_by)
    VALUES (:eid, :cat, :title, :path, :size, :mime, :uid)
");
$stmt->execute([
    ':eid'   => $targetEmpId,
    ':cat'   => $category,
    ':title' => $title,
    ':path'  => $relativeFilePath,
    ':size'  => $file['size'],
    ':mime'  => $mime,
    ':uid'   => Auth::id()
]);
$docId = (int)$pdo->lastInsertId();

Audit::log('document_uploaded', 'document', $docId, "Uploaded document '{$title}' ({$file['size']} bytes)");

Response::json([
    'id'        => $docId,
    'title'     => $title,
    'category'  => $category,
    'file_path' => $relativeFilePath,
    'file_size' => $file['size']
], 'Document uploaded successfully.', 201);
