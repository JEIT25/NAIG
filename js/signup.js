const registerForm = document.querySelector('#signUpForm');
let pwSpanMessage = document.getElementById('pwStrength');
let pwSpanMatch = document.getElementById('pwMatch');
const togglePassword = document.querySelector('#togglePassword');
const toggleRePassword = document.querySelector('#toggleRePassword');
const password = document.querySelector('#password');
const repassword = document.querySelector('#repassword');
let birthdateInput = registerForm.querySelector('#birthdate');
const ageInput = registerForm.querySelector('#age');
const stepIndicatorItems = document.querySelectorAll('.step-indicators li');

// --- New Multi-Step Form Variables ---
const formSteps = document.querySelectorAll('.form-step');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const submitBtn = document.getElementById('submitBtn');
const formSuccessMessage = document.getElementById('successModal');
let currentStep = 0;

// --- New Inline Validation Helpers ---

/**
 * Shows an error message below a specific field.
 * @param {string} fieldId - The id of the input field.
 * @param {string} message - The error message to display.
 */
function showValidationError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorSpan = document.getElementById(fieldId + 'Error');

    if (field) {
        field.classList.add('error');
    }
    if (errorSpan) {
        errorSpan.innerText = message;
        errorSpan.classList.add('active');
    }
}

/**
 * Clears an error message from a specific field.
 * @param {string} fieldId - The id of the input field.
 */
function clearValidationError(fieldId) {
    const field = document.getElementById(fieldId);
    const errorSpan = document.getElementById(fieldId + 'Error');

    if (field) {
        field.classList.remove('error');
    }
    if (errorSpan) {
        errorSpan.innerText = '';
        errorSpan.classList.remove('active');
    }
}

/**
 * Clears all validation errors from the form.
 */
function clearAllErrors() {
    const errorSpans = document.querySelectorAll('.validation-message');
    const errorInputs = document.querySelectorAll('.error');

    errorSpans.forEach(span => {
        span.innerText = '';
        span.classList.remove('active');
    });

    errorInputs.forEach(input => {
        input.classList.remove('error');
    });
}

/**
 * Shows the main form success message (The Card) and hides EVERYTHING else.
 * @param {string} message - The success message.
 */
function showSuccessMessage(message) {
    // 1. Hide the Form Title (h2)
    const title = document.getElementById('formTitle');
    if (title) title.style.display = 'none';

    // 2. Hide all Form Steps (The inputs)
    formSteps.forEach(step => step.style.display = 'none');

    // 3. Hide Navigation Buttons (Next, Prev, Submit)
    const navButtons = document.querySelector('.form-navigation-buttons');
    if (navButtons) navButtons.style.display = 'none';

    // 4. Hide any lingering Server Error messages
    const serverError = document.getElementById('serverError');
    if (serverError) serverError.style.display = 'none';

    // 5. Update the text inside the card (Optional)
    const successTextP = document.querySelector('.modal-simple-alert-text');
    if (successTextP) {
        successTextP.innerText = message;
    }

    // 6. SHOW the Success Card
    // We add the 'active' class which sets display: flex
    formSuccessMessage.classList.add('active');
}

// --- New Real-Time Validation Functions ---

let debounceTimer;
/**
 * Delays running a function until the user stops typing.
 * @param {function} func - The function to run.
 * @param {number} delay - The delay in milliseconds (e.g., 500).
 */
function debounce(func, delay) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(func, delay);
}

/**
 * Checks the database to see if a field value is already taken.
 * @param {string} fieldId - The id of the input (e.g., 'username').
 * @param {string} value - The value to check.
 * @param {string} fieldName - The user-friendly name (e.g., 'Username').
 */
