<?php
/**
 * Standardized JSON API Response Helper
 */

class Response {
    public static function json($data = null, string $message = 'Operation completed successfully', int $statusCode = 200, array $meta = []): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');

        $response = [
            'success' => true,
            'message' => $message,
            'data'    => $data
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function error(string $message = 'Something went wrong', array $errors = [], int $statusCode = 400): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors'  => $errors
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function unauthorized(string $message = 'Authentication required. Please log in.'): void {
        self::error($message, ['auth' => 'Unauthenticated session'], 401);
    }

    public static function forbidden(string $message = 'You do not have permission to access this resource.'): void {
        self::error($message, ['rbac' => 'Permission denied'], 403);
    }

    public static function notFound(string $message = 'Requested resource was not found.'): void {
        self::error($message, ['resource' => 'Not found'], 404);
    }
}
