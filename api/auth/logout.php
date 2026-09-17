<?php
/**
 * API: Authentication - Logout
 * POST /api/auth/logout.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

Auth::logout();
Response::json(null, 'Successfully logged out.');
