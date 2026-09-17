<?php
/**
 * Built-in PHP Development Server Router
 * Usage: php -S 127.0.0.1:8000 router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// 1. Static Assets in public/
if (str_starts_with($uri, '/assets/') || (file_exists(__DIR__ . '/public' . $uri) && is_file(__DIR__ . '/public' . $uri) && $uri !== '/')) {
    $filePath = __DIR__ . '/public' . $uri;
    if (file_exists($filePath) && is_file($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimes = [
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'application/javascript; charset=UTF-8',
            'json'  => 'application/json',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'ico'   => 'image/x-icon'
        ];
        $contentType = $mimes[$ext] ?? (function_exists('mime_content_type') ? @mime_content_type($filePath) : 'application/octet-stream');
        header('Content-Type: ' . $contentType);
        readfile($filePath);
        return true;
    }
}

// 2. Uploaded Storage files
if (str_starts_with($uri, '/uploads/')) {
    $filePath = __DIR__ . '/storage' . $uri;
    if (file_exists($filePath) && is_file($filePath)) {
        $mime = mime_content_type($filePath);
        header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
        readfile($filePath);
        return true;
    }
}

// 3. API Requests
if (str_starts_with($uri, '/api/')) {
    $subPath = substr($uri, 5); // remove /api/
    // If lacks .php extension, append it
    if (!str_ends_with($subPath, '.php')) {
        // e.g. /api/auth/login -> api/auth/login.php
        $candidate = __DIR__ . '/api/' . $subPath . '.php';
        if (file_exists($candidate)) {
            require $candidate;
            return true;
        }
    } else {
        $candidate = __DIR__ . '/api/' . $subPath;
        if (file_exists($candidate)) {
            require $candidate;
            return true;
        }
    }
}

// 4. Default: Serve public/index.php
require __DIR__ . '/public/index.php';
return true;
