<?php
session_start();
require __DIR__ . '/db_connect.php';

// Function to log login/logout actions
function logUserAction($user_id, $action)
{
    global $conn;
    try {
        $stmt = $conn->prepare("INSERT INTO login_logs (user_id, action) VALUES (?, ?)");
        $stmt->bind_param('ss', $user_id, $action);
        $stmt->execute();
        $stmt->close();
    }
    catch (Exception $e) {
    // Silently fail logging
    }
}

// This function now *only* handles failed login attempts and updates $response
function handleFailedLogin($isUsernameEmpty, $isPwEmpty, &$response, $usernameOrEmail = '') // Takes $response by reference
{
    $_SESSION['failed_attempts']++;
    $response['failed_attempts'] = $_SESSION['failed_attempts'];

    // Log failed login attempt - REMOVED (schema specific)
    // logLoginAttempt($usernameOrEmail, 'failed', ...);

    // Lockout durations in seconds for specific failed attempts
    $lockoutDurations = [3 => 15, 6 => 30, 9 => 60]; // 9 attempts = 60 seconds

    // Set lockout time if failed attempts reach specific thresholds
    if (array_key_exists($_SESSION['failed_attempts'], $lockoutDurations)) {
        $_SESSION['lockout_time'] = time() + $lockoutDurations[$_SESSION['failed_attempts']];

    // *** THIS IS THE FIX for 60-second timer ***
    // Use 60s value for 9 or more attempts
    }
    elseif ($_SESSION['failed_attempts'] >= 9) {
        $_SESSION['lockout_time'] = time() + $lockoutDurations[9]; // Was hard-coded to 15
    }

    // Return appropriate error messages
    if ($isUsernameEmpty) {
        $response['requireUsername'] = 'Username is required.';
    }

    if ($isPwEmpty) {
        $response['requirePw'] = 'Password is required.';
    }

    if (!$isUsernameEmpty && !$isPwEmpty) {
        $response['error'] = "Password did not match, try again.";
    }

    $response['lockout_time'] = $_SESSION['lockout_time'];
}



// *** THIS IS THE FIX for the JSON Error ***
// First, check what kind of request this is.
// JS FormData sends booleans as strings "true" and "false"
$isFormSubmission = isset($_POST['isForm']) && $_POST['isForm'] === 'true';

if ($isFormSubmission) {
    // --- START: LOGIN FORM LOGIC ---

    // Initialize or update session variables
    if (!isset($_SESSION['failed_attempts'])) {
        $_SESSION['failed_attempts'] = 0;
        $_SESSION['lockout_time'] = 0;
    }

    $response = [
        'error' => '',
        'requirePw' => '',
        'requireUsername' => '',
        'failed_attempts' => $_SESSION['failed_attempts'],
        'lockout_time' => $_SESSION['lockout_time']
    ];

    $usernameOrEmail = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $isPwEmpty = $password === '';
    $isUsernameEmpty = $usernameOrEmail === '';

    // If username or password is empty, return corresponding error messages
    if ($isPwEmpty || $isUsernameEmpty) {
        handleFailedLogin($isUsernameEmpty, $isPwEmpty, $response, $usernameOrEmail);
        echo json_encode($response);
        exit;
    }

    $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('ss', $usernameOrEmail, $usernameOrEmail);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Determine if the account is currently blocked
            $isBlocked = !empty($user['is_blocked']) && (int)$user['is_blocked'] === 1;
            $regStatus = null;

            // Only check registration approvals if the account is blocked.
            // This allows manual unblocking (is_blocked = 0) to always override pending/old approvals.
            if ($isBlocked) {
                $regStmt = $conn->prepare("SELECT status FROM approvals WHERE action_type = 'register_consumer' AND target_type = 'user' AND target_id = ? ORDER BY created_at DESC LIMIT 1");
                if ($regStmt) {
                    $regStmt->bind_param('s', $user['id']);
                    $regStmt->execute();
                    $regRes = $regStmt->get_result();
                    if ($row = $regRes->fetch_assoc()) {
                        $regStatus = $row['status'] ?? null;
                    }
                    $regStmt->close();
                }
            }

            // Show more specific messages for blocked accounts created via registration
            if ($isBlocked && $regStatus === 'pending') {
                $response['error'] = 'Your account is pending approval. Please wait for an administrator or superadmin to approve your registration.';
                // Do NOT increment failed attempts or start lockout timer here
            } elseif ($isBlocked && $regStatus === 'rejected') {
                $response['error'] = 'Your registration request was rejected. Please contact system administrator or superadmin for more information.';
            }
            // Generic blocked-account handling (for any blocked user without a specific registration status)
            elseif ($isBlocked) {
                $role = $user['role'] ?? 'consumer';
                if ($role === 'admin' || $role === 'superadmin') {
                    $response['error'] = 'Account has been disabled, please contact system superadmin.';
                } else {
                    $response['error'] = 'Account has been disabled, please contact system administrator or superadmin.';
                }
                // Do NOT increment failed attempts or start lockout timer here
            } else {
                $storedHash = $user['password'] ?? '';
                if ($storedHash !== '' && password_verify($password, $storedHash)) {
                    if (!array_key_exists('role', $user)) {
                        $user['role'] = 'consumer';
                    }
                    $_SESSION['user'] = $user;
                    $_SESSION['failed_attempts'] = 0;
                    $_SESSION['lockout_time'] = 0; // Reset lockout on success

                    // Log successful login
                    logUserAction($user['id'], 'login');

                    $base = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/NAIG';

                    // Role-based redirection
                    if ($user['role'] === 'admin') {
                        $response['redirect'] = $base . '/php/admin/index.php';
                    }
                    elseif ($user['role'] === 'superadmin') {
                        $response['redirect'] = $base . '/php/superadmin/index.php';
                    }
                    else {
                        $response['redirect'] = $base . '/php/auth/dashboard.php';
                    }
                } else {
                    handleFailedLogin(false, false, $response, $usernameOrEmail);
                }
            }
        }
        else {
            handleFailedLogin(false, false, $response, $usernameOrEmail);
        }

        $stmt->close();
    }
    else {
        $response['error'] = "Error preparing query.";
    }

    // Always send a valid JSON response for form submissions
    echo json_encode($response);
    exit;

// --- END: LOGIN FORM LOGIC ---

}
else {
    // --- START: updateRegisterAccess LOGIC ---

    // This logic is for restricting/unrestricting access via .htaccess
    if (!isset($_POST['isRegisterRestrict'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing isRegisterRestrict parameter']);
        exit;
    }

    // Path to .htaccess in the /forms/ directory
    $htaccessPath = $_SERVER['DOCUMENT_ROOT'] . '/NAIG/php/forms/.htaccess';

    // Restrict access
    if ($_POST['isRegisterRestrict'] === 'true') {
        $htaccessContent = <<<HTACCESS
<FilesMatch "^(homepage|signup)\.php$">
    Order Deny,Allow
    Deny from all
</FilesMatch>
HTACCESS;
        file_put_contents($htaccessPath, $htaccessContent);
        echo json_encode(['status' => 'File access restricted']);

    // Unrestrict access
    }
    else {
        if (file_exists($htaccessPath)) {
            unlink($htaccessPath); // Remove .htaccess file to unrestrict access
        }
        echo json_encode(['status' => 'File access unrestricted']);
    }
    exit; // Important: exit after handling this request.

// --- END: updateRegisterAccess LOGIC ---
}
?>