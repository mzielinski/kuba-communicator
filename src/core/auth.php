<?php

// ============================================
// Authentication & User Data Management
// ============================================

/**
 * Check if user is logged in (email-based session).
 */
function isUserLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['email']);
}

/**
 * Require user to be authenticated.
 * If $requireWrite is true, also block DEMO-role users (read-only).
 */
function requireAuth(bool $requireWrite = false): void {
    if (!isUserLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized - Please log in']);
        exit;
    }
    if ($requireWrite && ($_SESSION['role'] ?? '') === 'DEMO') {
        http_response_code(403);
        echo json_encode(['error' => 'Demo mode - read only access', 'demo' => true]);
        exit;
    }
}

/**
 * Require admin role. Exits with 403 if not admin.
 */
function requireAdmin(): void {
    requireAuth();
    if (($_SESSION['role'] ?? '') !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
}

/**
 * Return the current user's role, or empty string.
 */
function getCurrentRole(): string {
    return $_SESSION['role'] ?? '';
}

/**
 * Get user data directory path, creating it if necessary.
 * For DEMO users, returns language-specific template directory.
 * For regular users, returns their data directory.
 */
function getUserDataDir(): ?string {
    if (!isUserLoggedIn()) return null;
    
    if (($_SESSION['role'] ?? '') === 'DEMO') {
        $language = $_SESSION['language'] ?? 'pl';
        $dataDir = __DIR__ . '/../../data/templates/' . $language;
    } else {
        $dataDir = __DIR__ . '/../../data/' . ($_SESSION['data_dir'] ?? '');
    }
    
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    return $dataDir;
}

/**
 * Get user-specific JSON file path.
 */
function getUserJsonFile(string $filename): ?string {
    $dataDir = getUserDataDir();
    if (!$dataDir) return null;
    return $dataDir . '/' . $filename;
}

/**
 * Validate filename for security (no path traversal).
 */
function validateFilename(string $filename): bool {
    return (bool)preg_match('/^[a-zA-Z0-9_.-]+$/', $filename);
}

/**
 * Check if file extension is allowed.
 */
function isAllowedFileExtension(string $filename, array $allowedExtensions = ['json', 'backup']): bool {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    return in_array($ext, $allowedExtensions);
}
?>
