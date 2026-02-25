<?php
/**
 * Forgot Password - Step 4: Security Questions
 * Verify user identity via security questions
 */
session_start();
require_once __DIR__ . '/../includes/path_helper.php';
$basePath = getBasePath(__FILE__);
require_once '../database/db_connect.php';

// Check if OTP was verified
if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['otp_verified'])) {
    header('Location: forgot_password.php');
    exit;
}

$user_id = $_SESSION['reset_user_id'];
$error = '';

// Fetch user's security questions
$stmt = $conn->prepare("SELECT secure_quest1, secure_quest2, secure_quest3 FROM users WHERE id = ?");
$stmt->bind_param('s', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_questions = $result->fetch_assoc();
$stmt->close();

if (!$user_questions || empty($user_questions['secure_quest1'])) {
    // Handle case where user has no security questions set
    $error = "Security questions not set up for this account. Please contact support.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$error) {
    $ans1 = trim($_POST['ans1']);
    $ans2 = trim($_POST['ans2']);
    $ans3 = trim($_POST['ans3']);

    if (empty($ans1) || empty($ans2) || empty($ans3)) {
        $error = "Please answer all security questions.";
    }
    else {
        // Fetch hashed answers
        $stmt = $conn->prepare("SELECT secure_ans1, secure_ans2, secure_ans3 FROM users WHERE id = ?");
        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        $saved_answers = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Verify all answers
        if (password_verify($ans1, $saved_answers['secure_ans1']) &&
        password_verify($ans2, $saved_answers['secure_ans2']) &&
        password_verify($ans3, $saved_answers['secure_ans3'])) {

            $_SESSION['security_verified'] = true;
            header('Location: forgot_password_reset.php');
            exit;
        }
        else {
            $error = "One or more answers are incorrect.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=login.css">
    <title>Security Questions - FoodGrab</title>
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
            <h2>Security Check</h2>
            <p>Please answer your security questions to continue.</p>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php
endif; ?>

            <?php if (!empty($user_questions['secure_quest1'])): ?>
            <form method="POST" class="forgot-form">
                <div class="form-group">
                    <label><?php echo htmlspecialchars($user_questions['secure_quest1']); ?></label>
                    <div class="password-container">
                        <input type="password" name="ans1" id="ans1" required placeholder="Your answer">
                        <button type="button" class="password-toggle" data-target="ans1" aria-label="Toggle visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo htmlspecialchars($user_questions['secure_quest2']); ?></label>
                    <div class="password-container">
                        <input type="password" name="ans2" id="ans2" required placeholder="Your answer">
                        <button type="button" class="password-toggle" data-target="ans2" aria-label="Toggle visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo htmlspecialchars($user_questions['secure_quest3']); ?></label>
                    <div class="password-container">
                        <input type="password" name="ans3" id="ans3" required placeholder="Your answer">
                        <button type="button" class="password-toggle" data-target="ans3" aria-label="Toggle visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Verify Answers</button>
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
