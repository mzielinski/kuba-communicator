<?php

// ============================================
// Email Sending Helper
// Reads mail config from data/.env
// (alongside the Telegram token)
// ============================================

require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/config.php';

/**
 * Load config from data/.env (shared with Telegram config).
 */
function loadAppConfig(): array {
    return loadDataEnvConfig([
        'ADMIN_EMAIL'    => 'admin@localhost',
        'APP_URL'        => 'http://localhost',
        'SMTP_FROM'      => 'noreply@localhost',
        'SMTP_FROM_NAME' => 'Kuba Communication System',
        'SMTP_HOST'      => '',
        'SMTP_PORT'      => '587',
        'SMTP_ENCRYPTION'=> '',
        'SMTP_USERNAME'  => '',
        'SMTP_PASSWORD'  => '',
        'SMTP_AUTH'      => 'true',
        'SMTP_TIMEOUT'   => '15',
    ]);
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
        writeAppLog("[Email Template] Template not found: {$templatePath}");
        return '';
    }

    $html = file_get_contents($templatePath);
    foreach ($variables as $key => $value) {
        $html = str_replace('{{' . $key . '}}', $value, $html);
    }
    return $html;
}

/**
 * Send an HTML email via SMTP when configured.
 * Falls back to PHP's mail() only when no SMTP host is configured.
 */
function sendAppEmail(string $to, string $subject, string $html, ?string $replyTo = null): bool {
    $cfg      = loadAppConfig();
    $from     = trim((string)($cfg['SMTP_FROM'] ?? 'noreply@localhost'));
    $fromName = $cfg['SMTP_FROM_NAME'] ?? 'Kuba';
    $smtpUser = trim((string)($cfg['SMTP_USERNAME'] ?? ''));

    if ($from === '' || $from === 'noreply@localhost') {
        $from = $smtpUser !== '' ? $smtpUser : 'noreply@localhost';
    }

    $smtpHost = trim((string)($cfg['SMTP_HOST'] ?? ''));
    if ($smtpHost !== '') {
        return sendAppEmailViaSmtp($cfg, $to, $subject, $html, $replyTo);
    }

    $headers = implode("\r\n", [
        "From: {$fromName} <{$from}>",
        'Reply-To: ' . ($replyTo ?: $from),
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8",
        "X-Mailer: PHP/" . phpversion(),
    ]);

    $result = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, $headers);
    if (!$result) {
        writeAppLog("[Mailer] Failed to send email to {$to} | Subject: {$subject}");
    }
    return (bool)$result;
}

