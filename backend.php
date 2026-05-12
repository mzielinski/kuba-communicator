<?php
// ============================================
// KUBA - WhatsApp Backend Integration
// PHP Backend for sending messages via WhatsApp Business API
// ============================================

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// CONFIGURATION - SETUP REQUIRED
// ============================================

// Security
define('ALLOWED_ORIGINS', ['http://localhost', 'http://localhost:3000', 'http://127.0.0.1']);
define('REQUIRE_API_KEY', false);
define('API_KEY', 'your-secret-key-here');

// Start session for authentication
session_start();

// ============================================
// DEBUG LOGGING
// ============================================
$DEBUG_LOG = __DIR__ . '/debug.log';

function writeDebugLog($message) {
    global $DEBUG_LOG;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}\n";
    file_put_contents($DEBUG_LOG, $logEntry, FILE_APPEND);
}

// Clear old debug log if it's larger than 1MB
if (file_exists($DEBUG_LOG) && filesize($DEBUG_LOG) > 1024 * 1024) {
    unlink($DEBUG_LOG);
}

writeDebugLog("=== Backend.php loaded ===");

// Load .env file for local development (if it exists)
$envPath = __DIR__ . '/data/.env';
$envLoaded = false;

writeDebugLog("Checking for .env file at: {$envPath}");

if (file_exists($envPath)) {
    writeDebugLog(".env file found");
    if (is_readable($envPath)) {
        writeDebugLog(".env file is readable");
        $envContent = file_get_contents($envPath);
        $envLines = explode("\n", $envContent);
        writeDebugLog("Processing " . count($envLines) . " lines from .env");

        foreach ($envLines as $line) {
            // Skip empty lines and comments
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE format
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                    (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                    $value = substr($value, 1, -1);
                }

                if (!empty($key) && !getenv($key)) {
                    putenv("$key=$value");
                    writeDebugLog("Set environment variable: {$key} (length: " . strlen($value) . ")");
                    $envLoaded = true;
                }
            }
        }
        writeDebugLog("Finished loading .env - Total variables set: " . ($envLoaded ? "yes" : "no"));
    } else {
        writeDebugLog("ERROR: .env file exists at {$envPath} but is not readable. Check file permissions.");
    }
} else {
    writeDebugLog("WARNING: .env file not found at {$envPath}");
}

// ============================================
// TELEGRAM CONFIGURATION (loaded from .env)
// ============================================
define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: '');
define('TELEGRAM_CHAT_ID', getenv('TELEGRAM_CHAT_ID') ?: '');

writeDebugLog("TELEGRAM_BOT_TOKEN defined: " . (empty(TELEGRAM_BOT_TOKEN) ? "NO (empty)" : "YES (length: " . strlen(TELEGRAM_BOT_TOKEN) . ")"));

// ============================================
// REQUEST HANDLING
// ============================================

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // Get JSON payload
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data) {
            writeDebugLog("Invalid JSON payload");
            throw new Exception('Invalid JSON payload');
        }

        // Route based on action
        $action = $data['action'] ?? '';
        writeDebugLog("Action requested: {$action}");

        if ($action === 'send-telegram-message') {
            // ============================================
            // SEND TELEGRAM MESSAGE ENDPOINT
            // ============================================

            writeDebugLog("=== Send Telegram Message Request ===");

            // Extract message and chat ID
            $message = $data['message'] ?? null;
            $chatId = $data['chatId'] ?? null;

            if (!$message) {
                writeDebugLog("ERROR: Message is empty");
                throw new Exception('Message is required');
            }

            if (!$chatId) {
                writeDebugLog("ERROR: Chat ID is empty");
                throw new Exception('Chat ID is required');
            }

            writeDebugLog("Message length: " . strlen($message) . ", Chat ID: {$chatId}");

            // Validate API configuration
            if (!TELEGRAM_BOT_TOKEN || TELEGRAM_BOT_TOKEN === '') {
                writeDebugLog("ERROR: Telegram bot token not configured");
                throw new Exception('Telegram bot token not configured. Please set up credentials on the server.');
            }

            // Send message
            $result = sendTelegramMessage($message, $chatId);

            if ($result['success']) {
                writeDebugLog("SUCCESS: Message sent");
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Message sent to Telegram successfully',
                    'timestamp' => date('c'),
                    'messageId' => $result['messageId'] ?? null,
                ]);
            } else {
                writeDebugLog("ERROR: Failed to send message");
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to send Telegram message',
                    'error' => $result['error'] ?? 'Telegram API error',
                ]);
            }
        } elseif ($action === 'test-telegram-connection') {
            // ============================================
            // TEST TELEGRAM CONNECTION ENDPOINT
            // ============================================

            $chatId = $data['chatId'] ?? null;

            if (!$chatId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Chat ID is required',
                ]);
                exit;
            }

            // Diagnostic information
            $envPath = __DIR__ . '/data/.env';
            $diagnostics = [
                'env_path' => $envPath,
                'env_file_exists' => file_exists($envPath),
                'env_file_readable' => is_readable($envPath),
                'env_file_size' => file_exists($envPath) ? filesize($envPath) : 0,
                'env_file_perms' => file_exists($envPath) ? substr(sprintf('%o', fileperms($envPath)), -4) : 'N/A',
                'token_set' => !empty(TELEGRAM_BOT_TOKEN),
                'token_length' => strlen(TELEGRAM_BOT_TOKEN),
                'php_version' => phpversion(),
                'script_dir' => __DIR__,
                'files_in_dir' => array_filter(scandir(__DIR__), function($f) {
                    return !in_array($f, ['.', '..']);
                })
            ];

            if (!TELEGRAM_BOT_TOKEN || TELEGRAM_BOT_TOKEN === '') {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Telegram bot token not configured',
                    'debug' => $diagnostics
                ]);
                exit;
            }

            // Test with a simple message
            $testResult = sendTelegramMessage('🤖 Test wiadomości z KUBA', $chatId);

            if ($testResult['success']) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Telegram connection successful',
                    'debug' => $diagnostics
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Telegram connection failed',
                    'error' => $testResult['error'] ?? 'Unknown error',
                    'debug' => $diagnostics
                ]);
            }
        } else {
            writeDebugLog("ERROR: Invalid action: {$action}");
            throw new Exception('Invalid action');
        }
     } else {
        writeDebugLog("ERROR: Invalid request method: {$method}");
        throw new Exception('Invalid request method. Only POST is allowed.');
     }
} catch (Exception $e) {
    writeDebugLog("Exception caught: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => true,
    ]);
}

