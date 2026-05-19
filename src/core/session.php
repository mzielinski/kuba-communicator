<?php

// ============================================
// Session Management
// Centralized session initialization
// ============================================

/**
 * Initialize PHP session storage and start the session.
 * Uses project-local storage in data/sessions/ to avoid server /tmp permission issues.
 * Must be called BEFORE any headers are sent and BEFORE session_start() is called elsewhere.
 */
function initializeSession(): void {
    // Ensure session storage directory exists (writable by web server)
    $sessionDir = __DIR__ . '/../../data/sessions';
    if (!is_dir($sessionDir)) {
        mkdir($sessionDir, 0750, true);
    }
    session_save_path($sessionDir);

    // Start the session
    session_start();
}

/**
 * Set CORS headers that allow credentials (must be called before session_start).
 * When credentials: 'include' is used in fetch(), wildcard origin is rejected by browsers.
 */
function setCorsHeaders(): void {
    $allowedOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($allowedOrigin) {
        // If origin is present, echo it back (allows credentials)
        header("Access-Control-Allow-Origin: $allowedOrigin");
        header('Access-Control-Allow-Credentials: true');
    } else {
        // Fallback: allow all (same-origin requests don't send Origin header)
        header('Access-Control-Allow-Origin: *');
    }
}

?>

