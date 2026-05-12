<?php

// ============================================
// Words Management Handler
// ============================================

class WordsHandler {

    /**
     * Handle save words request
     */
    public static function handleSaveWords($input) {
        requireAuth();

        if (!isset($input['categories'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing categories data']);
            exit;
        }

        $userJsonFile = getUserJsonFile('words.json');

        $categories = $input['categories'];
        if (empty($categories) || array_values((array)$categories) === (array)$categories) {
            if (empty($categories)) {
                $categories = new stdClass();
            }
        }

        $fullData = json_encode(
            ['categories' => $categories],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        if ($fullData === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON format']);
            exit;
        }

        // Create backup before saving
        $backup = $userJsonFile . '.backup';
        if (file_exists($userJsonFile)) {
            copy($userJsonFile, $backup);
        }

        // Write new content to user-specific file
        if (file_put_contents($userJsonFile, $fullData) !== false) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Words saved successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to write to words.json']);
        }
    }

    /**
     * Handle load words request
     */
    public static function handleLoadWords() {
        requireAuth();

        $userWordsFile = getUserJsonFile('words.json');

        if (file_exists($userWordsFile)) {
            $words = json_decode(file_get_contents($userWordsFile), true);
            http_response_code(200);
            echo json_encode($words);
        } else {
            // Return default words template
            http_response_code(200);
            echo json_encode(['categories' => []]);
        }
    }
}
?>

