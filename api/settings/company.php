<?php
/**
 * API: Settings - Company Profile & General Configuration
 * GET /api/settings/company.php
 * POST /api/settings/company.php
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
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM company_settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    Response::json($settings);

} elseif ($method === 'POST') {
    RBAC::requireRoles(['super_admin']);
    $input = Validator::getJsonInput();

    if (empty($input)) {
        Response::error('No settings provided to update.', [], 400);
    }

    $stmt = $pdo->prepare("
        INSERT INTO company_settings (setting_key, setting_value)
        VALUES (:key, :val)
        ON CONFLICT(setting_key) DO UPDATE SET setting_value = :val2
    ");

    if (Database::getDriver() === 'mysql') {
        $stmt = $pdo->prepare("
            INSERT INTO company_settings (setting_key, setting_value)
            VALUES (:key, :val)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
    }

    foreach ($input as $key => $val) {
        $cleanKey = trim(strip_tags($key));
        $cleanVal = trim(strip_tags((string)$val));

        if (Database::getDriver() === 'mysql') {
            $stmt->execute([':key' => $cleanKey, ':val' => $cleanVal]);
        } else {
            $stmt->execute([':key' => $cleanKey, ':val' => $cleanVal, ':val2' => $cleanVal]);
        }
    }

    Audit::log('company_settings_updated', 'settings', null, 'Company global settings updated by Super Admin');
    Response::json(null, "Company settings saved successfully.");
} else {
    Response::error('Method not allowed', [], 405);
}
