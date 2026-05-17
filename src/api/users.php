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
    sendAppEmail($email, I18n::t('email_confirm_subject', $language), buildConfirmationEmail($email, $confirmLink, $language));

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
    if ($user['predefined_words']) {
        // Copy predefined word templates
        $templateWordsFile  = __DIR__ . '/../../data/templates/words.json';
        $templateGlobalFile = __DIR__ . '/../../data/templates/global-words.json';
        $userWordsFile      = $dataDir . '/words.json';
        $userGlobalFile     = $dataDir . '/global-words.json';

        if (file_exists($templateWordsFile) && !file_exists($userWordsFile)) {
            copy($templateWordsFile, $userWordsFile);
        }
        if (file_exists($templateGlobalFile) && !file_exists($userGlobalFile)) {
            copy($templateGlobalFile, $userGlobalFile);
        }
    }
}

/**
 * Build HTML confirmation email body.
 */
function buildConfirmationEmail(string $email, string $confirmLink, string $lang): string {
    $greeting = I18n::t('email_confirm_greeting', $lang);
    $intro    = I18n::t('email_confirm_intro', $lang);
    $btn      = I18n::t('email_confirm_btn', $lang);
    $footer   = I18n::t('email_confirm_footer', $lang);
    return "
    <div style='font-family:sans-serif;max-width:600px;margin:0 auto;padding:30px'>
      <h2 style='color:#667eea'>🧑 Kuba</h2>
      <p>{$greeting}</p>
      <p>{$intro}</p>
      <p style='text-align:center;margin:30px 0'>
        <a href='{$confirmLink}' style='background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:16px'>{$btn}</a>
      </p>
      <p style='color:#888;font-size:12px'>{$footer}</p>
    </div>";
}

/**
 * Build HTML admin approval email.
 */
function buildAdminApprovalEmail(array $user, string $approvalLink): string {
    $email    = $user['email'];
    $lang     = $user['language'] ?? 'pl';
    $created  = $user['created_at'] ?? '';
    return "
    <div style='font-family:sans-serif;max-width:600px;margin:0 auto;padding:30px'>
      <h2 style='color:#667eea'>🧑 Kuba – Nowe konto / New account</h2>
      <p>A new user has confirmed their email and is waiting for your approval:</p>
      <table style='border-collapse:collapse;width:100%;margin:20px 0'>
        <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold'>Email</td><td style='padding:8px;border:1px solid #ddd'>{$email}</td></tr>
        <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold'>Language</td><td style='padding:8px;border:1px solid #ddd'>{$lang}</td></tr>
        <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold'>Registered</td><td style='padding:8px;border:1px solid #ddd'>{$created}</td></tr>
      </table>
      <p>The account is currently <strong>WAITING_FOR_APPROVAL</strong>. Click below to activate it (you have 24h before a reminder will be sent):</p>
      <p style='text-align:center;margin:30px 0'>
        <a href='{$approvalLink}' style='background:linear-gradient(135deg,#28a745,#20c997);color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:16px'>
          ✅ Approve account
        </a>
      </p>
      <p style='color:#888;font-size:12px'>You can also approve or manage users from the admin panel in the application.</p>
    </div>";
}

/**
 * Send account approved notification to user.
 */
function sendApprovedEmail(array $user): void {
    $lang     = $user['language'] ?? 'pl';
    $appLink  = getAppUrl() . '/public/login.html';
    $subject  = I18n::t('email_approved_subject', $lang);
    $greeting = I18n::t('email_approved_greeting', $lang);
    $intro    = I18n::t('email_approved_intro', $lang);
    $btn      = I18n::t('email_approved_btn', $lang);
    $html     = "
    <div style='font-family:sans-serif;max-width:600px;margin:0 auto;padding:30px'>
      <h2 style='color:#667eea'>🧑 Kuba</h2>
      <p>{$greeting}</p><p>{$intro}</p>
      <p style='text-align:center;margin:30px 0'>
        <a href='{$appLink}' style='background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:16px'>{$btn}</a>
      </p>
    </div>";
    sendAppEmail($user['email'], $subject, $html);
}
?>