async function checkFieldExists(fieldId, value, fieldName) {
    const formData = new FormData();
    formData.append('field', fieldId);
    formData.append('value', value);

    try {
        const response = await fetch('http://localhost/NAIG/php/database/check_unique.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) throw new Error('Network response was not ok');

        const data = await response.json();

        if (data.exists) {
            showValidationError(fieldId, `This ${fieldName} is already taken.`);
        } else {
            // It's unique, so clear the "taken" error.
            // This won't clear other errors like "format is wrong".
            const errorSpan = document.getElementById(fieldId + 'Error');
            if (errorSpan && errorSpan.innerText.includes('taken')) {
                clearValidationError(fieldId);
            }
        }
    } catch (error) {
        console.error('Real-time validation check failed:', error);
    }
}

// --- New Multi-Step Navigation ---

/**
 * Shows the specified form step and updates navigation buttons.
 * @param {number} stepIndex - The index of the step to show.
 */
function showStep(stepIndex) {
    // Hide all steps
    formSteps.forEach((step, index) => {
        step.classList.toggle('active', index === stepIndex);
    });

    // Update sidebar step indicators
    if (stepIndicatorItems) {
        stepIndicatorItems.forEach((item, index) => {
            item.classList.remove('active', 'completed');
            if (index === stepIndex) {
                item.classList.add('active');
            } else if (index < stepIndex) {
                item.classList.add('completed');
            }
        });
    }

    // Update button visibility
    if (stepIndex === 0) {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'inline-block';
        submitBtn.style.display = 'none';
    } else if (stepIndex === formSteps.length - 1) {
        prevBtn.style.display = 'inline-block';
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'inline-block';
    } else {
        prevBtn.style.display = 'inline-block';
        nextBtn.style.display = 'inline-block';
        submitBtn.style.display = 'none';
    }

    currentStep = stepIndex;
}

// Next Button Click
nextBtn.addEventListener('click', () => {
    clearAllErrors();
    let isValid = false;

    if (currentStep === 0) {
        isValid = validateStep1();
    } else if (currentStep === 1) {
        isValid = validateStep2();
    } else if (currentStep === 2) {
        isValid = validateStep3();
    }
    // Note: Step 3 is the last one before submit, so nextBtn won't be visible on step 3 (index 2)

    if (isValid) {
        currentStep++;
        showStep(currentStep);
    }
});

// Previous Button Click
prevBtn.addEventListener('click', () => {
    currentStep--;
    showStep(currentStep);
});

// --- Refactored Validation Logic ---

// --- Step 1 Validation ---
function validateStep1() {
    let isValid = true;
    const form = registerForm;

    // --- Validations from checkMinAndMax (Step 1) ---
    const firstName = form.firstName.value;
    const lastName = form.lastName.value;
    const middleInitial = form.middleInitial.value;
    const nameExtension = form.extension.value;
    const sex = form.sex.value;

    if (firstName.length < 2 || firstName.length > 25) {
        showValidationError('firstName', 'First name must be between 2 and 25 characters.');
        isValid = false;
    } else {
        clearValidationError('firstName');
    }

    if (lastName.length < 2 || lastName.length > 25) {
        showValidationError('lastName', 'Last name must be between 2 and 25 characters.');
        isValid = false;
    } else {
        clearValidationError('lastName');
    }

    if (middleInitial.length > 1) {
        showValidationError('middleInitial', 'Middle initial must be exactly 1 character.');
        isValid = false;
    } else {
        clearValidationError('middleInitial');
    }

    if (nameExtension.length > 4) {
        showValidationError('extension', 'Name extension can be up to 4 characters.');
        isValid = false;
    } else {
        clearValidationError('extension');
    }

    if (!sex || (sex.length < 4 || sex.length > 6)) {
        showValidationError('sex', 'Please select a sex.');
        isValid = false;
    } else {
        clearValidationError('sex');
    }
    // --- End checkMinAndMax (Step 1) ---

    // --- Other Step 1 Validations ---
    if (!checkIdInput(form)) isValid = false;
    if (!handleAgeValidation(form)) isValid = false;
    if (!checkThreeConsecLetters(form, 1)) isValid = false;
    if (!checkNumberInputs(form, 1)) isValid = false;
    if (!checkSpecialChars(form, 1)) isValid = false;
    if (!checkUpperFirstLetter(form, 1)) isValid = false;
    if (form.extension.value.trim() && !extensionPatternChecker(form.extension.value)) isValid = false;
    if (!checkSmallLetters(form, 1)) isValid = false;
    if (!checkDoubleSpace(form, 1)) isValid = false;

    return isValid;
}

// --- Step 2 Validation ---
function validateStep2() {
    let isValid = true;
    const form = registerForm;

    // --- Validations from checkMinAndMax (Step 2) ---
    const purok = form.purok.value;
    const barangay = form.barangay.value;
    const city = form.city.value;
    const province = form.province.value;
    const zipCode = form.zipCode.value;
    const country = form.country.value;

    if (purok.length < 1 || purok.length > 10) {
        showValidationError('purok', 'Purok must be between 1 and 10 characters.');
        isValid = false;
    } else {
        clearValidationError('purok');
    }

    if (barangay.length < 1 || barangay.length > 25) {
        showValidationError('barangay', 'Barangay must be between 1 and 25 characters.');
        isValid = false;
    } else {
        clearValidationError('barangay');
    }

    if (city.length < 4 || city.length > 25) {
        showValidationError('city', 'City/Municipality must be between 4 and 25 characters.');
        isValid = false;
    } else {
        clearValidationError('city');
    }

    if (province.length < 4 || province.length > 25) {
        showValidationError('province', 'Province must be between 4 and 25 characters.');
        isValid = false;
    } else {
        clearValidationError('province');
    }

    if (zipCode.length < 4 || zipCode.length > 4) {
        showValidationError('zipCode', 'Zip code must be 4 digits.');
        isValid = false;
    } else {
        clearValidationError('zipCode');
    }

    if (country.length < 4 || country.length > 25) {
        showValidationError('country', 'Country must be between 4 and 25 characters.');
        isValid = false;
    } else {
        clearValidationError('country');
    }
    // --- End checkMinAndMax (Step 2) ---

    // --- Other Step 2 Validations ---
    if (!checkThreeConsecLetters(form, 2)) isValid = false;
    if (!checkNumberInputs(form, 2)) isValid = false;
    if (!checkSpecialChars(form, 2)) isValid = false;
    if (!checkUpperFirstLetter(form, 2)) isValid = false;
    if (!checkSmallLetters(form, 2)) isValid = false;
    if (!checkDoubleSpace(form, 2)) isValid = false;

    return isValid;
}

// --- Step 3 Validation ---
function validateStep3() {
    let isValid = true;
    const form = registerForm;

    // --- Validations from checkMinAndMax (Step 3) ---
    const username = form.username.value;
    const email = form.email.value;
    const passwordVal = form.password.value;
    const repasswordVal = form.repassword.value;

    if (username.length < 4 || username.length > 25) {
        showValidationError('username', 'Username must be between 4 and 25 characters.');
        isValid = false;
    } else {
        clearValidationError('username');
    }

    if (email.length < 10 || email.length > 50) {
        showValidationError('email', 'Email must be between 10 and 50 characters.');
        isValid = false;
    } else {
        clearValidationError('email');
    }

    if (passwordVal.length < 8 || passwordVal.length > 25) {
        showValidationError('password', 'Password must be between 8 and 25 characters.');
        isValid = false;
    } else {
        clearValidationError('password');
        displayPasswordStrength(form);
    }

    if (repasswordVal.length < 8 || repasswordVal.length > 25) {
        showValidationError('repassword', 'Confirmation password must be between 8 and 25 characters.');
        isValid = false;
    } else {
        clearValidationError('repassword');
    }

    if (passwordVal !== repasswordVal) {
        showValidationError('repassword', 'Passwords did not match.');
        isValid = false;
    } else if (repasswordVal.length >= 8) {
        clearValidationError('repassword');
    }
    // --- End checkMinAndMax (Step 3) ---

    // --- Other Step 3 Validations ---
    if (!checkUsername(form)) isValid = false;
    if (!checkDoubleSpace(form, 3)) isValid = false;

    return isValid;
}

// --- Step 4 Validation ---
function validateStep4() {
    let isValid = true;
    const form = registerForm;

    // Validate all 3 security questions and answers
    const qaPairs = [
        { q: 'secure_question', a: 'secure_answer' },
        { q: 'secure_question2', a: 'secure_answer2' },
        { q: 'secure_question3', a: 'secure_answer3' }
    ];

    qaPairs.forEach(({ q, a }) => {
        const qVal = form[q] ? form[q].value : '';
        const aVal = form[a] ? form[a].value : '';

        if (!qVal) {
            showValidationError(q, 'Please select a security question.');
            isValid = false;
        } else {
            clearValidationError(q);
        }

        if (aVal.length < 3 || aVal.length > 50) {
            showValidationError(a, 'Answer must be between 3 and 50 characters.');
            isValid = false;
        } else {
            clearValidationError(a);
        }
    });

    // Check for duplicate questions
    const selectedQs = qaPairs.map(({ q }) => form[q] ? form[q].value : '').filter(v => v);
    if (new Set(selectedQs).size !== selectedQs.length) {
        showValidationError('secure_question3', 'Each security question must be different.');
        isValid = false;
    }

    // --- Other Step 4 Validations ---
    if (!checkDoubleSpace(form, 4)) isValid = false;

    return isValid;
}


// --- Individual Validation Functions (Refactored) ---

function checkDoubleSpace(form, stepNum) {
    let isValid = true;
    const fieldsByStep = {
        1: ['id', 'firstName', 'lastName', 'middleInitial', 'extension'],
        2: ['purok', 'barangay', 'city', 'province', 'country'], // zipCode is number
        3: ['username', 'email', 'password', 'repassword'],
        4: ['secure_answer']
    };
    const fieldsToCheck = fieldsByStep[stepNum] || [];

    for (let fieldName of fieldsToCheck) {
        const element = form[fieldName];
        if (element && element.value.includes('  ')) {
            showValidationError(fieldName, `The ${fieldName} input field contains double spaces!`);
            isValid = false;
        }
    }
    return isValid;
}

function checkUsername(form) {
    const username = form.username.value;
    if (username.includes(' ')) {
        showValidationError("username", "The username must not contain spaces");
        return false;
    }
    return true;
}

function handleAgeValidation(form) {
    if (!birthdateInput.value) {
        ageInput.value = '';
        showValidationError('birthdate', `The birthdate field must be filled out.`);
        return false;
    }

    const birthdate = new Date(birthdateInput.value);
    const today = new Date();

    if (isNaN(birthdate.getTime())) {
        ageInput.value = '';
        showValidationError('birthdate', `The birthdate field must be a valid date!`);
        return false;
    }

    let age = today.getFullYear() - birthdate.getFullYear();
    const monthDiff = today.getMonth() - birthdate.getMonth();
    const dayDiff = today.getDate() - birthdate.getDate();

    if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
        age--;
    }

    if (age < 0) {
        ageInput.value = '';
        showValidationError('birthdate', `The birthdate field contains a date in the future!`);
        return false;
    }

    if (age < 18) {
        ageInput.value = age; // Show the age, but still fail validation
        showValidationError('birthdate', `Only ages 18 and above are allowed to register.`);
        return false;
    }

    ageInput.value = age;
    clearValidationError('birthdate');
    return true;
}


