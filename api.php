<?php

// ============================================
// Word Management & Preferences API
// Main Router - Delegates to specialized handlers
// ============================================

// Start session for authentication
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// ============================================
// Load Dependencies
// ============================================

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/handlers/words-handler.php';
require_once __DIR__ . '/handlers/preferences-handler.php';
require_once __DIR__ . '/handlers/file-handler.php';

// ============================================
// Route Requests
// ============================================

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'save':
            WordsHandler::handleSaveWords($input);
            break;
        case 'save-preferences':
            PreferencesHandler::handleSavePreferences($input);
            break;
        case 'save-telegram-config':
            PreferencesHandler::handleSaveTelegramConfig($input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} elseif ($method === 'GET') {
    switch ($action) {
        case 'load-preferences':
            PreferencesHandler::handleLoadPreferences();
            break;
        case 'load-words':
            WordsHandler::handleLoadWords();
            break;
        case 'get-file':
            FileHandler::handleGetFile($_GET['file'] ?? '');
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
?>


