<?php

// ============================================
// User Management API
// Handles registration, approval, deletion, listing
// ============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

require_once __DIR__ . '/../core/credentials.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/mailer.php';
require_once __DIR__ . '/../core/i18n.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── Routes ────────────────────────────────────────────────────────────────────

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    switch ($action) {
        case 'register':      handleRegister($input);      break;
        case 'approve-user':  handleApproveUser($input);   break;
        case 'delete-user':   handleDeleteUser($input);    break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}

if ($method === 'GET') {
    switch ($action) {
        case 'list-users':       handleListUsers();              break;
        case 'get-current-user': handleGetCurrentUser();         break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);

// ── Handlers ──────────────────────────────────────────────────────────────────

/**
 * POST ?action=register
 * Body: { email, password, passwordConfirm, language, predefined_words }
 */
function handleRegister(array $input): void {
    $email          = strtolower(trim($input['email'] ?? ''));
    $password       = $input['password'] ?? '';
    $passwordConfirm = $input['passwordConfirm'] ?? '';
    $language       = in_array($input['language'] ?? '', ['pl', 'en']) ? $input['language'] : 'pl';
    $predefinedWords = !empty($input['predefined_words']);
    $lang           = $input['lang'] ?? 'pl'; // UI language for error messages

    // — Validation —
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => I18n::t('reg_invalid_email', $lang)]);
        return;
    }

    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => I18n::t('reg_pass_too_short', $lang)]);
        return;
    }

    if ($password !== $passwordConfirm) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => I18n::t('reg_pass_mismatch', $lang)]);
        return;
    }

    // — Check for existing email —
    if (findUserByEmail($email)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => I18n::t('reg_email_exists', $lang)]);
        return;
    }

    // — Build new user record —
    $confirmToken  = generateToken();
    $approvalToken = generateToken();
    $dataDir       = emailToDataDir($email);

    // Ensure data_dir is unique (append number if needed)
    $existing = readCredentials();
    $existingDirs = array_column($existing['users'] ?? [], 'data_dir');
    $baseDir = $dataDir;
    $counter = 1;
    while (in_array($dataDir, $existingDirs)) {
        $dataDir = $baseDir . '_' . $counter++;
    }

    $now = date('c');
    $newUser = [
        'email'               => $email,
        'password'            => hashPassword($password),
        'role'                => 'USER',
        'status'              => 'WAITING_FOR_CONFIRMATION',
        'language'            => $language,
        'predefined_words'    => $predefinedWords,
        'data_dir'            => $dataDir,
        'confirmation_token'  => $confirmToken,
        'admin_approval_token' => $approvalToken,
        'created_at'          => $now,
        'updated_at'          => $now,
    ];

    if (!addUser($newUser)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => I18n::t('reg_failed', $lang)]);
        return;
    }

    // — Send confirmation email —
    $appUrl      = getAppUrl();
    $confirmLink = "{$appUrl}/src/auth/confirm-email.php?token={$confirmToken}";
    $confirmHtml = renderEmailTemplate('confirmation-email', [
        'title'       => I18n::t('email_confirm_subject',   $language),
        'greeting'    => I18n::t('email_confirm_greeting',  $language),
        'intro'       => I18n::t('email_confirm_intro',     $language),
        'confirm_link'=> $confirmLink,
        'button_text' => I18n::t('email_confirm_btn',       $language),
        'footer'      => I18n::t('email_confirm_footer',    $language),
    ]);
    sendAppEmail($email, I18n::t('email_confirm_subject', $language), $confirmHtml);

    http_response_code(201);
    echo json_encode(['success' => true, 'message' => I18n::t('reg_success', $lang)]);
}

/**
 * POST ?action=approve-user   (admin only)
 * Body: { email }
 */
function handleApproveUser(array $input): void {
    requireAdmin();

    $email = strtolower(trim($input['email'] ?? ''));
    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email is required']);
        return;
    }

    $user = findUserByEmail($email);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        return;
    }

    if ($user['status'] !== 'WAITING_FOR_APPROVAL') {
        http_response_code(400);
        echo json_encode(['error' => 'User is not waiting for approval (status: ' . $user['status'] . ')']);
        return;
    }

    $ok = updateUserByEmail($email, [
        'status'              => 'ACTIVE',
        'admin_approval_token' => null,
    ]);

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to approve user']);
        return;
    }

    // Create data directory and copy predefined words if requested
    activateUserData($user);

    // Notify user
    sendApprovedEmail($user);

    echo json_encode(['success' => true, 'message' => "User {$email} approved and activated."]);
}

/**
 * POST ?action=delete-user
 * Body: { email }  – admin can pass any email; non-admin can only delete themselves.
 */
