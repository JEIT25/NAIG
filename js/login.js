/**
 * NAIGO Login Page — JavaScript
 * Handles: login form submit, lockout timer, password toggle, forgot password 4-step flow
 */
(function () {
    window.BASE_URL = window.BASE_URL || 'http://localhost/NAIG';
    window.LOGIN_API = window.LOGIN_API || window.BASE_URL + '/php/database/login.php';
    window.FORGOT_PASSWORD_API = window.FORGOT_PASSWORD_API || window.BASE_URL + '/php/database/forgot_password.php';
})();

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    const serverError = document.getElementById('serverError');
    const passwordError = document.getElementById('passwordError');
    const lockoutTimer = document.getElementById('lockoutTimer');
    const loginBtn = document.getElementById('loginBtn');
    const togglePassword = document.getElementById('togglePassword');

    // ===== Universal Password Toggle =====
    document.addEventListener('click', (e) => {
        const toggleBtn = e.target.closest('.password-toggle');
        if (!toggleBtn) return;

        // Find the input in the same input-with-icon container
        const inputWithIcon = toggleBtn.closest('.input-with-icon');
        if (!inputWithIcon) return;

        const pwInput = inputWithIcon.querySelector('input');
        const icon = toggleBtn.querySelector('i');

        if (pwInput && icon) {
            if (pwInput.type === 'password') {
                pwInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                pwInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    });

    // ===== Lockout Logic =====
    function showLockout(failedAttempts, lockoutTime) {
        localStorage.setItem('failedAttempts', failedAttempts);
        if (lockoutTime > Date.now() / 1000) {
            const updateTimer = () => {
                const remaining = Math.max(0, lockoutTime - Math.floor(Date.now() / 1000));
                if (remaining <= 0) {
                    lockoutTimer.style.display = 'none';
                    loginBtn.disabled = false;
                    localStorage.removeItem('lockoutTime');
                    clearInterval(interval);
                    return;
                }
                lockoutTimer.style.display = 'block';
                lockoutTimer.textContent = `Too many failed attempts. Try again in ${remaining}s`;
                loginBtn.disabled = true;
            };
            localStorage.setItem('lockoutTime', lockoutTime);
            updateTimer();
            const interval = setInterval(updateTimer, 1000);
        }
    }

    // Check stored lockout on page load
    const storedLockout = localStorage.getItem('lockoutTime');
    if (storedLockout) {
        showLockout(parseInt(localStorage.getItem('failedAttempts') || '0'), parseInt(storedLockout));
    }

    // ===== Login Form Submit =====
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        serverError.textContent = '';
        if (passwordError) passwordError.textContent = '';

        const formData = new FormData(form);
        formData.append('isForm', true);
        formData.append('isRegisterRestrict', false);

        try {
            const response = await fetch(window.LOGIN_API, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                serverError.textContent = 'Server error. Please try again.';
                return;
            }

            if (data.redirect) {
                localStorage.clear();
                window.location.href = data.redirect;
                return;
            }
            if (data.error) {
                if (passwordError) passwordError.textContent = data.error;
                else serverError.textContent = data.error;
            }
            if (data.requireUsername) {
                serverError.textContent = data.requireUsername;
            }
            if (data.requirePw) {
                if (passwordError) passwordError.textContent = data.requirePw;
                else serverError.textContent = data.requirePw;
            }
            if (data.failed_attempts && data.lockout_time) {
                showLockout(data.failed_attempts, data.lockout_time);
            }
        } catch (err) {
            serverError.textContent = 'Network error. Please check your connection.';
        }
    });

    // ===== ENHANCED FORGOT PASSWORD FLOW =====
    const fpOverlay = document.getElementById('fpModalOverlay');
    const fpClose = document.getElementById('fpClose');
    // Select all potential triggers (link in auth/login.php and forms/login.php)
    const forgotLinks = document.querySelectorAll('.forgot-link, .forgot-pw');

    // State Variables
    let fpCurrentUserId = '';

    // ID Formatting
    const fpUserIdInput = document.getElementById('fpUserId');
    if (fpUserIdInput) {
        fpUserIdInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 4) {
                value = value.slice(0, 4) + '-' + value.slice(4, 9);
            }
            e.target.value = value;
        });
    }

    // Modal Controls
    function openFpModal() {
        if (fpOverlay) {
            fpOverlay.classList.add('active');
            showFpStep('fpStep1');
            if (fpUserIdInput) {
                fpUserIdInput.value = '';
                setTimeout(() => fpUserIdInput.focus(), 100);
            }
        }
    }

    function closeFpModal() {
        if (fpOverlay) fpOverlay.classList.remove('active');
    }

    if (forgotLinks.length > 0) {
        forgotLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                openFpModal();
            });
        });
    }

    if (fpClose) fpClose.addEventListener('click', closeFpModal);
    if (fpOverlay) {
        fpOverlay.addEventListener('click', (e) => {
            if (e.target === fpOverlay) closeFpModal();
        });
    }

    // Step Navigation
    function showFpStep(stepId) {
        document.querySelectorAll('.fp-step').forEach(el => el.classList.remove('active'));
        const step = document.getElementById(stepId);
        if (step) step.classList.add('active');
        // Clear errors
        ['fpStep1Error', 'fpStep3Error', 'fpStep4Error', 'fpStep5Error'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '';
        });
    }

    // --- STEP 1: Verify ID ---
    const fpStep1Btn = document.getElementById('fpStep1Btn');
    if (fpStep1Btn) {
        fpStep1Btn.addEventListener('click', async () => {
            const id = fpUserIdInput.value.trim();
            const errEl = document.getElementById('fpStep1Error');

            if (id.length < 9) {
                errEl.textContent = 'Please enter a valid ID (Format: XXXX-XXXX)';
                return;
            }

            fpStep1Btn.disabled = true;
            fpStep1Btn.textContent = 'Verifying...';

            const fd = new FormData();
            fd.append('user_id', id);

            try {
                // Adjust path based on where login.js is called
                // It's called from php/auth/login.php or php/forms/login.php
                // Both are effectively 1 level deep from root PHP folders relative to assets?
                // Actually server_asset.php serves this. baseUrl is defined at top.
                // But let's use relative path assumption from the PHP file location
                const apiUrl = window.BASE_URL + '/php/forms/action_get_user_info.php';

                const res = await fetch(apiUrl, { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    fpCurrentUserId = data.user.id;
                    const infoBox = document.getElementById('fpUserInfo');
                    infoBox.innerHTML = `
                        <p style="margin:5px 0"><strong>ID:</strong> ${data.user.id}</p>
                        <p style="margin:5px 0"><strong>Name:</strong> ${data.user.name}</p>
                        <p style="margin:5px 0"><strong>Email:</strong> ${data.user.email}</p>
                    `;
                    showFpStep('fpStep2');
                } else {
                    errEl.textContent = data.message || 'ID not found.';
                }
            } catch (e) {
                console.error(e);
                errEl.textContent = 'Network error. Please try again.';
            } finally {
                fpStep1Btn.disabled = false;
                fpStep1Btn.textContent = 'VERIFY IDENTITY';
            }
        });
    }

    // --- STEP 2: Confirm & Send OTP ---
    const fpStep2Btn = document.getElementById('fpStep2Btn');
    const fpStep2Back = document.getElementById('fpStep2Back');

    if (fpStep2Back) {
        fpStep2Back.addEventListener('click', () => showFpStep('fpStep1'));
    }

    if (fpStep2Btn) {
        fpStep2Btn.addEventListener('click', async () => {
            fpStep2Btn.disabled = true;
            fpStep2Btn.textContent = 'Sending...';

            try {
                const apiUrl = window.BASE_URL + '/php/forms/forgot_password_send_otp.php';
                const res = await fetch(apiUrl, { method: 'POST' });
                const text = await res.text(); // Get raw text first

                try {
                    const data = JSON.parse(text); // Try parsing
                    if (data.success) {
                        showFpStep('fpStep3');
                        startFpTimer();
                    } else {
                        alert(data.message || 'Failed to send OTP.');
                    }
                } catch (e) {
                    console.error("JSON Parse Error. Raw response:", text);
                    alert('Server error: Invalid response format. See console for details.');
                }
            } catch (e) {
                console.error(e);
                alert('Network error. Check console for details.');
            } finally {
                fpStep2Btn.disabled = false;
                fpStep2Btn.textContent = 'YES, SEND CODE';
            }
        });
    }

    // --- STEP 3: Verify OTP ---
    const fpStep3Btn = document.getElementById('fpStep3Btn');
    if (fpStep3Btn) {
        fpStep3Btn.addEventListener('click', async () => {
            const otpInput = document.getElementById('fpOtp');
            const otp = otpInput.value.trim();
            const errEl = document.getElementById('fpStep3Error');

            if (otp.length !== 6) {
                errEl.textContent = 'Please enter the 6-digit code.';
                return;
            }

            fpStep3Btn.disabled = true;
            fpStep3Btn.textContent = 'Verifying...';

            const fd = new FormData();
            fd.append('otp_code', otp);

            try {
                const apiUrl = window.BASE_URL + '/php/forms/forgot_password_verify_otp.php';
                const res = await fetch(apiUrl, { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    // Fetch security questions before showing Step 4
                    fetchSecurityQuestions();
                } else {
                    errEl.textContent = data.message || 'Invalid code.';
                }
            } catch (e) {
                errEl.textContent = 'Network error.';
            } finally {
                fpStep3Btn.disabled = false;
                fpStep3Btn.textContent = 'VERIFY CODE';
            }
        });
    }

    async function fetchSecurityQuestions() {
        const errEl = document.getElementById('fpStep3Error');
        try {
            const apiUrl = window.BASE_URL + '/php/forms/action_get_security_questions.php';
            const res = await fetch(apiUrl);
            const data = await res.json();

            if (data.success) {
                document.getElementById('fpQLabel1').textContent = data.questions[0];
                document.getElementById('fpQLabel2').textContent = data.questions[1];
                document.getElementById('fpQLabel3').textContent = data.questions[2];
                showFpStep('fpStep4');
            } else {
                errEl.textContent = data.message || 'Failed to load security questions.';
            }
        } catch (e) {
            errEl.textContent = 'Network error loading questions.';
        }
    }

    // --- STEP 4: Verify Security Answers ---
    const fpStep4Btn = document.getElementById('fpStep4Btn');
    if (fpStep4Btn) {
        fpStep4Btn.addEventListener('click', async () => {
            const ans1 = document.getElementById('fpAns1').value.trim();
            const ans2 = document.getElementById('fpAns2').value.trim();
            const ans3 = document.getElementById('fpAns3').value.trim();
            const errEl = document.getElementById('fpStep4Error');

            if (!ans1 || !ans2 || !ans3) {
                errEl.textContent = 'Please answer all questions.';
                return;
            }

            fpStep4Btn.disabled = true;
            fpStep4Btn.textContent = 'Verifying...';

            const fd = new FormData();
            fd.append('secure_answer', ans1);
            fd.append('secure_answer2', ans2);
            fd.append('secure_answer3', ans3);

            try {
                const apiUrl = window.BASE_URL + '/php/forms/action_verify_security_answers.php';
                const res = await fetch(apiUrl, { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    showFpStep('fpStep5');
                } else {
                    errEl.textContent = data.message || 'Incorrect answers.';
                }
            } catch (e) {
                errEl.textContent = 'Network error.';
            } finally {
                fpStep4Btn.disabled = false;
                fpStep4Btn.textContent = 'VERIFY ANSWERS';
            }
        });
    }

    // --- STEP 5: Reset Password ---
    const fpStep5Btn = document.getElementById('fpStep5Btn');
    const fpNewPass = document.getElementById('fpNewPass');
    const fpConfirmPass = document.getElementById('fpConfirmPass');
    const fpPwStrength = document.getElementById('fpPwStrength');
    const fpPwMatch = document.getElementById('fpPwMatch');

    function displayFpPasswordStrength(val) {
        if (!fpPwStrength) return;

        if (val.length < 8) {
            fpPwStrength.innerText = '';
            return;
        }

        let hasUppercase = /[A-Z]/.test(val);
        let hasLowercase = /[a-z]/.test(val);
        let hasNumber = /\d/.test(val);
        let hasSpecialChar = /[^A-Za-z0-9]/.test(val);

        let score = 0;
        if (hasUppercase) score++;
        if (hasLowercase) score++;
        if (hasNumber) score++;
        if (hasSpecialChar) score++;

        if (score === 4) {
            fpPwStrength.innerText = 'Strong Password';
            fpPwStrength.style.color = '#059669';
        } else if (score >= 2) {
            fpPwStrength.innerText = 'Medium Password';
            fpPwStrength.style.color = '#ff8c00';
        } else {
            fpPwStrength.innerText = 'Weak Password';
            fpPwStrength.style.color = '#f50606';
        }
    }

    if (fpNewPass) {
        fpNewPass.addEventListener('input', () => {
            displayFpPasswordStrength(fpNewPass.value);
            // Also update match if confirm has value
            if (fpConfirmPass.value) {
                if (fpNewPass.value === fpConfirmPass.value) {
                    fpPwMatch.innerText = "Passwords matched";
                    fpPwMatch.style.color = "#059669";
                } else {
                    fpPwMatch.innerText = "Passwords did not match";
                    fpPwMatch.style.color = "#f50606";
                }
            }
        });
    }

    if (fpConfirmPass) {
        fpConfirmPass.addEventListener('input', () => {
            if (fpNewPass.value !== fpConfirmPass.value) {
                fpPwMatch.innerText = "Passwords did not match";
                fpPwMatch.style.color = "#f50606";
            } else {
                fpPwMatch.innerText = "Passwords matched";
                fpPwMatch.style.color = "#059669";
            }
        });
    }

    if (fpStep5Btn) {
        fpStep5Btn.addEventListener('click', async () => {
            const newPass = document.getElementById('fpNewPass').value.trim();
            const confirmPass = document.getElementById('fpConfirmPass').value.trim();
            const errEl = document.getElementById('fpStep5Error');
            const successEl = document.getElementById('fpStep5Success');

            // Reset msgs
            errEl.textContent = '';
            successEl.textContent = '';
            successEl.style.display = 'none';

            if (newPass.length < 8) {
                errEl.textContent = 'Password must be at least 8 characters.';
                return;
            }
            if (newPass !== confirmPass) {
                errEl.textContent = 'Passwords do not match.';
                return;
            }

            fpStep5Btn.disabled = true;
            fpStep5Btn.textContent = 'Resetting...';

            const fd = new FormData();
            fd.append('new_password', newPass);
            fd.append('confirm_password', confirmPass);

            try {
                const apiUrl = window.BASE_URL + '/php/forms/action_reset_password.php';
                const res = await fetch(apiUrl, { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    successEl.textContent = data.message;
                    successEl.style.display = 'block';
                    fpStep5Btn.textContent = 'SUCCESS';

                    // Close modal after 2 seconds
                    setTimeout(() => {
                        closeFpModal();
                        // Reset form
                        document.getElementById('fpNewPass').value = '';
                        document.getElementById('fpConfirmPass').value = '';
                        fpStep5Btn.textContent = 'RESET PASSWORD';
                        fpStep5Btn.disabled = false;
                    }, 2000);
                } else {
                    errEl.textContent = data.message || 'Failed to reset password.';
                    fpStep5Btn.disabled = false;
                    fpStep5Btn.textContent = 'RESET PASSWORD';
                }
            } catch (e) {
                errEl.textContent = 'Network error.';
                fpStep5Btn.disabled = false;
                fpStep5Btn.textContent = 'RESET PASSWORD';
            }
        });
    }

    // Timer Logic
    let fpTimerInterval;
    const fpResendBtn = document.getElementById('fpResendBtn');

    function startFpTimer() {
        let sec = 60;
        const timerText = document.getElementById('fpTimerText');
        const countdown = document.getElementById('fpCountdown');

        if (timerText) timerText.style.display = 'inline';
        if (fpResendBtn) fpResendBtn.style.display = 'none';

        if (fpTimerInterval) clearInterval(fpTimerInterval);

        if (countdown) countdown.textContent = sec;

        fpTimerInterval = setInterval(() => {
            sec--;
            if (countdown) countdown.textContent = sec;

            if (sec <= 0) {
                clearInterval(fpTimerInterval);
                if (timerText) timerText.style.display = 'none';
                if (fpResendBtn) {
                    fpResendBtn.style.display = 'inline-block';
                    fpResendBtn.onclick = resendFpOtp;
                }
            }
        }, 1000);
    }

    async function resendFpOtp() {
        if (fpResendBtn) {
            fpResendBtn.textContent = 'Sending...';
            fpResendBtn.disabled = true;
        }

        try {
            const apiUrl = window.BASE_URL + '/php/forms/forgot_password_send_otp.php';
            const res = await fetch(apiUrl, { method: 'POST' });
            const data = await res.json();

            if (data.success) {
                startFpTimer();
            } else {
                alert(data.message);
            }
        } catch (e) {
            alert('Network error.');
        } finally {
            if (fpResendBtn) {
                fpResendBtn.textContent = 'Resend Code';
                fpResendBtn.disabled = false;
            }
        }
    }
});