function checkThreeConsecLetters(form, stepNum) {
    let isValid = true;
    const fieldsByStep = {
        1: ['firstName', 'lastName', 'middleInitial'],
        2: ['purok', 'barangay', 'city', 'province', 'country'],
        4: ['secure_answer']
    };
    const fieldsToCheck = fieldsByStep[stepNum] || [];
    const pattern = /(.)\1\1/; // Regex for three consecutive identical characters

    for (let fieldName of fieldsToCheck) {
        const element = form[fieldName];
        if (element && pattern.test(element.value)) {
            showValidationError(fieldName, `The ${fieldName} field has three consecutive identical letters!`);
            isValid = false;
        }
    }
    return isValid;
}

function checkNumberInputs(form, stepNum) {
    let isValid = true;
    const fieldsByStep = {
        1: ['firstName', 'lastName', 'middleInitial'],
        2: ['city', 'province', 'country'],
        4: ['secure_answer']
    };
    const fieldsToCheck = fieldsByStep[stepNum] || [];
    const pattern = /\d/; // Regex for any digit

    for (let fieldName of fieldsToCheck) {
        const element = form[fieldName];
        if (element && pattern.test(element.value)) {
            showValidationError(fieldName, `The ${fieldName} field contains numbers, which are not allowed.`);
            isValid = false;
        }
    }
    return isValid;
}

