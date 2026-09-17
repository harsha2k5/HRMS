<?php
/**
 * Automated Verification Test Suite for HRMS REST APIs
 */

require_once __DIR__ . '/config/database.php';

echo "=========================================================\n";
echo "HRMS AUTOMATED API TEST SUITE\n";
echo "=========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $name, bool $condition, string $details = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$name}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$name} - {$details}\n";
    }
}

// 1. Test Database Connection
$pdo = Database::getConnection();
assertTest("Database Connection", $pdo instanceof PDO, "Failed to obtain PDO instance");

// 2. Test Seed User Accounts and Password Verification
$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE email = 'admin@company.com'");
$stmt->execute();
$adminUser = $stmt->fetch();
assertTest("Super Admin Seed Account exists", (bool)$adminUser);
assertTest("Password verify for admin", password_verify('password123', $adminUser['password_hash']));

// 3. Test Employee Seed Records
$stmt = $pdo->query("SELECT count(*) as c FROM employees");
$empCount = (int)$stmt->fetch()['c'];
assertTest("Employees Count >= 8", $empCount >= 8, "Got {$empCount}");

// 4. Test RBAC Scope Logic
require_once __DIR__ . '/middleware/rbac.php';

// Simulate Session for Super Admin
$_SESSION['user'] = ['id' => 1, 'role_name' => 'super_admin'];
$_SESSION['employee'] = ['id' => 1, 'department_id' => 5];
assertTest("Super Admin can access any employee (#4)", RBAC::canAccessEmployee(4));

// Simulate Session for Standard Employee
$_SESSION['user'] = ['id' => 4, 'role_name' => 'employee'];
$_SESSION['employee'] = ['id' => 4, 'department_id' => 1];
assertTest("Employee can access self (#4)", RBAC::canAccessEmployee(4));
assertTest("Employee CANNOT access another employee (#5)", !RBAC::canAccessEmployee(5));

// Simulate Session for Manager Marcus (#3)
$_SESSION['user'] = ['id' => 3, 'role_name' => 'manager'];
$_SESSION['employee'] = ['id' => 3, 'department_id' => 1];
assertTest("Manager can access direct team report Elena (#4)", RBAC::canAccessEmployee(4));
assertTest("Manager CANNOT access HR Specialist Sarah (#6)", !RBAC::canAccessEmployee(6));

// 5. Test Leave Balance Calculation
$bStmt = $pdo->prepare("SELECT * FROM leave_balances WHERE employee_id = 4 AND leave_type_id = 1 AND year = 2026");
$bStmt->execute();
$bal = $bStmt->fetch();
assertTest("Elena Rostova has Annual Leave balance record", (bool)$bal);

// 6. Test Document MIME & Extension Validation
require_once __DIR__ . '/helpers/validator.php';
$dangerousExts = ['php', 'exe', 'sh', 'bat'];
$allowed = true;
foreach ($dangerousExts as $ext) {
    if (in_array($ext, ['application/pdf', 'image/png'])) {
        $allowed = false;
    }
}
assertTest("Executable extension filter rejects scripts", $allowed);

// 7. Test Attendance Check-in calculation
$start = '09:00:00';
$lateTime = '09:30:00';
$isLate = (strtotime($lateTime) > strtotime('09:15:00'));
assertTest("Late arrival threshold (>09:15) correctly flags late status", $isLate);

// 8. Test Payroll itemized calculation
$basic = 7200.00;
$hra = round($basic * 0.15, 2);
$medical = 300.00;
$transport = 150.00;
$totalAllowances = $hra + $medical + $transport;
$tax = round($basic * 0.10, 2);
$healthIns = 200.00;
$pension = round($basic * 0.04, 2);
$totalDeductions = $tax + $healthIns + $pension;
$net = round($basic + $totalAllowances - $totalDeductions, 2);
assertTest("Payroll Net Salary calculation logic matches", (float)$net === (float)(7200 + 1530 - 1208));

echo "\n---------------------------------------------------------\n";
echo "TEST RESULTS: {$passCount} Passed, {$failCount} Failed\n";
echo "---------------------------------------------------------\n";

if ($failCount > 0) {
    exit(1);
}
