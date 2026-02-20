<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - NAIGO</title>
    <link rel="stylesheet" href="../../css/serve_asset.php?file=design-system.css&v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../css/serve_asset.php?file=signup.css&v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- ===== Navbar (Fixed) ===== -->
    <nav class="signup-navbar">
        <a href="../forms/homepage.php" class="brand">
            <div class="navbar-logo-icon" aria-hidden="true" style="font-size: 1.25rem;"><i class="fa-solid fa-concierge-bell"></i></div>
            <span>NAIGO<span class="sub-text">Online Restaurant Reservation</span></span>
        </a>
        <div class="nav-buttons">
            <a href="../forms/homepage.php" class="nav-btn nav-btn-outline">Home</a>
            <a href="../auth/login.php" class="nav-btn nav-btn-primary">Login</a>
        </div>
    </nav>

    <div class="signup-page">
        <div class="signup-main">
            <!-- ===== Centered Card Content ===== -->
            <div class="signup-content">
                <div class="signup-header">
                    <h2 class="signup-title">Create Account</h2>
                    <p class="signup-subtitle">Join NAIGO and discover exceptional dining</p>
                </div>

                <!-- Horizontal Step Indicators -->
                <ul class="step-indicators" id="stepIndicators">
                    <li class="active" data-step="0">
                        <span class="step-indicator-circle"><i class="fa-solid fa-user"></i></span>
                        <span class="step-indicator-label">Personal</span>
                    </li>
                    <li data-step="1">
                        <span class="step-indicator-circle"><i class="fa-solid fa-location-dot"></i></span>
                        <span class="step-indicator-label">Address</span>
                    </li>
                    <li data-step="2">
                        <span class="step-indicator-circle"><i class="fa-solid fa-lock"></i></span>
                        <span class="step-indicator-label">Credentials</span>
                    </li>
                    <li data-step="3">
                        <span class="step-indicator-circle"><i class="fa-solid fa-shield-halved"></i></span>
                        <span class="step-indicator-label">Security</span>
                    </li>
                </ul>
                <form class="signup-form" id="signUpForm" method="POST">
                    <!-- Success Modal -->
                    <div id="successModal" class="modal-simple-alert">
                        <div class="modal-simple-alert-content">
                            <svg class="success-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="#059669" width="4em" height="4em">
                                <path d="M256 512C397.4 512 512 397.4 512 256C512 114.6 397.4 0 256 0C114.6 0 0 114.6 0 256C0 397.4 114.6 512 256 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/>
                            </svg>
                            <span class="modal-simple-alert-text">Account created successfully!</span>
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem;">Redirecting to login...</p>
                        </div>
                    </div>

                    <span class="validation-message" id="serverError"></span>

                    <!-- Step 1: Personal Info -->
                    <div class="form-step active">
                        <h2 id="formTitle">Personal Information</h2>
                        <p class="form-subtitle">Tell us about yourself to create your dining profile</p>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="id">Member ID <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-envelope input-icon"></i>
                                    <input type="text" id="id" name="id" placeholder="xxxx-xxxx">
                                </div>
                                <span class="validation-message" id="idError"></span>
                            </div>
                            <div class="form-group">
                                <label for="firstName">First Name <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-user input-icon"></i>
                                    <input type="text" id="firstName" name="firstName" placeholder="Enter first name">
                                </div>
                                <span class="validation-message" id="firstNameError"></span>
                            </div>
                            <div class="form-group">
                                <label for="lastName">Last Name <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-user input-icon"></i>
                                    <input type="text" id="lastName" name="lastName" placeholder="Enter last name">
                                </div>
                                <span class="validation-message" id="lastNameError"></span>
                            </div>
                            <div class="form-group">
                                <label for="middleInitial">M.I. <span class="optional">(optional)</span></label>
                                <input type="text" id="middleInitial" name="middleInitial" placeholder="M">
                                <span class="validation-message" id="middleInitialError"></span>
                            </div>
                            <div class="form-group">
                                <label for="extension">Suffix <span class="optional">(optional)</span></label>
                                <input type="text" id="extension" name="extension" placeholder="Jr, Sr.">
                                <span class="validation-message" id="extensionError"></span>
                            </div>
                            <div class="form-group">
                                <label for="sex">Gender <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-venus-mars input-icon"></i>
                                    <select id="sex" name="sex">
                                        <option value="">Select gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <span class="validation-message" id="sexError"></span>
                            </div>
                            <div class="form-group">
                                <label for="birthdate">Date of Birth <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-calendar input-icon"></i>
                                    <input type="date" id="birthdate" name="birthdate">
                                </div>
                                <span class="validation-message" id="birthdateError"></span>
                            </div>
                            <div class="form-group">
                                <label for="age">Age</label>
                                <input type="number" id="age" name="age" readonly placeholder="Auto">
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Address -->
                    <div class="form-step">
                        <h2>Address</h2>
                        <p class="form-subtitle">Where can we reach you?</p>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="purok">Purok <span class="required">*</span></label>
                                <input type="text" id="purok" name="purok" placeholder="">
                                <span class="validation-message" id="purokError"></span>
                            </div>
                            <div class="form-group">
                                <label for="barangay">Barangay <span class="required">*</span></label>
                                <input type="text" id="barangay" name="barangay" placeholder="">
                                <span class="validation-message" id="barangayError"></span>
                            </div>
                            <div class="form-group">
                                <label for="city">City/Municipality <span class="required">*</span></label>
                                <input type="text" id="city" name="city" placeholder="">
                                <span class="validation-message" id="cityError"></span>
                            </div>
                            <div class="form-group">
                                <label for="province">Province <span class="required">*</span></label>
                                <input type="text" id="province" name="province" placeholder="">
                                <span class="validation-message" id="provinceError"></span>
                            </div>
                            <div class="form-group">
                                <label for="zipCode">Zip Code <span class="required">*</span></label>
                                <input type="number" id="zipCode" name="zipCode" placeholder="">
                                <span class="validation-message" id="zipCodeError"></span>
                            </div>
                            <div class="form-group">
                                <label for="country">Country <span class="required">*</span></label>
                                <input type="text" id="country" name="country" placeholder="">
                                <span class="validation-message" id="countryError"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Credentials -->
                    <div class="form-step">
                        <h2>Credentials</h2>
                        <p class="form-subtitle">Set up your login details</p>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-at input-icon"></i>
                                    <input type="text" id="username" name="username" placeholder="">
                                </div>
                                <span class="validation-message" id="usernameError"></span>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-envelope input-icon"></i>
                                    <input type="email" id="email" name="email" placeholder="">
                                </div>
                                <span class="validation-message" id="emailError"></span>
                            </div>
                            <div class="form-group">
                                <label for="password">Password <span class="required">*</span></label>
                                <div class="password-container">
                                    <input type="password" id="password" name="password" placeholder="" autocomplete="new-password">
                                    <button type="button" class="eye-icon-btn" id="togglePassword" aria-label="Toggle visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <span id="pwStrength"></span>
                                <span class="validation-message" id="passwordError"></span>
                            </div>
                            <div class="form-group">
                                <label for="repassword">Re-enter Password <span class="required">*</span></label>
                                <div class="password-container">
                                    <input type="password" id="repassword" name="repassword" placeholder="" autocomplete="new-password">
                                    <button type="button" class="eye-icon-btn" id="toggleRePassword" aria-label="Toggle visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <span id="pwMatch"></span>
                                <span class="validation-message" id="repasswordError"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Security Questions -->
                    <div class="form-step">
                        <h2>Security Questions</h2>
                        <p class="form-subtitle">Help us keep your account safe</p>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="secure_question">Security Question 1 <span class="required">*</span></label>
                                <select id="secure_question" name="secure_question">
                                    <option value="">-- Choose a Question --</option>
                                    <option value="Who is your bestfriend in elementary?">Who is your bestfriend in elementary?</option>
                                    <option value="What is the name of your pet?">What is the name of your pet?</option>
                                    <option value="Who is your favorite teacher in highschool?">Who is your favorite teacher in highschool?</option>
                                    <option value="What was your first car?">What was your first car?</option>
                                    <option value="In what city were you born?">In what city were you born?</option>
                                </select>
                                <span class="validation-message" id="secure_questionError"></span>
                            </div>
                            <div class="form-group">
                                <label for="secure_answer">Your Answer 1 <span class="required">*</span></label>
                                <div class="password-container">
                                    <input type="password" id="secure_answer" name="secure_answer" placeholder="Enter your answer">
                                    <button type="button" class="eye-icon-btn" aria-label="Toggle visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <span class="validation-message" id="secure_answerError"></span>
                            </div>
                            <div class="form-group">
                                <label for="secure_question2">Security Question 2 <span class="required">*</span></label>
                                <select id="secure_question2" name="secure_question2">
                                    <option value="">-- Choose a Question --</option>
                                    <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                    <option value="What elementary school did you attend?">What elementary school did you attend?</option>
                                    <option value="What is your favorite food?">What is your favorite food?</option>
                                    <option value="What was your childhood nickname?">What was your childhood nickname?</option>
                                    <option value="What is the name of your best friend?">What is the name of your best friend?</option>
                                </select>
                                <span class="validation-message" id="secure_question2Error"></span>
                            </div>
                            <div class="form-group">
                                <label for="secure_answer2">Your Answer 2 <span class="required">*</span></label>
                                <div class="password-container">
                                    <input type="password" id="secure_answer2" name="secure_answer2" placeholder="Enter your answer">
                                    <button type="button" class="eye-icon-btn" aria-label="Toggle visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <span class="validation-message" id="secure_answer2Error"></span>
                            </div>
                            <div class="form-group">
                                <label for="secure_question3">Security Question 3 <span class="required">*</span></label>
                                <select id="secure_question3" name="secure_question3">
                                    <option value="">-- Choose a Question --</option>
                                    <option value="What is your father's middle name?">What is your father's middle name?</option>
                                    <option value="What street did you grow up on?">What street did you grow up on?</option>
                                    <option value="What is your favorite movie?">What is your favorite movie?</option>
                                    <option value="What is the name of your first pet?">What is the name of your first pet?</option>
                                    <option value="What year did you graduate high school?">What year did you graduate high school?</option>
                                </select>
                                <span class="validation-message" id="secure_question3Error"></span>
                            </div>
                            <div class="form-group">
                                <label for="secure_answer3">Your Answer 3 <span class="required">*</span></label>
                                <div class="password-container">
                                    <input type="password" id="secure_answer3" name="secure_answer3" placeholder="Enter your answer">
                                    <button type="button" class="eye-icon-btn" aria-label="Toggle visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <span class="validation-message" id="secure_answerError"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-navigation-buttons">
                        <button type="button" id="prevBtn">Previous</button>
                        <button type="button" id="nextBtn">NEXT &nbsp;&rsaquo;</button>
                        <button type="submit" id="submitBtn">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    <script src="../../js/serve_asset.php?file=signup.js"></script>
</body>
</html>