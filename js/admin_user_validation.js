/**
 * Admin/Superadmin User Form Validation
 * Ported from CEDULA — same restrictions for modal-based Add/Edit User forms.
 *
 * Usage:
 *   In your page, include this script, then call:
 *     AdminUserValidation.init(formElement, options)
 *
 *   options = {
 *     apiBase: '../../php/database',  // path to check_unique.php etc
 *     roleSelector: '#role',          // if present, toggles security questions
 *     securitySection: '#securitySection',
 *     onSuccess: function(formData){} // called after validation passes
 *   }
 */
const AdminUserValidation = (function () {
    let form, opts, debounceTimer, currentlyEditing = false;

    // ---- Inline error helpers ----
    function showError(fieldId, message) {
        const field = form.querySelector('#' + fieldId) || form.querySelector('[name="' + fieldId + '"]');
        const errorSpan = form.querySelector('#' + fieldId + 'Error');
        if (field) field.classList.add('error');
        if (errorSpan) { errorSpan.innerText = message; errorSpan.classList.add('active'); }
    }

    function clearError(fieldId) {
        const field = form.querySelector('#' + fieldId) || form.querySelector('[name="' + fieldId + '"]');
        const errorSpan = form.querySelector('#' + fieldId + 'Error');
        if (field) field.classList.remove('error');
        if (errorSpan) { errorSpan.innerText = ''; errorSpan.classList.remove('active'); }
    }

    function clearAllErrors() {
        form.querySelectorAll('.validation-message').forEach(s => { s.innerText = ''; s.classList.remove('active'); });
        form.querySelectorAll('.error').forEach(i => i.classList.remove('error'));
    }

    function val(id) {
        const el = form.querySelector('#' + id) || form.querySelector('[name="' + id + '"]');
        return el ? el.value : '';
    }

    // ---- Validation Functions ----

    async function validatePersonalInfo() {
        let ok = true;
        const firstName = val('firstName'), lastName = val('lastName');
        const middleInitial = val('middleInitial'), extension = val('extension');
        const sex = val('sex');

        if (firstName.length < 2 || firstName.length > 25) { showError('firstName', 'First name must be between 2 and 25 characters.'); ok = false; } else clearError('firstName');
        if (lastName.length < 2 || lastName.length > 25) { showError('lastName', 'Last name must be between 2 and 25 characters.'); ok = false; } else clearError('lastName');
        if (middleInitial.length > 1) { showError('middleInitial', 'Middle initial must be exactly 1 character.'); ok = false; } else clearError('middleInitial');
        if (extension.length > 4) { showError('extension', 'Name extension can be up to 4 characters.'); ok = false; } else clearError('extension');
        if (!sex || (sex !== 'male' && sex !== 'female')) { showError('sex', 'Please select a sex.'); ok = false; } else clearError('sex');

        // ID check (only when adding, not required for auto-gen)
        const customIdEl = form.querySelector('#customId');
        if (customIdEl && customIdEl.value.trim()) {
            const idVal = customIdEl.value.trim();
            if (!/^[0-9]{4}-[0-9]{4}$/.test(idVal)) { showError('customId', 'ID must be in xxxx-xxxx format.'); ok = false; }
            else { 
                const fd = new FormData();
                fd.append('field', 'id');
                fd.append('value', idVal);
                const hiddenId = form.querySelector('input[name="id"]');
                if (hiddenId && hiddenId.value) fd.append('exclude_id', hiddenId.value);
                try {
                    const res = await fetch(opts.apiBase + '/check_unique.php', { method: 'POST', body: fd });
                    const data = await res.json();
                    if (data.exists) { showError('customId', 'This ID is already taken.'); ok = false; }
                    else clearError('customId');
                } catch(e) { console.error(e); }
            }
        }

        // Age / birthdate
        if (!validateAge()) ok = false;

        // Name quality checks
        if (!checkConsecLetters(['firstName', 'lastName', 'middleInitial'])) ok = false;
        if (!checkNoNumbers(['firstName', 'lastName', 'middleInitial'])) ok = false;
        if (!checkNoSpecialChars(['firstName', 'lastName', 'middleInitial', 'extension'])) ok = false;
        if (!checkUpperFirst(['firstName', 'lastName', 'middleInitial'])) ok = false;
        if (!checkLowerAfterFirst(['firstName', 'lastName'])) ok = false;
        if (!checkNoDoubleSpace(['firstName', 'lastName', 'middleInitial', 'extension'])) ok = false;
        if (extension.trim() && !extensionPatternChecker(extension)) ok = false;

        return ok;
    }

    function validateAddress() {
        let ok = true;
        const purok = val('purok'), barangay = val('barangay'), city = val('city');
        const province = val('province'), zipCode = val('zipCode'), country = val('country');

        // Lengths
        if (purok && (purok.length < 1 || purok.length > 10)) { showError('purok', 'Purok must be between 1 and 10 characters.'); ok = false; } else clearError('purok');
        if (barangay && (barangay.length < 1 || barangay.length > 25)) { showError('barangay', 'Barangay must be between 1 and 25 characters.'); ok = false; } else clearError('barangay');
        if (city && (city.length < 4 || city.length > 25)) { showError('city', 'City must be between 4 and 25 characters.'); ok = false; } else clearError('city');
        if (province && (province.length < 4 || province.length > 25)) { showError('province', 'Province must be between 4 and 25 characters.'); ok = false; } else clearError('province');
        if (zipCode && zipCode.length !== 4) { showError('zipCode', 'Zip code must be 4 digits.'); ok = false; } else clearError('zipCode');
        if (country && (country.length < 4 || country.length > 25)) { showError('country', 'Country must be between 4 and 25 characters.'); ok = false; } else clearError('country');

        const filledText = ['purok', 'barangay', 'city', 'province', 'country'].filter(f => val(f).trim());
        if (filledText.length) {
            if (!checkConsecLetters(filledText)) ok = false;
            const noNumFields = ['city', 'province', 'country'].filter(f => val(f).trim());
            if (!checkNoNumbers(noNumFields)) ok = false;
            if (!checkAddressSpecialChars(filledText)) ok = false;
            if (!checkUpperFirst(filledText)) ok = false;
            if (!checkNoDoubleSpace(filledText)) ok = false;
        }
        return ok;
    }

    async function validateCredentials(isEdit) {
        let ok = true;
        const username = val('username'), email = val('email');
        const pw = val('password'), repw = val('repassword');

        if (username.length < 4 || username.length > 25) { showError('username', 'Username must be between 4 and 25 characters.'); ok = false; } 
        else if (username.includes(' ')) { showError('username', 'Username must not contain spaces.'); ok = false; }
        else {
            const fd = new FormData();
            fd.append('field', 'username');
            fd.append('value', username);
            const hiddenId = form.querySelector('input[name="id"]');
            if (hiddenId && hiddenId.value) fd.append('exclude_id', hiddenId.value);
            try {
                const res = await fetch(opts.apiBase + '/check_unique.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.exists) { showError('username', 'This Username is already taken.'); ok = false; }
                else clearError('username');
            } catch(e) { console.error(e); }
        }
        
        if (email.length < 10 || email.length > 50) { showError('email', 'Email must be between 10 and 50 characters.'); ok = false; } 
        else if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showError('email', 'Please enter a valid email address.'); ok = false; }
        else {
            const fd = new FormData();
            fd.append('field', 'email');
            fd.append('value', email);
            const hiddenId = form.querySelector('input[name="id"]');
            if (hiddenId && hiddenId.value) fd.append('exclude_id', hiddenId.value);
            try {
                const res = await fetch(opts.apiBase + '/check_unique.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.exists) { showError('email', 'This Email is already taken.'); ok = false; }
                else clearError('email');
            } catch(e) { console.error(e); }
        }

        if (!isEdit) {
            if (pw.length < 8 || pw.length > 25) { showError('password', 'Password must be between 8 and 25 characters.'); ok = false; } else { clearError('password'); }
            if (repw.length < 8 || repw.length > 25) { showError('repassword', 'Confirmation password must be between 8 and 25 characters.'); ok = false; } else { clearError('repassword'); }
            if (pw !== repw) { showError('repassword', 'Passwords did not match.'); ok = false; }
        } else {
            if (pw) {
                if (pw.length < 8 || pw.length > 25) { showError('password', 'Password must be between 8 and 25 characters.'); ok = false; } else { clearError('password'); }
                if (pw !== repw) { showError('repassword', 'Passwords did not match.'); ok = false; }
            }
        }

        if (!checkNoDoubleSpace(['username', 'email', 'password', 'repassword'])) ok = false;
        return ok;
    }

    function validateSecurityQuestions(isEdit) {
        let ok = true;
        const roleEl = form.querySelector(opts.roleSelector || '#role');
        const role = roleEl ? roleEl.value : 'consumer';

        if (role !== 'consumer') return true;
        if (isEdit) return true;

        const q1 = val('sq1') || val('secure_question');
        const a1 = val('sa1') || val('secure_answer');
        const q2 = val('sq2') || val('secure_question2');
        const a2 = val('sa2') || val('secure_answer2');
        const q3 = val('sq3') || val('secure_question3');
        const a3 = val('sa3') || val('secure_answer3');

        const q1Id = form.querySelector('#sq1') ? 'sq1' : 'secure_question';
        const a1Id = form.querySelector('#sa1') ? 'sa1' : 'secure_answer';
        const q2Id = form.querySelector('#sq2') ? 'sq2' : 'secure_question2';
        const a2Id = form.querySelector('#sa2') ? 'sa2' : 'secure_answer2';
        const q3Id = form.querySelector('#sq3') ? 'sq3' : 'secure_question3';
        const a3Id = form.querySelector('#sa3') ? 'sa3' : 'secure_answer3';

        if (!q1) { showError(q1Id, 'Please select a security question.'); ok = false; } else clearError(q1Id);
        if (a1.length < 3 || a1.length > 50) { showError(a1Id, 'Answer must be between 3 and 50 characters.'); ok = false; } else clearError(a1Id);
        if (!q2) { showError(q2Id, 'Please select a security question.'); ok = false; } else clearError(q2Id);
        if (a2.length < 3 || a2.length > 50) { showError(a2Id, 'Answer must be between 3 and 50 characters.'); ok = false; } else clearError(a2Id);
        if (!q3) { showError(q3Id, 'Please select a security question.'); ok = false; } else clearError(q3Id);
        if (a3.length < 3 || a3.length > 50) { showError(a3Id, 'Answer must be between 3 and 50 characters.'); ok = false; } else clearError(a3Id);

        return ok;
    }

    // ---- Sub-checks ----

    function validateAge() {
        const bdEl = form.querySelector('#birthdate');
        const ageEl = form.querySelector('#age');
        if (!bdEl || !bdEl.value) { showError('birthdate', 'Birthdate is required.'); return false; }
        const bd = new Date(bdEl.value), today = new Date();
        if (isNaN(bd.getTime())) { showError('birthdate', 'Invalid date.'); return false; }
        let age = today.getFullYear() - bd.getFullYear();
        const m = today.getMonth() - bd.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < bd.getDate())) age--;
        if (age < 0) { showError('birthdate', 'Date is in the future!'); return false; }
        if (age < 18) { if (ageEl) ageEl.value = age; showError('birthdate', 'Only ages 18+ are allowed.'); return false; }
        if (ageEl) ageEl.value = age;
        clearError('birthdate');
        return true;
    }

    function checkConsecLetters(fields) {
        let ok = true;
        const pat = /(.)\1\1/;
        fields.forEach(f => { const v = val(f); if (v && pat.test(v)) { showError(f, 'Must not have three consecutive identical letters.'); ok = false; } });
        return ok;
    }

    function checkNoNumbers(fields) {
        let ok = true;
        fields.forEach(f => { const v = val(f); if (v && /\d/.test(v)) { showError(f, 'Must not contain numbers.'); ok = false; } });
        return ok;
    }

    function checkNoSpecialChars(fields) {
        let ok = true;
        const pat = /[^a-zA-Z0-9 ]/;
        fields.forEach(f => { const v = val(f); if (v && pat.test(v)) { showError(f, 'Must not contain special characters.'); ok = false; } });
        return ok;
    }

    function checkAddressSpecialChars(fields) {
        let ok = true;
        fields.forEach(f => {
            const v = val(f);
            if (!v) return;
            if (f === 'purok') { if (v.includes('@')) { showError(f, "Cannot contain the '@' symbol."); ok = false; } }
            else { if (/[^a-zA-Z0-9 ]/.test(v)) { showError(f, 'Must not contain special characters.'); ok = false; } }
        });
        return ok;
    }

    function checkUpperFirst(fields) {
        let ok = true;
        fields.forEach(f => { const v = val(f).trim(); if (v.length > 0 && v[0] !== v[0].toUpperCase()) { showError(f, 'Must start with an uppercase letter.'); ok = false; } });
        return ok;
    }

    function checkLowerAfterFirst(fields) {
        let ok = true;
        fields.forEach(f => { const v = val(f).trim(); if (v.length > 1 && /[A-Z]/.test(v.slice(1))) { showError(f, 'Must be lowercase after the first letter.'); ok = false; } });
        return ok;
    }

    function checkNoDoubleSpace(fields) {
        let ok = true;
        fields.forEach(f => { const v = val(f); if (v && v.includes('  ')) { showError(f, 'Must not contain double spaces.'); ok = false; } });
        return ok;
    }

    function extensionPatternChecker(ext) {
        ext = ext.trim();
        if (!ext) return true;
        const roman = /^(M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3}))$/;
        const suffix = /^(Jr|Sr|Jr\.|Sr\.)$/;
        if (roman.test(ext)) { clearError('extension'); return true; }
        if (suffix.test(ext)) { if (/^[A-Z][a-z.]*$/.test(ext)) { clearError('extension'); return true; } else { showError('extension', 'Suffix must start with capital letter.'); return false; } }
        showError('extension', 'Not a valid Roman numeral or suffix (Jr., Sr.).');
        return false;
    }

    // ---- Password Strength ----
    function updatePasswordStrength() {
        const pwInput = form.querySelector('#password');
        const strengthSpan = form.querySelector('#pwStrength');
        if (!pwInput || !strengthSpan) return;
        const pw = pwInput.value;
        if (pw.length < 8) { strengthSpan.innerText = ''; return; }
        let score = 0;
        if (/[A-Z]/.test(pw)) score++;
        if (/[a-z]/.test(pw)) score++;
        if (/\d/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        if (score === 4) { strengthSpan.innerText = 'Strong Password'; strengthSpan.style.color = 'rgb(5, 172, 33)'; }
        else if (score >= 2) { strengthSpan.innerText = 'Medium Password'; strengthSpan.style.color = '#ff8c00'; }
        else { strengthSpan.innerText = 'Weak Password'; strengthSpan.style.color = '#f50606'; }
    }

    function updatePasswordMatch() {
        const matchSpan = form.querySelector('#pwMatch');
        if (!matchSpan) return;
        const pw = val('password'), repw = val('repassword');
        if (!repw) { matchSpan.innerText = ''; return; }
        if (pw !== repw) { matchSpan.innerText = 'Passwords did not match'; matchSpan.style.color = '#f50606'; }
        else { matchSpan.innerText = 'Passwords matched'; matchSpan.style.color = 'rgb(5, 172, 33)'; }
    }

    // ---- Uniqueness Check ----
    function debounce(func, delay) { clearTimeout(debounceTimer); debounceTimer = setTimeout(func, delay); }

    async function checkFieldExists(fieldId, value, fieldName) {
        const fd = new FormData();
        fd.append('field', fieldId === 'customId' ? 'id' : fieldId);
        fd.append('value', value);
        const hiddenId = form.querySelector('input[name="id"]');
        if (hiddenId && hiddenId.value) fd.append('exclude_id', hiddenId.value);
        try {
            const res = await fetch(opts.apiBase + '/check_unique.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.exists) showError(fieldId, `This ${fieldName} is already taken.`);
            else { const span = form.querySelector('#' + fieldId + 'Error'); if (span && span.innerText.includes('taken')) clearError(fieldId); }
        } catch (e) { console.error('Uniqueness check failed:', e); }
    }

    // ---- ID auto-format ----
    function formatIdInput(e) {
        let v = e.target.value.replace(/\D/g, '');
        if (v.length > 4) v = v.slice(0, 4) + '-' + v.slice(4, 8);
        e.target.value = v;
    }

    // ---- Init ----
    function init(formEl, options) {
        form = formEl;
        opts = Object.assign({ apiBase: '../../php/database', roleSelector: '#role', securitySection: '#securitySection' }, options);

        const pwInput = form.querySelector('#password');
        const repwInput = form.querySelector('#repassword');
        if (pwInput) pwInput.addEventListener('input', () => { updatePasswordStrength(); updatePasswordMatch(); });
        if (repwInput) repwInput.addEventListener('input', updatePasswordMatch);

        const idInput = form.querySelector('#customId');
        if (idInput) {
            idInput.addEventListener('input', (e) => {
                formatIdInput(e);
                const v = e.target.value;
                if (/^[0-9]{4}-[0-9]{4}$/.test(v)) { clearError('customId'); debounce(() => checkFieldExists('customId', v, 'ID'), 500); }
                else if (v.length > 0) showError('customId', 'ID must be in xxxx-xxxx format.');
                else clearError('customId');
            });
        }

        const unameInput = form.querySelector('#username');
        if (unameInput) {
            unameInput.addEventListener('input', (e) => {
                const v = e.target.value;
                if (/\s/.test(v)) { showError('username', 'Username cannot contain spaces.'); }
                else if (v.length > 0 && v.length < 4) { showError('username', 'Username must be at least 4 characters.'); }
                else if (v.length >= 4) { clearError('username'); debounce(() => checkFieldExists('username', v, 'Username'), 500); }
                else clearError('username');
            });
        }

        const emailInput = form.querySelector('#email');
        if (emailInput) {
            emailInput.addEventListener('input', (e) => {
                const v = e.target.value;
                if (!v) { clearError('email'); return; }
                if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) { clearError('email'); debounce(() => checkFieldExists('email', v, 'Email'), 500); }
                else showError('email', 'Please enter a valid email address.');
            });
        }

        const bdInput = form.querySelector('#birthdate');
        if (bdInput) bdInput.addEventListener('change', () => validateAge());

        const roleEl = form.querySelector(opts.roleSelector);
        if (roleEl) {
            roleEl.addEventListener('change', () => toggleSecurity());
            toggleSecurity();
        }

        form.querySelectorAll('input, select').forEach(el => {
            el.addEventListener('input', (e) => {
                const id = e.target.id || e.target.name;
                if (id !== 'username' && id !== 'email' && id !== 'customId' && id !== 'birthdate' && id !== 'password' && id !== 'repassword') {
                    clearError(id);
                }
            });
        });
    }

    function setEditMode(isEdit) { currentlyEditing = isEdit; }

    function toggleSecurity() {
        const roleEl = form.querySelector(opts.roleSelector);
        const secEl = form.querySelector(opts.securitySection);
        if (!roleEl || !secEl) return;
        const role = roleEl.value;
        if (role === 'consumer') {
            secEl.style.display = '';
            // Aggressively manage required attribute
            secEl.querySelectorAll('select, input').forEach(f => {
                if (currentlyEditing) {
                    f.required = false;
                    f.removeAttribute('required');
                } else {
                    f.required = true;
                    f.setAttribute('required', 'required');
                }
            });
        } else {
            secEl.style.display = 'none';
            secEl.querySelectorAll('select, input').forEach(f => { 
                f.required = false; 
                f.removeAttribute('required');
                f.value = ''; 
            });
        }
    }

    async function validateAll(isEdit) {
        clearAllErrors();
        const p1 = await validatePersonalInfo();
        const p2 = validateAddress();
        const p3 = await validateCredentials(isEdit);
        const p4 = validateSecurityQuestions(isEdit);
        return p1 && p2 && p3 && p4;
    }

    return { 
        init, 
        validateAll, 
        validatePersonalInfo, 
        validateAddress, 
        validateCredentials, 
        validateSecurityQuestions, 
        clearAllErrors, 
        showError, 
        clearError, 
        setEditMode 
    };
})();
