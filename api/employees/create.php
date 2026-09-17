<?php
/**
 * API: Employees - Create
 * POST /api/employees/create.php
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
    'first_name'        => 'required',
    'last_name'         => 'required',
    'email'             => 'required|email',
    'department_id'     => 'required|numeric',
    'designation_id'    => 'required|numeric',
    'joining_date'      => 'required|date',
    'basic_salary'      => 'required|numeric'
]);

if (!empty($errors)) {
    Response::error('Validation failed', $errors, 422);
}

$pdo = Database::getConnection();

// Check if email exists
$checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :e LIMIT 1");
$checkStmt->execute([':e' => strtolower(trim($input['email']))]);
if ($checkStmt->fetch()) {
    Response::error('An account with this email already exists.', ['email' => ['Already registered']], 409);
}

try {
    $pdo->beginTransaction();

    // 1. Create User account
    $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $input['first_name'] . '.' . $input['last_name']));
    // Ensure unique username
    $uStmt = $pdo->prepare("SELECT id FROM users WHERE username = :u");
    $uStmt->execute([':u' => $username]);
    if ($uStmt->fetch()) {
        $username .= rand(10, 99);
    }

    $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
    $roleId = !empty($input['role_id']) ? (int)$input['role_id'] : 4; // default employee

    $userStmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, role_id, status)
        VALUES (:username, :email, :password_hash, :role_id, 'active')
    ");
    $userStmt->execute([
        ':username'      => $username,
        ':email'         => strtolower(trim($input['email'])),
        ':password_hash' => $passwordHash,
        ':role_id'       => $roleId
    ]);
    $userId = (int)$pdo->lastInsertId();

    // 2. Generate Employee Code
    $codeStmt = $pdo->query("SELECT max(id) as max_id FROM employees");
    $nextId = (int)($codeStmt->fetch()['max_id'] ?? 0) + 1;
    $empCode = 'EMP-' . str_pad((string)$nextId, 3, '0', STR_PAD_LEFT);

    // 3. Create Employee Record
    $empStmt = $pdo->prepare("
        INSERT INTO employees (
            user_id, employee_code, first_name, last_name, email, phone,
            date_of_birth, gender, address, emergency_contact,
            department_id, designation_id, manager_id, joining_date,
            employment_type, employment_status, basic_salary,
            bank_name, bank_account_no
        ) VALUES (
            :user_id, :code, :first_name, :last_name, :email, :phone,
            :date_of_birth, :gender, :address, :emergency_contact,
            :department_id, :designation_id, :manager_id, :joining_date,
            :employment_type, 'active', :basic_salary,
            :bank_name, :bank_account_no
        )
    ");

    $empStmt->execute([
        ':user_id'           => $userId,
        ':code'              => $empCode,
        ':first_name'        => trim($input['first_name']),
        ':last_name'         => trim($input['last_name']),
        ':email'             => strtolower(trim($input['email'])),
        ':phone'             => trim($input['phone'] ?? ''),
        ':date_of_birth'     => !empty($input['date_of_birth']) ? $input['date_of_birth'] : null,
        ':gender'            => $input['gender'] ?? 'male',
        ':address'           => trim($input['address'] ?? ''),
        ':emergency_contact' => trim($input['emergency_contact'] ?? ''),
        ':department_id'     => (int)$input['department_id'],
        ':designation_id'    => (int)$input['designation_id'],
        ':manager_id'        => !empty($input['manager_id']) ? (int)$input['manager_id'] : null,
        ':joining_date'      => $input['joining_date'],
        ':employment_type'   => $input['employment_type'] ?? 'full_time',
        ':basic_salary'      => (float)$input['basic_salary'],
        ':bank_name'         => trim($input['bank_name'] ?? ''),
        ':bank_account_no'   => trim($input['bank_account_no'] ?? '')
    ]);
    $employeeId = (int)$pdo->lastInsertId();

    // 4. Seed Leave Balances for the current year
    $leaveTypes = $pdo->query("SELECT id, max_days_per_year FROM leave_types")->fetchAll();
    $year = (int)date('Y');
    foreach ($leaveTypes as $lt) {
        $lbStmt = $pdo->prepare("
            INSERT INTO leave_balances (employee_id, leave_type_id, year, total_days, used_days, pending_days)
            VALUES (:eid, :ltid, :year, :total, 0, 0)
        ");
        $lbStmt->execute([
            ':eid'   => $employeeId,
            ':ltid'  => $lt['id'],
            ':year'  => $year,
            ':total' => $lt['max_days_per_year']
        ]);
    }

    $pdo->commit();

    Audit::log('employee_created', 'employee', $employeeId, "Created employee {$empCode} ({$input['first_name']} {$input['last_name']})");

    Response::json([
        'employee_id'   => $employeeId,
        'employee_code' => $empCode,
        'username'      => $username
    ], 'Employee successfully registered.', 201);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    Response::error('Failed to create employee: ' . $e->getMessage(), [], 500);
}
