<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$pageTitle = 'Consumer Management';
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
        <?php $currentPage = 'admin_consumers';
include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h1 class="page-title">Consumer Management</h1>
                <button class="btn-primary" onclick="openUserModal()">Add Consumer</button>
            </div>

            <div id="usersTableContainer">Loading...</div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>

    <!-- Consumer Modal (Add/Edit) -->
    <div id="consumerModal" class="modal2">
        <div class="modal2-content" style="max-width: 800px; width: 95%;">
             <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <h2 id="modalTitle">Add Consumer</h2>
                <button onclick="closeConsumerModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>

            <form id="consumerForm" style="width: 100%; text-align: left; max-height: 80vh; overflow-y: auto; padding-right: 10px;">
                <input type="hidden" name="id" id="consumerId">
                <input type="hidden" name="custom_id" id="customId">

                <h3 class="section-heading">Personal Information</h3>
                <div class="form-grid">
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
                        <label>Password <span id="pwHint" class="hint">(Default)</span></label>
                        <input type="password" name="password" id="password" autocomplete="new-password">
                    </div>
                </div>

                <h3 class="section-heading">Security Questions</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Question 1<span class="required">*</span></label>
                        <select name="secure_question" id="sq1" required>
                            <option value="">-- Select --</option>
                            <option value="Who is your bestfriend in elementary?">Who is your bestfriend in elementary?</option>
                            <option value="What is the name of your pet?">What is the name of your pet?</option>
                            <option value="Who is your favorite teacher in highschool?">Who is your favorite teacher in highschool?</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Answer 1<span class="required">*</span></label>
                        <input type="password" name="secure_answer" id="sa1" required>
                    </div>
                    <div class="form-group">
                        <label>Question 2<span class="required">*</span></label>
                        <select name="secure_question2" id="sq2" required>
                            <option value="">-- Select --</option>
                            <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                            <option value="What elementary school did you attend?">What elementary school did you attend?</option>
                            <option value="What is your favorite food?">What is your favorite food?</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Answer 2<span class="required">*</span></label>
                        <input type="password" name="secure_answer2" id="sa2" required>
                    </div>
                    <div class="form-group">
                        <label>Question 3<span class="required">*</span></label>
                        <select name="secure_question3" id="sq3" required>
                            <option value="">-- Select --</option>
                            <option value="What is your father's middle name?">What is your father's middle name?</option>
                            <option value="What street did you grow up on?">What street did you grow up on?</option>
                            <option value="What is your favorite movie?">What is your favorite movie?</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Answer 3<span class="required">*</span></label>
                        <input type="password" name="secure_answer3" id="sa3" required>
                    </div>
                </div>

                <div style="text-align: right; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                    <button type="button" onclick="closeConsumerModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save Consumer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Block Request Modal -->
    <div id="blockModal" class="modal2">
        <div class="modal2-content" style="max-width: 450px;">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <div style="width: 60px; height: 60px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.75rem; color: #dc2626;"></i>
                </div>
                <h2 style="font-size: 1.5rem; color: #1f2937; margin-bottom: 0.5rem;">Request to Block Consumer</h2>
                <p style="color: #6b7280; font-size: 0.95rem;">This action will restrict the consumer's access. A super admin must approve this request.</p>
            </div>

            <form id="blockForm" style="width: 100%; text-align: left;">
                <input type="hidden" name="target_id" id="targetId">
                <div class="form-group">
                    <label style="font-weight: 600; color: #374151; margin-bottom: 0.5rem; display: block;">Reason for blocking <span class="required">*</span></label>
                    <textarea name="reason" id="blockReason" rows="4" required placeholder="Please provide a valid reason..." style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-family: inherit; resize: vertical;"></textarea>
                </div>
                <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" onclick="document.getElementById('blockModal').style.display='none'" class="btn-secondary" style="flex: 1; justify-content: center;">Cancel</button>
                    <button type="submit" class="btn-primary" style="background: #dc2626; flex: 1; justify-content: center; box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success/Error Modal -->
    <div id="messageModal" class="modal2" style="z-index: 210;">
        <div class="modal2-content" style="max-width: 400px; text-align: center; padding: 2rem;">
            <div id="msgIconContainer" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                <i id="msgIcon" class="fa-solid" style="font-size: 1.75rem;"></i>
            </div>
            <h2 id="msgTitle" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></h2>
            <p id="msgBody" style="color: #6b7280; margin-bottom: 1.5rem;"></p>
            <button onclick="closeMessageModal()" class="btn-primary" style="width: 100%; justify-content: center;">Okay</button>
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

        function loadUsers() {
            fetch(api + '/admin_consumers_list.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    if (data.consumers.length === 0) {
                        document.getElementById('usersTableContainer').innerHTML = '<div class="empty-state"><p>No consumers found.</p></div>';
                        return;
                    }

                    let html = '<table class="data-table"><thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Status</th><th>Actions</th><th>Privileges</th></tr></thead><tbody>';
                    data.consumers.forEach(u => {
                        const isBlocked = u.is_blocked == 1;
                        html += `<tr class="${isBlocked ? 'blocked-row' : ''}">
                            <td>${escapeHtml(u.firstName + ' ' + u.lastName)}</td>
                            <td>${escapeHtml(u.username)}</td>
                            <td>${escapeHtml(u.email)}</td>
                            <td>${isBlocked ? '<span class="status-badge no-dot status-trash">Blocked</span>' : '<span class="status-badge no-dot status-ok">Active</span>'}</td>
                            <td>
                                <button class="btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;" onclick='editUser(${JSON.stringify(u)})'>
                                    <i class="fa-solid fa-pen-to-square" style="margin-right:0.25rem;"></i> Edit
                                </button>
                                ${!isBlocked ? `<button class="btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; color: var(--error-color); border-color: var(--error-color);" onclick="openBlockModal('${u.id}')"><i class="fa-solid fa-ban" style="margin-right:0.25rem;"></i>Block</button>` : ''}
                            </td>
                            <td>
                                <button class="btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;" onclick="viewPrivileges('consumer', '${escapeHtml(u.username)}')">
                                    <i class="fa-solid fa-eye" style="margin-right:0.25rem;"></i> View
                                </button>
                            </td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    document.getElementById('usersTableContainer').innerHTML = html;
                });
        }

        const privileges = {
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

        function openUserModal() {
            document.getElementById('modalTitle').textContent = 'Add Consumer';
            document.getElementById('consumerForm').reset();
            document.getElementById('consumerId').value = '';
            document.getElementById('pwHint').textContent = '(Required)';
            document.getElementById('consumerModal').style.display = 'flex';
        }

        function closeConsumerModal() {
            document.getElementById('consumerModal').style.display = 'none';
        }

        function editUser(u) {
            document.getElementById('modalTitle').textContent = 'Edit Consumer';
            document.getElementById('consumerId').value = u.id;
            document.getElementById('customId').value = u.id;

            // Populate fields
            const fields = ['firstName', 'lastName', 'middleInitial', 'extension', 'sex', 'birthdate', 'purok', 'barangay', 'city', 'province', 'zipCode', 'country', 'username', 'email'];
            fields.forEach(f => {
                if (document.getElementById(f)) document.getElementById(f).value = u[f] || '';
            });

            if (u.birthdate) calculateAge();

            document.getElementById('password').value = '';
            document.getElementById('pwHint').textContent = '(Leave blank to keep current)';
            document.getElementById('consumerModal').style.display = 'flex';
        }

        document.getElementById('consumerForm').onsubmit = function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fetch(api + '/admin_consumer_save.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        closeConsumerModal();
                        loadUsers();
                    } else {
                        alert(d.error);
                    }
                });
        };

        document.getElementById('blockForm').onsubmit = function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fetch(api + '/admin_request_block.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    document.getElementById('blockModal').style.display = 'none';
                    if (d.success) {
                        showMessageModal('success', 'Request Submitted', 'The block request has been sent for approval.');
                    } else {
                        showMessageModal('error', 'Request Failed', d.error || 'An error occurred.');
                    }
                })
                .catch(() => {
                    document.getElementById('blockModal').style.display = 'none';
                    showMessageModal('error', 'Error', 'A network error occurred.');
                });
        };

        function showMessageModal(type, title, message) {
            const modal = document.getElementById('messageModal');
            const iconContainer = document.getElementById('msgIconContainer');
            const icon = document.getElementById('msgIcon');
            const titleEl = document.getElementById('msgTitle');
            const bodyEl = document.getElementById('msgBody');

            titleEl.textContent = title;
            bodyEl.textContent = message;

            if (type === 'success') {
                iconContainer.style.background = '#dcfce7';
                icon.className = 'fa-solid fa-check';
                icon.style.color = '#16a34a';
                titleEl.style.color = '#16a34a';
            } else {
                iconContainer.style.background = '#fee2e2';
                icon.className = 'fa-solid fa-xmark';
                icon.style.color = '#dc2626';
                titleEl.style.color = '#dc2626';
            }

            modal.style.display = 'flex';
        }

        function closeMessageModal() {
            document.getElementById('messageModal').style.display = 'none';
        }

        function openBlockModal(id) {
            document.getElementById('targetId').value = id;
            document.getElementById('blockReason').value = '';
            document.getElementById('blockModal').style.display = 'flex';
        }

        function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

        loadUsers();
    </script>
</body>
</html>
