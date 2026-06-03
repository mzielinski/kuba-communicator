<?php

// ============================================
// Feedback API Handler
// Sends user feedback to the admin email
// ============================================

require_once __DIR__ . '/../core/credentials.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/mailer.php';
require_once __DIR__ . '/../core/logger.php';
require_once __DIR__ . '/../core/i18n.php';

function handleSendFeedback(array $input): void {
    requireAuth();

    $lang = in_array($input['language'] ?? '', ['pl', 'en'], true)
        ? $input['language']
        : (in_array($_SESSION['language'] ?? 'pl', ['pl', 'en'], true) ? $_SESSION['language'] : 'pl');
    $email = strtolower(trim($_SESSION['email'] ?? ''));
    $user  = findUserByEmail($email);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => I18n::t('feedback_failed', $lang)]);
        return;
    }

    $type = strtolower(trim($input['type'] ?? ''));
    $allowedTypes = ['bug', 'feature', 'existing'];
    if (!in_array($type, $allowedTypes, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => I18n::t('feedback_invalid_type', $lang)]);
        return;
    }

    $message = trim((string)($input['message'] ?? ''));
    if ($message === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => I18n::t('feedback_invalid_message', $lang)]);
        return;
    }

    if (strlen($message) > 4000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => I18n::t('feedback_invalid_message', $lang)]);
        return;
    }

    $subjectBase = I18n::t('feedback_subject_' . $type, $lang);
    $subject = $subjectBase . ' — ' . $email;
    $appLink = getAppUrl() . '/';

    $html = renderEmailTemplate('feedback-email', [
        'title'          => $subjectBase,
        'greeting'       => I18n::t('feedback_email_greeting', $lang),
        'intro'          => I18n::t('feedback_email_intro', $lang),
        'sender_label'   => I18n::t('feedback_email_sender', $lang),
        'sender_email'   => esc($email),
        'type_label'     => I18n::t('feedback_email_type', $lang),
        'type'           => esc(I18n::t('feedback_type_' . $type, $lang)),
        'language_label' => I18n::t('feedback_email_language', $lang),
        'language'       => esc(strtoupper($lang)),
        'role_label'     => I18n::t('feedback_email_role', $lang),
        'role'           => esc($user['role'] ?? 'USER'),
        'message_label'  => I18n::t('feedback_email_message', $lang),
        'message'        => esc($message),
        'app_link'       => $appLink,
        'button_text'    => I18n::t('feedback_email_button', $lang),
        'footer'         => I18n::t('feedback_email_footer', $lang),
    ]);

    $sent = sendAppEmail(getAdminEmail(), $subject, $html, $email);
    if (!$sent) {
        writeAppLog("[Feedback] Failed to send feedback email | sender: {$email} | type: {$type} | lang: {$lang} | subject: {$subject}");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => I18n::t('feedback_failed', $lang)]);
        return;
    }

    echo json_encode(['success' => true, 'message' => I18n::t('feedback_sent', $lang)]);
}

function esc(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


