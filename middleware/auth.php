<?php
/**
 * Authentication Middleware & Session Manager
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/audit.php';

class Auth {
    public static function check(): bool {
        if (!isset($_SESSION['user']['id']) || empty($_SESSION['user']['id'])) {
            return false;
        }

        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            self::logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function requireAuth(): array {
        if (!self::check()) {
            Response::unauthorized('Authentication required. Please log in to proceed.');
        }
        return $_SESSION['user'];
    }

    public static function user(): ?array {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function role(): string {
        return $_SESSION['user']['role_name'] ?? 'guest';
    }

    public static function employee(): ?array {
        return $_SESSION['employee'] ?? null;
    }

    public static function employeeId(): ?int {
        return $_SESSION['employee']['id'] ?? null;
    }

    public static function login(array $user): void {
        // Prevent session fixation
        session_regenerate_id(true);

        $pdo = Database::getConnection();

        // Fetch Employee profile associated with this user if any
        $stmt = $pdo->prepare("
            SELECT e.*, d.name as department_name, des.title as designation_title
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN designations des ON e.designation_id = des.id
            WHERE e.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $user['id']]);
        $employee = $stmt->fetch();

        // Update last login
        if (Database::getDriver() === 'mysql') {
            $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id")->execute([':id' => $user['id']]);
        } else {
            $pdo->prepare("UPDATE users SET last_login_at = datetime('now') WHERE id = :id")->execute([':id' => $user['id']]);
        }

        // Store user in session (omit password hash)
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        $_SESSION['employee'] = $employee ?: null;
        $_SESSION['last_activity'] = time();

        Audit::log('user_login', 'auth', $user['id'], 'User logged in successfully', $user['id']);
    }

    public static function logout(): void {
        if (isset($_SESSION['user']['id'])) {
            Audit::log('user_logout', 'auth', $_SESSION['user']['id'], 'User logged out', $_SESSION['user']['id']);
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
