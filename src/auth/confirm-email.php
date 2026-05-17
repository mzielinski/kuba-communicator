<?php

// ============================================
// Email Confirmation & Admin Approval Handler
// Accessed via browser links from emails
// ============================================

session_start();

require_once __DIR__ . '/../core/credentials.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/mailer.php';
require_once __DIR__ . '/../core/i18n.php';

$action = $_GET['action'] ?? 'confirm';
$token  = trim($_GET['token'] ?? '');

if (empty($token)) {
    renderPage('error', 'pl', 'Brak tokenu / Missing token', 'Link jest nieprawidłowy. / The link is invalid.');
    exit;
}

// ── User email confirmation ────────────────────────────────────────────────────
if ($action === 'confirm') {
    $user = findUserByConfirmationToken($token);

    if (!$user) {
        $lang = 'pl';
        renderPage('error', $lang, I18n::t('confirm_invalid_link', $lang), I18n::t('confirm_invalid_body', $lang));
        exit;
    }

    $lang = $user['language'] ?? 'pl';

    if ($user['status'] !== 'WAITING_FOR_CONFIRMATION') {
        renderPage('info', $lang, I18n::t('confirm_already_done', $lang), I18n::t('confirm_already_body', $lang));
        exit;
    }

    $ok = updateUserByEmail($user['email'], ['status' => 'WAITING_FOR_APPROVAL', 'confirmation_token' => null]);

    if (!$ok) {
        renderPage('error', $lang, I18n::t('confirm_error', $lang), I18n::t('confirm_error_body', $lang));
        exit;
    }

    // Notify admin
    $approvalLink = getAppUrl() . '/src/auth/confirm-email.php?action=approve&token=' . urlencode($user['admin_approval_token']);
    error_log("[ConfirmEmail] Sending admin notification email for user: {$user['email']} to admin: " . getAdminEmail());
    $adminMailSent = sendAppEmail(getAdminEmail(), 'Kuba: New account awaiting approval – ' . $user['email'], buildAdminNotificationEmail($user, $approvalLink));
    error_log("[ConfirmEmail] Admin notification email result: " . ($adminMailSent ? 'SUCCESS' : 'FAILED') . " | user: {$user['email']}");

    renderPage('success', $lang, I18n::t('confirm_success', $lang), I18n::t('confirm_success_body', $lang));
    exit;
}

// ── Admin approval via email link ──────────────────────────────────────────────
if ($action === 'approve') {
    $user = findUserByApprovalToken($token);

    if (!$user) {
        renderPage('error', 'en', I18n::t('approve_invalid_link', 'en'), I18n::t('approve_invalid_body', 'en'));
        exit;
    }

    $lang = $user['language'] ?? 'pl';

    if ($user['status'] !== 'WAITING_FOR_APPROVAL') {
        renderPage('info', $lang, I18n::t('approve_already_done', 'en'), 'This account has already been processed (status: ' . $user['status'] . ').');
        exit;
    }

    $ok = updateUserByEmail($user['email'], ['status' => 'ACTIVE', 'admin_approval_token' => null]);

    if (!$ok) {
        renderPage('error', $lang, 'Error', 'Failed to activate the account. Please try again.');
        exit;
    }

    activateUserData($user);
    error_log("[ConfirmEmail] Sending approval notification email to user: {$user['email']}");
    sendApprovedEmail($user);

    renderPage('success', 'en', I18n::t('approve_success', 'en'),
        "The account for <strong>{$user['email']}</strong> has been activated. The user will receive an email notification.");
    exit;
}

renderPage('error', 'pl', 'Nieznana operacja', 'Nieznana operacja.');


// ── HTML Renderer ──────────────────────────────────────────────────────────────

function renderPage(string $type, string $lang, string $heading, string $body): void {
    $colors   = ['success' => ['bg' => '#e0ffe0', 'border' => '#28a745', 'icon' => '✅'],
                 'error'   => ['bg' => '#ffe0e0', 'border' => '#dc3545', 'icon' => '❌'],
                 'info'    => ['bg' => '#e0f0ff', 'border' => '#667eea', 'icon' => 'ℹ️']];
    $c        = $colors[$type] ?? $colors['info'];
    $loginUrl = getAppUrl() . '/login.html';
    $loginLbl = I18n::t('back_to_login', $lang);

    echo "<!DOCTYPE html>
<html lang='{$lang}'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>Kuba</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
           background:linear-gradient(135deg,#667eea,#764ba2);
           min-height:100vh; display:flex; justify-content:center; align-items:center; padding:20px; }
    .card { background:#fff; border-radius:12px; box-shadow:0 20px 60px rgba(0,0,0,.3);
            max-width:480px; width:100%; padding:40px; text-align:center; }
    .icon { font-size:48px; margin-bottom:20px; }
    h2 { color:#333; margin-bottom:16px; }
    .msg { background:{$c['bg']}; border:2px solid {$c['border']}; border-radius:8px;
           padding:20px; color:#333; line-height:1.6; margin-bottom:24px; }
    a.btn { display:inline-block; background:linear-gradient(135deg,#667eea,#764ba2);
            color:#fff; padding:12px 24px; border-radius:6px; text-decoration:none;
            font-weight:600; transition:.2s; }
    a.btn:hover { opacity:.9; }
  </style>
</head>
<body>
  <div class='card'>
    <div class='icon'>{$c['icon']}</div>
    <h2>{$heading}</h2>
    <div class='msg'>{$body}</div>
    <a class='btn' href='{$loginUrl}'>{$loginLbl}</a>
  </div>
</body>
</html>";
}

function activateUserData(array $user): void {
    $dataDir = __DIR__ . '/../../data/' . $user['data_dir'];
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    if (!empty($user['predefined_words'])) {
        $tplWords  = __DIR__ . '/../../data/templates/words.json';
        $tplGlobal = __DIR__ . '/../../data/templates/global-words.json';
        $uWords    = $dataDir . '/words.json';
        $uGlobal   = $dataDir . '/global-words.json';
        if (file_exists($tplWords)  && !file_exists($uWords))  copy($tplWords, $uWords);
        if (file_exists($tplGlobal) && !file_exists($uGlobal)) copy($tplGlobal, $uGlobal);
    }
}

function buildAdminNotificationEmail(array $user, string $approvalLink): string {
    return renderEmailTemplate('admin-notification', [
        'title'           => 'Kuba – New Account Awaiting Approval',
        'email'           => $user['email'],
        'language'        => $user['language'] ?? 'pl',
        'created_at'      => $user['created_at'] ?? '',
        'approval_link'   => $approvalLink,
    ]);
}

function sendApprovedEmail(array $user): void {
    $lang     = $user['language'] ?? 'pl';
    $appLink  = getAppUrl() . '/login.html';
    $subject  = I18n::t('email_approved_subject', $lang);

    $html = renderEmailTemplate('approved-email', [
        'title'       => $subject,
        'greeting'    => I18n::t('email_approved_greeting', $lang),
        'intro'       => I18n::t('email_approved_intro', $lang),
        'app_link'    => $appLink,
        'button_text' => I18n::t('email_approved_btn', $lang),
    ]);

    error_log("[sendApprovedEmail] Sending approved email | To: {$user['email']} | Subject: {$subject} | Lang: {$lang}");
    $result = sendAppEmail($user['email'], $subject, $html);
    error_log("[sendApprovedEmail] Result: " . ($result ? 'SUCCESS' : 'FAILED') . " | To: {$user['email']}");
}
?>

