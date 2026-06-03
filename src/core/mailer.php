<?php

// ============================================
// Email Sending Helper
// Reads mail config from data/.env
// (alongside the Telegram token)
// ============================================

/**
 * Load config from data/.env (shared with Telegram config).
 */
function loadAppConfig(): array {
    static $config = null;
    if ($config !== null) return $config;

    $config = [
        'ADMIN_EMAIL'    => 'admin@localhost',
        'APP_URL'        => 'http://localhost',
        'SMTP_FROM'      => 'noreply@localhost',
        'SMTP_FROM_NAME' => 'Kuba Communication System',
    ];

    // data/.env — shared config file (Telegram + mail)
    $envPath = __DIR__ . '/../../data/.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') continue;
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key   = trim($key);
                $value = trim($value, " \t\n\r\"'");
                if (!empty($key)) $config[$key] = $value;
            }
        }
    }

    return $config;
}

function getAdminEmail(): string {
    return loadAppConfig()['ADMIN_EMAIL'] ?? 'admin@localhost';
}

function getAppUrl(): string {
    return rtrim(loadAppConfig()['APP_URL'] ?? 'http://localhost', '/');
}

/**
 * Load an HTML email template from data/templates/emails/ and substitute {{placeholders}}.
 *
 * @param string $templateName  Filename without .html extension
 * @param array  $variables     Key → value substitutions for {{key}} placeholders
 */
function renderEmailTemplate(string $templateName, array $variables): string {
    $templatePath = __DIR__ . '/../../data/templates/emails/' . $templateName . '.html';

    if (!file_exists($templatePath)) {
        error_log("[Email Template] Template not found: {$templatePath}");
        return '';
    }

    $html = file_get_contents($templatePath);
    foreach ($variables as $key => $value) {
        $html = str_replace('{{' . $key . '}}', $value, $html);
    }
    return $html;
}

/**
 * Send an HTML email via PHP's mail().
 * For production configure sendmail or an SMTP relay in php.ini.
 */
function sendAppEmail(string $to, string $subject, string $html, ?string $replyTo = null): bool {
    $cfg      = loadAppConfig();
    $from     = $cfg['SMTP_FROM']      ?? 'noreply@localhost';
    $fromName = $cfg['SMTP_FROM_NAME'] ?? 'Kuba';

    $headers = implode("\r\n", [
        "From: {$fromName} <{$from}>",
        'Reply-To: ' . ($replyTo ?: $from),
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8",
        "X-Mailer: PHP/" . phpversion(),
    ]);

    $result = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, $headers);
    if (!$result) {
        error_log("[Mailer] Failed to send email to {$to} | Subject: {$subject}");
    }
    return (bool)$result;
}
?>
