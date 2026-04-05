<?php
/**
 * Base staff dashboard template - extend this for each staff type
 */

use account\role;

$staff = null;
$staffRole = role::tryFrom($_SESSION['role'] ?? '');

if ($staffRole) {
    $staff = match ($staffRole) {
        role::PHYSICIAN    => \account\Physician::getUserById($user_id),
        role::NURSE        => \account\Nurse::getUserById($user_id),
        role::PHARMACIST   => \account\Pharmacist::getUserById($user_id),
        role::RECEPTIONIST => \account\Receptionist::getUserById($user_id),
        role::LAB_TECH     => \account\LabTech::getUserById($user_id),
        role::RADIOLOGIST  => \account\Radiologist::getUserById($user_id),
        role::SURGEON      => \account\Surgeon::getUserById($user_id),
        role::THERAPIST    => \account\Therapist::getUserById($user_id),
        role::BILLING      => \account\Billing::getUserById($user_id),
        role::EMS          => \account\Ems::getUserById($user_id),
        default            => null,
    };
}

if (!$staff) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Unable to load staff profile.'];
    header('Location: /index');
    exit;
}

$initials = strtoupper(
    substr($staff->getFirstName(), 0, 1) .
    substr($staff->getLastName(),  0, 1)
);
$fullName = trim(
    $staff->getFirstName() . ' ' .
    $staff->getMiddleName() . ' ' .
    $staff->getLastName()
);

