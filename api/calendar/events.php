<?php
/**
 * API: Calendar - Unified Events & Schedule
 * GET /api/calendar/events.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

Auth::requireAuth();
$pdo = Database::getConnection();

$month = !empty($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = !empty($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$monthStr = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
$prefix = "{$year}-{$monthStr}";

$events = [];

// 1. Official Holidays
$hStmt = $pdo->prepare("SELECT * FROM holidays WHERE date LIKE :prefix ORDER BY date ASC");
$hStmt->execute([':prefix' => "{$prefix}%"]);
while ($row = $hStmt->fetch()) {
    $events[] = [
        'id'          => 'holiday-' . $row['id'],
        'title'       => '🎉 ' . $row['name'],
        'date'        => $row['date'],
        'type'        => 'holiday',
        'badge'       => 'Holiday',
        'description' => $row['description']
    ];
}

// 2. Company Events
$eStmt = $pdo->prepare("SELECT * FROM company_events WHERE event_date LIKE :prefix ORDER BY event_date ASC");
$eStmt->execute([':prefix' => "{$prefix}%"]);
while ($row = $eStmt->fetch()) {
    $events[] = [
        'id'          => 'event-' . $row['id'],
        'title'       => '📌 ' . $row['title'],
        'date'        => $row['event_date'],
        'type'        => 'company_event',
        'badge'       => 'Event',
        'time'        => $row['start_time'],
        'location'    => $row['location'],
        'description' => $row['description']
    ];
}

// 3. Approved Employee Leaves
$lStmt = $pdo->prepare("
    SELECT lr.*, e.first_name, e.last_name, lt.name as leave_name
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    JOIN leave_types lt ON lr.leave_type_id = lt.id
    WHERE lr.status = 'approved' AND (lr.start_date LIKE :prefix OR lr.end_date LIKE :prefix)
");
$lStmt->execute([':prefix' => "{$prefix}%"]);
while ($row = $lStmt->fetch()) {
    $events[] = [
        'id'          => 'leave-' . $row['id'],
        'title'       => '🏖️ ' . $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['leave_name'] . ')',
        'date'        => $row['start_date'],
        'end_date'    => $row['end_date'],
        'type'        => 'leave',
        'badge'       => 'On Leave',
        'description' => "Approved leave from {$row['start_date']} to {$row['end_date']}"
    ];
}

// 4. Scheduled Candidate Interviews (for HR / Managers)
if (in_array(Auth::role(), ['super_admin', 'hr_admin', 'manager'], true)) {
    $iStmt = $pdo->prepare("
        SELECT i.*, c.first_name, c.last_name, jo.title as job_title
        FROM interviews i
        JOIN candidates c ON i.candidate_id = c.id
        JOIN job_openings jo ON i.job_opening_id = jo.id
        WHERE i.interview_date LIKE :prefix AND i.status = 'scheduled'
    ");
    $iStmt->execute([':prefix' => "{$prefix}%"]);
    while ($row = $iStmt->fetch()) {
        $events[] = [
            'id'          => 'interview-' . $row['id'],
            'title'       => '💼 Interview: ' . $row['first_name'] . ' ' . $row['last_name'],
            'date'        => $row['interview_date'],
            'time'        => $row['interview_time'],
            'type'        => 'interview',
            'badge'       => 'Interview',
            'description' => "Candidate interview for {$row['job_title']} at {$row['location_or_link']}"
        ];
    }
}

Response::json([
    'month'  => $month,
    'year'   => $year,
    'events' => $events
]);
