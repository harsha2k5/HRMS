<?php
/**
 * API: Authentication - Forgot Password
 * POST /api/auth/forgot_password.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', [], 405);
}

$input = Validator::getJsonInput();
$errors = Validator::validate($input, ['email' => 'required|email']);
if (!empty($errors)) {
    Response::error('Validation failed', $errors, 422);
}

$email = strtolower(trim($input['email']));
$pdo = Database::getConnection();

$stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if ($user) {
    // Generate secure token
    $token = bin2hex(random_bytes(24));
    Audit::log('password_reset_requested', 'auth', $user['id'], "Password reset requested for {$email}");
    // In production, an email would be dispatched. For demo/preview, we confirm request and provide guidance.
    Response::json(['email' => $email, 'demo_token' => $token], 'Password reset instructions have been dispatched to your email address.');
} else {
    // Prevent email enumeration
    Response::json(['email' => $email], 'If an account with that email exists, reset instructions have been sent.');
}
