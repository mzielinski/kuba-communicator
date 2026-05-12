<?php

// ============================================
// File Access Handler
// ============================================

class FileHandler {

    /**
     * Handle secure file access endpoint
     * Only allow authenticated users to access their own files
     */
    public static function handleGetFile($fileParam) {
        requireAuth();

        $filename = $fileParam ?? '';

        // Security: Only allow specific filenames
        if (!validateFilename($filename)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid filename']);
            exit;
        }

        // Only allow certain file types
        if (!isAllowedFileExtension($filename)) {
            http_response_code(403);
            echo json_encode(['error' => 'File type not allowed']);
            exit;
        }

        $userFile = getUserJsonFile($filename);

        if (!file_exists($userFile)) {
            http_response_code(404);
            echo json_encode(['error' => 'File not found']);
            exit;
        }

        // Serve the file content
        http_response_code(200);
        header('Content-Type: application/json');
        readfile($userFile);
    }
}
?>

