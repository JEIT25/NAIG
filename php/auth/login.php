<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';

// --- Session redirect (CEDULA-style role-based) ---
if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? 'consumer';
    $redirect = getBaseUrl() . '/php/auth/dashboard.php';
    if ($role === 'admin') {
        $redirect = getBaseUrl() . '/php/admin/index.php';
    } elseif ($role === 'superadmin') {
        $redirect = getBaseUrl() . '/php/superadmin/index.php';
    }
    header('Location: ' . $redirect);
    exit;
}

// --- Login error from session (CEDULA-style) ---
$login_error = null;
if (isset($_SESSION['login_error'])) {
    $login_error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'blocked_consumer') {
        $login_error = "Your account has been blocked. Please contact admin or superadmin.";
    } elseif ($_GET['error'] === 'blocked_admin' || $_GET['error'] === 'blocked') {
        $login_error = "Your account has been blocked. Please contact superadmin.";
    }
}

// --- Lockout state (CEDULA-style) ---
$lockoutActive = false;
$lockoutTime = 0;
$failedAttempts = 0;
if (isset($_SESSION['lockout_time']) && $_SESSION['lockout_time'] > time()) {
    $lockoutActive = true;
    $lockoutTime = $_SESSION['lockout_time'];
    $failedAttempts = $_SESSION['failed_attempts'] ?? 0;
}
$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - NAIGO</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/serve_asset.php?file=login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <!-- ===== Navbar (Fixed Full Width) ===== -->
    <nav class="login-navbar">
        <a href="../forms/homepage.php" class="brand">
            <div class="navbar-logo-icon" aria-hidden="true" style="font-size: 1.25rem;"><i
                    class="fa-solid fa-concierge-bell"></i></div>
            <span>NAIGO<span class="sub-text">Online Restaurant Reservation</span></span>
        </a>
        <div class="nav-buttons">
            <a href="../forms/homepage.php" class="nav-btn nav-btn-outline">Home</a>
            <a href="../forms/signup.php" class="nav-btn nav-btn-primary">Register</a>
        </div>
    </nav>

    <!-- ===== Lockout Overlay Modal (CEDULA-style) ===== -->
    <div id="validationModal" style="display:none;" <?php if ($lockoutActive): ?>data-lockout-active="true"
            data-lockout-time="
        <?php echo $lockoutTime; ?>" data-failed-attempts="
        <?php echo $failedAttempts; ?>"
        <?php endif; ?>>
        <div class="modal-content">
            <svg class="error-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="#e66868" width="2em"
                height="2em">
                <path
                    d="M256 512C397.4 512 512 397.4 512 256C512 114.6 397.4 0 256 0C114.6 0 0 114.6 0 256C0 397.4 114.6 512 256 512zM232 128C232 119.2 239.2 112 248 112H264C272.8 112 280 119.2 280 128V288C280 296.8 272.8 304 264 304H248C239.2 304 232 296.8 232 288V128zM256 384C238.3 384 224 369.7 224 352C224 334.3 238.3 320 256 320C273.7 320 288 334.3 288 352C288 369.7 273.7 384 256 384z" />
            </svg>
            <div class="text">
                <span>Too Many Failed Attempts</span>
                <div id="timer">Try Again in <span id="countdown">0</span> seconds</div>
            </div>
        </div>
    </div>

    <div class="login-page">
        <!-- ===== Left Panel ===== -->
        <div class="login-left">
            <div class="login-illustration">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" fill="none">
                    <ellipse cx="100" cy="160" rx="70" ry="10" fill="rgba(255,255,255,0.1)" />
                    <rect x="50" y="90" width="100" height="8" rx="4" fill="#c8a951" />
                    <rect x="60" y="98" width="6" height="60" rx="3" fill="rgba(255,255,255,0.3)" />
                    <rect x="134" y="98" width="6" height="60" rx="3" fill="rgba(255,255,255,0.3)" />
                    <ellipse cx="75" cy="85" rx="16" ry="6" fill="rgba(255,255,255,0.6)" />
                    <ellipse cx="125" cy="85" rx="16" ry="6" fill="rgba(255,255,255,0.6)" />
                    <rect x="97" y="60" width="6" height="30" rx="3" fill="#c8a951" />
                    <ellipse cx="100" cy="58" rx="5" ry="7" fill="#f59e0b" opacity="0.8" />
                    <ellipse cx="100" cy="56" rx="3" ry="5" fill="#fbbf24" />
                    <path d="M68 82V72c0-5 4-9 7-9s7 4 7 9v10" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"
                        fill="none" />
                    <path d="M118 82V72c0-5 4-9 7-9s7 4 7 9v10" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"
                        fill="none" />
                    <circle cx="40" cy="40" r="2" fill="#c8a951" opacity="0.6" />
                    <circle cx="160" cy="50" r="1.5" fill="#c8a951" opacity="0.5" />
                    <circle cx="150" cy="30" r="2.5" fill="#c8a951" opacity="0.4" />
                    <circle cx="50" cy="60" r="1.5" fill="#c8a951" opacity="0.5" />
                </svg>
            </div>
            <h2>Welcome Back</h2>
            <p class="login-left-subtitle">Sign in to manage your reservations and discover new dining experiences.</p>
            <ul class="login-features">
                <li><span class="feature-dot"></span> Instant Confirmation</li>
                <li><span class="feature-dot"></span> 500+ Partner Restaurants</li>
                <li><span class="feature-dot"></span> Free Cancellation</li>
            </ul>
        </div>

        <!-- ===== Right Panel — Form ===== -->
        <div class="login-right">
            <div class="login-form-container">
                <h1>Sign In</h1>
                <p class="login-form-subtitle">Enter your credentials to access your account</p>

                <div id="lockoutTimer" class="lockout-timer" style="display:none;"></div>

                <form class="login-form" id="loginForm" method="POST" autocomplete="on">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-with-icon">
                            <i class="fa-solid fa-user input-icon"></i>
                            <input type="text" id="username" name="username" placeholder="Enter username">
                        </div>
                        <span id="validationMess" class="userValidationMess"></span>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" placeholder="Enter password">
                            <button type="button" class="password-toggle" id="togglePassword"
                                aria-label="Toggle password visibility">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <span id="validationMessPw" class="userValidationMess"></span>
                        <!-- Server-side error display (CEDULA-style) -->
                        <span id="serverError" class="error-text"
                            style="display:block;text-align:center;margin-top:0.5rem;color:#dc2626;">
                            <?php if ($login_error)
                                echo htmlspecialchars($login_error); ?>
                        </span>
                    </div>

                    <button type="submit" class="login-btn" id="loginBtn">
                        <i class="fa-solid fa-right-to-bracket"></i> SIGN IN
                    </button>
                </form>

                <a href="#" id="forgotPasswordLink" class="forgot-link" style="display: none;">Forgot your password?</a>
            </div>
        </div>
    </div>

    <!-- ===== Lockout Modal (CEDULA-style) ===== -->
    <div id="lockoutModal" class="lockout-modal-overlay">
        <div class="lockout-modal">
            <div class="lockout-modal-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3>Too Many Failed Attempts</h3>
            <p id="lockoutModalMessage"></p>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/layout/footer.php'; ?>

    <!-- ===== Enhanced Forgot Password Modal (CEDULA-style 5-step) ===== -->
    <div class="fp-modal-overlay" id="fpModalOverlay" style="display: none;">
        <div class="fp-modal">
            <button class="fp-close" id="fpClose">&times;</button>

            <!-- STEP 1: Enter ID -->
            <div class="fp-step active" id="fpStep1">
                <div class="fp-icon"><i class="fa-solid fa-id-card"></i></div>
                <h3>Forgot Password</h3>
                <p class="fp-subtitle">Enter your ID number to verify your identity.</p>
                <div class="form-group">
                    <label for="fpUserId">ID Number</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-user-tag input-icon"></i>
                        <input type="text" id="fpUserId" placeholder="XXXX-XXXX" maxlength="9">
                    </div>
                </div>
                <span id="fpStep1Error" class="error-text"></span>
                <button class="fp-btn" id="fpStep1Btn">VERIFY IDENTITY</button>
            </div>

            <!-- STEP 2: Confirm User -->
            <div class="fp-step" id="fpStep2">
                <div class="fp-icon"><i class="fa-solid fa-user-check"></i></div>
                <div class="fp-user-info-header" style="background: #f8fafc; padding: 1.25rem; border-radius: 15px; margin-bottom: 1.5rem; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); display:none;">
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div class="fp-id-row" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px dashed #e2e8f0; margin-bottom: 4px;">
                            <span style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.025em;">Account ID:</span>
                            <span class="fp-val-id" style="font-family: 'Monaco', 'Consolas', monospace; font-weight: 800; color: #1e293b; background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-size: 1rem;"></span>
                        </div>
                        <div class="fp-extra-info" style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.85rem; font-weight: 700; color: #64748b;">Username:</span>
                            <span class="fp-val-username" style="font-weight: 600; color: #334155;"></span>
                        </div>
                        <div class="fp-extra-info" style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.85rem; font-weight: 700; color: #64748b;">Email Address:</span>
                            <span class="fp-val-email" style="font-weight: 600; color: #334155;"></span>
                        </div>
                    </div>
                </div>
                <h3>Confirm Account</h3>
                <p class="fp-subtitle">Is this you? We'll send a code to this email.</p>

                <button class="fp-btn" id="fpStep2Btn">YES, SEND CODE</button>
                <button class="fp-btn" id="fpStep2Back" style="background:none; color:#64748b; margin-top:10px;">Not me?
                    Try again</button>
            </div>

            <!-- STEP 3: Enter OTP -->
            <div class="fp-step" id="fpStep3">
                <div class="fp-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
                <div class="fp-user-info-header" style="background: #f8fafc; padding: 1.25rem; border-radius: 15px; margin-bottom: 1.5rem; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); display:none;">
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div class="fp-id-row" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px dashed #e2e8f0; margin-bottom: 4px;">
                            <span style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.025em;">Account ID:</span>
                            <span class="fp-val-id" style="font-family: 'Monaco', 'Consolas', monospace; font-weight: 800; color: #1e293b; background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-size: 1rem;"></span>
                        </div>
                        <div class="fp-extra-info" style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.85rem; font-weight: 700; color: #64748b;">Username:</span>
                            <span class="fp-val-username" style="font-weight: 600; color: #334155;"></span>
                        </div>
                        <div class="fp-extra-info" style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.85rem; font-weight: 700; color: #64748b;">Email Address:</span>
                            <span class="fp-val-email" style="font-weight: 600; color: #334155;"></span>
                        </div>
                    </div>
                </div>
                <h3>Enter Verification Code</h3>
                <p class="fp-subtitle">Please enter the 6-digit code sent to your email.</p>
                <div class="form-group">
                    <label for="fpOtp">Verification Code</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-key input-icon"></i>
                        <input type="password" id="fpOtp" placeholder="000000" maxlength="6"
                            style="text-align: center; letter-spacing: 0.2em; font-size: 1.2rem; padding-right: 45px;">
                        <button type="button" class="password-toggle" id="toggleFpOtp" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b;">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
                <span id="fpStep3Error" class="error-text"></span>
                <button class="fp-btn" id="fpStep3Btn">VERIFY CODE</button>
                <div class="otp-resend">
                    <span id="fpTimerText">Resend available in <span id="fpCountdown">60</span>s</span>
                    <button id="fpResendBtn" style="display:none;">Resend Code</button>
                </div>
            </div>

            <!-- Step 4: Security Questions -->
            <div id="fpStep4" class="fp-step">
                <div class="fp-icon"><i class="fa-solid fa-question-circle"></i></div>
                <div class="fp-user-info-header" style="background: #f8fafc; padding: 1.25rem; border-radius: 15px; margin-bottom: 1.5rem; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); display:none;">
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div class="fp-id-row" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px dashed #e2e8f0; margin-bottom: 4px;">
                            <span style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.025em;">Account ID:</span>
                            <span class="fp-val-id" style="font-family: 'Monaco', 'Consolas', monospace; font-weight: 800; color: #1e293b; background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-size: 1rem;"></span>
                        </div>
                        <div class="fp-extra-info" style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.85rem; font-weight: 700; color: #64748b;">Username:</span>
                            <span class="fp-val-username" style="font-weight: 600; color: #334155;"></span>
                        </div>
                        <div class="fp-extra-info" style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.85rem; font-weight: 700; color: #64748b;">Email Address:</span>
                            <span class="fp-val-email" style="font-weight: 600; color: #334155;"></span>
                        </div>
                    </div>
                </div>
                <h3>Security Questions</h3>
                <p class="fp-subtitle">Please answer your security questions to continue.</p>
                <div id="fpQuestionsContainer">
                    <div class="form-group" style="text-align: left; margin-bottom: 15px;">
                        <label>Security Question 1</label>
                        <select id="forgotQ1" class="form-control" style="margin-bottom: 10px; width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                            <option value="">-- Choose a Question --</option>
                            <option value="Who is your bestfriend in elementary?">Who is your bestfriend in elementary?</option>
                            <option value="What is the name of your pet?">What is the name of your pet?</option>
                            <option value="Who is your favorite teacher in highschool?">Who is your favorite teacher in highschool?</option>
                            <option value="What was your first car?">What was your first car?</option>
                            <option value="In what city were you born?">In what city were you born?</option>
                        </select>
                        <div class="input-with-icon">
                            <i class="fa-solid fa-shield-halved input-icon"></i>
                            <input type="password" id="fpAns1" class="form-control" placeholder="Your answer">
                            <button type="button" class="password-toggle fp-toggle" id="toggleFpAns1" aria-label="Toggle visibility">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group" style="text-align: left; margin-bottom: 15px;">
                        <label>Security Question 2</label>
                        <select id="forgotQ2" class="form-control" style="margin-bottom: 10px; width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                            <option value="">-- Choose a Question --</option>
                            <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                            <option value="What elementary school did you attend?">What elementary school did you attend?</option>
                            <option value="What is your favorite food?">What is your favorite food?</option>
                            <option value="What was your childhood nickname?">What was your childhood nickname?</option>
                            <option value="What is the name of your best friend?">What is the name of your best friend?</option>
                        </select>
                        <div class="input-with-icon">
                            <i class="fa-solid fa-shield-halved input-icon"></i>
                            <input type="password" id="fpAns2" class="form-control" placeholder="Your answer">
                            <button type="button" class="password-toggle fp-toggle" id="toggleFpAns2" aria-label="Toggle visibility">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group" style="text-align: left; margin-bottom: 15px;">
                        <label>Security Question 3</label>
                        <select id="forgotQ3" class="form-control" style="margin-bottom: 10px; width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                            <option value="">-- Choose a Question --</option>
                            <option value="What is your father's middle name?">What is your father's middle name?</option>
                            <option value="What street did you grow up on?">What street did you grow up on?</option>
                            <option value="What is your favorite movie?">What is your favorite movie?</option>
                            <option value="What is the name of your first pet?">What is the name of your first pet?</option>
                            <option value="What year did you graduate high school?">What year did you graduate high school?</option>
                        </select>
                        <div class="input-with-icon">
                            <i class="fa-solid fa-shield-halved input-icon"></i>
                            <input type="password" id="fpAns3" class="form-control" placeholder="Your answer">
                            <button type="button" class="password-toggle fp-toggle" id="toggleFpAns3" aria-label="Toggle visibility">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <span id="fpStep4Error" class="error-text"></span>
                <button type="button" class="fp-btn" id="fpStep4Btn">VERIFY ANSWERS</button>
            </div>

            <!-- Step 5: Change Password -->
            <div id="fpStep5" class="fp-step">
                <div class="fp-icon"><i class="fa-solid fa-key"></i></div>
                <h3>Reset Password</h3>
                <p class="fp-subtitle">Create a new password for your account.</p>
                <div class="form-group">
                    <label for="fpNewPass">New Password</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="fpNewPass" placeholder="Enter new password" minlength="8">
                        <button type="button" class="password-toggle fp-toggle" aria-label="Toggle visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <span id="fpPwStrength" style="font-size: 0.8rem; margin-top: 5px; display: block;"></span>
                </div>
                <div class="form-group">
                    <label for="fpConfirmPass">Confirm New Password</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="fpConfirmPass" placeholder="Confirm new password" minlength="8">
                        <button type="button" class="password-toggle fp-toggle" aria-label="Toggle visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <span id="fpPwMatch" style="font-size: 0.8rem; margin-top: 5px; display: block;"></span>
                </div>
                <span id="fpStep5Error" class="error-text"></span>
                <span id="fpStep5Success" class="success-text" style="display: none;"></span>
                <button type="button" class="fp-btn" id="fpStep5Btn">RESET PASSWORD</button>
            </div>
        </div>
    </div>

    <script>
        window.BASE_URL = '<?php echo $baseUrl; ?>';
        window.LOGIN_API = '../database/login.php';
        window.CHECK_ID_API = '../database/check_id.php';
        window.FORGOT_PASSWORD_API = '../database/forgot_password.php';
    </script>
    <script src="<?php echo $baseUrl; ?>/js/serve_asset.php?file=login.js&v=<?php echo time(); ?>"></script>
</body>

</html>