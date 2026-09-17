<?php
/**
 * API: Recruitment - Interviews
 * GET /api/recruitment/interviews.php
 * POST /api/recruitment/interviews.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = "
        SELECT i.*, c.first_name as cand_first_name, c.last_name as cand_last_name, c.email as cand_email,
               jo.title as job_title, u.username as interviewer_username
        FROM interviews i
        JOIN candidates c ON i.candidate_id = c.id
        JOIN job_openings jo ON i.job_opening_id = jo.id
        JOIN users u ON i.interviewer_id = u.id
        ORDER BY i.interview_date ASC, i.interview_time ASC
    ";
    $interviews = $pdo->query($sql)->fetchAll();
    Response::json($interviews);

} elseif ($method === 'POST') {
    RBAC::requireRoles(['super_admin', 'hr_admin', 'manager']);
    $input = Validator::getJsonInput();

    $id = !empty($input['id']) ? (int)$input['id'] : null;

    if ($id) {
        // Update feedback or status
        $status = $input['status'] ?? 'scheduled';
        $feedback = trim($input['feedback'] ?? '');

        $upd = $pdo->prepare("UPDATE interviews SET status = :status, feedback = :feedback WHERE id = :id");
        $upd->execute([':status' => $status, ':feedback' => $feedback, ':id' => $id]);

        Audit::log('interview_updated', 'recruitment', $id, "Updated interview status to {$status}");
        Response::json(null, "Interview record updated successfully.");
    } else {
        // Schedule new interview
        $errors = Validator::validate($input, [
            'candidate_id'   => 'required|numeric',
            'job_opening_id' => 'required|numeric',
            'interview_date' => 'required|date',
            'interview_time' => 'required'
        ]);
        if (!empty($errors)) {
            Response::error('Validation failed', $errors, 422);
        }

        $interviewerId = !empty($input['interviewer_id']) ? (int)$input['interviewer_id'] : Auth::id();
        $location = trim($input['location_or_link'] ?? 'Online Video Meeting');

        $ins = $pdo->prepare("
            INSERT INTO interviews (candidate_id, job_opening_id, interviewer_id, interview_date, interview_time, location_or_link, status, feedback)
            VALUES (:cid, :jid, :iid, :date, :time, :loc, 'scheduled', :fb)
        ");
        $ins->execute([
            ':cid'  => (int)$input['candidate_id'],
            ':jid'  => (int)$input['job_opening_id'],
            ':iid'  => $interviewerId,
            ':date' => $input['interview_date'],
            ':time' => $input['interview_time'],
            ':loc'  => $location,
            ':fb'   => trim($input['feedback'] ?? '')
        ]);
        $newId = (int)$pdo->lastInsertId();

        Audit::log('interview_scheduled', 'recruitment', $newId, "Scheduled interview on {$input['interview_date']} at {$input['interview_time']}");
        Response::json(['id' => $newId], "Interview scheduled successfully.", 201);
    }
} else {
    Response::error('Method not allowed', [], 405);
}
