<?php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: ' . (function_exists('getBaseUrl') ? getBaseUrl() : 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/NAIG') . '/php/auth/dashboard.php');
    exit;
}
require_once __DIR__ . '/../includes/auth.php';
header('Location: ' . getBaseUrl() . '/php/auth/login.php');
exit;
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FoodGrab</title>
    <link rel="stylesheet" href="../../css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="../../css/serve_asset.php?file=login.css">
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
            <a href="./homepage.php" class="nav-link" id="home">Home</a>
            <a href="signup.php" class="nav-link" id="register">Registered</a>
        </div>
    </nav>

    <main>
        <div id="validationModal">
            <div class="modal-content">
                <svg class="error-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="#e66868"
                    width="2em" height="2em">
                    <path
                        d="M256 512C397.4 512 512 397.4 512 256C512 114.6 397.4 0 256 0C114.6 0 0 114.6 0 256C0 397.4 114.6 512 256 512zM232 128C232 119.2 239.2 112 248 112H264C272.8 112 280 119.2 280 128V288C280 296.8 272.8 304 264 304H248C239.2 304 232 296.8 232 288V128zM256 384C238.3 384 224 369.7 224 352C224 334.3 238.3 320 256 320C273.7 320 288 334.3 288 352C288 369.7 273.7 384 256 384z" />
                </svg>
                <div class="text">
                    <span>Too Many Failed Attempts</span>
                    <div id="timer">Try Again in <span id="countdown"></span> seconds</div>
                </div>
            </div>
        </div>

        <div id="userNotFoundModal" class="modal-simple-alert">
            <div class="modal-simple-alert-content">
                <svg class="error-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="#e66868" width="2em" height="2em">
                    <path d="M256 512C397.4 512 512 397.4 512 256C512 114.6 397.4 0 256 0C114.6 0 0 114.6 0 256C0 397.4 114.6 512 256 512zM232 128C232 119.2 239.2 112 248 112H264C272.8 112 280 119.2 280 128V288C280 296.8 272.8 304 264 304H248C239.2 304 232 296.8 232 288V128zM256 384C238.3 384 224 369.7 224 352C224 334.3 238.3 320 256 320C273.7 320 288 334.3 288 352C288 369.7 273.7 384 256 384z" />
                </svg>
                <span class="modal-simple-alert-text">User ID not found!</span>
                <button id="userNotFoundOkBtn" class="submitBtn">Okay</button>
            </div>
        </div>
        <form class="login-form" id="loginForm" method="POST" action="http://localhost/NAIG/php/forms/login.php">
            <div class="left-side">
                <img class="form-img" src="../../images/background2.png" alt="Food Delivery">
            </div>

            <div class="right-side">
                <h2>Login</h2>
                <div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" >
                            <span id="validationMess" class="userValidationMess" ></span>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <div class="password-container">
                                <input type="password" id="password" name="password" placeholder="" autocomplete>
                                <svg id="togglePassword" class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" onclick="togglePassword()"
                                    viewBox="0 0 24 24">
                                    <path d="M1 12s4.5-8 11-8 11 8 11 8-4.5 8-11 8S1 12 1 12z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </div>
                            <span id="validationMessPw" class="userValidationMess" style="<?php echo $login_error ? 'display: block;' : ''; ?>">
    <?php if ($login_error)
    echo htmlspecialchars($login_error); ?>
</span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submitBtn">Login</button>

                <a href="forgot_password.php" class="forgot-pw">Forgot Password? Reset Here</a>
            </div>
        </form>
    </main>

    <footer>
        <div class="footer-upper">
            <div class="footer-left">
                <div class="logo-area">
                    <img src="../../images/logo4.png" alt="FoodGrab Icon" class="logo">
                    <div class="text">
                        <h3>FoodGrab</h3>
                        <p class="sub-text">Online Food Delivery</p>
                    </div>
                </div>
                <p class="description">We bring the best local flavors right to your door with fast, reliable delivery and a smile.</p>
            </div>

            <!-- <div class="footer-right">
                <div class="wrapper">
                    <h4>Partners</h4>
                    <p>Partner with us</p>
                    <p>Ride with us</p>
                </div>
            </div> -->
        </div>

        <div class="footer-bottom">
            <p>All rights reserved &copy; 2026</p>
        </div>
    </footer>

    <!-- ===== Enhanced Forgot Password Modal ===== -->
<div class="fp-modal-overlay" id="fpModalOverlay">
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
            <h3>Confirm Account</h3>
            <p class="fp-subtitle">Is this you? We'll send a code to this email.</p>

            <div class="user-details-box" id="fpUserInfo" style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: left; border: 1px solid #e2e8f0;">
                <!-- Populated by JS -->
            </div>

            <button class="fp-btn" id="fpStep2Btn">YES, SEND CODE</button>
            <button class="fp-btn" id="fpStep2Back" style="background:none; color:#64748b; margin-top:10px;">Not me? Try again</button>
        </div>

        <!-- Step 3: Verify OTP -->
        <div id="fpStep3" class="fp-step">
            <div class="fp-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
            <h3>Enter Verification Code</h3>
            <p class="fp-subtitle">Please enter the 6-digit code sent to your email.</p>

            <div class="form-group">
                <label for="fpOtp">Verification Code</label>
                <input type="password" id="fpOtp" placeholder="000000" maxlength="6" style="text-align: center; letter-spacing: 0.2em; font-size: 1.2rem;">
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
            <h3>Security Questions</h3>
            <p class="fp-subtitle">Please answer your security questions to continue.</p>

            <div id="fpQuestionsContainer">
                <div class="form-group">
                    <label id="fpQLabel1">Question 1</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-shield-halved input-icon"></i>
                        <input type="password" id="fpAns1" placeholder="Your answer">
                        <button type="button" class="password-toggle fp-toggle" aria-label="Toggle visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label id="fpQLabel2">Question 2</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-shield-halved input-icon"></i>
                        <input type="password" id="fpAns2" placeholder="Your answer">
                        <button type="button" class="password-toggle fp-toggle" aria-label="Toggle visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label id="fpQLabel3">Question 3</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-shield-halved input-icon"></i>
                        <input type="password" id="fpAns3" placeholder="Your answer">
                        <button type="button" class="password-toggle fp-toggle" aria-label="Toggle visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <span id="fpStep4Error" class="error-text"></span>
            <button class="fp-btn" id="fpStep4Btn">VERIFY ANSWERS</button>
        </div>

        <!-- Step 5: Change Password -->
        <div id="fpStep5" class="fp-step">
            <div class="fp-icon"><i class="fa-solid fa-lock"></i></div>
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
                <label for="fpConfirmPass">Confirm Password</label>
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

            <button class="fp-btn" id="fpStep5Btn">RESET PASSWORD</button>
        </div>
    </div>
</div>

    <script src="../../js/serve_asset.php?file=login.js"></script>
</body>

</html>