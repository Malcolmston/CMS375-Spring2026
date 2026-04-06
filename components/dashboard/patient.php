<?php
require_once __DIR__ . '/../../account/role.php';
require_once __DIR__ . '/../../account/Patient.php';
require_once __DIR__ . '/../../account/Gaurduan.php';
require_once __DIR__ . '/../../account/Account.php';
require_once __DIR__ . '/../../account/blood.php';
require_once __DIR__ . '/../../account/prefix.php';
require_once __DIR__ . '/../../account/suffix.php';

use account\Patient;
use account\blood;
use account\prefix;
use account\suffix;

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /index');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Load patient data ──────────────────────────────────────────────────────
$patient    = Patient::getUserById($user_id);
$diagnoses  = $patient->getMyDiagnoses();
$allergies  = $patient->getMyAllergies();
$rx_details = $patient->getMyPrescriptionDetails();
$guardians  = $patient->getMyGuardians();

// Load dependents this patient is a guardian/parent of
$guardianSelf = \account\Guardian::getUserById($user_id);
$dependents   = $guardianSelf->getMyDependents();

// ── Flash messages ─────────────────────────────────────────────────────────
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ── POST handling (PRG) ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid request. Please try again.'];
        header('Location: /dashboard/patient');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // ── Profile update ──────────────────────────────────────────────────
    if ($action === 'update_profile') {
        $ok = $patient->updateUserProfile(
            $user_id,
            trim($_POST['firstname']  ?? ''),
            trim($_POST['lastname']   ?? ''),
            trim($_POST['middlename'] ?? ''),
            prefix::from($_POST['prefix'] ?? 'Mr'),
            !empty($_POST['suffix']) ? suffix::from($_POST['suffix']) : null,
            trim($_POST['gender']     ?? ''),
            trim($_POST['phone']      ?? ''),
            (float) ($_POST['loc_x']  ?? 0),
            (float) ($_POST['loc_y']  ?? 0),
            trim($_POST['email']      ?? ''),
            (int)   ($_POST['age']    ?? 0),
            blood::from($_POST['blood'] ?? 'O'),
            trim($_POST['extra']      ?? '') ?: null
        );
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'msg' => 'Profile updated successfully.']
            : ['type' => 'error',   'msg' => 'Update failed. Please check your details and try again.'];
        header('Location: /dashboard/patient#account');
        exit;
    }

    // ── Request prescription renewal ────────────────────────────────────
    if ($action === 'request_renewal') {
        $rxId = (int) ($_POST['prescription_id'] ?? 0);
        $ok   = $rxId > 0 && $patient->requestRenewal($rxId);
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'msg' => 'Renewal request sent to your physician.']
            : ['type' => 'error',   'msg' => 'Could not request renewal. The prescription may no longer be active.'];
        header('Location: /dashboard/patient');
        exit;
    }

    // ── Password change ─────────────────────────────────────────────────
    if ($action === 'change_password') {
        $old     = $_POST['old_password']     ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new !== $confirm) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'New passwords do not match.'];
        } elseif (strlen($new) < 8) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Password must be at least 8 characters.'];
        } else {
            $ok = $patient->changeMyPassword($old, $new);
            $_SESSION['flash'] = $ok
                ? ['type' => 'success', 'msg' => 'Password changed successfully.']
                : ['type' => 'error',   'msg' => 'Current password is incorrect.'];
        }
        header('Location: /dashboard/patient#access-control');
        exit;
    }
}

// ── Helpers ────────────────────────────────────────────────────────────────
function sev_class(int $s): string {
    return match(true) {
        $s === 0 => 'bg-slate-100 text-slate-500',
        $s <= 2  => 'bg-yellow-100 text-yellow-700',
        $s === 3 => 'bg-orange-100 text-orange-700',
        default  => 'bg-red-100 text-red-700',
    };
}
function sev_label(int $s): string {
    return match($s) {
        0 => 'None', 1 => 'Minimal', 2 => 'Mild',
        3 => 'Moderate', 4 => 'Severe', 5 => 'Critical',
        default => '—',
    };
}
function allergy_cls(string $s): string {
    return match($s) {
        'MILD'     => 'bg-yellow-100 text-yellow-700',
        'MODERATE' => 'bg-orange-100 text-orange-700',
        'SEVERE'   => 'bg-red-100 text-red-700',
        default    => 'bg-slate-100 text-slate-500',
    };
}

