<?php
// Get the referer from the request
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

// Define an array of valid referers (host-specific and path-only for flexibility)
$validReferers = [
    'http://localhost/NAIG/php/forms/',
    'http://localhost/NAIG/php/admin/',
    'http://localhost/NAIG/php/superadmin/',
    'http://localhost/NAIG/php/auth/',
    'http://localhost/NAIG/php/pages/',
    'http://127.0.0.1/NAIG/php/forms/',
    'http://127.0.0.1/NAIG/php/admin/',
    'http://127.0.0.1/NAIG/php/superadmin/',
    'http://127.0.0.1/NAIG/php/auth/',
    'http://127.0.0.1/NAIG/php/pages/',
    '/NAIG/php/forms/',
    '/NAIG/php/admin/',
    '/NAIG/php/superadmin/',
    '/NAIG/php/auth/',
    '/NAIG/php/pages/',
];

// Check if the referer matches any of the valid referers
$refererValid = false;

// Allow empty referer (direct access or privacy mode) to prevent 403s on valid assets
if (empty($referer)) {
    $refererValid = true;
}
else {
    foreach ($validReferers as $validReferer) {
        if (strpos($referer, $validReferer) !== false) {
            $refererValid = true;
            break;
        }
    }
}

// Deny access if no valid referer is found
if (!$refererValid) {
    http_response_code(403);
    exit;
}

// Sanitize the file parameter
if (isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $filePath = __DIR__ . '/' . $file;

    // Validate the file exists and is a CSS/JS file
    if (file_exists($filePath) && in_array(pathinfo($file, PATHINFO_EXTENSION), ['css', 'js'])) {
        // Serve the file with the appropriate MIME type
        $mimeType = pathinfo($file, PATHINFO_EXTENSION) === 'css' ? 'text/css' : 'application/javascript';
        header("Content-Type: $mimeType");
        readfile($filePath);
        exit;
    }
    else {
        http_response_code(404);
    }
}
else {
    http_response_code(400);
    echo "No file specified.";
}
