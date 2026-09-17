<?php
/**
 * Audit Logging Helper
 */

require_once __DIR__ . '/../config/database.php';

class Audit {
    public static function log(string $action, string $entityType, ?int $entityId = null, $details = null, ?int $userId = null): void {
        try {
            $pdo = Database::getConnection();

            if ($userId === null && isset($_SESSION['user']['id'])) {
                $userId = (int) $_SESSION['user']['id'];
            }

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $detailsText = is_array($details) || is_object($details) ? json_encode($details) : (string)$details;

            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address, created_at)
                VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address, datetime('now'))
            ");

            // Handle SQLite datetime('now') vs MySQL NOW()
            if (Database::getDriver() === 'mysql') {
                $stmt = $pdo->prepare("
                    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address, created_at)
                    VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address, NOW())
                ");
            }

            $stmt->execute([
                ':user_id'     => $userId,
                ':action'      => $action,
                ':entity_type' => $entityType,
                ':entity_id'   => $entityId,
                ':details'     => $detailsText,
                ':ip_address'  => $ipAddress
            ]);
        } catch (Exception $e) {
            error_log("Failed to write audit log: " . $e->getMessage());
        }
    }
}
