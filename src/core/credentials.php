<?php

// ============================================
// Credentials File Management
// Thread-safe read/write using flock()
// ============================================

$credentialsFile = __DIR__ . '/../../data/credentials.json';

/**
 * Read credentials.json with a shared lock.
 */
function readCredentials(): ?array {
    global $credentialsFile;
    if (!file_exists($credentialsFile)) return null;

    $fp = fopen($credentialsFile, 'r');
    if (!$fp) return null;

    flock($fp, LOCK_SH);
    $content = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return json_decode($content, true);
}

/**
 * Write credentials.json with an exclusive lock.
 */
function writeCredentials(array $data): bool {
    global $credentialsFile;

    $fp = fopen($credentialsFile, 'c+');
    if (!$fp) return false;

    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    $written = fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $written !== false;
}

/**
 * Find a user record by email (case-insensitive).
 */
function findUserByEmail(string $email): ?array {
    $data  = readCredentials();
    if (!$data || !isset($data['users'])) return null;
    $email = strtolower(trim($email));
    foreach ($data['users'] as $user) {
        if (strtolower($user['email'] ?? '') === $email) {
            return $user;
        }
    }
    return null;
}

/**
 * Find a user by their email confirmation token.
 */
function findUserByConfirmationToken(string $token): ?array {
    $data = readCredentials();
    if (!$data || !isset($data['users'])) return null;
    foreach ($data['users'] as $user) {
        if (!empty($user['confirmation_token']) && $user['confirmation_token'] === $token) {
            return $user;
        }
    }
    return null;
}

/**
 * Find a user by the admin approval token.
 */
function findUserByApprovalToken(string $token): ?array {
    $data = readCredentials();
    if (!$data || !isset($data['users'])) return null;
    foreach ($data['users'] as $user) {
        if (!empty($user['admin_approval_token']) && $user['admin_approval_token'] === $token) {
            return $user;
        }
    }
    return null;
}

/**
 * Update fields on a user record (matched by email) and persist.
 */
function updateUserByEmail(string $email, array $updates): bool {
    $data  = readCredentials();
    if (!$data || !isset($data['users'])) return false;
    $email = strtolower(trim($email));
    $found = false;
    foreach ($data['users'] as &$user) {
        if (strtolower($user['email'] ?? '') === $email) {
            foreach ($updates as $k => $v) {
                $user[$k] = $v;
            }
            $user['updated_at'] = date('c');
            $found = true;
            break;
        }
    }
    unset($user);
    if (!$found) return false;
    return writeCredentials($data);
}

/**
 * Append a new user to credentials.json.
 */
function addUser(array $newUser): bool {
    $data = readCredentials();
    if (!$data) $data = ['users' => []];
    if (!isset($data['users'])) $data['users'] = [];
    $data['users'][] = $newUser;
    return writeCredentials($data);
}

/**
 * Derive a safe filesystem directory name from an email address.
 * e.g. "john.doe@example.com" → "john_doe_at_example_com"
 */
function emailToDataDir(string $email): string {
    $email = strtolower(trim($email));
    $email = str_replace(['@', '.', '+', '-'], ['_at_', '_', '_', '_'], $email);
    $email = preg_replace('/[^a-z0-9_]/', '', $email);
    return substr($email, 0, 64);
}

/**
 * Password hashing (bcrypt cost 12).
 */
function hashPassword(string $plainText): string {
    return password_hash($plainText, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a plain-text password against stored hash.
 * Supports legacy plain-text passwords for migration.
 */
function verifyPassword(string $plainText, string $stored): bool {
    if (password_verify($plainText, $stored)) return true;
    // Legacy plain-text fallback
    if ($stored === $plainText) return true;
    return false;
}

/**
 * Generate a cryptographically secure random hex token (64 chars).
 */
function generateToken(): string {
    return bin2hex(random_bytes(32));
}
?>

