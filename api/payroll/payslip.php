<?php
/**
 * API: Payroll - Payslip Detail & Printable View
 * GET /api/payroll/payslip.php?id={id}
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$role = Auth::role();
$pdo = Database::getConnection();

$id = !empty($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    Response::error('Payroll ID is required', [], 400);
}

// Fetch payroll record
$stmt = $pdo->prepare("
    SELECT p.*, e.employee_code, e.first_name, e.last_name, e.email, e.joining_date,
           e.bank_name, e.bank_account_no,
           d.name as department_name, des.title as designation_title
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN designations des ON e.designation_id = des.id
    WHERE p.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$payroll = $stmt->fetch();

if (!$payroll) {
    Response::notFound('Payslip not found.');
}

// Security: Employees can only view their own payslip
if (!in_array($role, ['super_admin', 'hr_admin'], true)) {
    if (Auth::employeeId() !== (int)$payroll['employee_id']) {
        Response::forbidden('You are not authorized to view another employee\'s payslip.');
    }
}

// Fetch itemized breakdown
$itemStmt = $pdo->prepare("SELECT * FROM payroll_items WHERE payroll_id = :id ORDER BY item_type ASC, id ASC");
$itemStmt->execute([':id' => $id]);
$items = $itemStmt->fetchAll();

$allowances = [];
$deductions = [];

foreach ($items as $item) {
    if ($item['item_type'] === 'allowance') {
        $allowances[] = $item;
    } else {
        $deductions[] = $item;
    }
}

// Fetch company settings for branding
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM company_settings");
$settings = [];
while ($row = $settingsStmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$periodName = ($monthNames[$payroll['month']] ?? 'Month ' . $payroll['month']) . ' ' . $payroll['year'];

Response::json([
    'payroll'     => $payroll,
    'period'      => $periodName,
    'allowances'  => $allowances,
    'deductions'  => $deductions,
    'company'     => $settings
]);
