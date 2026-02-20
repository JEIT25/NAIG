<?php
/**
 * Forgot Password - Step 4: Reset Password
 * Allow user to set new password after OTP verification
 */
session_start();
require_once '../database/db_connect.php';

// Check if OTP and Security Questions are verified
if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['security_verified'])) {
    header('Location: forgot_password.php');
    exit;
}

$user_id = $_SESSION['reset_user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($new_password)) {
        $error = 'Please enter a new password';
    }
    elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long';
    }
    elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match';
    }
    else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param('ss', $hashed_password, $user_id);

        if ($stmt->execute()) {
            $success = 'Password reset successfully!';

            // Clear session
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['otp_verified']);
            unset($_SESSION['security_verified']);

            // Redirect to login after 3 seconds
            echo '<meta http-equiv="refresh" content="3;url=login.php">';
        }
        else {
            $error = 'Failed to reset password. Please try again.';
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="../../css/serve_asset.php?file=login.css">
    <title>Reset Password - FoodGrab</title>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-left" id="navbarLeft">
            <img src="../../images/logo4.png" alt="FoodGrab logo" class="logo">
            <span class="navbar-text">
                FoodGrab
                <span class="navbar-subtext">Online Food Delivery</span>
            </span>
        </div>
        <div class="navbar-right">
            <a href="./login.php" class="nav-link">Back to Login</a>
        </div>
    </nav>

    <main>
        <div class="form-container">
            <h2>Reset Password</h2>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php
endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                    <p>Redirecting to login page...</p>
                </div>
            <?php
else: ?>
                <form method="POST" class="forgot-form">
                    <p>Enter your new password below</p>

                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <div class="password-container">
                            <input type="password" id="new_password" name="new_password" required
                                   minlength="8" placeholder="Enter new password" autocomplete="new-password">
                            <button type="button" class="password-toggle" data-target="new_password" aria-label="Toggle visibility">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   minlength="8" placeholder="Confirm new password" autocomplete="new-password">
                            <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Toggle visibility">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="password-requirements">
                        <p><strong>Password Requirements:</strong></p>
                        <ul>
                            <li>At least 8 characters long</li>
                            <li>Contains letters and numbers</li>
                            <li>Not similar to your username</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn-primary">Reset Password</button>
                </form>
            <?php
endif; ?>
        </div>
    </main>

    <footer>
        <div class="footer-bottom">
            <p>All rights reserved &copy; 2026</p>
        </div>
    </footer>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });
    </script>
</body>
</html>
