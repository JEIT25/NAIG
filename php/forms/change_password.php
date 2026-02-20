<?php
session_start();
include __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/path_helper.php';

$errorMessage = ''; // Variable to hold error messages
$showSuccessModal = false; // Flag to tell JS whether to show the modal
$redirectUrl = ''; // URL for JS to redirect to after modal dismissal

// --- Check if a success message needs to be shown ---
if (isset($_SESSION['password_change_success']) && $_SESSION['password_change_success']) {
    $showSuccessModal = true;
    // Get the redirect URL stored in session, default to login if not found
    $redirectUrl = isset($_SESSION['redirect_after_pw_change']) ? $_SESSION['redirect_after_pw_change'] : 'login.php';
    // Unset the session flags so the modal doesn't show again on refresh
    unset($_SESSION['password_change_success']);
    unset($_SESSION['redirect_after_pw_change']);
}

// --- Check authorization ---
// Allow access if logged in OR if coming from the Forgot Password flow (allow_password_reset set)
if (!isset($_SESSION['allow_password_reset']) && !isset($_SESSION['user']) && !isset($_SESSION['username'])) {
    // Only redirect if we're not already showing the success modal
    if (!$showSuccessModal) {
        header('Location: ' . getBaseUrl() . '/php/auth/login.php');
        exit();
    }
}

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is a password change request
    if (isset($_POST['new_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $errorMessage = "Passwords do not match.";
        }
        else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = null;
            $redirectTarget = getBaseUrl() . '/php/auth/login.php'; // Default redirect

            // 1. Logged in user (Old session style)
            if (isset($_SESSION['username'])) {
                $username = $_SESSION['username'];
                $query = "UPDATE users SET password = ? WHERE username = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $hashed_password, $username);
                $redirectTarget = getBaseUrl() . '/php/auth/dashboard.php';

            // 2. Logged in user (New session style)
            }
            elseif (isset($_SESSION['user']['username'])) {
                $username = $_SESSION['user']['username'];
                $query = "UPDATE users SET password = ? WHERE username = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $hashed_password, $username);
                $redirectTarget = getBaseUrl() . '/php/auth/dashboard.php';

            // 3. Forgot Password Flow (THE FIX: Update by ID)
            }
            elseif (isset($_SESSION['reset_user_id']) && isset($_SESSION['allow_password_reset'])) {
                $userId = $_SESSION['reset_user_id'];
                $query = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $hashed_password, $userId);
            }

            // Execute the query if a valid session was found
            if ($stmt) {
                if ($stmt->execute()) {
                    // --- SUCCESS ---
                    $_SESSION['password_change_success'] = true; // Set flag for next page load
                    $_SESSION['redirect_after_pw_change'] = $redirectTarget; // Store where to go next

                    // Clean up Forgot Password session vars
                    if (isset($_SESSION['reset_user_id'])) {
                        unset($_SESSION['reset_user_id']);
                        unset($_SESSION['allow_password_reset']);
                    }
                    // Clean up temp username if exists
                    if (isset($_SESSION['username']) && !isset($_SESSION['user'])) {
                        unset($_SESSION['username']);
                    }

                    // Refresh page to show modal
                    header("Location: change_password.php");
                    exit();

                }
                else {
                    $errorMessage = "Error updating password. Please try again.";
                }
                $stmt->close();
            }
            else {
                $errorMessage = "Session expired or unauthorized request. Please login again.";
            }
        }
    }
}

