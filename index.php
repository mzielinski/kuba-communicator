<?php
/**
 * KUBA App — Front Controller
 *
 * Apache routes every request here via mod_rewrite (.htaccess).
 * Also works as a PHP built-in development server router (php -S).
 *
 * Routing order:
 *  1. Force HTTPS on production
 *  2. Map public API endpoints → /src/ handlers
 *  3. Root / → /public/index.html (with importmap)
 *  4. Serve any file found in /public/
 *  5. SPA fallback: extension-less paths → /public/index.html
 *  6. 404
 */
if (PHP_SAPI !== 'cli') {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? '') == '443');
    $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1'], true);

    if (!$isHttps && !$isLocal) {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        header('Cache-Control: no-cache');
        exit;
    }
}

/**
 * Serve a static file with appropriate cache headers.
 *
 * JS / CSS / HTML  → no-store (always fresh — SFTP-deployed changes visible immediately)
 * Images / fonts   → max-age=86400 with ETag / 304 support
 */
function serveStaticFile(string $filepath): void
{
    static $mimeTypes = [
        'html'  => 'text/html; charset=UTF-8',
        'css'   => 'text/css; charset=UTF-8',
        'js'    => 'application/javascript; charset=UTF-8',
        'json'  => 'application/json; charset=UTF-8',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'eot'   => 'application/vnd.ms-fontobject',
    ];

    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }

    // Never-cache for code assets
    if (in_array($ext, ['js', 'css', 'html'], true)) {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($filepath);
        return;
    }

    // Long-lived cache for images / fonts with 304 support
    $mtime = filemtime($filepath);
    $etag  = '"' . dechex($mtime) . '-' . dechex(filesize($filepath)) . '"';

    header('Cache-Control: max-age=86400, public');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

    $ifNoneMatch     = $_SERVER['HTTP_IF_NONE_MATCH']     ?? '';
    $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';

    if ($ifNoneMatch === $etag ||
        (!$ifNoneMatch && $ifModifiedSince && strtotime($ifModifiedSince) >= $mtime)) {
        http_response_code(304);
        return;
    }

    readfile($filepath);
}

/**
 * Serve an HTML file, injecting an ES-module importmap that versions every
 * JS file in /public/js/ by its filesystem mtime.
 *
 * This busts the browser module cache on every deploy — no build tool needed.
 */
function serveHtmlWithImportMap(string $filepath): void
{
    $jsDir = dirname($filepath) . '/js';

    // Build { "/js/foo.js": "/js/foo.js?v=<mtime>", … }
    $imports = [];
    if (is_dir($jsDir)) {
        foreach (glob($jsDir . '/*.js') as $f) {
            $name = '/js/' . basename($f);
            $imports[$name] = $name . '?v=' . filemtime($f);
        }
    }

    $importMapJson = json_encode(['imports' => $imports], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $importMapTag  = "\n    <script type=\"importmap\">\n    $importMapJson\n    </script>";

    $html = file_get_contents($filepath);

    // Inject importmap just before the first <script type="module">
    $html = preg_replace(
        '/(<script\s[^>]*type=["\']module["\'])/i',
        $importMapTag . "\n    $1",
        $html,
        1
    );

    // Bust the entry-point module src with a timestamp
    $html = preg_replace_callback(
        '/(<script\s[^>]*type=["\']module["\'][^>]*\ssrc=["\'])([^"\'?]+)(\.js)(["\'])/i',
        fn($m) => $m[1] . $m[2] . $m[3] . '?v=' . time() . $m[4],
        $html
    );

    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo $html;
}

// ── Normalise URI
$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$root = __DIR__;

// Strip sub-directory prefix when deployed under a sub-path
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
    $uri = substr($uri, strlen($scriptDir));
}
if ($uri === '' || $uri[0] !== '/') {
    $uri = '/' . $uri;
}

// 1. API endpoint map
$endpointMap = [
    '/api.php'                    => '/src/api/api.php',
    '/login.php'                  => '/src/auth/login.php',
    '/user.php'                   => '/src/api/users.php',
    '/notifications-telegram.php' => '/src/api/notifications-telegram.php',
];

if (isset($endpointMap[$uri])) {
    require $root . $endpointMap[$uri];
    return;
}

// 2. Root → index.html
if ($uri === '/') {
    $index = $root . '/public/index.html';
    if (file_exists($index)) {
        serveHtmlWithImportMap($index);
        return;
    }
}

// 3. Static assets from /public/
$filepath = $root . '/public' . $uri;

if (is_file($filepath)) {
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

    if ($ext === 'php') {
        // Never execute PHP files requested directly through /public/
        http_response_code(403);
        return;
    }

    if ($ext === 'html') {
        serveHtmlWithImportMap($filepath);
        return;
    }

    serveStaticFile($filepath);
    return;
}

// 4. SPA fallback (extension-less paths only)
// Only rewrite clean URLs (e.g. /dashboard), not missing assets (.css, .js …)
$index = $root . '/public/index.html';
if (pathinfo($uri, PATHINFO_EXTENSION) === '' && file_exists($index)) {
    serveHtmlWithImportMap($index);
    return;
}

// 5. Not found
http_response_code(404);
echo '404 – Not found: ' . htmlspecialchars($uri);
