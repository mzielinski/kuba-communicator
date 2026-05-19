<?php

// ============================================
// Login Authentication System
// ============================================

require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/credentials.php';
require_once __DIR__ . '/../core/i18n.php';

header('Content-Type: application/json');
setCorsHeaders();
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

initializeSession();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── Helpers ───────────────────────────────────────────────────────────────────

function createSession(array $user, string $lang = 'pl'): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = bin2hex(random_bytes(16));
    $_SESSION['email']      = $user['email'];
    $_SESSION['data_dir']   = $user['data_dir'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['language']   = $lang;
    $_SESSION['login_time'] = time();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['email']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'user_id' => $_SESSION['user_id'],
        'email'   => $_SESSION['email'],
        'role'    => $_SESSION['role'] ?? 'USER',
        'data_dir'=> $_SESSION['data_dir'] ?? '',
    ];
}

// ── Routes ────────────────────────────────────────────────────────────────────

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // ── Login ────────────────────────────────────────────────────────────────
    if ($action === 'login' || !$action) {
        $email    = strtolower(trim($input['email'] ?? ''));
        $password = $input['password'] ?? '';
        $lang     = in_array($input['lang'] ?? '', ['pl', 'en']) ? $input['lang'] : 'pl';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => I18n::t('err_fields_required', $lang)]);
            exit;
        }

        $user = findUserByEmail($email);

        if (!$user || !verifyPassword($password, $user['password'] ?? '')) {
            error_log("Failed login attempt for email: {$email}");
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => I18n::t('err_invalid_credentials', $lang)]);
            exit;
        }

        $status = $user['status'] ?? 'ACTIVE';
        if ($status !== 'ACTIVE') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => I18n::statusMessage($status, $lang), 'status' => $status]);
            exit;
        }

        createSession($user, $lang);
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => I18n::t('login_success', $lang), 'user' => getCurrentUser()]);
        exit;
    }

    // ── Logout ───────────────────────────────────────────────────────────────
    if ($action === 'logout') {
        session_destroy();
        http_response_code(200);
        echo json_encode(['success' => true]);
        exit;
    }
}

if ($method === 'GET') {
    // ── Check session ────────────────────────────────────────────────────────
    if ($action === 'check-session') {
        http_response_code(200);
        echo json_encode(['loggedIn' => isLoggedIn(), 'user' => getCurrentUser()]);
        exit;
    }

    // ── Demo auto-login ──────────────────────────────────────────────────────
    if ($action === 'demo-login') {
        $lang = in_array($_GET['lang'] ?? '', ['pl', 'en']) ? $_GET['lang'] : 'pl';
        $data = readCredentials();
        $demo = null;
        foreach (($data['users'] ?? []) as $u) {
            if (($u['role'] ?? '') === 'DEMO' && ($u['status'] ?? '') === 'ACTIVE') {
                $demo = $u;
                break;
            }
        }
        if ($demo) {
            createSession($demo, $lang);
            $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $basePath = rtrim(dirname($requestPath), '/');
            header('Location: ' . $basePath . '/index.html');
            exit;
        }
        http_response_code(404);
        echo json_encode(['error' => 'Demo account not available']);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
?>

