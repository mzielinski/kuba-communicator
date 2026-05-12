<?php

// ============================================
// Preferences Management Handler
// ============================================

class PreferencesHandler {

    /**
     * Get default preferences
     */
    public static function getDefaults() {
        return [
            'selectedAlarmDeviceId' => '',
            'dwellTimeMs' => 2000,
            'dwellEnabled' => true,
            'telegramEnabled' => false,
            'telegramChatId' => ''
        ];
    }

    /**
     * Handle load preferences request
     */
    public static function handleLoadPreferences() {
        if (isUserLoggedIn()) {
            // User is logged in - load their specific preferences
            $userPreferencesFile = getUserJsonFile('preferences.json');
            if (file_exists($userPreferencesFile)) {
                $preferences = json_decode(file_get_contents($userPreferencesFile), true);
                http_response_code(200);
                echo json_encode($preferences ?: []);
            } else {
                http_response_code(200);
                echo json_encode(self::getDefaults());
            }
        } else {
            // Not logged in - return defaults
            http_response_code(200);
            echo json_encode(self::getDefaults());
        }
    }

    /**
     * Handle save preferences request
     */
    public static function handleSavePreferences($input) {
        requireAuth();

        $userPreferencesFile = getUserJsonFile('preferences.json');
        $preferences = [];

        if (isset($input['selectedAlarmDeviceId'])) {
            $preferences['selectedAlarmDeviceId'] = $input['selectedAlarmDeviceId'];
        }

        if (isset($input['dwellTimeMs'])) {
            $preferences['dwellTimeMs'] = $input['dwellTimeMs'];
        }

        if (isset($input['dwellEnabled'])) {
            $preferences['dwellEnabled'] = (bool)$input['dwellEnabled'];
        }

        if (!empty($preferences)) {
            self::mergeAndSavePreferences($userPreferencesFile, $preferences);
        } else {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Preferences saved successfully']);
        }
    }

    /**
     * Handle save Telegram config request
     */
    public static function handleSaveTelegramConfig($input) {
        requireAuth();

        $userPreferencesFile = getUserJsonFile('preferences.json');
        $preferences = [];

        if (isset($input['telegramEnabled'])) {
            $preferences['telegramEnabled'] = (bool)$input['telegramEnabled'];
        }

        if (isset($input['telegramChatId'])) {
            $chatId = trim($input['telegramChatId']);
            // Validate Chat ID is a number
            if ($chatId && !preg_match('/^\d+$/', $chatId)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid Chat ID format. Must be a number.']);
                exit;
            }
            $preferences['telegramChatId'] = $chatId;
        }

        if (!empty($preferences)) {
            self::mergeAndSavePreferences($userPreferencesFile, $preferences, 'Telegram configuration');
        } else {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Telegram configuration saved successfully']);
        }
    }

    /**
     * Merge and save preferences to file
     */
    private static function mergeAndSavePreferences($preferencesFile, $newPreferences, $successMessage = 'Preferences') {
        // Load existing preferences first
        $existingPrefs = [];
        if (file_exists($preferencesFile)) {
            $existingPrefs = json_decode(file_get_contents($preferencesFile), true) ?: [];
        }

        // Merge with new preferences
        $mergedPreferences = array_merge($existingPrefs, $newPreferences);

        if (file_put_contents($preferencesFile, json_encode($mergedPreferences, JSON_PRETTY_PRINT)) !== false) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => $successMessage . ' saved successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save ' . strtolower($successMessage)]);
        }
    }
}
?>

