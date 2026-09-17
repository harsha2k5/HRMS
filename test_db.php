<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();
    echo "Connected successfully! Active driver: " . Database::getDriver() . PHP_EOL;

    $tables = [
        'roles', 'permissions', 'role_permissions', 'users', 'departments',
        'designations', 'employees', 'attendance', 'leave_types', 'leave_balances',
        'leave_requests', 'payroll', 'payroll_items', 'documents', 'announcements',
        'notifications', 'performance_goals', 'performance_reviews', 'job_openings',
        'candidates', 'applications', 'interviews', 'holidays', 'company_events',
        'audit_logs', 'company_settings'
    ];

    foreach ($tables as $t) {
        $count = $pdo->query("SELECT count(*) as c FROM {$t}")->fetch()['c'];
        echo "Table: {$t} => {$count} rows" . PHP_EOL;
    }
    echo "ALL 26 TABLES VERIFIED SUCCESSFULLY!" . PHP_EOL;
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
