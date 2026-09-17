<?php
/**
 * API: Recruitment - Pipeline / Kanban Board
 * GET /api/recruitment/pipeline.php
 * POST /api/recruitment/pipeline.php
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
    $jobId = !empty($_GET['job_id']) ? (int)$_GET['job_id'] : null;
    $where = "1=1";
    $params = [];

    if ($jobId) {
        $where = "a.job_opening_id = :job_id";
        $params[':job_id'] = $jobId;
    }

    $sql = "
        SELECT a.id as application_id, a.candidate_id, a.job_opening_id, a.stage, a.rating, a.notes, a.applied_at,
               c.first_name, c.last_name, c.email, c.phone, c.resume_path,
               jo.title as job_title, d.name as department_name
        FROM applications a
        JOIN candidates c ON a.candidate_id = c.id
        JOIN job_openings jo ON a.job_opening_id = jo.id
        JOIN departments d ON jo.department_id = d.id
        WHERE {$where}
        ORDER BY a.applied_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();

    // Group into Kanban columns
    $stages = [
        'applied'   => [],
        'screening' => [],
        'interview' => [],
        'selected'  => [],
        'hired'     => [],
        'rejected'  => []
    ];

    foreach ($applications as $app) {
        $stage = $app['stage'];
        if (isset($stages[$stage])) {
            $stages[$stage][] = $app;
        } else {
            $stages['applied'][] = $app;
        }
    }

    Response::json([
        'pipeline'      => $stages,
        'total'         => count($applications)
    ]);

} elseif ($method === 'POST') {
    RBAC::requireRoles(['super_admin', 'hr_admin', 'manager']);
    $input = Validator::getJsonInput();
    $action = $input['action'] ?? 'update_stage';

    if ($action === 'update_stage') {
        $appId = !empty($input['application_id']) ? (int)$input['application_id'] : null;
        $newStage = trim($input['stage'] ?? '');
        $validStages = ['applied', 'screening', 'interview', 'selected', 'hired', 'rejected'];

        if (!$appId || !in_array($newStage, $validStages, true)) {
            Response::error('Valid application ID and stage are required.', [], 400);
        }

        $stmt = $pdo->prepare("UPDATE applications SET stage = :stage WHERE id = :id");
        $stmt->execute([':stage' => $newStage, ':id' => $appId]);

        Audit::log('candidate_stage_updated', 'recruitment', $appId, "Candidate application #{$appId} moved to {$newStage}");
        Response::json(null, "Candidate stage moved to " . ucfirst($newStage));

    } elseif ($action === 'add_candidate') {
        $errors = Validator::validate($input, [
            'first_name'     => 'required',
            'last_name'      => 'required',
            'email'          => 'required|email',
            'job_opening_id' => 'required|numeric'
        ]);
        if (!empty($errors)) {
            Response::error('Validation failed', $errors, 422);
        }

        $pdo->beginTransaction();

        $cIns = $pdo->prepare("
            INSERT INTO candidates (job_opening_id, first_name, last_name, email, phone, status)
            VALUES (:job_id, :fn, :ln, :email, :phone, 'active')
        ");
        $cIns->execute([
            ':job_id' => (int)$input['job_opening_id'],
            ':fn'     => trim($input['first_name']),
            ':ln'     => trim($input['last_name']),
            ':email'  => strtolower(trim($input['email'])),
            ':phone'  => trim($input['phone'] ?? '')
        ]);
        $candId = (int)$pdo->lastInsertId();

        $appIns = $pdo->prepare("
            INSERT INTO applications (candidate_id, job_opening_id, stage, rating, notes)
            VALUES (:cid, :jid, 'applied', 3, :notes)
        ");
        $appIns->execute([
            ':cid'   => $candId,
            ':jid'   => (int)$input['job_opening_id'],
            ':notes' => trim($input['notes'] ?? 'Added manually by recruiter')
        ]);
        $appId = (int)$pdo->lastInsertId();

        $pdo->commit();

        Audit::log('candidate_added', 'recruitment', $candId, "Added candidate {$input['first_name']} {$input['last_name']}");
        Response::json(['candidate_id' => $candId, 'application_id' => $appId], "Candidate added to recruitment pipeline.", 201);
    } else {
        Response::error('Unknown pipeline action', [], 400);
    }
} else {
    Response::error('Method not allowed', [], 405);
}
