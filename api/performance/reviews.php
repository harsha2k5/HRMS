<?php
/**
 * API: Performance - Reviews & Appraisals
 * GET /api/performance/reviews.php
 * POST /api/performance/reviews.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/audit.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rbac.php';

Auth::requireAuth();
$role = Auth::role();
$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $scope = RBAC::getEmployeeScopeCondition('e');
    $where = $scope['where'];
    $params = $scope['params'];

    $sql = "
        SELECT pr.*, e.employee_code, e.first_name, e.last_name,
               d.name as department_name, des.title as designation_title,
               u.username as reviewer_username
        FROM performance_reviews pr
        JOIN employees e ON pr.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN designations des ON e.designation_id = des.id
        LEFT JOIN users u ON pr.reviewer_id = u.id
        WHERE {$where}
        ORDER BY pr.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll();

    Response::json($reviews);

} elseif ($method === 'POST') {
    $input = Validator::getJsonInput();
    $id = !empty($input['id']) ? (int)$input['id'] : null;

    if ($id) {
        // Employee submitting self-review
        $selfReview = trim($input['employee_self_review'] ?? '');
        $upd = $pdo->prepare("UPDATE performance_reviews SET employee_self_review = :sr WHERE id = :id");
        $upd->execute([':sr' => $selfReview, ':id' => $id]);

        Audit::log('self_review_submitted', 'performance', $id, "Submitted self review for appraisal #{$id}");
        Response::json(null, "Self-review submitted successfully.");
    } else {
        // Manager or HR creating an appraisal review
        RBAC::requireRoles(['super_admin', 'hr_admin', 'manager']);
        $errors = Validator::validate($input, [
            'employee_id'   => 'required|numeric',
            'review_period' => 'required',
            'rating'        => 'required|numeric'
        ]);
        if (!empty($errors)) {
            Response::error('Validation failed', $errors, 422);
        }

        $rating = min(5.0, max(1.0, (float)$input['rating']));
        $ins = $pdo->prepare("
            INSERT INTO performance_reviews (employee_id, reviewer_id, review_period, rating, manager_feedback, status)
            VALUES (:eid, :rid, :period, :rating, :fb, 'completed')
        ");
        $ins->execute([
            ':eid'    => (int)$input['employee_id'],
            ':rid'    => Auth::id(),
            ':period' => trim($input['review_period']),
            ':rating' => $rating,
            ':fb'     => trim($input['manager_feedback'] ?? '')
        ]);
        $newId = (int)$pdo->lastInsertId();

        Audit::log('performance_review_created', 'performance', $newId, "Created appraisal for employee #{$input['employee_id']} (Rating: {$rating})");
        Response::json(['id' => $newId], "Performance appraisal recorded successfully.", 201);
    }
} else {
    Response::error('Method not allowed', [], 405);
}