function checkIdInput(form) {
    const idField = form.id;
    const value = idField.value.trim();
    let idPattern = /^[0-9]{4}-[0-9]{4}$/;

    if (!idPattern.test(value)) {
        showValidationError("id", "The ID must be in the format xxxx-xxxx.");
        return false;
    }
    // Clear validation *only* if it passes. Real-time check will handle 'taken' error.
    clearValidationError("id");
    return true;
}

function formatIdInput(event) {
    let input = event.target;
    let value = input.value.replace(/\D/g, ''); // Remove non-digits
    if (value.length > 4) {
        value = value.slice(0, 4) + '-' + value.slice(4, 8); // Auto-add dash and limit length
    }
    input.value = value;
}

function checkSpecialChars(form, stepNum) {
    let isValid = true;
    const fieldsByStep = {
        1: ['firstName', 'lastName', 'middleInitial', 'extension'],
        // Added 'purok' here so it gets checked
        2: ['purok', 'barangay', 'city', 'province', 'country'],
    };
    const fieldsToCheck = fieldsByStep[stepNum] || [];

    // Standard pattern: Matches anything that is NOT a letter, number, or space
    // (Used for names, city, province, etc.)
    let standardSpecialCharPattern = /[^a-zA-Z0-9 ]/;

    for (let fieldName of fieldsToCheck) {
        const element = form[fieldName];

        if (element) {
            // --- SPECIAL RULE FOR PUROK ---
            // "Accept other characters except '@'"
            if (fieldName === 'purok') {
                // If the value includes '@', it is invalid.
                if (element.value.includes('@')) {
                    showValidationError(fieldName, `The ${fieldName} field cannot contain the '@' symbol.`);
                    isValid = false;
                }
            }
            // --- RULE FOR OTHER FIELDS (Strict) ---
            else {
                if (standardSpecialCharPattern.test(element.value)) {
                    showValidationError(fieldName, `The ${fieldName} field must not contain special characters.`);
                    isValid = false;
                }
            }
        }
    }
    return isValid;
}

