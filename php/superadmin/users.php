<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireRole('superadmin');
$pageTitle = 'User Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - NAIGO</title>
    <link rel="stylesheet" href="../../css/design-system.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .users-table { width: 100%; border-collapse: collapse; background: var(--bg-card); border-radius: 8px; overflow: hidden; }
        .users-table th, .users-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .users-table th { background: var(--bg-body); font-weight: 600; }
        .action-btn { padding: 6px 12px; border-radius: 4px; border: none; cursor: pointer; font-size: 0.85rem; margin-right: 4px; }
        .btn-edit { background: #3b82f6; color: white; }
        .btn-block { background: #ef4444; color: white; }
        .btn-unblock { background: #10b981; color: white; }
        .btn-priv { background: #8b5cf6; color: white; }
        .blocked-row { background-color: #f1f5f9 !important; }
        .blocked-row td { color: #94a3b8 !important; }
        .blocked-row .status-badge { filter: grayscale(1); opacity: 0.6; }

        /* Enhanced Modal Styling */
        #userModal .modal2-content {
            padding: 3.5rem;
            border: 1px solid rgba(26, 86, 83, 0.08);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.18);
            border-radius: 1.5rem;
            background: #fff;
        }

        #userForm fieldset {
            border: 1px solid #f3f4f6;
            border-radius: 1.25rem;
            padding: 2rem;
            margin-bottom: 1.5rem;
            background: #fff;
            transition: all 0.25s ease;
        }
        #userForm fieldset:hover {
            border-color: rgba(26, 86, 83, 0.15);
            box-shadow: 0 8px 20px rgba(0,0,0,0.02);
            transform: translateY(-1px);
        }

        #userForm legend {
            font-weight: 700;
            color: var(--primary-color);
            padding: 0 1rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #fff;
        }

        /* Modal Form Styles */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .section-heading { grid-column: 1 / -1; color: var(--primary-color); border-bottom: 2px solid #f3f4f6; padding-bottom: 0.5rem; margin-top: 1rem; margin-bottom: 0.5rem; }
        .required { color: #ef4444; margin-left: 2px; }
        .hint { font-size: 0.8rem; color: #94a3b8; font-weight: normal; }

        /* RED Validation Messages */
        .validation-message {
            color: #ef4444 !important;
            font-size: 0.75rem;
            margin-top: 0.4rem;
            display: block;
            font-weight: 600;
            animation: fadeIn 0.2s ease-out;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-3px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
        }

        /* Premium Stepper Styles */
        .modal-stepper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            position: relative;
            padding: 0 2rem;
        }
        .modal-stepper::before {
            content: '';
            position: absolute;
            top: 16px;
            left: 3rem;
            right: 3rem;
            height: 2px;
            background: #f3f4f6;
            z-index: 1;
        }
        .step-item {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            background: #fff;
            padding: 0 8px;
        }
        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            color: #d1d5db;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .step-label {
            font-size: 0.65rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            transition: color 0.3s ease;
        }
        .step-item.active .step-circle {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: #fff;
            transform: scale(1.15);
            box-shadow: 0 0 0 6px rgba(26, 86, 83, 0.08);
        }
        .step-item.active .step-label { color: var(--primary-color); }
        .step-item.completed .step-circle {
            border-color: #10b981;
            background: #10b981;
            color: #fff;
        }
        .step-item.completed .step-label { color: #10b981; }
        .step-circle i { font-size: 0.75rem; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php $currentPage = 'superadmin_users'; include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <header style="margin-bottom: 2rem;">
                <h1 class="page-title" style="font-size: 1.75rem; margin-bottom: 0.25rem;">User Management</h1>
                <p class="page-subtitle text-muted" style="margin: 0; font-size: 0.95rem;">Manage identities, roles, and access across the platform.</p>
            </header>

            <article class="sa-box" style="margin-bottom: 2rem; background: var(--bg-card); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <header class="sa-box-header" style="margin-bottom: 1.5rem;">
                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; width: 100%;">
                        <input type="text" id="searchInput" placeholder="Search by name, username, or email..." class="input-field" style="min-width: 200px; flex: 1;" onkeyup="handleSearch(event)">
                        <select id="filterRole" class="input-field" style="width: auto;" onchange="applyFilters()">
                            <option value="">All Roles</option>
                            <option value="consumer">Consumer</option>
                            <option value="admin">Admin</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                        <select id="filterStatus" class="input-field" style="width: auto;" onchange="applyFilters()">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="blocked">Blocked</option>
                        </select>
                        <button class="btn-secondary" onclick="resetFilters()">Reset</button>
                        <button class="btn-primary" onclick="openUserModal()" style="white-space: nowrap;"><i class="fa-solid fa-plus" style="margin-right: 8px;"></i> Add User</button>
                    </div>
                </header>

                <div class="sa-box-content no-pad">
                    <div id="usersTableContainer">
                        <div style="padding: 2rem; text-align: center; color: var(--text-muted);">Loading...</div>
                    </div>
                </div>
            </article>

            <div class="pagination-container" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 1rem 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                <span id="userCountBadge" style="background:#dbeafe; color:#1e40af; padding:0.35rem 0.8rem; border-radius:20px; font-weight:700; font-size:0.85rem;">0 Users</span>
                <div id="paginationControls" style="display: flex; align-items: center; justify-content: flex-end;"></div>
            </div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal2">
        <div class="modal2-content" style="max-width: 400px;">
            <h2 style="color: var(--error-color);">Confirm Delete</h2>
            <p>Are you sure you want to permanently delete <span id="deleteUserName" style="font-weight: bold;"></span>?</p>
            <p class="hint" style="margin-top: 0.5rem;">This action cannot be undone and may fail if the user has active orders or dependencies.</p>
            <div style="text-align: right; margin-top: 1.5rem;">
                <button onclick="closeDeleteModal()" class="btn-secondary">Cancel</button>
                <button id="confirmDeleteBtn" class="btn-primary" style="background: var(--error-color); border-color: var(--error-color);">Delete User</button>
            </div>
        </div>
    </div>

    <!-- Response Modal -->
    <div id="responseModal" class="modal2" style="z-index: 1050;">
        <div class="modal2-content" style="max-width: 400px; text-align: center;">
            <div id="responseIcon" style="font-size: 3rem; margin-bottom: 1rem;"></div>
            <h2 id="responseTitle">Success</h2>
            <p id="responseMessage"></p>
            <div style="margin-top: 1.5rem;">
                <button onclick="closeResponseModal()" class="btn-primary">OK</button>
            </div>
        </div>
    </div>

    <!-- User Modal (Add/Edit) -->
    <div id="userModal" class="modal2">
        <div class="modal2-content" style="max-width: 850px; width: 95%;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
                <h2 id="modalTitle" style="margin:0; font-size:1.6rem; font-weight:700; color:#1f2937;">Add User</h2>
                <button onclick="closeUserModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#94a3b8; transition:color 0.2s;" onmouseover="this.style.color='#1f2937'" onmouseout="this.style.color='#94a3b8'">&times;</button>
            </div>

            <!-- Stepper -->
            <div class="modal-stepper">
                <div class="step-item active" id="stepIndicator0">
                    <div class="step-circle">1</div>
                    <span class="step-label">Personal</span>
                </div>
                <div class="step-item" id="stepIndicator1">
                    <div class="step-circle">2</div>
                    <span class="step-label">Address</span>
                </div>
                <div class="step-item" id="stepIndicator2">
                    <div class="step-circle">3</div>
                    <span class="step-label">Account</span>
                </div>
                <div class="step-item" id="stepIndicator3">
                    <div class="step-circle">4</div>
                    <span class="step-label">Security</span>
                </div>
            </div>

            <form id="userForm" novalidate style="width: 100%; text-align: left; max-height: 65vh; overflow-y: auto; padding: 0.5rem 1rem 0.5rem 0;">
                <input type="hidden" name="id" id="userId">

                <!-- STEP 1: Personal Info -->
                <div class="form-step" id="step0">
                    <fieldset>
                        <legend>Personal Information</legend>
                        <div class="form-grid">
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label style="font-weight:600; color:#4b5563; margin-bottom:0.5rem; display:block;">Select User Role<span class="required">*</span></label>
                                <select name="role" id="role" class="input-field" style="background:#f9fafb;">
                                    <option value="consumer">Consumer</option>
                                    <option value="admin">Admin</option>
                                    <option value="superadmin">Superadmin</option>
                                </select>
                                <span class="hint" style="display:block; margin-top:0.5rem;">Administrative roles (Admin/Superadmin) skip security question setup.</span>
                            </div>
                            <div class="form-group">
                                <label>Identity Number <span class="hint">(xxxx-xxxx)</span></label>
                                <input type="text" name="custom_id" id="customId" placeholder="Auto-generated if blank" class="input-field">
                                <span class="validation-message" id="customIdError"></span>
                            </div>
                            <div class="form-group">
                                <label>First Name<span class="required">*</span></label>
                                <input type="text" name="firstName" id="firstName" class="input-field">
                                <span class="validation-message" id="firstNameError"></span>
                            </div>
                            <div class="form-group">
                                <label>Last Name<span class="required">*</span></label>
                                <input type="text" name="lastName" id="lastName" class="input-field">
                                <span class="validation-message" id="lastNameError"></span>
                            </div>
                            <div class="form-group">
                                <label>Middle Initial</label>
                                <input type="text" name="middleInitial" id="middleInitial" class="input-field">
                                <span class="validation-message" id="middleInitialError"></span>
                            </div>
                            <div class="form-group">
                                <label>Suffix / Extension</label>
                                <input type="text" name="extension" id="extension" class="input-field">
                                <span class="validation-message" id="extensionError"></span>
                            </div>
                            <div class="form-group">
                                <label>Sex<span class="required">*</span></label>
                                <select name="sex" id="sex" class="input-field">
                                    <option value="">Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                                <span class="validation-message" id="sexError"></span>
                            </div>
                            <div class="form-group">
                                <label>Birthdate<span class="required">*</span></label>
                                <input type="date" name="birthdate" id="birthdate" class="input-field">
                                <span class="validation-message" id="birthdateError"></span>
                            </div>
                            <div class="form-group">
                                <label>Calculated Age</label>
                                <input type="number" name="age" id="age" readonly class="input-field" style="background:#f3f4f6; color:#6b7280; font-weight:600;">
                            </div>
                        </div>
                    </fieldset>
                </div>

                <!-- STEP 2: Address -->
                <div class="form-step" id="step1" style="display:none;">
                    <fieldset>
                        <legend>Address Details</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Purok / Zone</label>
                                <input type="text" name="purok" id="purok" class="input-field">
                                <span class="validation-message" id="purokError"></span>
                            </div>
                            <div class="form-group">
                                <label>Barangay</label>
                                <input type="text" name="barangay" id="barangay" class="input-field">
                                <span class="validation-message" id="barangayError"></span>
                            </div>
                            <div class="form-group">
                                <label>City / Municipality</label>
                                <input type="text" name="city" id="city" class="input-field">
                                <span class="validation-message" id="cityError"></span>
                            </div>
                            <div class="form-group">
                                <label>Province</label>
                                <input type="text" name="province" id="province" class="input-field">
                                <span class="validation-message" id="provinceError"></span>
                            </div>
                            <div class="form-group">
                                <label>Zip Code</label>
                                <input type="text" name="zipCode" id="zipCode" class="input-field">
                                <span class="validation-message" id="zipCodeError"></span>
                            </div>
                            <div class="form-group">
                                <label>Country</label>
                                <input type="text" name="country" id="country" value="Philippines" class="input-field">
                                <span class="validation-message" id="countryError"></span>
                            </div>
                        </div>
                    </fieldset>
                </div>

                <!-- STEP 3: Credentials -->
                <div class="form-step" id="step2" style="display:none;">
                    <fieldset>
                        <legend>Account Credentials</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Username<span class="required">*</span></label>
                                <input type="text" name="username" id="username" class="input-field">
                                <span class="validation-message" id="usernameError"></span>
                            </div>
                            <div class="form-group">
                                <label>Email Address<span class="required">*</span></label>
                                <input type="email" name="email" id="email" class="input-field">
                                <span class="validation-message" id="emailError"></span>
                            </div>
                            <div class="form-group">
                                <label>Account Password <span id="pwHint" class="hint"></span></label>
                                <input type="password" name="password" id="password" autocomplete="new-password" class="input-field">
                                <span class="validation-message" id="passwordError"></span>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <input type="password" name="repassword" id="repassword" autocomplete="new-password" class="input-field">
                                <span class="validation-message" id="repasswordError"></span>
                            </div>
                        </div>
                    </fieldset>
                </div>

                <!-- STEP 4: Security -->
                <div class="form-step" id="step3" style="display:none;">
                    <fieldset id="securitySection">
                        <legend>Security Setup</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Security Question 1 <span class="hint">(Optional)</span></label>
                                <select name="secure_question" id="sq1" class="input-field">
                                    <option value="">-- Select Question --</option>
                                    <option value="Who is your bestfriend in elementary?">Who is your bestfriend in elementary?</option>
                                    <option value="What is the name of your pet?">What is the name of your pet?</option>
                                    <option value="Who is your favorite teacher in highschool?">Who is your favorite teacher in highschool?</option>
                                    <option value="What was your first car?">What was your first car?</option>
                                    <option value="In what city were you born?">In what city were you born?</option>
                                </select>
                                <span class="validation-message" id="sq1Error"></span>
                            </div>
                            <div class="form-group">
                                <label>Answer 1 <span class="hint">(Optional)</span></label>
                                <div class="password-container" style="position:relative;">
                                    <input type="password" name="secure_answer" id="sa1" class="input-field">
                                    <i class="fa-solid fa-eye eye-icon pw-toggle" onclick="toggleAnswerVisibility(this)" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; color:#94a3b8;"></i>
                                </div>
                                <span class="validation-message" id="sa1Error"></span>
                            </div>
                            <div class="form-group">
                                <label>Security Question 2 <span class="hint">(Optional)</span></label>
                                <select name="secure_question2" id="sq2" class="input-field">
                                    <option value="">-- Select Question --</option>
                                    <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                    <option value="What elementary school did you attend?">What elementary school did you attend?</option>
                                    <option value="What is your favorite food?">What is your favorite food?</option>
                                    <option value="What was your childhood nickname?">What was your childhood nickname?</option>
                                    <option value="What is the name of your best friend?">What is the name of your best friend?</option>
                                </select>
                                <span class="validation-message" id="sq2Error"></span>
                            </div>
                            <div class="form-group">
                                <label>Answer 2 <span class="hint">(Optional)</span></label>
                                <div class="password-container" style="position:relative;">
                                    <input type="password" name="secure_answer2" id="sa2" class="input-field">
                                    <i class="fa-solid fa-eye eye-icon pw-toggle" onclick="toggleAnswerVisibility(this)" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; color:#94a3b8;"></i>
                                </div>
                                <span class="validation-message" id="sa2Error"></span>
                            </div>
                            <div class="form-group">
                                <label>Security Question 3 <span class="hint">(Optional)</span></label>
                                <select name="secure_question3" id="sq3" class="input-field">
                                    <option value="">-- Select Question --</option>
                                    <option value="What is your father's middle name?">What is your father's middle name?</option>
                                    <option value="What street did you grow up on?">What street did you grow up on?</option>
                                    <option value="What is your favorite movie?">What is your favorite movie?</option>
                                    <option value="What is the name of your first pet?">What is the name of your first pet?</option>
                                    <option value="What year did you graduate high school?">What year did you graduate high school?</option>
                                </select>
                                <span class="validation-message" id="sq3Error"></span>
                            </div>
                            <div class="form-group">
                                <label>Answer 3 <span class="hint">(Optional)</span></label>
                                <div class="password-container" style="position:relative;">
                                    <input type="password" name="secure_answer3" id="sa3" class="input-field">
                                    <i class="fa-solid fa-eye eye-icon pw-toggle" onclick="toggleAnswerVisibility(this)" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; color:#94a3b8;"></i>
                                </div>
                                <span class="validation-message" id="sa3Error"></span>
                            </div>
                        </div>
                        <p class="hint" style="margin-top: 1rem; font-style: italic;">Note: If you are editing an existing user, you can leave these blank to keep the current security settings.</p>
                    </fieldset>
                </div>

                <div class="form-navigation-buttons" style="display:flex; justify-content:space-between; margin-top:2rem; border-top: 1px solid #f3f4f6; padding-top: 2rem;">
                    <button type="button" id="prevBtn" onclick="prevStep()" class="btn-secondary" style="display:none; padding: 0.75rem 1.5rem;">Back</button>
                    <div style="margin-left: auto; display:flex; gap:10px;">
                        <button type="button" onclick="closeUserModal()" class="btn-secondary" style="padding: 0.75rem 1.5rem;">Cancel</button>
                        <button type="button" id="nextBtn" onclick="nextStep()" class="btn-primary" style="padding: 0.75rem 1.5rem; background: var(--primary-color);">Next Step <i class="fa-solid fa-arrow-right" style="margin-left:8px; font-size:0.8rem;"></i></button>
                        <button type="submit" id="submitBtn" class="btn-primary" style="display:none; padding: 0.75rem 2rem; background:#10b981; border-color:#10b981;">Complete & Save <i class="fa-solid fa-circle-check" style="margin-left:8px;"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Privileges Modal -->
    <div id="privModal" class="modal2">
        <div class="modal2-content" style="max-width:450px;">
            <h2 style="margin:0 0 0.5rem; font-size:1.5rem; color:#1f2937;">Account Privileges</h2>
            <p id="privUserName" style="font-weight: 600; margin-bottom: 1.5rem; color:#64748b; font-size:0.9rem;"></p>
            <div id="privContent" style="width: 100%;"></div>
            <button onclick="document.getElementById('privModal').style.display='none'" class="btn-primary" style="margin-top: 1.5rem; width:100%; justify-content:center;">Close</button>
        </div>
    </div>

    <script src="../../js/admin_user_validation.js?v=<?php echo time(); ?>"></script>
    <script src="../../js/pagination_util.js"></script>
    <script>
        function toggleAnswerVisibility(icon) {
            const input = icon.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text'; icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password'; icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        const api = '../../php/database';
        const currentUserId = '<?php echo $_SESSION['user']['id']; ?>';

        let currentPage = 1;
        let limit = 10;
        let currentSearch = '';
        let currentRole = '';
        let currentStatus = '';
        let usersMap = {};
        let editingUserId = '';

        function loadUsers(page = 1) {
            currentPage = page;
            const params = new URLSearchParams({ page, limit, search: currentSearch, role: currentRole, status: currentStatus });
            fetch(api + '/superadmin_users_list.php?' + params.toString())
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    let html = '<table class="data-table"><thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Actions</th><th>Privileges</th></tr></thead><tbody>';
                    usersMap = {};
                    data.users.forEach(u => { usersMap[u.id] = u; });
                    if (data.users.length === 0) {
                        html += '<tr><td colspan="6" style="text-align:center; padding:2rem; color: var(--text-muted);">No users found matching your criteria.</td></tr>';
                    } else {
                        data.users.forEach(u => {
                            const isBlocked = u.is_blocked == 1;
                            const isSelf = u.id === currentUserId;
                            const rowClass = isBlocked ? 'blocked-row' : '';
                            const roleLower = (u.role || 'consumer').toLowerCase();
                            let roleClass = roleLower === 'admin' ? 'status-ok' : (roleLower === 'superadmin' ? 'status-trash' : 'status-pending');
                            html += `<tr class="${rowClass}">
                                <td style="font-weight: 500;">${escapeHtml(u.firstName + ' ' + u.lastName)}</td>
                                <td class="text-muted">@${escapeHtml(u.username)}</td>
                                <td><span class="status-badge ${roleClass}">${escapeHtml(u.role || 'consumer')}</span></td>
                                <td>${isBlocked ? '<span class="status-badge status-trash">Blocked</span>' : '<span class="status-badge status-ok">Active</span>'}</td>
                                <td>
                                    <div style="display: flex; gap: 4px;">
                                        <button class="btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;" onclick="editUser('${u.id}')">Edit</button>
                                        ${!isSelf ? (isBlocked ?
                                            `<button class="btn-secondary" style="padding: 4px 10px; font-size: 0.75rem; color: #10b981; border-color: #10b981;" onclick="blockUser('${u.id}', 'unblock')">Unblock</button>` :
                                            `<button class="btn-secondary" style="padding: 4px 10px; font-size: 0.75rem; color: #ef4444; border-color: #ef4444;" onclick="blockUser('${u.id}', 'block')">Block</button>`) : ''}
                                        ${!isSelf ? `<button class="btn-secondary" style="padding: 4px 10px; font-size: 0.75rem; color: #ef4444; border-color: #ef4444;" onclick="openDeleteModal('${u.id}', '${escapeHtml(u.firstName + ' ' + u.lastName)}')">Delete</button>` : ''}
                                    </div>
                                </td>
                                <td><button class="btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;" onclick="viewPrivileges('${u.role}', '${escapeHtml(u.username)}')">View</button></td>
                            </tr>`;
                        });
                    }
                    html += '</tbody></table>';
                    document.getElementById('usersTableContainer').innerHTML = html;
                    updatePagination(data.pagination);
                });
        }

        function updatePagination(p) {
            const controls = document.getElementById('paginationControls');
            const badge = document.getElementById('userCountBadge');
            badge.textContent = `${p.total_users} User${p.total_users !== 1 ? 's' : ''}`;
            if (p.total_users > 0) { badge.style.background = '#dbeafe'; badge.style.color = '#1e40af'; }
            else { badge.style.background = '#fee2e2'; badge.style.color = '#dc2626'; }
            window.renderPagination(controls, currentPage, p.total_pages || 1, limit, n => loadUsers(n), l => { limit = l; loadUsers(1); });
        }

        let searchTimeout;
        function handleSearch(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { currentSearch = e.target.value; loadUsers(1); }, 300);
        }

        function applyFilters() {
            currentRole = document.getElementById('filterRole').value;
            currentStatus = document.getElementById('filterStatus').value;
            loadUsers(1);
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterRole').value = '';
            document.getElementById('filterStatus').value = '';
            currentSearch = ''; currentRole = ''; currentStatus = '';
            loadUsers(1);
        }

        let userToDelete = null;
        function openDeleteModal(id, name) {
            userToDelete = id;
            document.getElementById('deleteUserName').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() { document.getElementById('deleteModal').style.display = 'none'; userToDelete = null; }

        function showResponse(success, message) {
            const title = document.getElementById('responseTitle');
            const msg = document.getElementById('responseMessage');
            const icon = document.getElementById('responseIcon');
            title.textContent = success ? 'Success' : 'Error';
            title.style.color = success ? '#10b981' : '#ef4444';
            msg.textContent = message;
            icon.innerHTML = success ? '<i class="fa-solid fa-circle-check" style="color:#10b981;"></i>' : '<i class="fa-solid fa-circle-xmark" style="color:#ef4444;"></i>';
            document.getElementById('responseModal').style.display = 'flex';
        }

        function closeResponseModal() { document.getElementById('responseModal').style.display = 'none'; }

        document.getElementById('confirmDeleteBtn').onclick = function() {
            if (!userToDelete) return;
            const fd = new FormData(); fd.append('user_id', userToDelete);
            fetch(api + '/superadmin_user_delete.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    closeDeleteModal();
                    if (d.success) { showResponse(true, 'User deleted successfully.'); loadUsers(currentPage); }
                    else showResponse(false, d.error || 'Delete failed.');
                });
        };

        function blockUser(userId, action) {
            if (!userId) return;
            if (!confirm(`Are you sure you want to ${action} this user?`)) return;
            fetch(api + '/superadmin_user_block.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'user_id=' + encodeURIComponent(userId) + '&action=' + encodeURIComponent(action)
            }).then(r => r.json()).then(d => {
                if (d.success) { showResponse(true, `User ${action}ed.`); loadUsers(currentPage); }
                else showResponse(false, d.error || 'Action failed.');
            });
        }

        let currentStep = 0;
        const totalSteps = 4;

        function showStep(n) {
            document.querySelectorAll('.form-step').forEach(el => el.style.display = 'none');
            const activeStep = document.getElementById('step' + n);
            if (activeStep) activeStep.style.display = 'block';

            document.querySelectorAll('.step-item').forEach((item, index) => {
                item.classList.remove('active', 'completed');
                if (index === n) {
                    item.classList.add('active');
                    item.querySelector('.step-circle').innerHTML = index + 1;
                } else if (index < n) {
                    item.classList.add('completed');
                    item.querySelector('.step-circle').innerHTML = '<i class="fa-solid fa-check"></i>';
                } else {
                    item.querySelector('.step-circle').innerHTML = index + 1;
                }
            });

            document.getElementById('prevBtn').style.display = (n === 0) ? 'none' : 'inline-block';
            const role = document.getElementById('role').value;
            const maxStep = (role === 'consumer') ? totalSteps - 1 : totalSteps - 2;

            if (n >= maxStep) {
                document.getElementById('nextBtn').style.display = 'none';
                document.getElementById('submitBtn').style.display = 'inline-block';
            } else {
                document.getElementById('nextBtn').style.display = 'inline-block';
                document.getElementById('submitBtn').style.display = 'none';
            }
        }

        async function nextStep() {
            const isEdit = !!editingUserId;
            AdminUserValidation.clearAllErrors();
            let isValid = false;

            if (currentStep === 0) isValid = await AdminUserValidation.validatePersonalInfo();
            else if (currentStep === 1) isValid = AdminUserValidation.validateAddress();
            else if (currentStep === 2) isValid = await AdminUserValidation.validateCredentials(isEdit);

            if (isValid) { currentStep++; showStep(currentStep); }
        }

        function prevStep() { if (currentStep > 0) { currentStep--; showStep(currentStep); } }

        function openUserModal() {
            editingUserId = '';
            AdminUserValidation.setEditMode(false);
            document.getElementById('modalTitle').textContent = 'Add User';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('pwHint').textContent = '(Required for new user)';
            document.getElementById('role').value = 'consumer';
            document.getElementById('role').dispatchEvent(new Event('change'));
            AdminUserValidation.clearAllErrors();
            currentStep = 0; showStep(0);
            document.getElementById('userModal').style.display = 'flex';
        }

        document.getElementById('role').addEventListener('change', () => { showStep(currentStep); });

        function closeUserModal() { document.getElementById('userModal').style.display = 'none'; }

        function editUser(userId) {
            const u = usersMap[userId]; if (!u) return;
            editingUserId = u.id;
            AdminUserValidation.setEditMode(true);
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = u.id;
            document.getElementById('customId').value = u.id;
            
            const fields = ['firstName', 'lastName', 'middleInitial', 'extension', 'sex', 'birthdate', 'purok', 'barangay', 'city', 'province', 'zipCode', 'country', 'username', 'email'];
            fields.forEach(f => { if (document.getElementById(f)) document.getElementById(f).value = u[f] || ''; });
            
            // Robust Role Assignment
            const roleEl = document.getElementById('role');
            if (roleEl && u.role) {
                const rVal = u.role.toLowerCase();
                roleEl.value = rVal;
                roleEl.dispatchEvent(new Event('change'));
            }
            
            if (u.role === 'consumer') {
                if (document.getElementById('sq1')) document.getElementById('sq1').value = u.secure_question || '';
                if (document.getElementById('sq2')) document.getElementById('sq2').value = u.secure_question2 || '';
                if (document.getElementById('sq3')) document.getElementById('sq3').value = u.secure_question3 || '';
            }

            if (u.birthdate) {
                const bd = new Date(u.birthdate), today = new Date();
                let age = today.getFullYear() - bd.getFullYear();
                if (today.getMonth() < bd.getMonth() || (today.getMonth() === bd.getMonth() && today.getDate() < bd.getDate())) age--;
                document.getElementById('age').value = age;
            }
            document.getElementById('password').value = '';
            document.getElementById('pwHint').textContent = '(Leave blank to keep current)';
            AdminUserValidation.clearAllErrors();
            currentStep = 0; showStep(0);
            document.getElementById('userModal').style.display = 'flex';
        }

        document.getElementById('userForm').onsubmit = async function(e) {
            e.preventDefault();
            const isEdit = !!editingUserId;
            const role = document.getElementById('role').value;
            
            try {
                // SEQUENTIAL VALIDATION: Stay on the step that has the error
                if (!(await AdminUserValidation.validatePersonalInfo())) { showStep(0); return; }
                if (!AdminUserValidation.validateAddress()) { showStep(1); return; }
                if (!(await AdminUserValidation.validateCredentials(isEdit))) { showStep(2); return; }
                if (role === 'consumer' && !AdminUserValidation.validateSecurityQuestions(isEdit)) { showStep(3); return; }
                
                const fd = new FormData(this); 
                fd.set('id', editingUserId);
                
                const response = await fetch(api + '/superadmin_user_save.php', { method: 'POST', body: fd });
                const d = await response.json();
                
                if (d.success) { 
                    closeUserModal(); 
                    showResponse(true, 'User data has been saved successfully.'); 
                    loadUsers(currentPage); 
                } else { 
                    showResponse(false, d.error || 'Failed to save user data.'); 
                }
            } catch (err) {
                console.error('Submission Error:', err);
                showResponse(false, 'A network error occurred while saving.');
            }
        };

        const privilegesConfig = {
            'superadmin': [
                { desc: 'Access My Profile',                          has: true  },
                { desc: 'Change Password',                             has: true  },
                { desc: 'Manage All Users (Admins & Consumers)',       has: true  },
                { desc: 'Approve / Reject Registration Requests',      has: true  },
                { desc: 'Block / Unblock Any User',                    has: true  },
                { desc: 'Manage All Restaurants & Menus',              has: true  },
                { desc: 'View & Manage All Orders',                    has: true  },
                { desc: 'View Full Login Audit Logs',                  has: true  },
                { desc: 'Assign / Change User Roles',                  has: true  },
                { desc: 'Full System Administration',                  has: true  },
            ],
            'admin': [
                { desc: 'Access My Profile',                          has: true  },
                { desc: 'Change Password',                             has: true  },
                { desc: 'Manage Consumer Accounts',                    has: true  },
                { desc: 'Approve / Reject Registration Requests',      has: true  },
                { desc: 'Block / Unblock Consumers',                   has: true  },
                { desc: 'Manage Restaurants & Menu Items',             has: true  },
                { desc: 'View & Manage Orders',                        has: true  },
                { desc: 'View Login History (Own)',                     has: true  },
                { desc: 'Manage All Users (Admins)',                   has: false },
                { desc: 'Assign / Change User Roles',                  has: false },
                { desc: 'Full System Administration',                  has: false },
            ],
            'consumer': [
                { desc: 'Access My Profile',                          has: true  },
                { desc: 'Change Password',                             has: true  },
                { desc: 'Browse & Order Food',                         has: true  },
                { desc: 'Manage Cart & Checkout',                      has: true  },
                { desc: 'View Personal Order History',                 has: true  },
                { desc: 'Save Favourite Restaurants',                  has: true  },
                { desc: 'Manage Payment Methods',                      has: true  },
                { desc: 'Manage Consumer Accounts',                    has: false },
                { desc: 'Approve / Reject Registration Requests',      has: false },
                { desc: 'Block / Unblock Users',                       has: false },
                { desc: 'Manage Restaurants & Menu Items',             has: false },
                { desc: 'Full System Administration',                  has: false },
            ],
        };

        function viewPrivileges(role, name) {
            document.getElementById('privUserName').textContent = name + ' (' + role + ')';
            const privs = privilegesConfig[role] || privilegesConfig['consumer'];
            const listHtml = privs.map(p => `
                <div style="display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:8px;
                     background:${p.has ? '#f0fdf4' : '#f8fafc'};
                     border:1px solid ${p.has ? '#bbf7d0' : '#e2e8f0'}; margin-bottom:4px;">
                    <i class="fa-solid ${p.has ? 'fa-circle-check' : 'fa-circle-xmark'}"
                       style="color:${p.has ? '#16a34a' : '#94a3b8'}; font-size:17px;"></i>
                    <span style="font-size:0.875rem; font-weight:500; color:${p.has ? '#166534' : '#64748b'};
                          text-decoration:${p.has ? 'none' : 'line-through'};">${p.desc}</span>
                </div>`).join('');
            document.getElementById('privContent').innerHTML = listHtml;
            document.getElementById('privModal').style.display = 'flex';
        }

        function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

        document.addEventListener('DOMContentLoaded', () => {
            if (typeof AdminUserValidation !== 'undefined') {
                AdminUserValidation.init(document.getElementById('userForm'), { apiBase: api, roleSelector: '#role', securitySection: '#securitySection' });
            }
            loadUsers(1);
        });
    </script>
</body>
</html>
