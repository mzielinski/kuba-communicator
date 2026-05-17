<?php
/**
 * Development Server Router
 * Handles routing for the new project structure:
 * - Static files from /public/
 * - API requests to /src/api/ and /src/auth/
 */
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$root = __DIR__;

$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($scriptDir !== '' && $scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
    $uri = substr($uri, strlen($scriptDir));
}
if (empty($uri) || $uri[0] !== '/') {
    $uri = '/' . $uri;
}

// List of directories to check for static files in public/
$publicDirs = ['/', '/js/', '/css/'];
$isPublic = false;

foreach ($publicDirs as $dir) {
    if (strpos($uri, $dir) === 0) {
        $isPublic = true;
        break;
    }
}

// Also check for specific static file extensions
$staticExtensions = ['.js', '.css', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.woff', '.woff2', '.ttf', '.eot'];
foreach ($staticExtensions as $ext) {
    if (strpos($uri, $ext) !== false) {
        $isPublic = true;
        break;
    }
}

// ── Map frontend endpoints to backend handlers ────────────────────────────────
$endpointMap = [
    '/api.php'                      => '/src/api/api.php',
    '/login.php'                    => '/src/auth/login.php',
    '/user.php'                     => '/src/api/users.php',
    '/notifications-telegram.php'   => '/src/api/notifications-telegram.php',
];

foreach ($endpointMap as $publicPath => $srcPath) {
    if ($uri === $publicPath) {
        $_SERVER["REQUEST_URI"] = $_SERVER["REQUEST_URI"]; // Preserve original with query string
        require $root . $srcPath;
        return true;
    }
}

// Check if requesting root index.html
if ($uri === '/' || $uri === '') {
    $file = $root . '/public/index.html';
    if (file_exists($file)) {
        header('Content-Type: text/html; charset=UTF-8');
        readfile($file);
        return true;
    }
}

// Serve public files (HTML, CSS, JS, images, fonts)
if ($isPublic || strpos($uri, '/public/') === 0) {
    // Remove /public/ prefix if present
    $file = str_replace('/public/', '/', $uri);
    $filepath = $root . '/public' . $file;

    if (is_file($filepath) && file_exists($filepath)) {
        // Determine MIME type and serve the file
        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        // Execute PHP files
        if ($ext === 'php') {
            require $filepath;
            return true;
        }

        $mimeTypes = [
            'html' => 'text/html; charset=UTF-8',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
        ];
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        readfile($filepath);
        return true;
    }
}

// Route API requests through proper files
if (strpos($uri, '/src/api/') === 0 || strpos($uri, '/src/auth/') === 0) {
    // Request to API endpoint - route through the file
    $parts = explode('?', $uri);
    $path = $parts[0];
    $query = isset($parts[1]) ? $parts[1] : '';

    // Try to find the PHP file
    $phpFile = $root . $path;
    if (file_exists($phpFile) && is_file($phpFile)) {
        $_SERVER["REQUEST_URI"] = $uri;
        require $phpFile;
        return true;
    }
}

// Default: Try to serve from public/
$file = $root . '/public' . $uri;
if (is_file($file) && file_exists($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    // Execute PHP files
    if ($ext === 'php') {
        require $file;
        return true;
    }

    $mimeTypes = [
        'html' => 'text/html; charset=UTF-8',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
    ];
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }
    readfile($file);
    return true;
}

// If not found, serve index.html (for SPA-style routing in public/)
$index = $root . '/public/index.html';
if (file_exists($index)) {
    require $index;
    return true;
}

// If still not found, return 404
http_response_code(404);
echo "404 - File not found: " . htmlspecialchars($uri);
return true;
?>
