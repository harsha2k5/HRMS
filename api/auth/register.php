<?php
/**
 * API: Authentication - User Registration
 * POST /api/auth/register.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', [], 405);
}

$input = Validator::getJsonInput();
$errors = Validator::validate($input, [
    'username'   => 'required|min:3',
    'email'      => 'required|email',
    'password'   => 'required|min:8',
    'first_name' => 'required',
    'last_name'  => 'required'
]);

if (!empty($errors)) {
    Response::error('Validation failed', $errors, 422);
}

$username = trim($input['username']);
$email = strtolower(trim($input['email']));
$password = $input['password'];
$firstName = trim($input['first_name']);
$lastName = trim($input['last_name']);

try {
    $pdo = Database::getConnection();

    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1");
    $stmt->execute([':u' => $username, ':e' => $email]);
    if ($stmt->fetch()) {
        Response::error('An account with this email or username already exists.', ['email' => ['Already registered']], 409);
    }

    // Default role for self-registration: employee (role_id = 4)
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, role_id, status)
        VALUES (:username, :email, :password_hash, 4, 'active')
    ");
    $stmt->execute([
        ':username'      => $username,
        ':email'         => $email,
        ':password_hash' => $passwordHash
    ]);
    $userId = (int)$pdo->lastInsertId();

    // Generate unique employee code
    $empCode = 'EMP-' . str_pad((string)$userId, 3, '0', STR_PAD_LEFT);

    // Create Employee record
    $empStmt = $pdo->prepare("
        INSERT INTO employees (user_id, employee_code, first_name, last_name, email, joining_date, employment_status)
        VALUES (:user_id, :code, :first_name, :last_name, :email, CURRENT_DATE, 'active')
    ");
    $empStmt->execute([
        ':user_id'    => $userId,
        ':code'       => $empCode,
        ':first_name' => $firstName,
        ':last_name'  => $lastName,
        ':email'      => $email
    ]);
    $employeeId = (int)$pdo->lastInsertId();

    // Create initial leave balances for standard leave types
    $leaveTypes = $pdo->query("SELECT id, max_days_per_year FROM leave_types")->fetchAll();
    $year = (int)date('Y');
    foreach ($leaveTypes as $lt) {
        $lbStmt = $pdo->prepare("
            INSERT INTO leave_balances (employee_id, leave_type_id, year, total_days, used_days, pending_days)
            VALUES (:emp_id, :lt_id, :year, :total, 0, 0)
        ");
        $lbStmt->execute([
            ':emp_id' => $employeeId,
            ':lt_id'  => $lt['id'],
            ':year'   => $year,
            ':total'  => $lt['max_days_per_year']
        ]);
    }

    $pdo->commit();

    Audit::log('user_registered', 'user', $userId, "New user registered: {$email}", $userId);

    Response::json(['user_id' => $userId, 'employee_code' => $empCode], 'Account created successfully! You may now sign in.');
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    Response::error('Failed to create account: ' . $e->getMessage(), [], 500);
}
