<?php
// Get the referer from the request
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

// Define valid bases: current host and relative path
$currentHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$validBases = [
    "http://$currentHost/NAIG/",
    "https://$currentHost/NAIG/",
    "/NAIG/"
];

// Check if the referer matches any of the valid referers
$refererValid = false;

// Allow empty referer (direct access or privacy mode)
if (empty($referer)) {
    $refererValid = true;
}
else {
    foreach ($validBases as $base) {
        if (strpos($referer, $base) !== false) {
            $refererValid = true;
            break;
        }
    }
}

// Deny access if no valid referer is found
if (!$refererValid) {
    http_response_code(403);
    error_log("Serve Asset JSError Forbidden: Referer=$referer, Host=$currentHost");
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
        echo "File not found.";
    }
}
else {
    http_response_code(400);
    echo "No file specified.";
}
