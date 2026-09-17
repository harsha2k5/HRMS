<?php
/**
 * API: Recruitment - Job Openings
 * GET /api/recruitment/jobs.php
 * POST /api/recruitment/jobs.php
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
    $status = trim($_GET['status'] ?? '');
    $where = "1=1";
    $params = [];

    if ($status !== '') {
        $where = "jo.status = :status";
        $params[':status'] = $status;
    }

    $sql = "
        SELECT jo.*, d.name as department_name, des.title as designation_title,
               count(c.id) as candidate_count
        FROM job_openings jo
        JOIN departments d ON jo.department_id = d.id
        LEFT JOIN designations des ON jo.designation_id = des.id
        LEFT JOIN candidates c ON jo.id = c.job_opening_id
        WHERE {$where}
        GROUP BY jo.id, jo.title, jo.department_id, jo.designation_id, jo.job_type,
                 jo.experience_level, jo.vacancies, jo.description, jo.requirements,
                 jo.status, jo.created_at, d.name, des.title
        ORDER BY jo.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();

    Response::json($jobs);
} elseif ($method === 'POST') {
    RBAC::requireRoles(['super_admin', 'hr_admin']);
    $input = Validator::getJsonInput();

    $id = !empty($input['id']) ? (int)$input['id'] : null;

    if ($id) {
        // Update job
        $upd = $pdo->prepare("
            UPDATE job_openings
            SET title = :title, department_id = :dept_id, designation_id = :desig_id,
                job_type = :type, experience_level = :exp, vacancies = :vac,
                description = :desc, requirements = :req, status = :status
            WHERE id = :id
        ");
        $upd->execute([
            ':title'    => trim($input['title']),
            ':dept_id'  => (int)$input['department_id'],
            ':desig_id' => !empty($input['designation_id']) ? (int)$input['designation_id'] : null,
            ':type'     => $input['job_type'] ?? 'full_time',
            ':exp'      => $input['experience_level'] ?? 'Mid-Level',
            ':vac'      => (int)($input['vacancies'] ?? 1),
            ':desc'     => trim($input['description'] ?? ''),
            ':req'      => trim($input['requirements'] ?? ''),
            ':status'   => $input['status'] ?? 'open',
            ':id'       => $id
        ]);
        Audit::log('job_updated', 'recruitment', $id, "Updated job {$input['title']}");
        Response::json(null, "Job opening updated successfully.");
    } else {
        // Create job
        $errors = Validator::validate($input, [
            'title'         => 'required|min:3',
            'department_id' => 'required|numeric'
        ]);
        if (!empty($errors)) {
            Response::error('Validation failed', $errors, 422);
        }

        $ins = $pdo->prepare("
            INSERT INTO job_openings (title, department_id, designation_id, job_type, experience_level, vacancies, description, requirements, status)
            VALUES (:title, :dept_id, :desig_id, :type, :exp, :vac, :desc, :req, 'open')
        ");
        $ins->execute([
            ':title'    => trim($input['title']),
            ':dept_id'  => (int)$input['department_id'],
            ':desig_id' => !empty($input['designation_id']) ? (int)$input['designation_id'] : null,
            ':type'     => $input['job_type'] ?? 'full_time',
            ':exp'      => $input['experience_level'] ?? 'Mid-Level',
            ':vac'      => (int)($input['vacancies'] ?? 1),
            ':desc'     => trim($input['description'] ?? ''),
            ':req'      => trim($input['requirements'] ?? '')
        ]);
        $newId = (int)$pdo->lastInsertId();
        Audit::log('job_created', 'recruitment', $newId, "Created job opening {$input['title']}");
        Response::json(['id' => $newId], "Job opening created successfully.", 201);
    }
} else {
    Response::error('Method not allowed', [], 405);
}
