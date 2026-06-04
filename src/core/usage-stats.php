<?php

// ============================================
// Usage statistics storage
// File-based per-user click tracking
// ============================================

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/auth.php';

/**
 * Return the absolute path to a user's stats file, or null if the data dir is invalid.
 */
function getUsageStatsFilePath(string $dataDir): ?string {
    $dataDir = trim($dataDir);
    if ($dataDir === '' || !preg_match('/^[a-zA-Z0-9_-]+$/', $dataDir)) {
        return null;
    }

    return __DIR__ . '/../../data/' . $dataDir . '/stats.json';
}

/**
 * Ensure a stats file parent directory exists.
 */
function ensureUsageStatsDirectory(string $filePath): bool {
    $dir = dirname($filePath);
    if (is_dir($dir)) {
        return true;
    }

    return mkdir($dir, 0755, true);
}

/**
 * Default stats payload.
 */
function defaultUsageStats(): array {
    return [
        'total_clicks'    => 0,
        'first_click_at'  => null,
        'last_click_at'   => null,
    ];
}

/**
 * Read a user's stats file.
 */
function readUsageStatsByDataDir(string $dataDir): array {
    $path = getUsageStatsFilePath($dataDir);
    if (!$path || !file_exists($path)) {
        return defaultUsageStats();
    }

    $raw = json_decode((string)file_get_contents($path), true);
    if (!is_array($raw)) {
        return defaultUsageStats();
    }

    return normalizeUsageStats($raw);
}

/**
 * Write a user's stats file using an exclusive lock.
 */
function writeUsageStatsByDataDir(string $dataDir, array $stats): bool {
    $path = getUsageStatsFilePath($dataDir);
    if (!$path || !ensureUsageStatsDirectory($path)) {
        return false;
    }

    $stats = normalizeUsageStats($stats);
    $fp = fopen($path, 'c+');
    if (!$fp) {
        return false;
    }

    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);

    $written = fwrite($fp, json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $written !== false;
}

/**
 * Load, update and persist usage stats for the current session user.
 */
function recordUsageEvent(array $event): array {
    requireAuth();

    $dataDir = $_SESSION['data_dir'] ?? '';
    $stats = readUsageStatsByDataDir($dataDir);
    $now = date('c');

    $stats['total_clicks'] = (int)($stats['total_clicks'] ?? 0) + 1;
    $stats['first_click_at'] = $stats['first_click_at'] ?? $now;
    $stats['last_click_at'] = $now;

    writeUsageStatsByDataDir($dataDir, $stats);
    return normalizeUsageStats($stats);
}

/**
 * Attach usage stats summary to a user record.
 */
function attachUsageStatsToUser(array $user): array {
    $user['usage_stats'] = summarizeUsageStats(readUsageStatsByDataDir($user['data_dir'] ?? ''));
    return $user;
}

/**
 * Normalize stats structure after reads/writes.
 */
function normalizeUsageStats(array $stats): array {
    $normalized = defaultUsageStats();
    $normalized['total_clicks'] = (int)($stats['total_clicks'] ?? 0);
    $normalized['first_click_at'] = $stats['first_click_at'] ?? null;
    $normalized['last_click_at'] = $stats['last_click_at'] ?? null;
    return $normalized;
}

/**
 * Return a compact summary suitable for the UI.
 */
function summarizeUsageStats(array $stats): array {
    $stats = normalizeUsageStats($stats);

    return [
        'total_clicks'   => (int)$stats['total_clicks'],
        'first_click_at' => $stats['first_click_at'],
        'last_click_at'  => $stats['last_click_at'],
    ];
}

?>
