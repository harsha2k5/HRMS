<?php
/**
 * API: Dashboard Statistics & Overview
 * GET /api/dashboard/stats.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$role = Auth::role();
$employee = Auth::employee();
$pdo = Database::getConnection();
$today = date('Y-m-d');
$currentYear = (int)date('Y');
$currentMonth = (int)date('m');

$stats = [];

if (in_array($role, ['super_admin', 'hr_admin'], true)) {
    // 1. Total & Active Employees
    $stmt = $pdo->query("SELECT count(*) as total, sum(case when employment_status = 'active' then 1 else 0 end) as active, sum(case when employment_status = 'on_leave' then 1 else 0 end) as on_leave FROM employees");
    $empStats = $stmt->fetch();
    $stats['total_employees'] = (int)($empStats['total'] ?? 0);
    $stats['active_employees'] = (int)($empStats['active'] ?? 0);
    $stats['on_leave_employees'] = (int)($empStats['on_leave'] ?? 0);

    // 2. Today's Attendance breakdown
    $stmt = $pdo->prepare("
        SELECT 
            sum(case when status = 'present' then 1 else 0 end) as present,
            sum(case when status = 'late' then 1 else 0 end) as late,
            sum(case when status = 'absent' then 1 else 0 end) as absent,
            sum(case when status = 'on_leave' then 1 else 0 end) as on_leave
        FROM attendance
        WHERE date = :today
    ");
    $stmt->execute([':today' => $today]);
    $attStats = $stmt->fetch();
    $stats['attendance_today'] = [
        'present'  => (int)($attStats['present'] ?? 0),
        'late'     => (int)($attStats['late'] ?? 0),
        'absent'   => max(0, $stats['active_employees'] - ((int)($attStats['present'] ?? 0) + (int)($attStats['late'] ?? 0))),
        'on_leave' => (int)($attStats['on_leave'] ?? 0)
    ];

    // 3. Pending Leave Requests
    $stmt = $pdo->query("SELECT count(*) as c FROM leave_requests WHERE status = 'pending'");
    $stats['pending_leave_count'] = (int)($stmt->fetch()['c'] ?? 0);

    // 4. Open Job Positions
    $stmt = $pdo->query("SELECT count(*) as c FROM job_openings WHERE status = 'open'");
    $stats['open_jobs_count'] = (int)($stmt->fetch()['c'] ?? 0);

    // 5. Total Payroll for latest cycle
    $stmt = $pdo->query("SELECT sum(net_salary) as total_payroll FROM payroll WHERE year = {$currentYear}");
    $stats['payroll_summary'] = (float)($stmt->fetch()['total_payroll'] ?? 0.00);

    // 6. Department Distribution for chart
    $deptStmt = $pdo->query("
        SELECT d.name, count(e.id) as employee_count
        FROM departments d
        LEFT JOIN employees e ON d.id = e.department_id
        GROUP BY d.id, d.name
    ");
    $stats['department_distribution'] = $deptStmt->fetchAll();

    // 7. Recent 7-Day Attendance Trend
    $trendStmt = $pdo->query("
        SELECT date, count(id) as present_count
        FROM attendance
        GROUP BY date
        ORDER BY date DESC
        LIMIT 7
    ");
    $stats['attendance_trend'] = array_reverse($trendStmt->fetchAll());

    // 8. Recent Activities (Audit log)
    $actStmt = $pdo->query("
        SELECT a.*, u.username
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
        LIMIT 6
    ");
    $stats['recent_activities'] = $actStmt->fetchAll();

} elseif ($role === 'manager') {
    $empId = $employee ? $employee['id'] : 0;
    $deptId = $employee ? $employee['department_id'] : 0;

    // Team members
    $stmt = $pdo->prepare("SELECT count(*) as c FROM employees WHERE manager_id = :mid OR department_id = :dept_id");
    $stmt->execute([':mid' => $empId, ':dept_id' => $deptId]);
    $stats['team_size'] = (int)($stmt->fetch()['c'] ?? 0);

    // Team pending leave
    $stmt = $pdo->prepare("
        SELECT count(*) as c
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        WHERE lr.status = 'pending' AND (e.manager_id = :mid OR e.department_id = :dept_id)
    ");
    $stmt->execute([':mid' => $empId, ':dept_id' => $deptId]);
    $stats['pending_team_leave'] = (int)($stmt->fetch()['c'] ?? 0);

    // Today's team attendance
    $stmt = $pdo->prepare("
        SELECT count(a.id) as present_today
        FROM attendance a
        JOIN employees e ON a.employee_id = e.id
        WHERE a.date = :today AND (e.manager_id = :mid OR e.department_id = :dept_id)
    ");
    $stmt->execute([':today' => $today, ':mid' => $empId, ':dept_id' => $deptId]);
    $stats['team_present_today'] = (int)($stmt->fetch()['present_today'] ?? 0);

    // Team goals pending
    $stmt = $pdo->prepare("
        SELECT count(pg.id) as c
        FROM performance_goals pg
        JOIN employees e ON pg.employee_id = e.id
        WHERE pg.status = 'in_progress' AND (e.manager_id = :mid OR e.department_id = :dept_id)
    ");
    $stmt->execute([':mid' => $empId, ':dept_id' => $deptId]);
    $stats['team_goals_active'] = (int)($stmt->fetch()['c'] ?? 0);

} else {
    // Employee Role
    $empId = $employee ? $employee['id'] : 0;

    // Today's attendance
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = :eid AND date = :today LIMIT 1");
    $stmt->execute([':eid' => $empId, ':today' => $today]);
    $stats['my_attendance_today'] = $stmt->fetch() ?: null;

    // Leave Balances
    $stmt = $pdo->prepare("
        SELECT lb.*, lt.name as leave_name, lt.code as leave_code
        FROM leave_balances lb
        JOIN leave_types lt ON lb.leave_type_id = lt.id
        WHERE lb.employee_id = :eid AND lb.year = :year
    ");
    $stmt->execute([':eid' => $empId, ':year' => $currentYear]);
    $stats['my_leave_balances'] = $stmt->fetchAll();

    // Pending Leaves
    $stmt = $pdo->prepare("SELECT count(*) as c FROM leave_requests WHERE employee_id = :eid AND status = 'pending'");
    $stmt->execute([':eid' => $empId]);
    $stats['my_pending_leaves'] = (int)($stmt->fetch()['c'] ?? 0);

    // My active goals
    $stmt = $pdo->prepare("SELECT count(*) as c FROM performance_goals WHERE employee_id = :eid AND status != 'completed'");
    $stmt->execute([':eid' => $empId]);
    $stats['my_active_goals'] = (int)($stmt->fetch()['c'] ?? 0);

    // Latest Payslip
    $stmt = $pdo->prepare("
        SELECT * FROM payroll
        WHERE employee_id = :eid
        ORDER BY year DESC, month DESC
        LIMIT 1
    ");
    $stmt->execute([':eid' => $empId]);
    $stats['latest_payslip'] = $stmt->fetch() ?: null;
}

// Common items for all roles:
// Upcoming holidays
$holidaysStmt = $pdo->query("SELECT * FROM holidays WHERE date >= '{$today}' ORDER BY date ASC LIMIT 4");
$stats['upcoming_holidays'] = $holidaysStmt->fetchAll();

// Upcoming company events
$eventsStmt = $pdo->query("SELECT * FROM company_events WHERE event_date >= '{$today}' ORDER BY event_date ASC LIMIT 4");
$stats['upcoming_events'] = $eventsStmt->fetchAll();

// Recent announcements
$announcementsStmt = $pdo->query("
    SELECT a.*, u.username as author_name
    FROM announcements a
    JOIN users u ON a.author_id = u.id
    ORDER BY a.is_pinned DESC, a.created_at DESC
    LIMIT 3
");
$stats['announcements'] = $announcementsStmt->fetchAll();

Response::json($stats);
