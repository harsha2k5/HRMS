<?php
/**
 * API: Authentication - Change Password
 * POST /api/auth/change_password.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';
require_once __DIR__ . '/../../middleware/auth.php';

Auth::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', [], 405);
}

$input = Validator::getJsonInput();
$errors = Validator::validate($input, [
    'current_password' => 'required',
    'new_password'     => 'required|min:8',
    'confirm_password' => 'required'
]);

if (!empty($errors)) {
    Response::error('Validation failed', $errors, 422);
}

if ($input['new_password'] !== $input['confirm_password']) {
    Response::error('New password and confirmation do not match.', ['confirm_password' => ['Passwords do not match']], 422);
}

$userId = Auth::id();
$pdo = Database::getConnection();

$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user || !password_verify($input['current_password'], $user['password_hash'])) {
    Response::error('Current password is incorrect.', ['current_password' => ['Current password does not match.']], 400);
}

$newHash = password_hash($input['new_password'], PASSWORD_DEFAULT);
$updateStmt = $pdo->prepare("UPDATE users SET password_hash = :hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
$updateStmt->execute([':hash' => $newHash, ':id' => $userId]);

Audit::log('password_changed', 'user', $userId, 'User changed their password', $userId);

Response::json(null, 'Password updated successfully.');
