<?php

// ============================================
// Login Authentication System
// Handles user login and session management
// ============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Start session
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Credentials file path
$credentialsFile = __DIR__ . '/data/credentials.json';

// ============================================
// Helper Functions
// ============================================

/**
 * Load credentials from file
 */
function loadCredentials() {
    global $credentialsFile;

    if (!file_exists($credentialsFile)) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Credentials file not found. Please configure credentials.json in data/ directory.'
        ]);
        exit;
    }

    $content = file_get_contents($credentialsFile);
    $data = json_decode($content, true);

    if (!isset($data['users']) || empty($data['users'])) {
        http_response_code(500);
        echo json_encode([
            'error' => 'No users configured in credentials.json'
        ]);
        exit;
    }

    return $data['users'];
}

/**
 * Verify user credentials
 * Uses bcrypt password_verify for secure password comparison
 */
function verifyCredentials($username, $password) {
    $credentials = loadCredentials();

    foreach ($credentials as $user) {
        if ($user['username'] === $username) {
            // Use password_verify for secure password comparison
            // Supports both hashed and plain text (for migration purposes)
            if (password_verify($password, $user['password'])) {
                return true;
            } elseif ($user['password'] === $password) {
                // Fallback for plain text passwords (during migration)
                return true;
            }
        }
    }

    return false;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Get current user
 */
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username']
        ];
    }
    return null;
}

// ============================================
// API Endpoints
// ============================================

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'login' || !$action) {
        // Login endpoint
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Nazwa użytkownika i hasło są wymagane'
            ]);
            exit;
        }

        // Verify credentials
        if (verifyCredentials($username, $password)) {
            // Create user data directory if it doesn't exist
            $userDataDir = __DIR__ . '/data/' . $username;
            if (!is_dir($userDataDir)) {
                mkdir($userDataDir, 0755, true);
            }

            // Create session
            $_SESSION['user_id'] = md5($username . time());
            $_SESSION['username'] = $username;
            $_SESSION['login_time'] = time();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Zalogowano pomyślnie',
                'user_id' => $_SESSION['user_id'],
                'username' => $username
            ]);
        } else {
            // Log failed attempt
            error_log("Failed login attempt for user: {$username}");

            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Błędna nazwa użytkownika lub hasło'
            ]);
        }
        exit;
    } elseif ($action === 'logout') {
        // Logout endpoint
        session_destroy();
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Wylogowano pomyślnie'
        ]);
        exit;
    }
} elseif ($method === 'GET') {
    if ($action === 'check-session') {
        // Check if user is logged in
        if (isLoggedIn()) {
            http_response_code(200);
            echo json_encode([
                'loggedIn' => true,
                'user' => getCurrentUser()
            ]);
        } else {
            http_response_code(200);
            echo json_encode([
                'loggedIn' => false,
                'user' => null
            ]);
        }
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
?>

