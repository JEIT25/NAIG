<?php
/**
 * Forgot Password - Enhanced Flow
 * Step 1: ID Input & Format
 * Step 2: User Details Confirmation
 * Step 3: OTP Verification
 * (Version: Enhanced Flow v1.1)
 */
session_start();
require_once __DIR__ . '/../includes/path_helper.php';
$basePath = getBasePath(__FILE__);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>Forgot Password - FoodGrab</title>
    <style>
        .step-container { display: none; }
        .step-container.active { display: block; animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .user-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .user-card-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }
        .user-card-row:last-child { margin-bottom: 0; }
        .user-card-label { color: #64748b; font-weight: 500; }
        .user-card-value { color: #0f172a; font-weight: 600; }

        .timer-text {
            color: #64748b; font-size: 0.9rem; margin-top: 1rem;
        }
    </style>
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
            <h2>Forgot Password</h2>

            <div id="globalError" class="error-message" style="display: none;"></div>
            <div id="globalSuccess" class="success-message" style="display: none;"></div>

            <!-- STEP 1: Enter ID -->
            <div id="step1" class="step-container active">
                <p>Enter your ID number to verify your identity</p>
                <form id="formStep1" onsubmit="handleStep1(event)">
                    <div class="form-group">
                        <label for="user_id">ID Number:</label>
                        <input type="text" id="user_id" name="user_id" required
                               placeholder="XXXX-XXXX" maxlength="9">
                    </div>
                    <button type="submit" class="btn-primary" id="btnStep1">Verify Identity</button>
                </form>
            </div>

            <!-- STEP 2: Confirm User -->
            <div id="step2" class="step-container">
                <h3>Confirm Account</h3>
                <p>Is this you? We will send a verification code to this email.</p>

                <div class="user-card" id="userInfoCard">
                    <!-- Populated by JS -->
                </div>

                <button onclick="sendOTP()" class="btn-primary" id="btnSendOTP">Yes, Send Verification Code</button>
                <button onclick="resetFlow()" class="btn-secondary" style="background:none; border:none; color:#64748b; margin-top:10px;">Not me? Try again</button>
            </div>

            <!-- STEP 3: Verify OTP -->
            <div id="step3" class="step-container">
                <div id="step3IdentityHeader" style="background: #eff6ff; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; text-align: left; border-left: 4px solid var(--primary-color);">
                    <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 0.25rem;">Recovering account for:</div>
                    <div id="persistentName" style="font-weight: 800; color: #1e3a8a; font-size: 1.1rem;"></div>
                    <div id="persistentDetails" style="font-size: 0.85rem; color: #3b82f6; font-weight: 600;"></div>
                </div>

                <h3>Enter Verification Code</h3>
                <p>Please enter the 6-digit code sent to your email.</p>

                <form id="formStep3" onsubmit="handleStep3(event)">
                    <div class="form-group">
                        <label for="otp_code">Verification Code:</label>
                        <div style="position: relative;">
                            <input type="password" id="otp_code" name="otp_code" required
                                   placeholder="000000" maxlength="6" pattern="[0-9]{6}"
                                   autocomplete="one-time-code" style="text-align: center; letter-spacing: 0.5em; font-size: 1.25rem; padding-right: 45px;">
                            <i class="fa-solid fa-eye" id="toggleOtp" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8;"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" id="btnVerify">Verify Code</button>
                </form>

                <div class="timer-text">
                    <span id="timerContainer">Resend available in <span id="countdown">60</span>s</span>
                    <button id="btnResend" onclick="resendOTP()" style="display:none; background:none; border:none; color:var(--primary-color); cursor:pointer; font-weight:600; text-decoration:underline;">Resend Code</button>
                </div>
            </div>

        </div>
    </main>

    <footer>
        <div class="footer-bottom">
            <p>All rights reserved &copy; 2026</p>
        </div>
    </footer>

    <script>
        // --- ID Formatting ---
        const idInput = document.getElementById('user_id');

        idInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            if (value.length > 4) {
                value = value.slice(0, 4) + '-' + value.slice(4, 8);
            }
            e.target.value = value;
        });

        // --- Flow State ---
        function showError(msg) {
            const el = document.getElementById('globalError');
            el.textContent = msg;
            el.style.display = 'block';
            setTimeout(() => el.style.display = 'none', 5000);
        }

        function showStep(stepId) {
            document.querySelectorAll('.step-container').forEach(el => el.classList.remove('active'));
            document.getElementById(stepId).classList.add('active');
            document.getElementById('globalError').style.display = 'none';
        }

        function resetFlow() {
            document.getElementById('formStep1').reset();
            showStep('step1');
        }

        let userData = null;

        // --- STEP 1: Fetch User Info ---
        async function handleStep1(e) {
            e.preventDefault();
            const btn = document.getElementById('btnStep1');
            const userId = idInput.value;

            if (userId.length < 9) { // XXXX-XXXX is 9 chars
                showError('Please enter a valid ID (Format: XXXX-XXXX)');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Verifying...';

            try {
                const formData = new FormData();
                formData.append('user_id', userId);

                const response = await fetch('action_get_user_info.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    userData = data.user;
                    // Populate User Card
                    const card = document.getElementById('userInfoCard');
                    card.innerHTML = `
                        <div class="user-card-row">
                            <span class="user-card-label">ID Number</span>
                            <span class="user-card-value">${data.user.id}</span>
                        </div>
                        <div class="user-card-row">
                            <span class="user-card-label">Username</span>
                            <span class="user-card-value">@${data.user.username}</span>
                        </div>
                        <div class="user-card-row">
                            <span class="user-card-label">Name</span>
                            <span class="user-card-value">${data.user.name}</span>
                        </div>
                        <div class="user-card-row">
                            <span class="user-card-label">Email</span>
                            <span class="user-card-value">${data.user.email}</span>
                        </div>
                    `;
                    
                    // Populate persistent headers
                    document.getElementById('persistentName').textContent = data.user.name;
                    document.getElementById('persistentDetails').textContent = `ID: ${data.user.id} | @${data.user.username} | ${data.user.email}`;

                    showStep('step2');
                } else {
                    showError(data.message || 'User not found');
                }
            } catch (err) {
                showError('An error occurred. Please try again.');
                console.error(err);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Verify Identity';
            }
        }

        document.getElementById('toggleOtp').onclick = function() {
            const input = document.getElementById('otp_code');
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye');
            }
        };

        // --- STEP 2: Send OTP ---
        async function sendOTP() {
            const btn = document.getElementById('btnSendOTP');
            btn.disabled = true;
            btn.textContent = 'Sending Code...';

            try {
                const response = await fetch('forgot_password_send_otp.php', { method: 'POST' });
                const data = await response.json(); // It returns success/message

                if (data.success) {
                    showStep('step3');
                    startTimer();
                } else {
                    showError(data.message || 'Failed to send OTP.');
                    btn.disabled = false;
                    btn.textContent = 'Yes, Send Verification Code';
                }
            } catch (err) {
                showError('Network error. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Yes, Send Verification Code';
            }
        }

        // --- STEP 3: Verify OTP ---
        async function handleStep3(e) {
            e.preventDefault();
            const btn = document.getElementById('btnVerify');
            const otp = document.getElementById('otp_code').value;

            btn.disabled = true;
            btn.textContent = 'Verifying...';

            try {
                const formData = new FormData();
                formData.append('otp_code', otp);

                const response = await fetch('forgot_password_verify_otp.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    showError(data.message || 'Invalid code.');
                    btn.disabled = false;
                    btn.textContent = 'Verify Code';
                }
            } catch (err) {
                showError('Network error.');
                btn.disabled = false;
                btn.textContent = 'Verify Code';
            }
        }

        // --- Timer Logic ---
        let timerInterval;
        function startTimer() {
            let timeLeft = 60; // 60 seconds
            const countdownEl = document.getElementById('countdown');
            const container = document.getElementById('timerContainer');
            const resendBtn = document.getElementById('btnResend');

            container.style.display = 'inline';
            resendBtn.style.display = 'none';
            countdownEl.textContent = timeLeft;

            clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                timeLeft--;
                countdownEl.textContent = timeLeft;
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    container.style.display = 'none';
                    resendBtn.style.display = 'inline-block';
                }
            }, 1000);
        }

        async function resendOTP() {
            const resendBtn = document.getElementById('btnResend');
            resendBtn.textContent = 'Sending...';
            resendBtn.disabled = true;

            try {
                const response = await fetch('forgot_password_send_otp.php', { method: 'POST' });
                const data = await response.json();

                if (data.success) {
                    startTimer(); // Restart timer
                } else {
                    showError(data.message);
                }
            } catch (err) {
                showError('Network error.');
            } finally {
                resendBtn.textContent = 'Resend Code';
                resendBtn.disabled = false;
            }
        }
    </script>
</body>
</html>
