<?php

// ============================================
// Application configuration loader
// Reads only data/.env
// ============================================

function loadDataEnvConfig(array $defaults = []): array {
    static $loadedEnv = null;

    if ($loadedEnv === null) {
        $loadedEnv = [];
        $envPath = __DIR__ . '/../../data/.env';

        if (file_exists($envPath) && is_readable($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                if (strpos($line, '=') === false) continue;

                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\"'");

                if ($key !== '') {
                    $loadedEnv[$key] = $value;
                }
            }
        }
    }

    return array_merge($defaults, $loadedEnv);
}

