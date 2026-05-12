<?php

// ============================================
// Authentication & User Data Management
// ============================================

/**
 * Check if user is logged in
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Require user to be authenticated
 * Exits with 401 error if not authenticated
 */
function requireAuth() {
    if (!isUserLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized - Please log in']);
        exit;
    }
}

/**
 * Get user data directory path
 * Creates the directory if it doesn't exist
 */
function getUserDataDir() {
    if (!isUserLoggedIn()) {
        return null;
    }
    $username = $_SESSION['username'];
    $dataDir = __DIR__ . '/../data/' . $username;

    // Ensure directory exists
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    return $dataDir;
}

/**
 * Get user-specific JSON file path
 */
function getUserJsonFile($filename) {
    $dataDir = getUserDataDir();
    if (!$dataDir) {
        return null;
    }
    return $dataDir . '/' . $filename;
}

/**
 * Validate filename for security
 */
function validateFilename($filename) {
    return preg_match('/^[a-zA-Z0-9_.-]+$/', $filename);
}

/**
 * Check if file extension is allowed
 */
function isAllowedFileExtension($filename, $allowedExtensions = ['json', 'backup']) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    return in_array($ext, $allowedExtensions);
}
?>