function sendAppEmailViaSmtp(array $cfg, string $to, string $subject, string $html, ?string $replyTo = null): bool {
    $host = trim((string)($cfg['SMTP_HOST'] ?? ''));
    $port = (int)($cfg['SMTP_PORT'] ?? 587);
    $timeout = (int)($cfg['SMTP_TIMEOUT'] ?? 15);
    $encryption = strtolower(trim((string)($cfg['SMTP_ENCRYPTION'] ?? '')));
    $fromName = trim((string)($cfg['SMTP_FROM_NAME'] ?? 'Kuba'));
    $username = trim((string)($cfg['SMTP_USERNAME'] ?? ''));
    $password = (string)($cfg['SMTP_PASSWORD'] ?? '');
    $authEnabled = filter_var($cfg['SMTP_AUTH'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
    $useAuth = $authEnabled && $username !== '';

    $from = trim((string)($cfg['SMTP_FROM'] ?? 'noreply@localhost'));
    if ($from === '' || $from === 'noreply@localhost') {
        $from = $username !== '' ? $username : $from;
    }

    $envelopeFrom = $username !== '' ? $username : $from;

    if ($username === '' && $useAuth) {
        $username = $from;
    }

    if ($encryption === '') {
        $encryption = $port === 465 ? 'ssl' : 'tls';
    }

    $remoteHost = ($encryption === 'ssl') ? "ssl://{$host}" : $host;
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
            'peer_name' => $host,
            'SNI_enabled' => true,
        ],
    ]);

    $fp = @stream_socket_client($remoteHost . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    if (!$fp) {
        writeAppLog("[Mailer] SMTP connection failed to {$host}:{$port} | {$errno} {$errstr}");
        return false;
    }

    stream_set_timeout($fp, $timeout);

    $expect = function (array $codes, string $context = '') use ($fp, $host): array {
        $response = smtpReadResponse($fp);
        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            writeAppLog("[Mailer] SMTP {$host} unexpected response {$response} {$context}");
            return ['ok' => false, 'response' => $response, 'code' => $code];
        }
        return ['ok' => true, 'response' => $response, 'code' => $code];
    };

    $send = function (string $command, array $expectedCodes, string $context = '') use ($fp, $expect, $host): bool {
        fwrite($fp, $command . "\r\n");
        $result = $expect($expectedCodes, $context ?: $command);
        return (bool)$result['ok'];
    };

    $greeting = $expect([220], 'greeting');
    if (!$greeting['ok']) {
        fclose($fp);
        return false;
    }

    $heloHost = preg_replace('/[^A-Za-z0-9.\-]/', '', parse_url(loadAppConfig()['APP_URL'] ?? 'http://localhost', PHP_URL_HOST) ?: 'localhost');
    if (!$send('EHLO ' . $heloHost, [250], 'EHLO')) {
        fclose($fp);
        return false;
    }

    if ($encryption === 'tls') {
        if (!$send('STARTTLS', [220], 'STARTTLS')) {
            fclose($fp);
            return false;
        }
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            writeAppLog("[Mailer] SMTP STARTTLS negotiation failed for {$host}:{$port}");
            fclose($fp);
            return false;
        }
        if (!$send('EHLO ' . $heloHost, [250], 'EHLO after STARTTLS')) {
            fclose($fp);
            return false;
        }
    }

    if ($useAuth) {
        if (!$send('AUTH LOGIN', [334], 'AUTH LOGIN')) {
            fclose($fp);
            return false;
        }
        if (!$send(base64_encode($username), [334], 'AUTH username')) {
            fclose($fp);
            return false;
        }
        if (!$send(base64_encode($password), [235], 'AUTH password')) {
            fclose($fp);
            return false;
        }
    }

    $headers = buildEmailHeaders($from, $fromName, $to, $subject, $replyTo, $html);
    $body = smtpNormalizeBody($headers['body']);
    $rawMessage = $headers['headers'] . "\r\n\r\n" . $body;

    if (!$send('MAIL FROM:<' . smtpSanitizeAddress($envelopeFrom) . '>', [250], 'MAIL FROM')) {
        fclose($fp);
        return false;
    }

    if (!$send('RCPT TO:<' . smtpSanitizeAddress($to) . '>', [250, 251], 'RCPT TO')) {
        fclose($fp);
        return false;
    }

    if (!$send('DATA', [354], 'DATA')) {
        fclose($fp);
        return false;
    }

    $rawMessage = smtpDotStuff($rawMessage);
    fwrite($fp, $rawMessage . "\r\n.\r\n");
    $dataResult = $expect([250], 'message body');
    if (!$dataResult['ok']) {
        fclose($fp);
        return false;
    }

    fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return true;
}

function buildEmailHeaders(string $from, string $fromName, string $to, string $subject, ?string $replyTo, string $html): array {
    $replyToAddress = smtpSanitizeAddress($replyTo ?: $from);

    $headers = implode("\r\n", [
        'Date: ' . gmdate('D, d M Y H:i:s O'),
        'From: ' . smtpEncodeHeader($fromName) . ' <' . smtpSanitizeAddress($from) . '>',
        'To: <' . smtpSanitizeAddress($to) . '>',
        'Reply-To: <' . $replyToAddress . '>',
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . (parse_url(loadAppConfig()['APP_URL'] ?? 'http://localhost', PHP_URL_HOST) ?: 'localhost') . '>',
        'Subject: ' . smtpEncodeHeader($subject),
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        'X-Mailer: PHP/' . phpversion(),
    ]);

    return [
        'headers' => $headers,
        'body' => chunk_split(base64_encode($html), 76, "\r\n"),
    ];
}

function smtpReadResponse($fp): string {
    $response = '';
    while (!feof($fp)) {
        $line = fgets($fp, 515);
        if ($line === false) break;
        $response .= $line;
        if (preg_match('/^\d{3}\s/', $line)) {
            break;
        }
    }
    return trim($response);
}

function smtpNormalizeBody(string $body): string {
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    return str_replace("\n", "\r\n", $body);
}

function smtpDotStuff(string $message): string {
    $message = smtpNormalizeBody($message);
    return preg_replace('/^\./m', '..', $message) ?? $message;
}

function smtpSanitizeAddress(string $address): string {
    return trim(str_replace(["\r", "\n"], '', $address));
}

function smtpEncodeHeader(string $value): string {
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}
?>