function handleDeleteUser(array $input): void {
    requireAuth();

    $role         = getCurrentRole();
    $sessionEmail = $_SESSION['email'] ?? '';
    $targetEmail  = strtolower(trim($input['email'] ?? $sessionEmail));

    // Non-admin can only delete their own account
    if ($role !== 'ADMIN' && $targetEmail !== $sessionEmail) {
        http_response_code(403);
        echo json_encode(['error' => 'You can only delete your own account']);
        return;
    }

    $user = findUserByEmail($targetEmail);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        return;
    }

    // Prevent deleting the last admin
    if ($user['role'] === 'ADMIN') {
        $data = readCredentials();
        $adminCount = 0;
        foreach ($data['users'] as $u) {
            if (($u['role'] ?? '') === 'ADMIN' && ($u['status'] ?? '') !== 'DELETED') {
                $adminCount++;
            }
        }
        if ($adminCount <= 1) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete the last admin account']);
            return;
        }
    }

    $ok = updateUserByEmail($targetEmail, ['status' => 'DELETED']);

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete user']);
        return;
    }

    // If user deleted their own account, destroy session
    if ($targetEmail === $sessionEmail) {
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Account deleted. You have been logged out.', 'logout' => true]);
    } else {
        echo json_encode(['success' => true, 'message' => "User {$targetEmail} deleted."]);
    }
}

/**
 * GET ?action=list-users
 * Admin sees all users; regular users see only their own record.
 */
function handleListUsers(): void {
    requireAuth();

    $role         = getCurrentRole();
    $sessionEmail = $_SESSION['email'] ?? '';
    $data         = readCredentials();

    $users = [];
    foreach (($data['users'] ?? []) as $u) {
        // Hide DELETED users from non-admins
        if ($role !== 'ADMIN' && ($u['status'] ?? '') === 'DELETED') continue;
        // Non-admin only sees own record
        if ($role !== 'ADMIN' && strtolower($u['email'] ?? '') !== strtolower($sessionEmail)) continue;

        $users[] = [
            'email'      => $u['email'] ?? '',
            'role'       => $u['role'] ?? 'USER',
            'status'     => $u['status'] ?? 'ACTIVE',
            'language'   => $u['language'] ?? 'pl',
            'created_at' => $u['created_at'] ?? '',
            'updated_at' => $u['updated_at'] ?? '',
        ];
    }

    echo json_encode(['success' => true, 'users' => $users]);
}

/**
 * GET ?action=get-current-user
 */
function handleGetCurrentUser(): void {
    requireAuth();
    $email = $_SESSION['email'] ?? '';
    $user  = findUserByEmail($email);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        return;
    }
    echo json_encode([
        'success' => true,
        'user'    => [
            'email'      => $user['email'],
            'role'       => $user['role'],
            'status'     => $user['status'],
            'language'   => $user['language'],
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at'],
        ],
    ]);
}

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Create data directory and copy predefined words if configured.
 */
function activateUserData(array $user): void {
    $dataDir = __DIR__ . '/../../data/' . $user['data_dir'];
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    // Create preferences.json with the user's chosen language
    $userPreferencesFile = $dataDir . '/preferences.json';
    if (!file_exists($userPreferencesFile)) {
        $defaults = [
            'selectedAlarmDeviceId'  => '',
            'alarmType'              => 'high',
            'alarmDuration'          => 6,
            'dwellTimeMs'            => 2000,
            'dwellEnabled'           => false,
            'darkModeEnabled'        => true,
            'telegramEnabled'        => false,
            'telegramChats'          => [],
            'telegramSelectedChatId' => '',
            'language'               => $user['language'] ?? 'pl',
        ];
        file_put_contents($userPreferencesFile, json_encode($defaults, JSON_PRETTY_PRINT));
    }

    if ($user['predefined_words']) {
        // Copy predefined word templates from language-specific subdirectory
        $lang = $user['language'] ?? 'pl';
        $langWordsFile  = __DIR__ . '/../../data/templates/' . $lang . '/words.json';
        $langGlobalFile = __DIR__ . '/../../data/templates/' . $lang . '/global-words.json';
        $userWordsFile  = $dataDir . '/words.json';
        $userGlobalFile = $dataDir . '/global-words.json';

        if (file_exists($langWordsFile) && !file_exists($userWordsFile)) {
            copy($langWordsFile, $userWordsFile);
        }
        if (file_exists($langGlobalFile) && !file_exists($userGlobalFile)) {
            copy($langGlobalFile, $userGlobalFile);
        }
    }
}

/**
 * Send account approved notification to user.
 */
function sendApprovedEmail(array $user): void {
    $lang    = $user['language'] ?? 'pl';
    $appLink = getAppUrl() . '/login.html';
    $subject = I18n::t('email_approved_subject', $lang);
    $html    = renderEmailTemplate('approved-email', [
        'greeting'    => I18n::t('email_approved_greeting', $lang),
        'intro'       => I18n::t('email_approved_intro',    $lang),
        'app_link'    => $appLink,
        'button_text' => I18n::t('email_approved_btn',      $lang),
    ]);
    sendAppEmail($user['email'], $subject, $html);
}
?>

