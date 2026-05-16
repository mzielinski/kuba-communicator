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
            'alarmType' => 'high',
            'alarmDuration' => 6,
            'dwellTimeMs' => 2000,
            'dwellEnabled' => true,
            'darkModeEnabled' => false,
            'telegramEnabled' => false,
            'telegramChats' => [],
            'telegramSelectedChatId' => ''
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

        if (isset($input['alarmType'])) {
            $preferences['alarmType'] = $input['alarmType'];
        }

        if (isset($input['alarmDuration'])) {
            $preferences['alarmDuration'] = (int)$input['alarmDuration'];
        }

        if (isset($input['dwellTimeMs'])) {
            $preferences['dwellTimeMs'] = $input['dwellTimeMs'];
        }

        if (isset($input['dwellEnabled'])) {
            $preferences['dwellEnabled'] = (bool)$input['dwellEnabled'];
        }

        if (isset($input['darkModeEnabled'])) {
            $preferences['darkModeEnabled'] = (bool)$input['darkModeEnabled'];
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


        if (isset($input['telegramSelectedChatId'])) {
            $chatId = trim($input['telegramSelectedChatId']);
            if ($chatId && !preg_match('/^\d+$/', $chatId)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid Chat ID format. Must be a number.']);
                exit;
            }
            $preferences['telegramSelectedChatId'] = $chatId;
        }

        if (!empty($preferences)) {
            self::mergeAndSavePreferences($userPreferencesFile, $preferences, 'Telegram configuration');
        } else {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Telegram configuration saved successfully']);
        }
    }

    /**
     * Handle add Telegram chat request
     */
    public static function handleAddTelegramChat($input) {
        requireAuth();

        $userPreferencesFile = getUserJsonFile('preferences.json');

        $chatId = trim($input['chatId'] ?? '');
        $name = trim($input['name'] ?? '');

        // Validate inputs
        if (!$chatId) {
            http_response_code(400);
            echo json_encode(['error' => 'Chat ID is required']);
            exit;
        }

        if (!preg_match('/^\d+$/', $chatId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid Chat ID format. Must be a number.']);
            exit;
        }

        if (!$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Name is required']);
            exit;
        }

        // Load existing preferences
        $existingPrefs = [];
        if (file_exists($userPreferencesFile)) {
            $existingPrefs = json_decode(file_get_contents($userPreferencesFile), true) ?: [];
        }

        // Initialize telegramChats if not exists
        if (!isset($existingPrefs['telegramChats'])) {
            $existingPrefs['telegramChats'] = [];
        }

        // Check if chat ID already exists
        foreach ($existingPrefs['telegramChats'] as $chat) {
            if ($chat['id'] === $chatId) {
                http_response_code(400);
                echo json_encode(['error' => 'This Chat ID already exists']);
                exit;
            }
        }

        // Add new chat
        $existingPrefs['telegramChats'][] = [
            'id' => $chatId,
            'name' => $name
        ];

        // If this is the first chat, select it
        if (!isset($existingPrefs['telegramSelectedChatId']) || !$existingPrefs['telegramSelectedChatId']) {
            $existingPrefs['telegramSelectedChatId'] = $chatId;
        }

        if (file_put_contents($userPreferencesFile, json_encode($existingPrefs, JSON_PRETTY_PRINT)) !== false) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Telegram chat added successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save Telegram chat']);
        }
    }

    /**
     * Handle remove Telegram chat request
     */
    public static function handleRemoveTelegramChat($input) {
        requireAuth();

        $userPreferencesFile = getUserJsonFile('preferences.json');

        $chatId = trim($input['chatId'] ?? '');

        if (!$chatId) {
            http_response_code(400);
            echo json_encode(['error' => 'Chat ID is required']);
            exit;
        }

        // Load existing preferences
        $existingPrefs = [];
        if (file_exists($userPreferencesFile)) {
            $existingPrefs = json_decode(file_get_contents($userPreferencesFile), true) ?: [];
        }

        if (!isset($existingPrefs['telegramChats'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No Telegram chats found']);
            exit;
        }

        // Find and remove the chat
        $found = false;
        $newChats = [];
        foreach ($existingPrefs['telegramChats'] as $chat) {
            if ($chat['id'] !== $chatId) {
                $newChats[] = $chat;
            } else {
                $found = true;
            }
        }

        if (!$found) {
            http_response_code(400);
            echo json_encode(['error' => 'Chat ID not found']);
            exit;
        }

        $existingPrefs['telegramChats'] = $newChats;

        // If deleted chat was selected, select the first available
        if (isset($existingPrefs['telegramSelectedChatId']) && $existingPrefs['telegramSelectedChatId'] === $chatId) {
            $existingPrefs['telegramSelectedChatId'] = !empty($newChats) ? $newChats[0]['id'] : '';
        }

        if (file_put_contents($userPreferencesFile, json_encode($existingPrefs, JSON_PRETTY_PRINT)) !== false) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Telegram chat removed successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove Telegram chat']);
        }
    }

    /**
     * Handle update Telegram chat request
     */
    public static function handleUpdateTelegramChat($input) {
        requireAuth();

        $userPreferencesFile = getUserJsonFile('preferences.json');

        $chatId = trim($input['chatId'] ?? '');
        $name = trim($input['name'] ?? '');

        if (!$chatId) {
            http_response_code(400);
            echo json_encode(['error' => 'Chat ID is required']);
            exit;
        }

        if (!$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Name is required']);
            exit;
        }

        // Load existing preferences
        $existingPrefs = [];
        if (file_exists($userPreferencesFile)) {
            $existingPrefs = json_decode(file_get_contents($userPreferencesFile), true) ?: [];
        }

        if (!isset($existingPrefs['telegramChats'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No Telegram chats found']);
            exit;
        }

        // Find and update the chat
        $found = false;
        foreach ($existingPrefs['telegramChats'] as &$chat) {
            if ($chat['id'] === $chatId) {
                $chat['name'] = $name;
                $found = true;
                break;
            }
        }

        if (!$found) {
            http_response_code(400);
            echo json_encode(['error' => 'Chat ID not found']);
            exit;
        }

        if (file_put_contents($userPreferencesFile, json_encode($existingPrefs, JSON_PRETTY_PRINT)) !== false) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Telegram chat updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update Telegram chat']);
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

