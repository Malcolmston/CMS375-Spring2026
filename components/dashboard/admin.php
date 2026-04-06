<?php
require_once __DIR__ . '/../../account/role.php';
require_once __DIR__ . '/../../account/Admin.php';
require_once __DIR__ . '/../../account/Account.php';

use account\Admin;
use account\role;

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /index');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Load admin data ──────────────────────────────────────────────────────────
$admin = Admin::getUserById($user_id);

// ── Flash messages ──────────────────────────────────────────────────────────
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ── POST handling (PRG) ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid request. Please try again.'];
        header('Location: /dashboard/admin');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // ── Profile update ──────────────────────────────────────────────────────
    if ($action === 'update_profile') {

        $ok = $admin->updateUserProfile(
            $user_id,
            trim($_POST['firstname']  ?? ''),
            trim($_POST['lastname']   ?? ''),
            trim($_POST['middlename'] ?? ''),
            null, // prefix
            null, // suffix
            trim($_POST['gender']     ?? ''),
            trim($_POST['phone']      ?? ''),
            (float) ($_POST['loc_x']  ?? 0),
            (float) ($_POST['loc_y']  ?? 0),
            trim($_POST['email']      ?? ''),
            (int)   ($_POST['age']    ?? 0),
            null, // blood
            trim($_POST['extra']      ?? '') ?: null
        );
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'msg' => 'Profile updated successfully.']
            : ['type' => 'error',   'msg' => 'Update failed. Please check your details and try again.'];
        header('Location: /dashboard/admin#account');
        exit;
    }

    // ── Password change ─────────────────────────────────────────────────────
    if ($action === 'change_password') {
        $old     = $_POST['old_password']     ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new !== $confirm) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'New passwords do not match.'];
        } elseif (strlen($new) < 8) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Password must be at least 8 characters.'];
        } else {
            $ok = $admin->changePassword($old, $new);
            $_SESSION['flash'] = $ok
                ? ['type' => 'success', 'msg' => 'Password changed successfully.']
                : ['type' => 'error',   'msg' => 'Current password is incorrect.'];
        }
        header('Location: /dashboard/admin#access-control');
        exit;
    }
}

