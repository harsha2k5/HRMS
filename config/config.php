<?php
/**
 * Application Configuration
 */

// Define Base Paths
define('BASE_PATH', dirname(__DIR__));
define('STORAGE_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'storage');
define('UPLOADS_PATH', STORAGE_PATH . DIRECTORY_SEPARATOR . 'uploads');

// Load .env variables if .env file exists
if (file_exists(BASE_PATH . DIRECTORY_SEPARATOR . '.env')) {
    $lines = file(BASE_PATH . DIRECTORY_SEPARATOR . '.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Strip surrounding quotes
            $value = trim($value, "\"'");
            if (!array_key_exists($key, $_ENV)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }
}

// Helper to get environment variables
function env(string $key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// Application Defaults
define('APP_NAME', env('APP_NAME', 'Apex Global HRMS'));
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', filter_var(env('APP_DEBUG', true), FILTER_VALIDATE_BOOLEAN));
define('APP_URL', env('APP_URL', 'http://localhost:8000'));
define('SESSION_LIFETIME', 7200); // 2 hours

// Error Reporting
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

// Timezone
date_default_timezone_set(env('TIMEZONE', 'America/New_York'));

// Cross-Origin Resource Sharing (CORS) for external frontends (e.g. Netlify)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit(0);
    }
}

// Ensure required upload directories exist
$subdirs = ['contracts', 'certificates', 'identity', 'policies', 'resumes', 'avatars', 'general'];
foreach ($subdirs as $subdir) {
    $path = UPLOADS_PATH . DIRECTORY_SEPARATOR . $subdir;
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

// Start Secure Session if not active
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
