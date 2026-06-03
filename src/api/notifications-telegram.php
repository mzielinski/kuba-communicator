<?php
// ============================================
// KUBA - Telegram Backend Integration
// ============================================

require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/logger.php';
require_once __DIR__ . '/../core/config.php';

header('Content-Type: application/json');
setCorsHeaders();
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// CONFIGURATION - SETUP REQUIRED
// ============================================

// Security
define('ALLOWED_ORIGINS', [
    'https://kuba-communication-system.eu',
    'https://www.kuba-communication-system.eu',
    'http://localhost',
    'http://localhost:3000',
    'http://127.0.0.1',
]);
define('REQUIRE_API_KEY', false);
define('API_KEY', 'your-secret-key-here');

// Start session for authentication
initializeSession();

// ============================================
// TELEGRAM CONFIGURATION (loaded from .env)
// ============================================
$telegramConfig = loadDataEnvConfig([
    'TELEGRAM_BOT_TOKEN' => '',
    'TELEGRAM_CHAT_ID'   => '',
]);

define('TELEGRAM_BOT_TOKEN', $telegramConfig['TELEGRAM_BOT_TOKEN'] ?? '');
define('TELEGRAM_CHAT_ID', $telegramConfig['TELEGRAM_CHAT_ID'] ?? '');

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
            writeAppLog("[Telegram] Invalid JSON payload");
            throw new Exception('Invalid JSON payload');
        }

        // Route based on action
        $action = $data['action'] ?? '';
        if ($action === 'send-telegram-message') {
            // ============================================
            // SEND TELEGRAM MESSAGE ENDPOINT
            // ============================================

            // Extract message and chat ID
            $message = $data['message'] ?? null;
            $chatId = $data['chatId'] ?? null;

            if (!$message) {
                writeAppLog("[Telegram] Message is empty");
                throw new Exception('Message is required');
            }

            if (!$chatId) {
                writeAppLog("[Telegram] Chat ID is empty");
                throw new Exception('Chat ID is required');
            }

            // Validate API configuration
            if (!TELEGRAM_BOT_TOKEN || TELEGRAM_BOT_TOKEN === '') {
                writeAppLog("[Telegram] Telegram bot token not configured");
                throw new Exception('Telegram bot token not configured. Please set up credentials on the server.');
            }

            // Send message
            $result = sendTelegramMessage($message, $chatId);

            if ($result['success']) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Message sent to Telegram successfully',
                    'timestamp' => date('c'),
                    'messageId' => $result['messageId'] ?? null,
                ]);
            } else {
                writeAppLog("[Telegram] Failed to send message | chatId: {$chatId} | error: " . ($result['error'] ?? 'Telegram API error'));
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
            $envPath = __DIR__ . '/../../data/.env';
            $curlInfo = curl_version();
            $diagnostics = [
                'env_path' => $envPath,
                'env_file_exists' => file_exists($envPath),
                'env_file_readable' => is_readable($envPath),
                'env_file_size' => file_exists($envPath) ? filesize($envPath) : 0,
                'env_file_perms' => file_exists($envPath) ? substr(sprintf('%o', fileperms($envPath)), -4) : 'N/A',
                'token_set' => !empty(TELEGRAM_BOT_TOKEN),
                'token_length' => strlen(TELEGRAM_BOT_TOKEN),
                'php_version' => phpversion(),
                'curl_version' => $curlInfo['version'],
                'curl_ssl_version' => $curlInfo['ssl_version'],
                'curl_protocols' => $curlInfo['protocols'],
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
            writeAppLog("[Telegram] Invalid action: {$action}");
            throw new Exception('Invalid action');
        }
     } else {
        writeAppLog("[Telegram] Invalid request method: {$method}");
        throw new Exception('Invalid request method. Only POST is allowed.');
     }
} catch (Exception $e) {
    writeAppLog("[Telegram] Exception caught: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => true,
    ]);
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
function sendTelegramMessage($message, $chatId): array {
    try {
        $botToken = TELEGRAM_BOT_TOKEN;

        if (empty($botToken)) {
            writeAppLog("[Telegram] sendTelegramMessage: No bot token");
            return [
                'success' => false,
                'error' => 'Telegram bot token not configured'
            ];
        }

        if (empty($chatId)) {
            writeAppLog("[Telegram] sendTelegramMessage: No chat ID");
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

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        // DNS resolution settings to improve reliability
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);  // Prefer IPv4
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 3600);       // Cache DNS for 1 hour

        // Follow redirects and set user agent
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, 'KUBA-Communication-Device/1.0');

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200) {
            $responseData = json_decode($response, true);
            if (isset($responseData['ok']) && $responseData['ok']) {
                return [
                    'success' => true,
                    'messageId' => $responseData['result']['message_id'] ?? null
                ];
            } else {
                $errorMsg = $responseData['description'] ?? 'Unknown Telegram error';
                writeAppLog("[Telegram] Telegram error: {$errorMsg}");
                return [
                    'success' => false,
                    'error' => $errorMsg
                ];
            }
        } else {
            $errorMsg = 'HTTP ' . $httpCode;
            if (!empty($curlError)) {
                $errorMsg .= ': ' . $curlError;

                // Check if it's a DNS error
                if (strpos($curlError, 'getaddrinfo') !== false || strpos($curlError, 'Could not resolve') !== false) {
                    $errorMsg = 'DNS resolution failed for api.telegram.org. Your hosting provider may have network restrictions. ' .
                               'Contact your hosting support to enable outbound HTTPS connections to Telegram API (api.telegram.org:443).';
                }
            }

            // Try to parse error from response
            $responseData = json_decode($response, true);
            if (isset($responseData['description'])) {
                $errorMsg = $responseData['description'];
            }

            writeAppLog("[Telegram] API error: {$errorMsg}");
            return [
                'success' => false,
                'error' => $errorMsg
            ];
        }

    } catch (Exception $e) {
        writeAppLog("[Telegram] Exception in sendTelegramMessage: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

?>
