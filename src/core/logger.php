<?php

// ============================================
// Application logging helper
// Writes logs to the project root (same level as debug.log)
// ============================================

function appLogPath(string $filename = 'error.log'): string {
    return __DIR__ . '/../../' . $filename;
}

function writeAppLog(string $message, string $filename = 'error.log'): void {
    $path = appLogPath($filename);
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[{$timestamp}] {$message}\n";
    @file_put_contents($path, $entry, FILE_APPEND | LOCK_EX);
}

