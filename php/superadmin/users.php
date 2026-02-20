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
    <title><?php echo $pageTitle; ?> - FoodGrab</title>
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
        .blocked-row { opacity: 0.6; background: #fee2e2; }

        /* Modal Form Styles */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .section-heading { grid-column: 1 / -1; color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 0.5rem; margin-top: 1rem; margin-bottom: 0.5rem; }
        .required { color: red; margin-left: 2px; }
        .hint { font-size: 0.8rem; color: #666; font-weight: normal; }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php $currentPage = 'superadmin_users';
include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h1 class="page-title">User Management</h1>
                <button class="btn-primary" onclick="openUserModal()">Add User</button>
            </div>

            <div id="usersTableContainer">Loading...</div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>

    <!-- User Modal (Add/Edit) -->
    <div id="userModal" class="modal2">
        <div class="modal2-content" style="max-width: 800px; width: 95%;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <h2 id="modalTitle">Add User</h2>
                <button onclick="closeUserModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>

            <form id="userForm" style="width: 100%; text-align: left; max-height: 80vh; overflow-y: auto; padding-right: 10px;">
                <input type="hidden" name="id" id="userId">

                <h3 class="section-heading">Personal Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>ID No. (Leave blank to auto-generate)</label>
                        <input type="text" name="custom_id" id="customId" placeholder="xxxx-xxxx">
                    </div>
                    <div class="form-group">
                        <label>First Name<span class="required">*</span></label>
                        <input type="text" name="firstName" id="firstName" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name<span class="required">*</span></label>
                        <input type="text" name="lastName" id="lastName" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Initial</label>
                        <input type="text" name="middleInitial" id="middleInitial">
                    </div>
                    <div class="form-group">
                        <label>Extension</label>
                        <input type="text" name="extension" id="extension">
                    </div>
                    <div class="form-group">
                        <label>Sex<span class="required">*</span></label>
                        <select name="sex" id="sex" required>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Birthdate<span class="required">*</span></label>
                        <input type="date" name="birthdate" id="birthdate" required onchange="calculateAge()">
                    </div>
                    <div class="form-group">
                        <label>Age</label>
                        <input type="number" name="age" id="age" readonly>
                    </div>
                </div>

                <h3 class="section-heading">Address</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Purok</label>
                        <input type="text" name="purok" id="purok">
                    </div>
                    <div class="form-group">
                        <label>Barangay</label>
                        <input type="text" name="barangay" id="barangay">
                    </div>
                    <div class="form-group">
                        <label>City/Municipality</label>
                        <input type="text" name="city" id="city">
                    </div>
                    <div class="form-group">
                        <label>Province</label>
                        <input type="text" name="province" id="province">
                    </div>
                    <div class="form-group">
                        <label>Zip Code</label>
                        <input type="text" name="zipCode" id="zipCode">
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country" id="country" value="Philippines">
                    </div>
                </div>

                <h3 class="section-heading">Account Details</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Username<span class="required">*</span></label>
                        <input type="text" name="username" id="username" required>
                    </div>
                    <div class="form-group">
                        <label>Email<span class="required">*</span></label>
                        <input type="email" name="email" id="email" required>
                    </div>
                    <div class="form-group">
                        <label>Role<span class="required">*</span></label>
                        <select name="role" id="role" onchange="toggleSecurityQuestions()">
                            <option value="consumer">Consumer</option>
                            <option value="admin">Admin</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Password <span id="pwHint" class="hint">(Default)</span></label>
                        <input type="password" name="password" id="password" autocomplete="new-password">
                    </div>
                </div>

                <div id="securitySection" style="display:none;">
                    <h3 class="section-heading">Security Questions</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Question 1</label>
                            <select name="secure_question" id="sq1">
                                <option value="">-- Select --</option>
                                <option value="Who is your bestfriend in elementary?">Who is your bestfriend in elementary?</option>
                                <option value="What is the name of your pet?">What is the name of your pet?</option>
                                <option value="Who is your favorite teacher in highschool?">Who is your favorite teacher in highschool?</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Answer 1</label>
                            <input type="password" name="secure_answer" id="sa1">
                        </div>
                        <div class="form-group">
                            <label>Question 2</label>
                            <select name="secure_question2" id="sq2">
                                <option value="">-- Select --</option>
                                <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                <option value="What elementary school did you attend?">What elementary school did you attend?</option>
                                <option value="What is your favorite food?">What is your favorite food?</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Answer 2</label>
                            <input type="password" name="secure_answer2" id="sa2">
                        </div>
                        <div class="form-group">
                            <label>Question 3</label>
                            <select name="secure_question3" id="sq3">
                                <option value="">-- Select --</option>
                                <option value="What is your father's middle name?">What is your father's middle name?</option>
                                <option value="What street did you grow up on?">What street did you grow up on?</option>
                                <option value="What is your favorite movie?">What is your favorite movie?</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Answer 3</label>
                            <input type="password" name="secure_answer3" id="sa3">
                        </div>
                    </div>
                </div>

                <div style="text-align: right; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                    <button type="button" onclick="closeUserModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Privileges Modal -->
    <div id="privModal" class="modal2">
        <div class="modal2-content">
            <h2>User Privileges</h2>
            <p id="privUserName" style="font-weight: bold; margin-bottom: 1rem;"></p>
            <div id="privContent" style="width: 100%;"></div>
            <button onclick="document.getElementById('privModal').style.display='none'" class="btn-primary" style="margin-top: 1rem;">Close</button>
        </div>
    </div>

    <script>
        const api = '../../php/database';
        const currentUserId = '<?php echo $_SESSION['user']['id']; ?>';

        function loadUsers() {
            fetch(api + '/superadmin_users_list.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    let html = '<table class="data-table"><thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Actions</th><th>Privileges</th></tr></thead><tbody>';
                    data.users.forEach(u => {
                        const isBlocked = u.is_blocked == 1;
                        const isSelf = u.id === currentUserId;
                        const rowClass = isBlocked ? 'blocked-row' : '';

                        let roleClass = 'role-consumer';
                        if (u.role === 'admin') roleClass = 'role-admin';
                        if (u.role === 'superadmin') roleClass = 'role-superadmin';

                        html += `<tr class="${rowClass}">
                            <td>${escapeHtml(u.firstName + ' ' + u.lastName)}</td>
                            <td>${escapeHtml(u.username)}</td>
                            <td><span class="status-badge no-dot ${roleClass}">${u.role}</span></td>
                            <td>${isBlocked ? '<span class="status-badge no-dot status-trash">Blocked</span>' : '<span class="status-badge no-dot status-ok">Active</span>'}</td>
                            <td>
                                <button class="btn-primary" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;" onclick='editUser(${JSON.stringify(u)})'>Edit</button>
                                ${!isSelf ? (isBlocked ?
                                    `<button class="btn-secondary" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;" onclick="blockUser('${u.id}', 'unblock')">Unblock</button>` :
                                    `<button class="btn-secondary" style="padding: 0.25rem 0.75rem; font-size: 0.8rem; color: var(--error-color); border-color: var(--error-color);" onclick="blockUser('${u.id}', 'block')">Block</button>`) : ''}
                            </td>
                            <td>
                                <button class="btn-secondary" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;" onclick="viewPrivileges('${u.role}', '${escapeHtml(u.username)}')">View</button>
                            </td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    document.getElementById('usersTableContainer').innerHTML = html;
                });
        }

        function blockUser(id, action) {
            if (!confirm(`Are you sure you want to ${action} this user?`)) return;
            const fd = new FormData();
            fd.append('user_id', id);
            fd.append('action', action);
            fetch(api + '/superadmin_user_block.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) loadUsers();
                    else alert(d.error);
                });
        }

        function calculateAge() {
            const birthDate = new Date(document.getElementById('birthdate').value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            document.getElementById('age').value = age;
        }

        function toggleSecurityQuestions() {
            const role = document.getElementById('role').value;
            const secSection = document.getElementById('securitySection');
            const requiredFields = secSection.querySelectorAll('select, input');

            if (role === 'consumer') {
                secSection.style.display = 'block';
                requiredFields.forEach(f => f.required = true);
            } else {
                secSection.style.display = 'none';
                requiredFields.forEach(f => f.required = false);
            }
        }

        function openUserModal() {
            document.getElementById('modalTitle').textContent = 'Add User';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('pwHint').textContent = '(Required for new user)';
            document.getElementById('role').value = 'consumer';
            toggleSecurityQuestions();
            document.getElementById('userModal').style.display = 'flex';
        }

        function closeUserModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        function editUser(u) {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = u.id;
            document.getElementById('customId').value = u.id;

            const fields = ['firstName', 'lastName', 'middleInitial', 'extension', 'sex', 'birthdate', 'purok', 'barangay', 'city', 'province', 'zipCode', 'country', 'username', 'email', 'role'];
            fields.forEach(f => {
                if (document.getElementById(f)) document.getElementById(f).value = u[f] || '';
            });

            if (u.birthdate) calculateAge();

            document.getElementById('password').value = '';
            document.getElementById('pwHint').textContent = '(Leave blank to keep current)';

            toggleSecurityQuestions();

            document.getElementById('userModal').style.display = 'flex';
        }

        document.getElementById('userForm').onsubmit = function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fetch(api + '/superadmin_user_save.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        closeUserModal();
                        loadUsers();
                    } else {
                        alert(d.error);
                    }
                });
        };

        const privileges = {
            'superadmin': [
                'Manage system restaurants',
                'Manage restaurant tables',
                'Manage reservations',
                'Manage user roles and accounts (including blocking users)',
                'View request history',
                'View my profile and change password',
                'Approve and block requests from admin'
            ],
            'admin': [
                'Manage system restaurants',
                'Manage restaurant tables',
                'Manage reservations',
                'Manage consumer accounts',
                'View request history',
                'View my profile and change password'
            ],
            'consumer': [
                'View restaurants',
                'Book reservation',
                'View reservation history',
                'View my profile and change password'
            ]
        };

        function viewPrivileges(role, name) {
            document.getElementById('privUserName').textContent = name + ' (' + role + ')';
            const privs = privileges[role] || [];

            const listHtml = privs.map((p, index) => `
                <div style="display:flex; align-items:center; gap:0.75rem; padding:0.25rem 0; text-align:left;">
                    <span style="font-weight:700; color:var(--primary-color); min-width:1.5rem; text-align:right;">${index + 1}.</span>
                    <span>${p}</span>
                </div>
            `).join('');

            const contentDiv = document.getElementById('privContent');
            contentDiv.innerHTML = `<div style="display:inline-block; text-align:left;">${listHtml}</div>`;
            contentDiv.style.display = 'block';
            contentDiv.style.textAlign = 'center';
            document.getElementById('privModal').style.display = 'flex';
        }

        function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

        loadUsers();
    </script>
</body>
</html>