// ============================================
// HELPER FUNCTIONS
// ============================================


/**
 * Save message to local log file (fallback if API fails)
 *
 * @param string $message The message to save
 * @param string $recipientPhone The recipient phone
 * @return bool Success status
 */
function saveMessageLocally($message, $recipientPhone = null) {
    try {
        $logFile = __DIR__ . '/messages_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] To: {$recipientPhone} | Message: {$message}\n";

        return file_put_contents($logFile, $logEntry, FILE_APPEND) !== false;
    } catch (Exception $e) {
        error_log("Error saving message locally: " . $e->getMessage());
        return false;
    }
}

// ============================================
// TELEGRAM BOT FUNCTIONS
// ============================================

/**
 * Send message via Telegram Bot API
 *
 * @param string $message The message to send
 * @param string|int $chatId The chat ID (user ID or group ID)
 * @return array Array with 'success' (bool) and 'error' (string) keys
 */
function sendTelegramMessage($message, $chatId) {
    try {
        $botToken = TELEGRAM_BOT_TOKEN;

        if (empty($botToken)) {
            writeDebugLog("sendTelegramMessage: No bot token");
            return [
                'success' => false,
                'error' => 'Telegram bot token not configured'
            ];
        }

        if (empty($chatId)) {
            writeDebugLog("sendTelegramMessage: No chat ID");
            return [
                'success' => false,
                'error' => 'Telegram chat ID not configured'
            ];
        }

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $payload = [
            'chat_id' => (int)$chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];

        writeDebugLog("Calling Telegram API: POST {$url}");

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Log the response for debugging
        writeDebugLog("Telegram API Response (HTTP {$httpCode}): " . substr($response, 0, 200));

        if ($httpCode === 200) {
            $responseData = json_decode($response, true);
            if (isset($responseData['ok']) && $responseData['ok']) {
                writeDebugLog("Message sent successfully");
                return [
                    'success' => true,
                    'messageId' => $responseData['result']['message_id'] ?? null
                ];
            } else {
                $errorMsg = $responseData['description'] ?? 'Unknown Telegram error';
                writeDebugLog("Telegram error: {$errorMsg}");
                return [
                    'success' => false,
                    'error' => $errorMsg
                ];
            }
        } else {
            $errorMsg = 'HTTP ' . $httpCode;
            if (!empty($curlError)) {
                $errorMsg .= ': ' . $curlError;
            }

            // Try to parse error from response
            $responseData = json_decode($response, true);
            if (isset($responseData['description'])) {
                $errorMsg = $responseData['description'];
            }

            writeDebugLog("API error: {$errorMsg}");
            return [
                'success' => false,
                'error' => $errorMsg
            ];
        }

    } catch (Exception $e) {
        writeDebugLog("Exception in sendTelegramMessage: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

?>
