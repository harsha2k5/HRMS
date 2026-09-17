<?php
/**
 * API: Authentication - Login
 * POST /api/auth/login.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', [], 405);
}

$input = Validator::getJsonInput();
$errors = Validator::validate($input, [
    'login'    => 'required',
    'password' => 'required'
]);

if (!empty($errors)) {
    Response::error('Validation failed', $errors, 422);
}

$login = trim($input['login']);
$password = (string)$input['password'];

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name, r.display_name as role_display_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE (u.email = :login OR u.username = :login)
        LIMIT 1
    ");
    $stmt->execute([':login' => $login]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        Audit::log('failed_login_attempt', 'auth', null, "Failed login attempt for user: {$login}");
        Response::error('Invalid credentials. Please check your email/username and password.', [], 401);
    }

    if ($user['status'] !== 'active') {
        Response::error('Your account is inactive or suspended. Please contact Human Resources.', [], 403);
    }

    Auth::login($user);

    Response::json([
        'user'     => Auth::user(),
        'employee' => Auth::employee()
    ], 'Authentication successful. Welcome back!');
} catch (Exception $e) {
    Response::error('An unexpected authentication error occurred: ' . $e->getMessage(), [], 500);
}