function checkUpperFirstLetter(form, stepNum) {
    let isValid = true;
    const fieldsByStep = {
        1: ['firstName', 'lastName', 'middleInitial'],
        2: ['purok', 'barangay', 'city', 'province', 'country'],
    };
    const fieldsToCheck = fieldsByStep[stepNum] || [];

    for (let fieldName of fieldsToCheck) {
        const element = form[fieldName];
        if (!element) continue;

        let value = element.value.trim();
        if (value.length > 0 && value[0] !== value[0].toUpperCase()) {
            showValidationError(fieldName, `The ${fieldName} field must start with an uppercase letter.`);
            isValid = false;
        }
    }
    return isValid; // FIXED: Returns the status instead of always true
}


function checkSmallLetters(form, stepNum) {
    let isValid = true;
    const fieldsByStep = {
        1: ['firstName', 'lastName'],
    };
    const fieldsToCheck = fieldsByStep[stepNum] || [];

    for (let fieldName of fieldsToCheck) {
        const element = form[fieldName];
        if (!element) continue;

        let value = element.value.trim();
        if (value.length > 1 && value.slice(1) !== value.slice(1).toLowerCase()) {
            // This checks if any letter *after* the first is uppercase
            if (/[A-Z]/.test(value.slice(1))) {
                showValidationError(fieldName, `The ${fieldName} field must be lowercase after the first letter.`);
                isValid = false;
            }
        }
    }
    return isValid; // FIXED: Returns the status instead of always true
}


