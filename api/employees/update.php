<?php
/**
 * API: Employees - Update
 * POST /api/employees/update.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$role = Auth::role();
$input = Validator::getJsonInput();
$id = !empty($input['id']) ? (int)$input['id'] : null;

if (!$id) {
    Response::error('Employee ID is required for update.', [], 400);
}

if (!RBAC::canAccessEmployee($id)) {
    Response::forbidden('You are not authorized to update this profile.');
}

$isPrivileged = in_array($role, ['super_admin', 'hr_admin'], true);
$pdo = Database::getConnection();

// Fetch existing employee
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$existing = $stmt->fetch();

if (!$existing) {
    Response::notFound('Employee not found.');
}

$fieldsToUpdate = [];
$params = [':id' => $id];

// Fields that any authorized user (including employee self-service) can update
if (isset($input['phone'])) {
    $fieldsToUpdate[] = 'phone = :phone';
    $params[':phone'] = trim($input['phone']);
}
if (isset($input['address'])) {
    $fieldsToUpdate[] = 'address = :address';
    $params[':address'] = trim($input['address']);
}
if (isset($input['emergency_contact'])) {
    $fieldsToUpdate[] = 'emergency_contact = :emergency_contact';
    $params[':emergency_contact'] = trim($input['emergency_contact']);
}

// Privileged fields (Super Admin / HR Admin only)
if ($isPrivileged) {
    if (isset($input['first_name'])) {
        $fieldsToUpdate[] = 'first_name = :first_name';
        $params[':first_name'] = trim($input['first_name']);
    }
    if (isset($input['last_name'])) {
        $fieldsToUpdate[] = 'last_name = :last_name';
        $params[':last_name'] = trim($input['last_name']);
    }
    if (isset($input['email'])) {
        $fieldsToUpdate[] = 'email = :email';
        $params[':email'] = strtolower(trim($input['email']));
    }
    if (isset($input['department_id'])) {
        $fieldsToUpdate[] = 'department_id = :department_id';
        $params[':department_id'] = (int)$input['department_id'];
    }
    if (isset($input['designation_id'])) {
        $fieldsToUpdate[] = 'designation_id = :designation_id';
        $params[':designation_id'] = (int)$input['designation_id'];
    }
    if (isset($input['manager_id'])) {
        $fieldsToUpdate[] = 'manager_id = :manager_id';
        $params[':manager_id'] = !empty($input['manager_id']) ? (int)$input['manager_id'] : null;
    }
    if (isset($input['employment_type'])) {
        $fieldsToUpdate[] = 'employment_type = :employment_type';
        $params[':employment_type'] = $input['employment_type'];
    }
    if (isset($input['employment_status'])) {
        $fieldsToUpdate[] = 'employment_status = :employment_status';
        $params[':employment_status'] = $input['employment_status'];
    }
    if (isset($input['basic_salary'])) {
        $fieldsToUpdate[] = 'basic_salary = :basic_salary';
        $params[':basic_salary'] = (float)$input['basic_salary'];
    }
    if (isset($input['bank_name'])) {
        $fieldsToUpdate[] = 'bank_name = :bank_name';
        $params[':bank_name'] = trim($input['bank_name']);
    }
    if (isset($input['bank_account_no'])) {
        $fieldsToUpdate[] = 'bank_account_no = :bank_account_no';
        $params[':bank_account_no'] = trim($input['bank_account_no']);
    }
}

if (empty($fieldsToUpdate)) {
    Response::json(null, 'No changes were submitted.');
}

$fieldsSql = implode(', ', $fieldsToUpdate);
$updateSql = "UPDATE employees SET {$fieldsSql}, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
$stmt = $pdo->prepare($updateSql);
$stmt->execute($params);

Audit::log('employee_updated', 'employee', $id, "Updated fields for employee {$existing['employee_code']}");

Response::json(null, 'Employee record updated successfully.');
