<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = $_SESSION['user'];
$userRole = $user['role'] ?? 'consumer';
$pageTitle = 'My Profile';

// Calculate age from birthdate
$age = '';
if (!empty($user['birthdate'])) {
    $birth = new DateTime($user['birthdate']);
    $today = new DateTime();
    $age = $birth->diff($today)->y;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FoodGrab</title>
    <link rel="stylesheet" href="../../css/design-system.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ── Row 1: Profile Card ── */
        .profile-hero {
            background: var(--bg-card);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .profile-hero-avatar {
            width: 90px; height: 90px; min-width: 90px;
            background: linear-gradient(135deg, var(--primary-color), #6366f1);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.25rem; color: #fff;
            box-shadow: 0 4px 12px rgba(99,102,241,0.25);
        }
        .profile-hero-info { flex: 1; }
        .profile-hero-name { font-size: 1.35rem; font-weight: 700; color: var(--text-heading); margin: 0 0 0.15rem; }
        .profile-hero-meta { color: var(--text-muted); font-size: 0.9rem; margin: 0 0 0.5rem; }
        .profile-hero-badges { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .badge-role { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-role.consumer { background: #dbeafe; color: #2563eb; }
        .badge-role.admin { background: #fef3c7; color: #d97706; }
        .badge-role.superadmin { background: #ede9fe; color: #7c3aed; }
        .badge-verified { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: #dcfce7; color: #16a34a; }

        @media (max-width: 600px) {
            .profile-hero { flex-direction: column; text-align: center; }
            .profile-hero-badges { justify-content: center; }
        }

        /* ── Row 2: Info Categories ── */
        .info-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .info-tab {
            padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid var(--border-color);
            background: var(--bg-card); color: var(--text-muted); cursor: pointer;
            font-size: 0.85rem; font-weight: 500; transition: all 0.2s;
            display: flex; align-items: center; gap: 0.4rem;
        }
        .info-tab:hover { border-color: var(--primary-color); color: var(--primary-color); }
        .info-tab.active { background: var(--primary-color); color: #fff; border-color: var(--primary-color); }

        .info-panel { display: none; }
        .info-panel.active { display: block; }

        .info-card {
            background: var(--bg-card); border-radius: 12px;
            box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);
            padding: 1.5rem;
        }
        .info-card-header {
            display: flex; align-items: center; gap: 0.6rem;
            margin-bottom: 1rem; padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }
        .info-card-header i { color: var(--primary-color); }
        .info-card-header h3 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--primary-color); }

        .info-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 600px) { .info-grid { grid-template-columns: 1fr; } }

        .info-item label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-item .info-value {
            font-size: 0.95rem; color: var(--text-heading); font-weight: 500;
            padding: 0.5rem 0.75rem; background: var(--bg-body);
            border-radius: 6px; border: 1px solid var(--border-color);
            min-height: 2.25rem; display: flex; align-items: center;
        }
        .info-value.empty { color: var(--text-muted); font-style: italic; }

        /* Security form */
        .form-group label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input {
            width: 100%; padding: 0.6rem 0.75rem;
            border: 1px solid var(--border-color); border-radius: 6px;
            background: var(--bg-body); color: var(--text-heading);
            font-size: 0.9rem; transition: all 0.2s;
        }
        .form-group input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
        <?php $currentPage = 'profile';
include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <h1 class="page-title">My Profile</h1>
            <p class="page-subtitle">View your account information and manage security settings.</p>

            <!-- ╔═══ ROW 1: Profile Hero Card ═══╗ -->
            <div class="profile-hero">
                <div class="profile-hero-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="profile-hero-info">
                    <h2 class="profile-hero-name"><?php echo htmlspecialchars($user['firstName'] . ' ' . ($user['middleInitial'] ?? '') . ' ' . $user['lastName'] . ' ' . ($user['extension'] ?? '')); ?></h2>
                    <p class="profile-hero-meta">@<?php echo htmlspecialchars($user['username']); ?> &middot; <?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="profile-hero-badges">
                        <span class="badge-role <?php echo $userRole; ?>">
                            <i class="fa-solid <?php echo $userRole === 'superadmin' ? 'fa-crown' : ($userRole === 'admin' ? 'fa-shield-halved' : 'fa-user'); ?>"></i>
                            <?php echo ucfirst($userRole); ?>
                        </span>
                        <span class="badge-verified"><i class="fa-solid fa-circle-check"></i> Active</span>
                    </div>
                </div>
            </div>

            <!-- ╔═══ ROW 2: Info Category Tabs ═══╗ -->
            <div class="info-tabs">
                <button class="info-tab active" data-tab="personal"><i class="fa-regular fa-id-card"></i> Personal</button>
                <button class="info-tab" data-tab="address"><i class="fa-solid fa-map-location-dot"></i> Address</button>
                <button class="info-tab" data-tab="account"><i class="fa-solid fa-at"></i> Account</button>
                <button class="info-tab" data-tab="security"><i class="fa-solid fa-lock"></i> Security</button>
            </div>

            <!-- Personal Info -->
            <div class="info-panel active" id="panel-personal">
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fa-regular fa-id-card"></i>
                        <h3>Personal Information</h3>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>First Name</label>
                            <div class="info-value"><?php echo htmlspecialchars($user['firstName']); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Last Name</label>
                            <div class="info-value"><?php echo htmlspecialchars($user['lastName']); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Middle Initial</label>
                            <div class="info-value <?php echo empty($user['middleInitial']) ? 'empty' : ''; ?>"><?php echo htmlspecialchars($user['middleInitial'] ?? '') ?: 'Not set'; ?></div>
                        </div>
                        <div class="info-item">
                            <label>Extension</label>
                            <div class="info-value <?php echo empty($user['extension']) ? 'empty' : ''; ?>"><?php echo htmlspecialchars($user['extension'] ?? '') ?: 'Not set'; ?></div>
                        </div>
                        <div class="info-item">
                            <label>Sex</label>
                            <div class="info-value"><?php echo ucfirst(htmlspecialchars($user['sex'] ?? 'Not set')); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Birthdate</label>
                            <div class="info-value"><?php echo !empty($user['birthdate']) ? date('F j, Y', strtotime($user['birthdate'])) : 'Not set'; ?></div>
                        </div>
                        <div class="info-item">
                            <label>Age</label>
                            <div class="info-value"><?php echo $age ?: 'N/A'; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Info -->
            <div class="info-panel" id="panel-address">
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <h3>Address Details</h3>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Purok / Street</label>
                            <div class="info-value"><?php echo htmlspecialchars($user['purok'] ?? ''); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Barangay</label>
                            <div class="info-value"><?php echo htmlspecialchars($user['barangay'] ?? ''); ?></div>
                        </div>
                        <div class="info-item">
                            <label>City / Municipality</label>
                            <div class="info-value"><?php echo htmlspecialchars($user['city'] ?? ''); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Province</label>
                            <div class="info-value"><?php echo htmlspecialchars($user['province'] ?? ''); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Zip Code</label>
                            <div class="info-value"><?php echo htmlspecialchars($user['zipCode'] ?? ''); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Country</label>
                            <div class="info-value"><?php echo htmlspecialchars($user['country'] ?? ''); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Info -->
            <div class="info-panel" id="panel-account">
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fa-solid fa-at"></i>
                        <h3>Account Details</h3>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>User ID</label>
                            <div class="info-value"><?php echo htmlspecialchars($user['id']); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Username</label>
                            <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Role</label>
                            <div class="info-value"><?php echo ucfirst($userRole); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security (Change Password) -->
            <div class="info-panel" id="panel-security">
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fa-solid fa-lock"></i>
                        <h3>Change Password</h3>
                    </div>
                    <form id="profileForm">
                        <div class="info-grid" style="margin-bottom: 0.5rem;">
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>Current Password</label>
                                <div class="password-container">
                                    <input type="password" name="current_password" id="current_password" required placeholder="Enter current password">
                                    <button type="button" class="pw-toggle" data-target="current_password" aria-label="Toggle visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>New Password <span style="font-weight:400; font-size:0.75rem; color:var(--text-muted);">(8-25 characters)</span></label>
                                <div class="password-container">
                                    <input type="password" name="new_password" id="new_password" placeholder="Min 8 characters">
                                    <button type="button" class="pw-toggle" data-target="new_password" aria-label="Toggle visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <div id="pwStrength" style="display:none; margin-top:0.35rem; font-size:0.8rem; font-weight:600;"></div>
                                <div id="pwRequirements" style="margin-top:0.35rem; font-size:0.75rem; color:var(--text-muted); line-height:1.6;"></div>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <div class="password-container">
                                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter new password">
                                    <button type="button" class="pw-toggle" data-target="confirm_password" aria-label="Toggle visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <div id="pwMatch" style="display:none; margin-top:0.35rem; font-size:0.8rem; font-weight:600;"></div>
                            </div>
                        </div>
                        <p id="pwMsg" style="color: var(--error-color); font-size: 0.85rem; min-height: 1.25rem; margin: 0.5rem 0;"></p>
                        <div style="text-align: right; padding-top: 0.75rem; border-top: 1px solid var(--border-color);">
                            <button type="submit" class="submitBtn" style="padding: 0.5rem 1.5rem; font-size: 0.9rem;">
                                <i class="fa-solid fa-floppy-disk" style="margin-right: 0.4rem;"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>

    <!-- Message Modal -->
    <div id="messageModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1100; align-items:center; justify-content:center;">
        <div style="background:white; padding:1.5rem; border-radius:12px; width:90%; max-width:350px; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.1);">
            <div id="msgIconContainer" style="width:50px; height:50px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
                <i id="msgIcon" class="fa-solid" style="font-size:1.5rem;"></i>
            </div>
            <h3 id="msgTitle" style="margin-bottom:0.25rem; font-size: 1.1rem;"></h3>
            <p id="msgBody" style="color:var(--text-muted); margin-bottom:1.5rem; font-size: 0.9rem;"></p>
            <button onclick="document.getElementById('messageModal').style.display='none'" class="submitBtn" style="width:100%; padding: 0.5rem;">Okay</button>
        </div>
    </div>

    <script>
        // Sidebar toggle
        (function(){ var o=document.getElementById('sidebarOverlay'),t=document.getElementById('sidebarToggle'); if(t&&o){ t.addEventListener('click',function(){ document.body.classList.toggle('sidebar-open'); o.classList.toggle('is-open',document.body.classList.contains('sidebar-open')); }); o.addEventListener('click',function(){ document.body.classList.remove('sidebar-open'); o.classList.remove('is-open'); }); } })();

        // Tab switching
        document.querySelectorAll('.info-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.info-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.info-panel').forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
            });
        });

        // Password change
        const api = '../../php/database';
        const newPwInput = document.getElementById('new_password');
        const confirmPwInput = document.getElementById('confirm_password');
        const pwStrengthEl = document.getElementById('pwStrength');
        const pwMatchEl = document.getElementById('pwMatch');
        const pwReqEl = document.getElementById('pwRequirements');

        // --- Password Toggle (Eye Icon) ---
        document.querySelectorAll('.pw-toggle').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const input = document.getElementById(toggle.dataset.target);
                const icon = toggle.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });

        // --- Password Strength Checker (same as signup.js) ---
        function checkPasswordStrength(password) {
            let score = 0;
            if (password.length >= 8) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^a-zA-Z0-9]/.test(password)) score++;
            return score;
        }

        function updateRequirements(password) {
            const checks = [
                { test: password.length >= 8, label: '8+ characters' },
                { test: /[A-Z]/.test(password), label: 'Uppercase letter' },
                { test: /[a-z]/.test(password), label: 'Lowercase letter' },
                { test: /[0-9]/.test(password), label: 'Number' },
                { test: /[^a-zA-Z0-9]/.test(password), label: 'Special character' },
            ];
            pwReqEl.innerHTML = checks.map(c =>
                `<span style="display:inline-flex; align-items:center; gap:0.25rem; margin-right:0.75rem; color:${c.test ? '#16a34a' : '#9ca3af'};">
                    <i class="fa-solid ${c.test ? 'fa-circle-check' : 'fa-circle-xmark'}" style="font-size:0.7rem;"></i> ${c.label}
                </span>`
            ).join('');
        }

        newPwInput.addEventListener('input', () => {
            const pw = newPwInput.value;
            if (pw.length === 0) {
                pwStrengthEl.style.display = 'none';
                pwReqEl.innerHTML = '';
            } else {
                const score = checkPasswordStrength(pw);
                pwStrengthEl.style.display = 'block';
                if (score <= 2) {
                    pwStrengthEl.textContent = 'Weak Password';
                    pwStrengthEl.style.color = '#f50606';
                } else if (score === 3) {
                    pwStrengthEl.textContent = 'Medium Password';
                    pwStrengthEl.style.color = '#E99002';
                } else {
                    pwStrengthEl.textContent = 'Strong Password';
                    pwStrengthEl.style.color = '#2BB673';
                }
                updateRequirements(pw);
            }
            checkPasswordMatch();
        });

        // --- Password Match Checker ---
        function checkPasswordMatch() {
            const newPw = newPwInput.value;
            const confirmPw = confirmPwInput.value;
            if (confirmPw.length > 0 || newPw.length > 0) {
                pwMatchEl.style.display = 'block';
                if (newPw === confirmPw && newPw.length > 0) {
                    pwMatchEl.textContent = 'Passwords match';
                    pwMatchEl.style.color = '#2BB673';
                } else {
                    pwMatchEl.textContent = 'Passwords do not match';
                    pwMatchEl.style.color = '#f50606';
                }
            } else {
                pwMatchEl.style.display = 'none';
            }
        }
        confirmPwInput.addEventListener('input', checkPasswordMatch);

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
                icon.className = 'fa-solid fa-check'; icon.style.color = '#16a34a'; titleEl.style.color = '#16a34a';
            } else {
                iconContainer.style.background = '#fee2e2';
                icon.className = 'fa-solid fa-xmark'; icon.style.color = '#dc2626'; titleEl.style.color = '#dc2626';
            }
            modal.style.display = 'flex';
        }

        document.getElementById('profileForm').onsubmit = function(e) {
            e.preventDefault();
            const np = newPwInput.value;
            const cp = confirmPwInput.value;
            const msg = document.getElementById('pwMsg');
            msg.textContent = '';

            // Validation (same rules as signup)
            if (!np) { msg.textContent = 'Please enter a new password.'; return; }
            if (np.length < 8 || np.length > 25) { msg.textContent = 'Password must be between 8 and 25 characters.'; return; }
            if (np.includes('  ')) { msg.textContent = 'Password must not contain double spaces.'; return; }
            if (np !== cp) { msg.textContent = 'Passwords do not match.'; return; }

            const fd = new FormData(this);
            fetch(api + '/update_password.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showMessageModal('success', 'Password Updated', 'Your password has been changed successfully.');
                        this.reset();
                        msg.textContent = '';
                        pwStrengthEl.style.display = 'none';
                        pwMatchEl.style.display = 'none';
                        pwReqEl.innerHTML = '';
                    } else {
                        showMessageModal('error', 'Update Failed', d.error || 'Could not update password.');
                    }
                })
                .catch(() => showMessageModal('error', 'Error', 'Network error occurred.'));
        };
    </script>
</body>
</html>
