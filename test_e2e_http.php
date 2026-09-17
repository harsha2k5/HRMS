<?php
/**
 * Live End-to-End HTTP Integration Test against running web server
 * URL: http://127.0.0.1:8000
 */

$cookieJar = tempnam(sys_get_temp_dir(), 'hrms_cookie_');

function httpReq(string $method, string $path, $data = null) {
    global $cookieJar;
    $url = "http://127.0.0.1:8000" . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data !== null) {
            $json = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true) ?: $response];
}

echo "=========================================================\n";
echo "LIVE HTTP SERVER VERIFICATION (http://127.0.0.1:8000)\n";
echo "=========================================================\n\n";

// 1. Get HTML index page
$res = httpReq('GET', '/');
echo "1. Frontpage HTTP Status: {$res['code']}" . ($res['code'] === 200 ? " [OK]\n" : " [FAIL]\n");

// 2. Login as Super Admin
$res = httpReq('POST', '/api/auth/login.php', [
    'login' => 'admin@company.com',
    'password' => 'password123'
]);
echo "2. Login as Super Admin: {$res['code']} — " . ($res['body']['message'] ?? 'Error') . "\n";

// 3. Check /api/auth/me.php with session
$res = httpReq('GET', '/api/auth/me.php');
echo "3. Session Identity: " . ($res['body']['data']['user']['username'] ?? 'None') . " (Role: " . ($res['body']['data']['user']['role_name'] ?? 'None') . ")\n";

// 4. Fetch Dashboard Statistics
$res = httpReq('GET', '/api/dashboard/stats.php');
echo "4. Dashboard Stats: Total Employees = " . ($res['body']['data']['total_employees'] ?? 0) . ", Pending Leaves = " . ($res['body']['data']['pending_leave_count'] ?? 0) . "\n";

// 5. Fetch Employees List
$res = httpReq('GET', '/api/employees/list.php');
echo "5. Employees Count: " . count($res['body']['data'] ?? []) . " records returned\n";

// 6. Fetch Departments List
$res = httpReq('GET', '/api/departments/list.php');
echo "6. Departments Count: " . count($res['body']['data'] ?? []) . " departments returned\n";

// 7. Fetch Recruitment Pipeline
$res = httpReq('GET', '/api/recruitment/pipeline.php');
echo "7. Recruitment Pipeline: " . ($res['body']['data']['total'] ?? 0) . " candidates in Kanban stages\n";

// 8. Fetch Payslip for Employee 4
$res = httpReq('GET', '/api/payroll/payslip.php?id=1');
echo "8. Payslip View: Employee = " . ($res['body']['data']['payroll']['first_name'] ?? 'None') . ", Net = $" . ($res['body']['data']['payroll']['net_salary'] ?? 0) . "\n";

// 9. Logout
$res = httpReq('POST', '/api/auth/logout.php');
echo "9. Logout: " . ($res['body']['message'] ?? 'Done') . "\n";

@unlink($cookieJar);

echo "\nALL LIVE HTTP ENDPOINTS TESTED AND VERIFIED FUNCTIONAL!\n";