$initials = strtoupper(
    substr($patient->getFirstName(), 0, 1) .
    substr($patient->getLastName(),  0, 1)
);
$fullName = trim(
    ($patient->getPrefix()?->value ?? '') . ' ' .
    $patient->getFirstName() . ' ' .
    $patient->getMiddleName() . ' ' .
    $patient->getLastName()  . ' ' .
    ($patient->getSuffix()?->value ?? '')
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
        #sidebar-toggle svg { transition:transform 0.3s ease; }
        #dashboard-sidebar.expanded #sidebar-toggle svg { transform:rotate(180deg); }

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
        #sidebar-logout:hover { background:rgba(239,68,68,0.08); color:#dc2626; }
        #sidebar-logout:hover svg { color:#dc2626; }

        .panel { display:none; }
        .panel.active { display:block; }

        .form-input { width:100%; border:1px solid #e2e8f0; border-radius:0.5rem; padding:0.5rem 0.75rem; font-size:0.875rem; color:#1e293b; background:#fff; transition:border-color 0.2s, box-shadow 0.2s; }
        .form-input:focus { outline:none; border-color:#94a3b8; box-shadow:0 0 0 3px rgba(148,163,184,0.15); }
        .form-label { display:block; font-size:0.75rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:0.35rem; }
    </style>
</head>
<body class="min-h-screen bg-slate-50">

<?php if ($flash): ?>
<div id="flash-banner" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg border text-sm font-medium animate__animated animate__fadeInDown <?= $flash['type'] === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
    <?php if ($flash['type'] === 'success'): ?>
        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    <?php else: ?>
        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <?php endif; ?>
    <?= htmlspecialchars($flash['msg']) ?>
    <button onclick="document.getElementById('flash-banner').remove()" class="ml-2 opacity-50 hover:opacity-100">&times;</button>
</div>
<script>setTimeout(() => document.getElementById('flash-banner')?.remove(), 5000);</script>
<?php endif; ?>

<!-- ════ SIDEBAR ════ -->
<aside id="dashboard-sidebar" class="fixed left-0 top-0 h-full z-40 bg-white border-r border-slate-200/80 shadow-sm flex flex-col select-none">

    <div class="flex items-center justify-between px-3 pt-5 pb-4 border-b border-slate-100">
        <div class="flex items-center gap-3 min-w-0">
            <div class="avatar-ring w-9 h-9 rounded-full bg-gradient-to-br from-slate-700 to-slate-500 flex-shrink-0 flex items-center justify-center text-white text-xs font-semibold tracking-wide">
                <?= htmlspecialchars($initials) ?>
            </div>
            <div class="sidebar-label min-w-0">
                <p class="text-xs font-semibold text-slate-800 truncate leading-tight"><?= htmlspecialchars($patient->getFirstName() . ' ' . $patient->getLastName()) ?></p>
                <p class="text-[10px] text-slate-400 font-medium tracking-wide uppercase">Patient Portal</p>
            </div>
        </div>
        <button id="sidebar-toggle" class="flex-shrink-0 w-6 h-6 rounded-md flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-3 px-2 space-y-0.5">
        <div class="sidebar-section-label px-2 pb-1">
            <span class="text-[9px] font-semibold text-slate-400 uppercase tracking-widest">Patient</span>
        </div>

        <button class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-500 transition-all duration-200 active" data-panel="account" data-tooltip="Account">
            <svg class="sidebar-icon w-5 h-5 flex-shrink-0 text-slate-600 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="sidebar-label text-sm font-medium text-slate-700">Account</span>
        </button>

        <button class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-500 transition-all duration-200" data-panel="health-profile" data-tooltip="Health &amp; Profile">
            <svg class="sidebar-icon w-5 h-5 flex-shrink-0 text-slate-400 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
            <span class="sidebar-label text-sm font-medium text-slate-700">Health &amp; Profile</span>
        </button>

        <button class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-500 transition-all duration-200" data-panel="medical-overview" data-tooltip="Medical Overview">
            <svg class="sidebar-icon w-5 h-5 flex-shrink-0 text-slate-400 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
            <span class="sidebar-label text-sm font-medium text-slate-700">Medical Overview</span>
        </button>

        <button class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-500 transition-all duration-200" data-panel="emergency-contacts" data-tooltip="Emergency Contacts">
            <svg class="sidebar-icon w-5 h-5 flex-shrink-0 text-slate-400 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
            <span class="sidebar-label text-sm font-medium text-slate-700">Emergency Contacts</span>
        </button>

        <button class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-500 transition-all duration-200" data-panel="dependents" data-tooltip="My Dependents">
            <svg class="sidebar-icon w-5 h-5 flex-shrink-0 text-slate-400 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            <span class="sidebar-label text-sm font-medium text-slate-700">My Dependents</span>
        </button>

        <button class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-500 transition-all duration-200" data-panel="schedule" data-tooltip="Medication Schedule">
            <svg class="sidebar-icon w-5 h-5 flex-shrink-0 text-slate-400 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="sidebar-label text-sm font-medium text-slate-700">Schedule</span>
        </button>

        <button class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-500 transition-all duration-200" data-panel="nearby-institutions" data-tooltip="Nearby Institutions">
            <svg class="sidebar-icon w-5 h-5 flex-shrink-0 text-slate-400 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
            <span class="sidebar-label text-sm font-medium text-slate-700">Nearby Institutions</span>
        </button>

        <button class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-500 transition-all duration-200" data-panel="quick-actions" data-tooltip="Quick Actions">
            <svg class="sidebar-icon w-5 h-5 flex-shrink-0 text-slate-400 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
            <span class="sidebar-label text-sm font-medium text-slate-700">Quick Actions</span>
        </button>

        <div class="py-3 px-2">
            <div class="relative flex items-center">
                <div class="flex-grow border-t border-slate-200"></div>
                <span class="sidebar-section-label flex-shrink mx-2 text-[9px] font-semibold text-slate-400 uppercase tracking-widest">Access</span>
                <div class="flex-grow border-t border-slate-200"></div>
            </div>
        </div>

        <button class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-500 transition-all duration-200" data-panel="access-control" data-tooltip="Access &amp; Security">
            <svg class="sidebar-icon w-5 h-5 flex-shrink-0 text-slate-400 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
            <span class="sidebar-label text-sm font-medium text-slate-700">Access &amp; Security</span>
        </button>

        <button class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-500 transition-all duration-200" data-panel="data-retrieval" data-tooltip="Data Retrieval">
            <svg class="sidebar-icon w-5 h-5 flex-shrink-0 text-slate-400 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
            <span class="sidebar-label text-sm font-medium text-slate-700">Data Retrieval</span>
        </button>
    </nav>

    <div class="border-t border-slate-100 p-2">
        <button id="sidebar-logout" class="sidebar-nav-item w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl text-slate-400 transition-all duration-200" data-tooltip="Log Out" onclick="window.location.href='/logout'">
            <svg class="w-5 h-5 flex-shrink-0 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
            <span class="sidebar-label text-sm font-medium">Log Out</span>
        </button>
    </div>
</aside>

<!-- ════ MAIN ════ -->
<main id="dashboard-main" class="min-h-screen p-8">
<div class="max-w-4xl mx-auto">

    <div class="mb-6">
        <h1 id="panel-title" class="text-2xl font-light text-slate-800" style="font-family:'DM Serif Display',serif;">Account</h1>
        <p id="panel-subtitle" class="text-sm text-slate-500 mt-0.5">Manage your account details</p>
    </div>

    <!-- ══ PANEL: Account ══ -->
    <div id="panel-account" class="panel active animate__animated animate__fadeIn">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <form method="POST" action="/dashboard/patient" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action"     value="update_profile">
                <input type="hidden" name="loc_x"      value="<?= htmlspecialchars((string) $patient->getLocation()->x) ?>">
                <input type="hidden" name="loc_y"      value="<?= htmlspecialchars((string) $patient->getLocation()->y) ?>">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label">Prefix</label>
                        <select name="prefix" class="form-input">
                            <?php foreach (prefix::cases() as $p): ?>
                                <option value="<?= $p->value ?>" <?= ($patient->getPrefix()->value === $p->value) ? 'selected' : '' ?>><?= $p->value ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">First Name</label>
                        <input type="text" name="firstname" class="form-input" required value="<?= htmlspecialchars($patient->getFirstName()) ?>">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middlename" class="form-input" value="<?= htmlspecialchars($patient->getMiddleName()) ?>">
                    </div>
                    <div>
                        <label class="form-label">Last Name</label>
                        <input type="text" name="lastname" class="form-input" required value="<?= htmlspecialchars($patient->getLastName()) ?>">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Suffix</label>
                        <select name="suffix" class="form-input">
                            <option value="">— None —</option>
                            <?php foreach (suffix::cases() as $s): ?>
                                <option value="<?= $s->value ?>" <?= ($patient->getSuffix()?->value === $s->value) ? 'selected' : '' ?>><?= $s->value ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Gender</label>
                        <input type="text" name="gender" class="form-input" required value="<?= htmlspecialchars($patient->getGender()) ?>">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-input" required value="<?= htmlspecialchars($patient->getPhone()) ?>">
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" required value="<?= htmlspecialchars($patient->getEmail()) ?>">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Age</label>
                        <input type="number" name="age" class="form-input" required min="1" max="200" value="<?= (int) $patient->getAge() ?>">
                    </div>
                    <div>
                        <label class="form-label">Blood Type</label>
                        <select name="blood" class="form-input">
                            <?php foreach (blood::cases() as $b): ?>
                                <option value="<?= $b->value ?>" <?= ($patient->getBlood()->value === $b->value) ? 'selected' : '' ?>><?= htmlspecialchars($b->value) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="form-label">Extra / Medical Notes</label>
                    <textarea name="extra" rows="3" class="form-input resize-none"><?= htmlspecialchars($patient->getExtra()) ?></textarea>
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit" class="px-5 py-2 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors duration-200">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══ PANEL: Health & Profile ══ -->
    <div id="panel-health-profile" class="panel animate__animated animate__fadeIn space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-4">Identity</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-[11px] text-slate-400 uppercase tracking-wide">Full Name</p>
                        <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($fullName) ?></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-[11px] text-slate-400 uppercase tracking-wide">Age</p>
                            <p class="text-sm font-medium text-slate-800"><?= (int) $patient->getAge() ?></p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 uppercase tracking-wide">Gender</p>
                            <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($patient->getGender()) ?></p>
                        </div>
                    </div>
                    <div>
                        <p class="text-[11px] text-slate-400 uppercase tracking-wide">Status</p>
                        <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700"><?= htmlspecialchars($patient->getStatus()) ?></span>
                    </div>
                    <div>
                        <p class="text-[11px] text-slate-400 uppercase tracking-wide">Blood Type</p>
                        <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-50 text-red-700"><?= htmlspecialchars($patient->getBlood()->value) ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-4">Contact</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-[11px] text-slate-400 uppercase tracking-wide">Phone</p>
                        <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($patient->getPhone()) ?></p>
                    </div>
                    <div>
                        <p class="text-[11px] text-slate-400 uppercase tracking-wide">Email</p>
                        <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($patient->getEmail()) ?></p>
                    </div>
                    <?php if ($patient->getExtra()): ?>
                    <div>
                        <p class="text-[11px] text-slate-400 uppercase tracking-wide">Notes</p>
                        <p class="text-sm text-slate-600 leading-relaxed"><?= htmlspecialchars($patient->getExtra()) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-4">Known Allergies</h3>
            <?php if (empty($allergies)): ?>
                <p class="text-sm text-slate-400 italic">No allergies on record.</p>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($allergies as $a): ?>
                    <div class="py-3 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($a['allergy_name']) ?></p>
                            <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($a['allergy_type']) ?><?= $a['reaction'] ? ' · ' . htmlspecialchars($a['reaction']) : '' ?></p>
                        </div>
                        <span class="flex-shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold <?= allergy_cls($a['severity']) ?>"><?= htmlspecialchars($a['severity']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══ PANEL: Medical Overview ══ -->
    <div id="panel-medical-overview" class="panel animate__animated animate__fadeIn space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-4">Active Diagnoses</h3>
            <?php if (empty($diagnoses)): ?>
                <p class="text-sm text-slate-400 italic">No active diagnoses on record.</p>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($diagnoses as $d): ?>
                    <div class="py-3 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($d['condition']) ?></p>
                            <?php if ($d['notes']): ?><p class="text-xs text-slate-500 mt-0.5 leading-relaxed"><?= htmlspecialchars($d['notes']) ?></p><?php endif; ?>
                            <p class="text-xs text-slate-400 mt-1"><?= date('M j, Y', strtotime($d['created_at'])) ?></p>
                        </div>
                        <span class="flex-shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold <?= sev_class((int)$d['severity']) ?>"><?= sev_label((int)$d['severity']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-4">Prescriptions &amp; Medications</h3>
            <?php if (empty($rx_details)): ?>
                <p class="text-sm text-slate-400 italic">No prescriptions on record.</p>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($rx_details as $rx): ?>
                    <div class="py-4">
                        <div class="flex items-start justify-between gap-4 mb-2">
                            <p class="text-sm font-semibold text-slate-800">
                                <?= htmlspecialchars($rx['generic_name']) ?>
                                <?php if ($rx['brand_name']): ?><span class="font-normal text-slate-500">(<?= htmlspecialchars($rx['brand_name']) ?>)</span><?php endif; ?>
                            </p>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $rx['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : ($rx['status'] === 'renewal_requested' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500') ?>">
                                    <?= htmlspecialchars($rx['status'] === 'renewal_requested' ? 'Renewal Requested' : ucfirst($rx['status'])) ?>
                                </span>
                                <?php if ($rx['status'] === 'active'): ?>
                                <form method="POST" action="/dashboard/patient" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="request_renewal">
                                    <input type="hidden" name="prescription_id" value="<?= (int) $rx['prescription_id'] ?>">
                                    <button type="submit" class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors">
                                        Request Renewal
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <div><p class="text-[10px] text-slate-400 uppercase">Dosage</p><p class="text-xs text-slate-700"><?= htmlspecialchars($rx['dosage']) ?></p></div>
                            <div><p class="text-[10px] text-slate-400 uppercase">Frequency</p><p class="text-xs text-slate-700"><?= htmlspecialchars($rx['frequency']) ?></p></div>
                            <div><p class="text-[10px] text-slate-400 uppercase">Route</p><p class="text-xs text-slate-700"><?= htmlspecialchars($rx['route']) ?></p></div>
                            <div><p class="text-[10px] text-slate-400 uppercase">Duration</p><p class="text-xs text-slate-700"><?= (int)$rx['duration_days'] ?> days</p></div>
                        </div>
                        <?php if ($rx['instructions']): ?><p class="text-xs text-slate-500 mt-2 italic"><?= htmlspecialchars($rx['instructions']) ?></p><?php endif; ?>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Prescribed by <?= htmlspecialchars($rx['doctor_prefix'] . ' ' . $rx['doctor_firstname'] . ' ' . $rx['doctor_lastname']) ?>
                            · Issued <?= date('M j, Y', strtotime($rx['issue_date'])) ?>
                            <?= $rx['expire_date'] ? '· Expires ' . date('M j, Y', strtotime($rx['expire_date'])) : '' ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══ PANEL: Emergency Contacts ══ -->
    <div id="panel-emergency-contacts" class="panel animate__animated animate__fadeIn">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-4">Parents &amp; Legal Guardians</h3>
            <?php if (empty($guardians)): ?>
                <p class="text-sm text-slate-400 italic">No guardian or parent relationships on record.</p>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($guardians as $g): ?>
                    <div class="py-4 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex-shrink-0 flex items-center justify-center text-slate-600 font-semibold text-sm">
                            <?= strtoupper(substr($g['parent_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($g['parent_name']) ?></p>
                            <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700"><?= htmlspecialchars($g['relationship']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══ PANEL: My Dependents ══ -->
    <div id="panel-dependents" class="panel animate__animated animate__fadeIn">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-4">My Dependents</h3>
            <p class="text-sm text-slate-500 mb-5">Patients you are a parent or legal guardian of.</p>
            <?php if (empty($dependents)): ?>
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                    </div>
                    <p class="text-sm font-medium text-slate-600">No dependents on record</p>
                    <p class="text-xs text-slate-400 mt-1">Contact your healthcare provider to link dependent patients to your account.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($dependents as $dep): ?>
                    <div class="py-4 flex items-center gap-4">
                        <div class="w-11 h-11 rounded-full bg-indigo-100 flex-shrink-0 flex items-center justify-center text-indigo-600 font-semibold text-sm">
                            <?= strtoupper(substr($dep['patient_firstname'] ?? '?', 0, 1) . substr($dep['patient_lastname'] ?? '', 0, 1)) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800">
                                <?= htmlspecialchars(($dep['patient_firstname'] ?? '') . ' ' . ($dep['patient_lastname'] ?? '')) ?>
                            </p>
                            <div class="flex flex-wrap gap-1.5 mt-1">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                    <?= htmlspecialchars(ucwords(strtolower(str_replace('_', ' ', $dep['relationship'] ?? '')))) ?>
                                </span>
                                <?php if (!empty($dep['patient_age'])): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                    Age <?= (int) $dep['patient_age'] ?>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($dep['patient_blood'])): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600">
                                    <?= htmlspecialchars($dep['patient_blood']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══ PANEL: Medication Schedule ══ -->
    <div id="panel-schedule" class="panel animate__animated animate__fadeIn">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-5">Daily Medication Schedule</h3>
            <?php
            // Build time-slot grid from active, non-expired prescriptions
            $slots = ['Morning' => [], 'Noon' => [], 'Evening' => [], 'Bedtime' => []];
            $asNeeded = [];
            $today = date('Y-m-d');
            foreach ($rx_details as $rx) {
                if ($rx['status'] !== 'active') continue;
                if (!empty($rx['expire_date']) && $rx['expire_date'] < $today) continue;
                $freq = strtolower(trim($rx['frequency'] ?? ''));
                $card = [
                    'name'    => $rx['generic_name'],
                    'brand'   => $rx['brand_name'],
                    'dosage'  => $rx['dosage'],
                    'route'   => $rx['route'],
                    'instr'   => $rx['instructions'],
                ];
                switch ($freq) {
                    case 'once daily':
                        $slots['Morning'][] = $card; break;
                    case 'twice daily': case 'every 12 hours':
                        $slots['Morning'][] = $card; $slots['Evening'][] = $card; break;
                    case 'three times daily': case 'every 8 hours':
                        $slots['Morning'][] = $card; $slots['Noon'][] = $card; $slots['Evening'][] = $card; break;
                    case 'four times daily': case 'every 6 hours':
                        $slots['Morning'][] = $card; $slots['Noon'][] = $card;
                        $slots['Evening'][] = $card; $slots['Bedtime'][] = $card; break;
                    case 'every 4 hours':
                        $card['note'] = 'Every 4 hrs';
                        $slots['Morning'][] = $card; $slots['Noon'][] = $card;
                        $slots['Evening'][] = $card; $slots['Bedtime'][] = $card; break;
                    default:
                        $asNeeded[] = $card; break;
                }
            }
            $slotIcons = ['Morning' => '🌅', 'Noon' => '☀️', 'Evening' => '🌆', 'Bedtime' => '🌙'];
            $hasAny = array_filter($slots, fn($s) => !empty($s));
            ?>
            <?php if (empty($hasAny) && empty($asNeeded)): ?>
                <p class="text-sm text-slate-400 italic">No active medications to schedule.</p>
            <?php else: ?>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <?php foreach ($slots as $slotName => $meds): ?>
                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200">
                            <p class="text-xs font-semibold text-slate-600"><?= $slotIcons[$slotName] ?> <?= $slotName ?></p>
                        </div>
                        <div class="p-3 space-y-2 min-h-[80px]">
                        <?php if (empty($meds)): ?>
                            <p class="text-xs text-slate-300 italic">—</p>
                        <?php else: foreach ($meds as $m): ?>
                            <div class="p-2 rounded-lg bg-indigo-50 border border-indigo-100">
                                <p class="text-xs font-semibold text-indigo-800 leading-tight"><?= htmlspecialchars($m['name']) ?></p>
                                <?php if ($m['brand']): ?><p class="text-[10px] text-indigo-400"><?= htmlspecialchars($m['brand']) ?></p><?php endif; ?>
                                <p class="text-[10px] text-slate-500 mt-0.5"><?= htmlspecialchars($m['dosage']) ?> · <?= htmlspecialchars($m['route']) ?></p>
                                <?php if (!empty($m['note'])): ?><p class="text-[10px] text-amber-600"><?= htmlspecialchars($m['note']) ?></p><?php endif; ?>
                            </div>
                        <?php endforeach; endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php if (!empty($asNeeded)): ?>
                <div class="mt-2">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">As Needed / Recurring</p>
                    <div class="flex flex-wrap gap-2">
                    <?php foreach ($asNeeded as $m): ?>
                        <div class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs">
                            <span class="font-semibold text-slate-700"><?= htmlspecialchars($m['name']) ?></span>
                            <span class="text-slate-400"> · <?= htmlspecialchars($m['dosage']) ?></span>
                            <?php if (!empty($m['instr'])): ?><span class="text-slate-400"> · <?= htmlspecialchars($m['instr']) ?></span><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══ PANEL: Nearby Institutions ══ -->
    <div id="panel-nearby-institutions" class="panel animate__animated animate__fadeIn">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <p class="text-sm text-slate-500 mb-5">Loads the nearest 100 health facilities to your registered address and opens them on the interactive map.</p>
            <button id="btn-open-map"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                <span id="btn-open-map-label">Open Nearby Institutions</span>
            </button>
            <p id="map-status" class="text-xs text-slate-400 mt-3 hidden"></p>
        </div>
    </div>

    <!-- ══ PANEL: Quick Actions ══ -->
    <div id="panel-quick-actions" class="panel animate__animated animate__fadeIn">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php
            $actions = [
                ['panel'=>'account',            'icon'=>'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z', 'title'=>'Update Profile', 'sub'=>'Edit your personal information'],
                ['panel'=>'medical-overview',    'icon'=>'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z', 'title'=>'Medical History', 'sub'=>'View diagnoses & prescriptions'],
                ['panel'=>'emergency-contacts',  'icon'=>'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z', 'title'=>'Emergency Contacts', 'sub'=>'View guardians & family'],
                ['panel'=>'access-control',      'icon'=>'M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z', 'title'=>'Change Password', 'sub'=>'Update your login credentials'],
            ];
            foreach ($actions as $a): ?>
            <button onclick="switchPanel('<?= $a['panel'] ?>')"
                    class="group bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-start gap-4 hover:border-slate-300 hover:shadow-md transition-all duration-200 text-left w-full">
                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 group-hover:bg-slate-800 group-hover:text-white transition-all duration-200 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $a['icon'] ?>"/></svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($a['title']) ?></p>
                    <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($a['sub']) ?></p>
                </div>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ══ PANEL: Access Control & Security ══ -->
    <div id="panel-access-control" class="panel animate__animated animate__fadeIn">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 max-w-md">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-5">Change Password</h3>
            <form method="POST" action="/dashboard/patient" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action"     value="change_password">
                <div>
                    <label class="form-label">Current Password</label>
                    <input type="password" name="old_password" class="form-input" required autocomplete="current-password">
                </div>
                <div>
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-input" required minlength="8" autocomplete="new-password">
                </div>
                <div>
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-input" required minlength="8" autocomplete="new-password">
                </div>
                <button type="submit" class="w-full py-2 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors duration-200 mt-1">
                    Update Password
                </button>
            </form>
            <div class="mt-6 pt-5 border-t border-slate-100">
                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-3">Active Session</h4>
                <div class="flex justify-between text-xs">
                    <span class="text-slate-500">IP Address</span>
                    <span class="font-mono text-slate-700"><?= htmlspecialchars($_SESSION['ip'] ?? '—') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ PANEL: Data Retrieval ══ -->
    <div id="panel-data-retrieval" class="panel animate__animated animate__fadeIn">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 opacity-60">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    <p class="text-sm font-semibold text-slate-700">Download Medical Record</p>
                </div>
                <p class="text-xs text-slate-400">Export a full summary of your medical history as PDF.</p>
                <span class="inline-block mt-3 text-xs text-slate-400 italic">Coming soon</span>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 opacity-60">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375z"/></svg>
                    <p class="text-sm font-semibold text-slate-700">Export Prescriptions</p>
                </div>
                <p class="text-xs text-slate-400">Download your current and past prescriptions as CSV.</p>
                <span class="inline-block mt-3 text-xs text-slate-400 italic">Coming soon</span>
            </div>
        </div>
    </div>

</div>
</main>

<script>
const panelMeta = {
    'account':            { title:'Account',                   sub:'Manage your account details' },
    'health-profile':     { title:'Health & Profile',          sub:'Your personal health summary' },
    'medical-overview':   { title:'Medical Overview',          sub:'Diagnoses, prescriptions & allergies' },
    'emergency-contacts': { title:'Emergency & Family',        sub:'Parents and legal guardians' },
    'dependents':         { title:'My Dependents',             sub:'Patients under your guardianship' },
    'schedule':               { title:'Medication Schedule',       sub:'Daily time-slot view of your active medicines' },
    'nearby-institutions':    { title:'Nearby Institutions',       sub:'Find the closest health facilities on the map' },
    'quick-actions':          { title:'Quick Actions',             sub:'Common tasks and shortcuts' },
    'access-control':     { title:'Access Control & Security', sub:'Password and session management' },
    'data-retrieval':     { title:'Data Retrieval',            sub:'Export and download your records' },
};

function switchPanel(panelId) {
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sidebar-nav-item[data-panel]').forEach(b => b.classList.remove('active'));

    const panel = document.getElementById('panel-' + panelId);
    if (panel) {
        panel.classList.add('active');
        panel.classList.remove('animate__fadeIn');
        void panel.offsetWidth;
        panel.classList.add('animate__fadeIn');
    }

    const btn = document.querySelector(`.sidebar-nav-item[data-panel="${panelId}"]`);
    if (btn) btn.classList.add('active');

    const meta = panelMeta[panelId] || {};
    document.getElementById('panel-title').textContent    = meta.title || '';
    document.getElementById('panel-subtitle').textContent = meta.sub   || '';
}

$(function () {
    $('#sidebar-toggle').on('click', function () {
        $('#dashboard-sidebar').toggleClass('expanded');
    });

    $('.sidebar-nav-item[data-panel]').on('click', function () {
        switchPanel($(this).data('panel'));
    });

    // Deep-link via hash (e.g. redirect after form submit lands on #access-control)
    const hash = location.hash.replace('#', '');
    if (hash && panelMeta[hash]) switchPanel(hash);

    // ── Nearby Institutions → map ──────────────────────────────────────────
    $('#btn-open-map').on('click', function () {
        const $btn    = $(this);
        const $label  = $('#btn-open-map-label');
        const $status = $('#map-status');

        $btn.prop('disabled', true);
        $label.text('Loading…');
        $status.text('Fetching nearest institutions…').removeClass('hidden text-red-500').addClass('text-slate-400');

        $.getJSON('/api/nearest_institutions')
            .done(function (institutions) {
                $status.text('Sending to map…');
                $.ajax({
                    url: '/map',
                    method: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify(institutions)
                })
                .done(function () {
                    window.location.href = '/map';
                })
                .fail(function () {
                    $status.text('Failed to load map. Please try again.').addClass('text-red-500').removeClass('text-slate-400');
                    $btn.prop('disabled', false);
                    $label.text('Open Nearby Institutions');
                });
            })
            .fail(function () {
                $status.text('Could not fetch institutions. Please try again.').addClass('text-red-500').removeClass('text-slate-400');
                $btn.prop('disabled', false);
                $label.text('Open Nearby Institutions');
            });
    });
});
</script>
</body>
</html>