$user = $_SESSION['user'] ?? null;
if ($user) {
    $basePath = getBasePath(__FILE__);
    $baseUrl = getBaseUrl();
    $currentPage = 'change_password';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo isset($basePath) ? $basePath : '../../'; ?>css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo isset($basePath) ? $basePath : '../../'; ?>css/serve_asset.php?file=dashboard.css">
    <link rel="stylesheet" href="<?php echo isset($basePath) ? $basePath : '../../'; ?>css/serve_asset.php?file=change_password.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Change Password - FoodGrab</title>
</head>
<body data-show-modal="<?php echo $showSuccessModal ? 'true' : 'false'; ?>"
    data-redirect-url="<?php echo htmlspecialchars($redirectUrl); ?>">
<?php if ($user): ?>
    <?php $showSidebarToggle = true;
    include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
        <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>
        <main class="dashboard-main">
            <div class="change-password-container">
                <div class="left-side">
                    <img src="<?php echo $basePath; ?>images/profile.png" alt="Security Illustration" class="form-img">
                </div>
                <div class="right-side">
                    <h2>Change Password</h2>
                    <form class="change-password-form" id="changePasswordForm" method="POST" action="change_password.php">
                        <div class="form-group">
                            <label for="new_password">New Password:</label>
                            <div class="password-container">
                                <input type="password" id="new_password" name="new_password" required autocomplete="new-password">
                                <button type="button" class="eye-icon-btn" id="togglePassword1" aria-label="Toggle password visibility">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <span id="pwStrength" class="userValidationMess"></span>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password:</label>
                            <div class="password-container">
                                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                                <button type="button" class="eye-icon-btn" id="togglePassword2" aria-label="Toggle password visibility">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <span id="pwMatch" class="userValidationMess<?php echo !empty($errorMessage) ? ' is-visible' : ''; ?>"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <button type="submit" class="submitBtn btn-action">Change Password</button>
                    </form>
                </div>
            </div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>
    <script>(function(){ var o=document.getElementById('sidebarOverlay'),t=document.getElementById('sidebarToggle'); if(t&&o){ t.addEventListener('click',function(){ document.body.classList.toggle('sidebar-open'); o.classList.toggle('is-open',document.body.classList.contains('sidebar-open')); }); o.addEventListener('click',function(){ document.body.classList.remove('sidebar-open'); o.classList.remove('is-open'); }); } })();</script>
<?php
else: ?>
    <nav class="navbar">
        <div class="navbar-left">
            <img src="../../images/logo4.png" alt="FoodGrab logo" class="logo">
            <span class="navbar-text">FoodGrab <span class="navbar-subtext">Online Food Delivery</span></span>
        </div>
        <div class="navbar-right">
            <a href="<?php echo getBaseUrl(); ?>/php/auth/login.php" class="nav-link">Login</a>
        </div>
    </nav>
    <main>
        <div class="change-password-container">
            <div class="left-side">
                <img src="../../images/profile.png" alt="Security Illustration" class="form-img">
            </div>
            <div class="right-side">
                <h2>Change Password</h2>
                <form class="change-password-form" id="changePasswordForm" method="POST" action="change_password.php">
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <div class="password-container">
                            <input type="password" id="new_password" name="new_password" required autocomplete="new-password">
                            <button type="button" class="eye-icon-btn" id="togglePassword1_alt" aria-label="Toggle password visibility">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <span id="pwStrength" class="userValidationMess"></span>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                            <button type="button" class="eye-icon-btn" id="togglePassword2_alt" aria-label="Toggle password visibility">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <span id="pwMatch" class="userValidationMess<?php echo !empty($errorMessage) ? ' is-visible' : ''; ?>"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <button type="submit" class="submitBtn btn-action">Change Password</button>
                </form>
            </div>
        </div>
    </main>
<?php
endif; ?>

    <div id="successModal" class="modal-simple-alert">
        <div class="modal-simple-alert-content success"> <svg class="success-icon" width="3.3em" height="3.3em"
                viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"
                xmlns="http://www.w3.org/2000/svg">
                <style type="text/css">
                    .st0 {
                        fill: #2BB673;
                    }

                    .st1 {
                        fill: none;
                        stroke: #FFFFFF;
                        stroke-width: 30;
                        stroke-miterlimit: 10;
                    }
                </style>
                <path class="st0"
                    d="M489,255.9c0-0.2,0-0.5,0-0.7c0-1.6,0-3.2-0.1-4.7c0-0.9-0.1-1.8-0.1-2.8c0-0.9-0.1-1.8-0.1-2.7  c-0.1-1.1-0.1-2.2-0.2-3.3c0-0.7-0.1-1.4-0.1-2.1c-0.1-1.2-0.2-2.4-0.3-3.6c0-0.5-0.1-1.1-0.1-1.6c-0.1-1.3-0.3-2.6-0.4-4   c0-0.3-0.1-0.7-0.1-1C474.3,113.2,375.7,22.9,256,22.9S37.7,113.2,24.5,229.5c0,0.3-0.1,0.7-0.1,1c-0.1,1.3-0.3,2.6-0.4,4   c-0.1,0.5-0.1,1.1-0.1,1.6c-0.1,1.2-0.2-2.4-0.3,3.6c0,0.7-0.1,1.4-0.1,2.1c-0.1-1.1-0.1,2.2-0.2,3.3c0,0.9-0.1,1.8-0.1,2.7     c0,0.9-0.1,1.8-0.1,2.8c0,1.6-0.1,3.2-0.1,4.7c0,0.2,0,0.5,0,0.7c0,0,0,0,0,0.1s0,0,0,0.1c0,0.2,0,0.5,0,0.7c0,1.6,0,3.2,0.1,4.7    c0,0.9,0.1,1.8,0.1,2.8c0,0.9,0.1,1.8,0.1,2.7c0.1,1.1,0.1,2.2,0.2,3.3c0,0.7,0.1,1.4,0.1,2.1c0.1,1.2,0.2,2.4,0.3,3.6  c0,0.5,0.1,1.1,0.1,1.6c0.1,1.3,0.3,2.6,0.4,4c0,0.3,0.1,0.7,0.1,1C37.7,398.8,136.3,489.1,256,489.1s218.3-90.3,231.5-206.5    c0-0.3,0.1-0.7,0.1-1c0.1-1.3,0.3-2.6,0.4-4c0.1-0.5,0.1-1.1,0.1-1.6c0.1-1.2,0.2-2.4,0.3-3.6c0-0.7,0.1-1.4,0.1-2.1    c0.1-1.1,0.1-2.2,0.2-3.3c0-0.9,0.1-1.8,0.1-2.7c0-0.9,0.1-1.8-0.1-2.8c0-1.6,0.1-3.2,0.1-4.7c0-0.2,0-0.5,0-0.7    C489,256,489,256,489,255.9C489,256,489,256,489,255.9z" />
                <g>
                    <line class="st1" x1="213.6" x2="369.7" y1="344.2" y2="188.2" />
                    <line class="st1" x1="233.8" x2="154.7" y1="345.2" y2="266.1" />
                </g>
            </svg>
            <span class="modal-simple-alert-text">Password changed successfully!</span>
            <button id="successOkBtn" class="submitBtn">Okay</button>
        </div>
    </div>
<?php if (!$user): ?>
    <footer>
        <div class="footer-bottom"><p>All rights reserved &copy; 2026</p></div>
    </footer>
<?php
endif; ?>
    <script src="<?php echo isset($basePath) ? $basePath : '../../'; ?>js/serve_asset.php?file=change_password.js"></script>
</body>
</html>