// ── Helpers ────────────────────────────────────────────────────────────────
$initials = strtoupper(
    substr($admin->getFirstName(), 0, 1) .
    substr($admin->getLastName(),  0, 1)
);
$fullName = trim(
    $admin->getFirstName() . ' ' .
    $admin->getMiddleName() . ' ' .
    $admin->getLastName()
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://kit.fontawesome.com/1ad3aa32da.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; }

        #dashboard-sidebar { width:64px; transition:width 0.3s ease; overflow:hidden; white-space:nowrap; }
        #dashboard-sidebar.expanded { width:224px; }

        #dashboard-main { margin-left:64px; transition:margin-left 0.3s ease; }
        @media (max-width: 768px) {
            #dashboard-sidebar { position:fixed; z-index:50; height:100%; }
            #dashboard-main { margin-left:0; }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">

<!-- ── Sidebar ─────────────────────────────────────────────────────────────── -->
<nav id="dashboard-sidebar" class="fixed left-0 top-0 h-full bg-slate-800 flex flex-col justify-between z-40">
    <div>
        <div class="h-16 flex items-center justify-center border-b border-slate-700">
            <a href="/dashboard" class="text-white font-semibold text-lg tracking-tight" style="font-family: 'DM Serif Display', serif;">M</a>
        </div>

        <!-- Tab Icons -->
        <div class="py-4 space-y-1" id="sidebar-tabs">
            <?php
            $tabs = [
                ['icon' => 'fa-user', 'id' => 'account', 'label' => 'Account'],
                ['icon' => 'fa-cogs', 'id' => 'admin-tools', 'label' => 'Admin Tools'],
                ['icon' => 'fa-chart-bar', 'id' => 'reports', 'label' => 'Reports'],
                ['icon' => 'fa-lock', 'id' => 'access-control', 'label' => 'Access Control'],
                ['icon' => 'fa-folder', 'id' => 'data', 'label' => 'Data Retrieval'],
            ];
            foreach ($tabs as $t): ?>
            <a href="#<?= $t['id'] ?>"
               class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700 hover:text-white transition-colors"
               title="<?= $t['label'] ?>">
                <i class="fas <?= $t['icon'] ?> text-lg"></i>
                <span class="ml-3 tab-label whitespace-nowrap"><?= $t['label'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="p-4 border-t border-slate-700">
        <a href="/logout" class="flex items-center justify-center text-slate-400 hover:text-red-400 transition-colors py-2">
            <i class="fas fa-sign-out-alt"></i>
            <span class="ml-2 tab-label whitespace-nowrap">Logout</span>
        </a>
    </div>
</nav>

<!-- ── Main Content ───────────────────────────────────────────────────────── -->
<main id="dashboard-main" class="p-8">
    <!-- Header -->
    <header class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800" style="font-family: 'DM Serif Display', serif;">Admin Dashboard</h1>
            <p class="text-slate-500 text-sm mt-1">Welcome back, <?= htmlspecialchars($fullName) ?></p>
        </div>
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-medium">
                <?= $initials ?>
            </div>
        </div>
    </header>

    <!-- Flash Message -->
    <?php if ($flash): ?>
    <div class="mb-6 p-4 rounded-xl animate__animated animate__fadeIn <?= $flash['type'] === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
        <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- Panels Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Account Panel -->
        <section id="account" class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Account Information</h2>

            <form method="POST" action="/dashboard/admin" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="update_profile">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">First Name</label>
                        <input type="text" name="firstname" value="<?= htmlspecialchars($admin->getFirstName() ?? '') ?>"
                               class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Middle Name</label>
                        <input type="text" name="middlename" value="<?= htmlspecialchars($admin->getMiddleName() ?? '') ?>"
                               class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Last Name</label>
                        <input type="text" name="lastname" value="<?= htmlspecialchars($admin->getLastName() ?? '') ?>"
                               class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($admin->getEmail() ?? '') ?>"
                               class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Phone</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($admin->getPhone() ?? '') ?>"
                               class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Gender</label>
                        <select name="gender" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                            <option value="">Select...</option>
                            <option value="Male" <?= ($admin->getGender() ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($admin->getGender() ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($admin->getGender() ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Age</label>
                        <input type="number" name="age" value="<?= (int) ($admin->getAge() ?? 0) ?>"
                               class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Department</label>
                        <input type="text" value="Not assigned" disabled
                               class="w-full px-4 py-2 rounded-lg bg-slate-50 border border-slate-200 text-slate-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Additional Info</label>
                    <textarea name="extra" rows="2"
                              class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition"><?= htmlspecialchars($admin->getExtra() ?? '') ?></textarea>
                </div>

                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                    Update Profile
                </button>
            </form>
        </section>

        <!-- Quick Info Panel -->
        <aside class="bg-gradient-to-br from-indigo-600 to-indigo-800 rounded-2xl p-6 text-white">
            <h3 class="text-lg font-semibold mb-4">Quick Info</h3>
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <i class="fas fa-id-badge w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center"></i>
                    <div>
                        <p class="text-xs text-indigo-200">Admin ID</p>
                        <p class="font-medium"><?= htmlspecialchars($admin->getAdminId() ?? '—') ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <i class="fas fa-envelope w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center"></i>
                    <div>
                        <p class="text-xs text-indigo-200">Email</p>
                        <p class="font-medium truncate"><?= htmlspecialchars($admin->getEmail() ?? '—') ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <i class="fas fa-calendar w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center"></i>
                    <div>
                        <p class="text-xs text-indigo-200">Joined</p>
                        <p class="font-medium"><?= htmlspecialchars($admin->getCreatedAt() ? date('M j, Y', strtotime($admin->getCreatedAt())) : '—') ?></p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Admin Tools Panel -->
        <section id="admin-tools" class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Admin Tools</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <a href="/admin/users" class="p-4 rounded-xl border border-slate-200 hover:border-indigo-500 hover:shadow-md transition-all group">
                    <i class="fas fa-users text-2xl block mb-2"></i>
                    <span class="font-medium text-slate-700 group-hover:text-indigo-600">User Management</span>
                </a>
                <a href="/admin/roles" class="p-4 rounded-xl border border-slate-200 hover:border-indigo-500 hover:shadow-md transition-all group">
                    <i class="fas fa-shield-alt text-2xl block mb-2"></i>
                    <span class="font-medium text-slate-700 group-hover:text-indigo-600">Role Management</span>
                </a>
                <a href="/admin/settings" class="p-4 rounded-xl border border-slate-200 hover:border-indigo-500 hover:shadow-md transition-all group">
                    <i class="fas fa-cogs text-2xl block mb-2"></i>
                    <span class="font-medium text-slate-700 group-hover:text-indigo-600">System Settings</span>
                </a>
                <a href="/admin/audit" class="p-4 rounded-xl border border-slate-200 hover:border-indigo-500 hover:shadow-md transition-all group">
                    <i class="fas fa-clipboard-list text-2xl block mb-2"></i>
                    <span class="font-medium text-slate-700 group-hover:text-indigo-600">Audit Logs</span>
                </a>
                <a href="/admin/backup" class="p-4 rounded-xl border border-slate-200 hover:border-indigo-500 hover:shadow-md transition-all group">
                    <i class="fas fa-save text-2xl block mb-2"></i>
                    <span class="font-medium text-slate-700 group-hover:text-indigo-600">Backup & Restore</span>
                </a>
            </div>
        </section>

        <!-- Access Control Panel -->
        <section id="access-control" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Security</h2>

            <form method="POST" action="/dashboard/admin" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="change_password">

                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Current Password</label>
                    <input type="password" name="old_password"
                           class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">New Password</label>
                    <input type="password" name="new_password"
                           class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Confirm New Password</label>
                    <input type="password" name="confirm_password"
                           class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>

                <button type="submit" class="w-full px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-medium rounded-lg transition-colors">
                    Change Password
                </button>
            </form>
        </section>

        <!-- Reports Panel -->
        <section id="reports" class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Reports & Analytics</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-5 rounded-xl bg-slate-50 border border-slate-200">
                    <h4 class="font-medium text-slate-700 mb-2">System Overview</h4>
                    <p class="text-slate-500 text-sm">View overall system statistics and health metrics.</p>
                </div>
                <div class="p-5 rounded-xl bg-slate-50 border border-slate-200">
                    <h4 class="font-medium text-slate-700 mb-2">User Activity</h4>
                    <p class="text-slate-500 text-sm">Track user logins, actions, and behavioral patterns.</p>
                </div>
            </div>
        </section>

        <!-- Data Retrieval Panel -->
        <section id="data" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Data Retrieval</h2>
            <p class="text-slate-500 mb-4">Export system data in various formats.</p>
            <div class="space-y-2">
                <button disabled class="w-full px-4 py-2 bg-slate-100 text-slate-400 rounded-lg cursor-not-allowed">
                    Export as PDF
                </button>
                <button disabled class="w-full px-4 py-2 bg-slate-100 text-slate-400 rounded-lg cursor-not-allowed">
                    Export as CSV
                </button>
                <p class="text-xs text-slate-400 mt-2">Coming soon</p>
            </div>
        </section>

    </div>
</main>

<!-- ── Sidebar Toggle ──────────────────────────────────────────────────────── -->
<script>
$(document).ready(function() {
    $('#sidebar-tabs a').on('click', function(e) {
        var target = $(this).attr('href');
        if (target.startsWith('#')) {
            e.preventDefault();
            var offset = $(target).offset().top - 20;
            $('html, body').animate({ scrollTop: offset }, 300);
        }
    });

    // Optional: expand sidebar on hover
    $('#dashboard-sidebar').hover(
        function() { $(this).addClass('expanded'); },
        function() { $(this).removeClass('expanded'); }
    );
});
</script>

</body>
</html>