$roleLabel = match ($staffRole) {
    role::PHYSICIAN    => 'Physician',
    role::NURSE        => 'Nurse',
    role::PHARMACIST   => 'Pharmacist',
    role::RECEPTIONIST => 'Receptionist',
    role::LAB_TECH     => 'Lab Technician',
    role::RADIOLOGIST  => 'Radiologist',
    role::SURGEON      => 'Surgeon',
    role::THERAPIST    => 'Therapist',
    role::BILLING      => 'Billing',
    role::EMS          => 'EMS',
    default            => 'Staff',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $roleLabel ?> Dashboard | MedHealth</title>
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
        #dashboard-sidebar.expanded ~ #dashboard-main { margin-left:224px; }

        .sidebar-label { opacity:0; transform:translateX(-6px); transition:opacity 0.2s ease 0.05s, transform 0.2s ease 0.05s; pointer-events:none; }
        #dashboard-sidebar.expanded .sidebar-label { opacity:1; transform:translateX(0); }

        .sidebar-section-label { opacity:0; max-height:0; overflow:hidden; transition:opacity 0.2s ease, max-height 0.3s ease; }
        #dashboard-sidebar.expanded .sidebar-section-label { opacity:1; max-height:24px; }

        .sidebar-nav-item { position:relative; }
        .sidebar-nav-item.active { background:rgba(15,23,42,0.07); color:#0f172a; }
        .sidebar-nav-item.active .sidebar-icon { color:#0f172a; }
        .sidebar-nav-item:hover { background:rgba(15,23,42,0.05); color:#0f172a; }
        .sidebar-nav-item:hover .sidebar-icon { color:#334155; }
        .sidebar-nav-item.active::before { content:''; position:absolute; left:0; top:20%; height:60%; width:3px; background:linear-gradient(180deg,#1e293b,#475569); border-radius:0 3px 3px 0; }

        .avatar-ring { box-shadow:0 0 0 2px #e2e8f0, 0 0 0 4px rgba(30,41,59,0.08); }
        #sidebar-toggle i { transition:transform 0.3s ease; }
        #dashboard-sidebar.expanded #sidebar-toggle i { transform:rotate(180deg); }

        #dashboard-sidebar:not(.expanded) .sidebar-nav-item:hover::after {
            content:attr(data-tooltip); position:absolute; left:calc(100% + 10px); top:50%;
            transform:translateY(-50%); background:#1e293b; color:#f8fafc;
            font-size:11px; font-weight:500; padding:4px 10px; border-radius:6px;
            white-space:nowrap; pointer-events:none; z-index:100;
            box-shadow:0 4px 12px rgba(0,0,0,0.15);
            animation:tooltipFade 0.15s ease forwards;
        }
        @keyframes tooltipFade {
            from { opacity:0; transform:translateY(-50%) translateX(-4px); }
            to   { opacity:1; transform:translateY(-50%) translateX(0); }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">

<!-- Sidebar -->
<aside id="dashboard-sidebar" class="fixed left-0 top-0 h-full z-40 bg-white border-r border-slate-200/80 shadow-sm flex flex-col select-none">

    <div class="flex items-center justify-between px-3 pt-5 pb-4 border-b border-slate-100">
        <div class="flex items-center gap-3 min-w-0">
            <div class="avatar-ring w-9 h-9 rounded-full bg-gradient-to-br from-indigo-600 to-indigo-400 flex-shrink-0 flex items-center justify-center text-white text-xs font-semibold tracking-wide">
                <?= htmlspecialchars($initials) ?>
            </div>
            <div class="sidebar-label min-w-0">
                <p class="text-xs font-semibold text-slate-800 truncate leading-tight"><?= htmlspecialchars($fullName) ?></p>
                <p class="text-[10px] text-slate-400 font-medium tracking-wide uppercase"><?= $roleLabel ?></p>
            </div>
        </div>
        <button id="sidebar-toggle" class="flex-shrink-0 w-6 h-6 rounded-md flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all duration-200">
            <i class="fas fa-chevron-right text-xs"></i>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-3 px-2 space-y-0.5">
        <div class="sidebar-section-label px-2 pb-1">
            <span class="text-[9px] font-semibold text-slate-400 uppercase tracking-widest">Dashboard</span>
        </div>

        <button class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-500 transition-all duration-200 active" data-panel="account" data-tooltip="Account">
            <i class="sidebar-icon fas fa-user w-5 h-5 flex-shrink-0 text-slate-600 transition-colors duration-200"></i>
            <span class="sidebar-label text-sm font-medium text-slate-700">Account</span>
        </button>

        <button class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-500 transition-all duration-200" data-panel="workplace" data-tooltip="Workplace">
            <i class="sidebar-icon fas fa-briefcase w-5 h-5 flex-shrink-0 text-slate-400 transition-colors duration-200"></i>
            <span class="sidebar-label text-sm font-medium text-slate-700">Workplace</span>
        </button>

        <button class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-500 transition-all duration-200" data-panel="tasks" data-tooltip="Tasks">
            <i class="sidebar-icon fas fa-tasks w-5 h-5 flex-shrink-0 text-slate-400 transition-colors duration-200"></i>
            <span class="sidebar-label text-sm font-medium text-slate-700">Tasks</span>
        </button>

        <div class="py-3 px-2">
            <div class="relative flex items-center">
                <div class="flex-grow border-t border-slate-200"></div>
                <span class="sidebar-section-label flex-shrink mx-2 text-[9px] font-semibold text-slate-400 uppercase tracking-widest">Access</span>
                <div class="flex-grow border-t border-slate-200"></div>
            </div>
        </div>

        <button class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-500 transition-all duration-200" data-panel="access-control" data-tooltip="Access &amp; Security">
            <i class="sidebar-icon fas fa-shield-alt w-5 h-5 flex-shrink-0 text-slate-400 transition-colors duration-200"></i>
            <span class="sidebar-label text-sm font-medium text-slate-700">Access &amp; Security</span>
        </button>

        <button class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-500 transition-all duration-200" data-panel="data" data-tooltip="Data Retrieval">
            <i class="sidebar-icon fas fa-database w-5 h-5 flex-shrink-0 text-slate-400 transition-colors duration-200"></i>
            <span class="sidebar-label text-sm font-medium text-slate-700">Data Retrieval</span>
        </button>
    </nav>

    <div class="border-t border-slate-100 p-2">
        <button id="sidebar-logout" class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-400 transition-all duration-200" data-tooltip="Log Out" onclick="window.location.href='/logout'">
            <i class="fas fa-sign-out-alt w-5 h-5 flex-shrink-0 transition-colors duration-200"></i>
            <span class="sidebar-label text-sm font-medium">Log Out</span>
        </button>
    </div>
</aside>

<!-- Main Content -->
<main id="dashboard-main" class="p-4 md:p-8">
    <!-- Mobile Header -->
    <div class="md:hidden flex items-center justify-between mb-4 pb-4 border-b border-slate-200">
        <button id="sidebar-toggle-mobile" class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white text-sm font-medium">
                <?= $initials ?>
            </div>
            <div>
                <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($fullName) ?></p>
                <p class="text-xs text-slate-500"><?= $roleLabel ?></p>
            </div>
        </button>
        <a href="/logout" class="p-2 text-slate-400 hover:text-red-500">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>

    <!-- Desktop Header -->
    <header class="hidden md:flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800" style="font-family: 'DM Serif Display', serif;"><?= $roleLabel ?> Dashboard</h1>
            <p class="text-slate-500 text-sm mt-1">Welcome back, <?= htmlspecialchars($fullName) ?></p>
        </div>
        <div class="flex items-center gap-4">
            <span class="px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-sm font-medium"><?= $roleLabel ?></span>
            <button id="sidebar-toggle-desktop" class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-medium hover:bg-indigo-700 transition-colors">
                <?= $initials ?>
            </button>
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
        <section id="panel-account" class="panel lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Account Information</h2>
            <form method="POST" action="/dashboard/staff" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="update_profile">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">First Name</label>
                        <input type="text" name="firstname" value="<?= htmlspecialchars($staff->getFirstName() ?? '') ?>"
                               class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Middle Name</label>
                        <input type="text" name="middlename" value="<?= htmlspecialchars($staff->getMiddleName() ?? '') ?>"
                               class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Last Name</label>
                        <input type="text" name="lastname" value="<?= htmlspecialchars($staff->getLastName() ?? '') ?>"
                               class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($staff->getEmail() ?? '') ?>"
                               class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Phone</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($staff->getPhone() ?? '') ?>"
                               class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Gender</label>
                        <select name="gender" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                            <option value="">Select...</option>
                            <option value="Male" <?= ($staff->getGender() ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($staff->getGender() ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($staff->getGender() ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Age</label>
                        <input type="number" name="age" value="<?= (int) ($staff->getAge() ?? 0) ?>"
                               class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Employee ID</label>
                        <input type="text" value="<?= htmlspecialchars($staff->getEmployId() ?? '') ?>" disabled
                               class="w-full px-4 py-2 rounded-lg bg-slate-50 border border-slate-200 text-slate-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Additional Info</label>
                    <textarea name="extra" rows="2"
                              class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition"><?= htmlspecialchars($staff->getExtra() ?? '') ?></textarea>
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
                        <p class="text-xs text-indigo-200">Employee ID</p>
                        <p class="font-medium"><?= htmlspecialchars($staff->getEmployId() ?? '—') ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <i class="fas fa-envelope w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center"></i>
                    <div>
                        <p class="text-xs text-indigo-200">Email</p>
                        <p class="font-medium truncate"><?= htmlspecialchars($staff->getEmail() ?? '—') ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <i class="fas fa-calendar w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center"></i>
                    <div>
                        <p class="text-xs text-indigo-200">Joined</p>
                        <p class="font-medium"><?= $staff->getCreatedAt() ? (($staff->getCreatedAt() instanceof DateTime) ? $staff->getCreatedAt()->format('M j, Y') : date('M j, Y', strtotime($staff->getCreatedAt()))) : '—' ?></p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Workplace Panel -->
        <section id="panel-workplace" class="panel lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hidden">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Workplace Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-5 rounded-xl bg-slate-50 border border-slate-200">
                    <h4 class="font-medium text-slate-700 mb-2">Department</h4>
                    <p class="text-slate-500">Not assigned</p>
                </div>
                <div class="p-5 rounded-xl bg-slate-50 border border-slate-200">
                    <h4 class="font-medium text-slate-700 mb-2">Institution</h4>
                    <p class="text-slate-500">Not assigned</p>
                </div>
            </div>
        </section>

        <!-- Access Control Panel -->
        <section id="panel-access-control" class="panel bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hidden">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Security</h2>
            <form method="POST" action="/dashboard/staff" class="space-y-4">
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

        <!-- Tasks Panel -->
        <section id="panel-tasks" class="panel bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hidden">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">My Tasks</h2>
            <div class="space-y-3">
                <div class="p-4 rounded-lg border border-slate-200 hover:border-indigo-500 transition-colors cursor-pointer">
                    <p class="font-medium text-slate-700">Today's Schedule</p>
                    <p class="text-sm text-slate-500">View your appointments and rounds</p>
                </div>
                <div class="p-4 rounded-lg border border-slate-200 hover:border-indigo-500 transition-colors cursor-pointer">
                    <p class="font-medium text-slate-700">Pending Tasks</p>
                    <p class="text-sm text-slate-500">Review items awaiting your action</p>
                </div>
                <div class="p-4 rounded-lg border border-slate-200 hover:border-indigo-500 transition-colors cursor-pointer">
                    <p class="font-medium text-slate-700">Recent Activity</p>
                    <p class="text-sm text-slate-500">View your recent actions</p>
                </div>
            </div>
        </section>

        <!-- Data Retrieval Panel -->
        <section id="panel-data" class="panel lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hidden">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Data Retrieval</h2>
            <p class="text-slate-500 mb-4">Export your data in various formats.</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <button disabled class="p-4 rounded-xl border border-slate-200 bg-slate-100 text-slate-400 cursor-not-allowed">
                    <i class="fas fa-file-pdf text-2xl block mb-2"></i>
                    <span class="text-sm">PDF Export</span>
                </button>
                <button disabled class="p-4 rounded-xl border border-slate-200 bg-slate-100 text-slate-400 cursor-not-allowed">
                    <i class="fas fa-chart-line text-2xl block mb-2"></i>
                    <span class="text-sm">CSV Export</span>
                </button>
                <button disabled class="p-4 rounded-xl border border-slate-200 bg-slate-100 text-slate-400 cursor-not-allowed">
                    <i class="fas fa-clipboard-list text-2xl block mb-2"></i>
                    <span class="text-sm">Print View</span>
                </button>
                <button disabled class="p-4 rounded-xl border border-slate-200 bg-slate-100 text-slate-400 cursor-not-allowed">
                    <i class="fas fa-envelope text-2xl block mb-2"></i>
                    <span class="text-sm">Email Report</span>
                </button>
            </div>
            <p class="text-xs text-slate-400 mt-4">Coming soon</p>
        </section>

    </div>
</main>

<!-- Sidebar Toggle -->
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
});
</script>

<script>
$(function() {
    // Sidebar toggle
    $('#sidebar-toggle, #sidebar-toggle-mobile').on('click', function() {
        $('#dashboard-sidebar').toggleClass('expanded');
    });

    // Panel navigation
    $('.sidebar-nav-item[data-panel]').on('click', function() {
        var panel = $(this).data('panel');
        if (panel) {
            // Hide all panels
            $('.panel').removeClass('active').removeClass('animate__fadeIn').addClass('hidden');
            // Show selected panel
            $('#panel-' + panel).removeClass('hidden').addClass('active animate__animated animate__fadeIn');
            // Update active state
            $('.sidebar-nav-item').removeClass('active');
            $(this).addClass('active');
        }
    });
});
</script>
</body>
</html>
