document.addEventListener('DOMContentLoaded', () => {

    // --- Get Elements ---
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const pwStrengthSpan = document.getElementById('pwStrength');
    const pwMatchSpan = document.getElementById('pwMatch');
    const form = document.getElementById('changePasswordForm');
    // *** Get Success Modal Elements ***
    const successModal = document.getElementById('successModal');
    const successOkBtn = document.getElementById('successOkBtn');
    const bodyElement = document.body; // Get the body element

    // --- Password Toggle (Eye Icon) Logic ---
    document.addEventListener('click', (e) => {
        const toggleBtn = e.target.closest('.eye-icon-btn');
        if (!toggleBtn) return;

        const container = toggleBtn.closest('.password-container');
        if (!container) return;

        const input = container.querySelector('input');
        const icon = toggleBtn.querySelector('i');

        if (input && icon) {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            icon.classList.toggle('fa-eye', !isPassword);
            icon.classList.toggle('fa-eye-slash', isPassword);
        }
    });

    // --- Password Strength Checker Logic ---
    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        return strength;
    }

    if (newPasswordInput && pwStrengthSpan) {
        newPasswordInput.addEventListener('input', () => {
            const password = newPasswordInput.value;
            if (password.length === 0) {
                pwStrengthSpan.style.display = 'none';
                pwStrengthSpan.textContent = '';
            } else {
                const strength = checkPasswordStrength(password);
                pwStrengthSpan.style.display = 'block';
                switch (strength) {
                    case 0: case 1: case 2:
                        pwStrengthSpan.textContent = 'Weak';
                        pwStrengthSpan.style.color = '#f50606'; break;
                    case 3:
                        pwStrengthSpan.textContent = 'Medium';
                        pwStrengthSpan.style.color = '#E99002'; break;
                    case 4: case 5:
                        pwStrengthSpan.textContent = 'Strong';
                        pwStrengthSpan.style.color = '#2BB673'; break;
                    default: pwStrengthSpan.textContent = '';
                }
            }
            checkPasswordMatch();
        });
    }

    // --- Password Match Logic ---
    function checkPasswordMatch() {
        const newPw = newPasswordInput.value;
        const confirmPw = confirmPasswordInput.value;
        if (confirmPw.length > 0 || newPw.length > 0) {
            pwMatchSpan.style.display = 'block';
            if (newPw === confirmPw) {
                pwMatchSpan.textContent = 'Passwords match';
                pwMatchSpan.style.color = '#2BB673';
            } else {
                pwMatchSpan.textContent = 'Passwords do not match';
                pwMatchSpan.style.color = '#f50606';
            }
        } else {
            pwMatchSpan.style.display = 'none';
        }
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', checkPasswordMatch);
    }

    // --- Form Validation on Submit ---
    if (form) {
        form.addEventListener('submit', (e) => {
            const newPw = newPasswordInput.value;
            const confirmPw = confirmPasswordInput.value;
            if (newPw !== confirmPw) {
                e.preventDefault();
                pwMatchSpan.style.display = 'block';
                pwMatchSpan.textContent = 'Passwords do not match';
                pwMatchSpan.style.color = '#f50606';
            }
            // Strength check removed as per previous request
        });
    }

    // *** SUCCESS MODAL LOGIC ***
    if (successModal && successOkBtn && bodyElement) {
        const showModal = bodyElement.getAttribute('data-show-modal') === 'true';
        const redirectUrl = bodyElement.getAttribute('data-redirect-url');

        if (showModal) {
            successModal.style.display = 'flex'; // Show the modal
        }

        successOkBtn.addEventListener('click', () => {
            successModal.style.display = 'none'; // Hide the modal
            if (redirectUrl) {
                window.location.href = redirectUrl; // Redirect if URL is set
            }
        });
    }
    // *** END SUCCESS MODAL LOGIC ***

});