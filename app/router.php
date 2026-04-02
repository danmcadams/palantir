<?php
// PHP built-in server router
// - Serves image/SVG files from /var/www/docs/ under the /docs/ URL prefix
// - Falls through to index.php for everything else

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static files from the app doc root directly (e.g. style.css)
$appFile = '/var/www/app' . $uri;
if ($uri !== '/' && file_exists($appFile) && is_file($appFile)) {
    return false;
}

// Serve image files from the docs directory
if (preg_match('#^/docs/(.+)$#', $uri, $m)) {
    $fullPath = realpath('/var/www/docs/' . $m[1]);

    if ($fullPath === false || strpos($fullPath, '/var/www/docs/') !== 0 || !is_file($fullPath)) {
        http_response_code(404);
        exit;
    }

    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $mimes = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'pdf'  => 'application/pdf',
    ];

    if (!isset($mimes[$ext])) {
        http_response_code(403);
        exit;
    }

    header('Content-Type: ' . $mimes[$ext]);
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
}

// Serve raw file content as plain text
if ($uri === '/raw') {
    $requested = $_GET['file'] ?? null;
    if ($requested === null) {
        http_response_code(400);
        exit;
    }
    $fullPath = realpath('/var/www/docs/' . $requested);
    if ($fullPath === false || strpos($fullPath, '/var/www/docs/') !== 0 || !is_file($fullPath)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
}

// Export a docs folder as a zip download
if ($uri === '/export') {
    $requested = $_GET['folder'] ?? null;
    if ($requested === null) {
        http_response_code(400);
        exit;
    }
    $fullPath = realpath('/var/www/docs/' . $requested);
    if ($fullPath === false || strpos($fullPath, '/var/www/docs/') !== 0 || !is_dir($fullPath)) {
        http_response_code(404);
        exit;
    }

    $folderName = basename($fullPath);
    $tmpFile    = tempnam(sys_get_temp_dir(), 'palantir_export_');
    $zip        = new ZipArchive();

    if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        exit;
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($it as $file) {
        if ($file->isFile()) {
            $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($fullPath) + 1));
            $zip->addFile($file->getPathname(), $folderName . '/' . $relativePath);
        }
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $folderName . '.zip"');
    header('Content-Length: ' . filesize($tmpFile));
    readfile($tmpFile);
    unlink($tmpFile);
    exit;
}

// Everything else goes to index.php
require __DIR__ . '/index.php';