function extensionPatternChecker(extension) {
    const romanNumeralPattern = /^(M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3}))$/;
    const suffixPattern = /^(Jr|Sr|Jr\.|Sr\.)$/;
    extension = extension.trim();

    if (romanNumeralPattern.test(extension)) {
        clearValidationError('extension');
        return true;
    }
    if (suffixPattern.test(extension)) {
        if (/^[A-Z][a-z.]*$/.test(extension)) {
            clearValidationError('extension');
            return true;
        } else {
            showValidationError('extension', `Suffix "${extension}" must start with a capital letter.`);
            return false;
        }
    }

    if (extension.length > 0) { // Only show error if something is typed
        showValidationError('extension', `Not a valid Roman numeral or suffix (Jr., Sr.).`);
        return false;
    }

    return true; // Is valid if empty (optional field)
}

function displayPasswordStrength(form) {
    let password = form.password.value;
    let hasUppercase = /[A-Z]/.test(password);
    let hasLowercase = /[a-z]/.test(password);
    let hasNumber = /\d/.test(password);
    let hasSpecialChar = /[^A-Za-z0-9]/.test(password);

    if (password.length < 8) {
        pwSpanMessage.innerText = '';
        return; // Length check is handled by validateStep3
    }

    let score = 0;
    if (hasUppercase) score++;
    if (hasLowercase) score++;
    if (hasNumber) score++;
    if (hasSpecialChar) score++;

    if (score === 4) {
        pwSpanMessage.innerText = 'Strong Password';
        pwSpanMessage.style.color = 'rgb(5, 172, 33)';
    } else if (score >= 2) {
        pwSpanMessage.innerText = 'Medium Password';
        pwSpanMessage.style.color = '#ff8c00'; // Orange
    } else {
        pwSpanMessage.innerText = 'Weak Password';
        pwSpanMessage.style.color = '#f50606';
    }

    clearValidationError('password');
}

// --- Original Event Listeners (Modified) ---

togglePassword.addEventListener('click', function () {
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
});

toggleRePassword.addEventListener('click', function () {
    const type = repassword.getAttribute('type') === 'password' ? 'text' : 'password';
    repassword.setAttribute('type', type);
});

registerForm.elements.password.addEventListener('input', (e) => {
    displayPasswordStrength(registerForm);
});

registerForm.elements.repassword.addEventListener('input', (e) => {
    if (registerForm.elements.password.value !== registerForm.elements.repassword.value) {
        pwSpanMatch.innerText = "Passwords did not match";
        pwSpanMatch.style.color = "#f50606";
    } else {
        pwSpanMatch.innerText = "Passwords matched";
        pwSpanMatch.style.color = "rgb(5, 172, 33)";
    }
});

birthdateInput.addEventListener('change', () => {
    handleAgeValidation(registerForm);
});

// This listener is now handled in 'DOMContentLoaded'
// document.querySelector('input[name="id"]').addEventListener('input', formatIdInput);

// Brand click handled by navbar link

registerForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    clearAllErrors();

    // Final validation of ALL steps before submitting
    const isStep1Valid = validateStep1();
    const isStep2Valid = validateStep2();
    const isStep3Valid = validateStep3();
    const isStep4Valid = validateStep4();

    if (isStep1Valid && isStep2Valid && isStep3Valid && isStep4Valid) {
        // Disable button to prevent double clicks
        submitBtn.disabled = true;
        submitBtn.innerText = 'Submitting...';

        const formData = new FormData(this);

        try {
            const response = await fetch('http://localhost/NAIG/php/database/signup.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.text();

            // Check if exact success string matches your PHP output
            if (data.trim() === "User successfully registered!") {

                // TRIGGER THE CLEAN SUCCESS DISPLAY
                showSuccessMessage(data);

                // Redirect after 3 seconds
                setTimeout(() => {
                    window.location.href = "http://localhost/NAIG/php/auth/login.php";
                }, 3000);
            } else {
                // ... (Error handling logic remains the same)
                if (data.includes("username")) {
                    showValidationError('username', data);
                    showStep(2);
                } else if (data.includes("email")) {
                    showValidationError('email', data);
                    showStep(2);
                } else if (data.includes("ID")) {
                    showValidationError('id', data);
                    showStep(0);
                } else {
                    showValidationError('serverError', data);
                }
                submitBtn.disabled = false;
                submitBtn.innerText = 'Submit';
            }
        } catch (error) {
            console.error('Error:', error);
            showValidationError('serverError', 'An unexpected error occurred.');
            submitBtn.disabled = false;
            submitBtn.innerText = 'Submit';
        }
    } else {
        // If validation fails, find the error
        if (!isStep1Valid) showStep(0);
        else if (!isStep2Valid) showStep(1);
        else if (!isStep3Valid) showStep(2);
        else if (!isStep4Valid) showStep(3);

        showValidationError('serverError', 'Please fix the errors on the form.');
    }
});

// --- Event Listeners ---
document.addEventListener('DOMContentLoaded', () => {
    // Show the first step on page load (your original code)
    showStep(0);

    // Add auto-formatting for ID input
    document.querySelector('input[name="id"]').addEventListener('input', formatIdInput);

    // --- NEW: Add real-time validation listeners ---

    // 1. ID Check
    document.getElementById('id').addEventListener('input', (e) => {
        const value = e.target.value;
        // First, check the format client-side
        if (/^[0-9]{4}-[0-9]{4}$/.test(value)) {
            // Format is valid, clear format error and check database
            clearValidationError('id');
            debounce(() => checkFieldExists('id', value, 'ID'), 500);
        } else if (value.length > 0) {
            // Format is invalid, show format error
            clearTimeout(debounceTimer); // Stop any pending server check
            showValidationError('id', 'ID must be in xxxx-xxxx format.');
        } else {
            // Field is empty, clear all errors
            clearValidationError('id');
        }
    });

    // 2. Username Check
    document.getElementById('username').addEventListener('input', (e) => {
        const value = e.target.value;
        if (/\s/.test(value)) {
            // Client-side check for spaces
            clearTimeout(debounceTimer);
            showValidationError('username', 'Username cannot contain spaces.');
        } else if (value.length > 0 && value.length < 4) {
            // Client-side check for length
            clearTimeout(debounceTimer);
            showValidationError('username', 'Username must be at least 4 characters.');
        } else if (value.length >= 4) {
            // Looks good, check database
            clearValidationError('username'); // Clear client-side errors
            debounce(() => checkFieldExists('username', value, 'Username'), 500);
        } else {
            // Field is empty
            clearValidationError('username');
        }
    });

    // 3. Email Check
    document.getElementById('email').addEventListener('input', (e) => {
        const value = e.target.value;
        if (value.length === 0) {
            clearValidationError('email');
            return;
        }
        // Client-side check for email format
        if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            // Format looks valid, check database
            clearValidationError('email');
            debounce(() => checkFieldExists('email', value, 'Email'), 500);
        } else {
            // Format is invalid
            clearTimeout(debounceTimer);
            showValidationError('email', 'Please enter a valid email address.');
        }
    });
});