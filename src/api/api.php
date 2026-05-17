<?php

// ============================================
// Word Management & Preferences API
// Main Router - Delegates to specialized handlers
// ============================================

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/words-handler.php';
require_once __DIR__ . '/preferences-handler.php';
require_once __DIR__ . '/file-handler.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // Allow action in POST body when not in query string
    if (!$action && isset($input['action'])) {
        $action = $input['action'];
    }

    switch ($action) {
        case 'save':
            WordsHandler::handleSaveWords($input);
            break;
        case 'save-global-words':
            WordsHandler::handleSaveGlobalWords($input);
            break;
        case 'save-preferences':
            PreferencesHandler::handleSavePreferences($input);
            break;
        case 'save-telegram-config':
            PreferencesHandler::handleSaveTelegramConfig($input);
            break;
        case 'add-telegram-chat':
            PreferencesHandler::handleAddTelegramChat($input);
            break;
        case 'remove-telegram-chat':
            PreferencesHandler::handleRemoveTelegramChat($input);
            break;
        case 'update-telegram-chat':
            PreferencesHandler::handleUpdateTelegramChat($input);
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
        case 'load-global-words':
            WordsHandler::handleLoadGlobalWords();
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


