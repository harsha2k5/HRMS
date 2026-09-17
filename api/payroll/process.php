<?php
/**
 * API: Payroll - Process Monthly Payroll
 * POST /api/payroll/process.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

// Strict RBAC: Employees and Managers CANNOT run payroll
RBAC::requireRoles(['super_admin', 'hr_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', [], 405);
}

$input = Validator::getJsonInput();
$errors = Validator::validate($input, [
    'month' => 'required|numeric',
    'year'  => 'required|numeric'
]);

if (!empty($errors)) {
    Response::error('Validation failed', $errors, 422);
}

$month = (int)$input['month'];
$year  = (int)$input['year'];

if ($month < 1 || $month > 12) {
    Response::error('Invalid month. Must be between 1 and 12.', [], 422);
}

$pdo = Database::getConnection();

try {
    $pdo->beginTransaction();

    // Fetch active employees
    $empStmt = $pdo->query("SELECT id, basic_salary, first_name, last_name FROM employees WHERE employment_status = 'active'");
    $employees = $empStmt->fetchAll();

    if (empty($employees)) {
        Response::error('No active employees found to process payroll for.', [], 400);
    }

    $processedCount = 0;
    $totalBatchDisbursed = 0;

    foreach ($employees as $emp) {
        $basic = (float)$emp['basic_salary'];
        if ($basic <= 0) {
            $basic = 5000.00; // default baseline if not set
        }

        // Standard statutory components
        $hra = round($basic * 0.15, 2);      // 15% Housing Allowance
        $medical = 300.00;                   // Medical allowance
        $transport = 150.00;                 // Transport / Internet perks
        $totalAllowances = $hra + $medical + $transport;

        $tax = round($basic * 0.10, 2);      // 10% Withholding Tax
        $healthIns = 200.00;                 // Corporate Health Insurance
        $pension = round($basic * 0.04, 2);  // 4% 401(k) / Provident fund
        $totalDeductions = $tax + $healthIns + $pension;

        $netSalary = round($basic + $totalAllowances - $totalDeductions, 2);
        $totalBatchDisbursed += $netSalary;

        // Check if payroll already exists for this employee, month, year
        $chk = $pdo->prepare("SELECT id FROM payroll WHERE employee_id = :eid AND month = :m AND year = :y");
        $chk->execute([':eid' => $emp['id'], ':m' => $month, ':y' => $year]);
        $existing = $chk->fetch();

        if ($existing) {
            $payrollId = $existing['id'];
            $upd = $pdo->prepare("
                UPDATE payroll
                SET basic_salary = :basic, total_allowances = :ta, total_deductions = :td,
                    net_salary = :net, payment_status = 'processed', payment_date = CURRENT_DATE
                WHERE id = :id
            ");
            $upd->execute([
                ':basic' => $basic,
                ':ta'    => $totalAllowances,
                ':td'    => $totalDeductions,
                ':net'   => $netSalary,
                ':id'    => $payrollId
            ]);

            // Clear old items
            $pdo->prepare("DELETE FROM payroll_items WHERE payroll_id = :pid")->execute([':pid' => $payrollId]);
        } else {
            $ins = $pdo->prepare("
                INSERT INTO payroll (employee_id, month, year, basic_salary, total_allowances, total_deductions, net_salary, payment_status, payment_date)
                VALUES (:eid, :m, :y, :basic, :ta, :td, :net, 'processed', CURRENT_DATE)
            ");
            $ins->execute([
                ':eid'   => $emp['id'],
                ':m'     => $month,
                ':y'     => $year,
                ':basic' => $basic,
                ':ta'    => $totalAllowances,
                ':td'    => $totalDeductions,
                ':net'   => $netSalary
            ]);
            $payrollId = (int)$pdo->lastInsertId();
        }

        // Insert Itemized breakdown
        $items = [
            ['allowance', 'Housing Allowance (HRA)', $hra],
            ['allowance', 'Medical Care Allowance', $medical],
            ['allowance', 'Transport & Remote Work Stipend', $transport],
            ['deduction', 'Federal Withholding Tax', $tax],
            ['deduction', 'Corporate Health Insurance', $healthIns],
            ['deduction', 'Retirement 401(k) / PF Contribution', $pension]
        ];

        $itemStmt = $pdo->prepare("INSERT INTO payroll_items (payroll_id, item_type, name, amount) VALUES (:pid, :type, :name, :amount)");
        foreach ($items as $item) {
            $itemStmt->execute([
                ':pid'    => $payrollId,
                ':type'   => $item[0],
                ':name'   => $item[1],
                ':amount' => $item[2]
            ]);
        }

        $processedCount++;
    }

    $pdo->commit();

    Audit::log('payroll_batch_processed', 'payroll', null, "Processed payroll for {$processedCount} employees for {$year}-" . str_pad((string)$month, 2, '0', STR_PAD_LEFT));

    Response::json([
        'month'               => $month,
        'year'                => $year,
        'employees_processed' => $processedCount,
        'total_disbursed'     => $totalBatchDisbursed
    ], "Successfully processed payroll for {$processedCount} employees. Total Net Disbursement: $" . number_format($totalBatchDisbursed, 2));

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    Response::error('Failed to process payroll batch: ' . $e->getMessage(), [], 500);
}
