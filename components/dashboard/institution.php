<?php
require_once __DIR__ . '/../../account/role.php';
require_once __DIR__ . '/../../account/Account.php';
require_once __DIR__ . '/../../account/Admin.php';
require_once __DIR__ . '/../../services/Institution.php';

use account\role;

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /index');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

if (!isset($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (\Random\RandomException $e) {
        error_log('Failed to generate CSRF token: ' . $e->getMessage());
    }
}

$roleValue = $_SESSION['role'] ?? '';
$staffRole = role::tryFrom($roleValue);

if ($staffRole !== role::ADMIN) {
    header('Location: /index');
    exit;
}

$admin = \account\Admin::getUserById($user_id);
if (!$admin) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Unable to load admin profile.'];
    header('Location: /index');
    exit;
}

// ── POST handling (PRG) ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid request.'];
        header('Location: /dashboard/institution');
        exit;
    }

    $action        = $_POST['action']         ?? '';
    $institutionId = (int) ($_POST['institution_id'] ?? 0);

    if ($action === 'link_staff') {
        $employId = trim($_POST['employ_id'] ?? '');
        $roleStr  = trim($_POST['role']      ?? '');
        if ($employId && $roleStr && $institutionId) {
            $userId = \services\Institution::findUserIdByEmployeeId($employId);
            if ($userId) {
                $ok = $admin->hire($userId, $institutionId, strtoupper($roleStr));
                $_SESSION['flash'] = $ok
                    ? ['type' => 'success', 'msg' => 'Staff member linked to institution.']
                    : ['type' => 'error',   'msg' => 'Failed to link staff. They may already be assigned.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'msg' => 'No user found with that Employee ID.'];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Employee ID, role, and institution are required.'];
        }

    } elseif ($action === 'unlink_staff') {
        $institutionUserId = (int) ($_POST['institution_user_id'] ?? 0);
        if ($institutionUserId) {
            $ok = $admin->fire($institutionUserId);
            $_SESSION['flash'] = $ok
                ? ['type' => 'success', 'msg' => 'Staff member removed from institution.']
                : ['type' => 'error',   'msg' => 'Failed to remove staff member.'];
        }
    }

    header('Location: /dashboard/institution' . ($institutionId ? '?inst=' . $institutionId : ''));
    exit;
}

// ── Data loading ───────────────────────────────────────────────────────────
$allInstitutions = $admin->viewAllInstitutions() ?: [];
$myInstitutions  = $admin->viewMyInstitutions()  ?: [];

// Selected institution
$selectedId  = (int) ($_GET['inst'] ?? ($myInstitutions[0]['institution_id'] ?? 0));
$institution = $selectedId ? services\Institution::getById($selectedId) : null;

// Flat array from viewAllInstitutions for the overview card
$currentInst = null;
foreach ($allInstitutions as $inst) {
    if ((int) $inst['id'] === $selectedId) { $currentInst = $inst; break; }
}

$staffRoster  = $institution ? $institution->getStaff()        : [];
$recentVisits = $institution ? $institution->getRecentVisits() : [];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$initials = strtoupper(
    substr($admin->getFirstName(), 0, 1) .
    substr($admin->getLastName(),  0, 1)
);
$fullName = trim($admin->getFirstName() . ' ' . $admin->getLastName());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Institution Management | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://kit.fontawesome.com/1ad3aa32da.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>* { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen">

<?php if ($flash): ?>
<div class="fixed top-4 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl shadow-lg text-sm font-medium animate__animated animate__fadeInDown
     <?= $flash['type'] === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
    <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<div class="p-6 md:p-10 max-w-7xl mx-auto">

    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800" style="font-family:'DM Serif Display',serif;">Institution Management</h1>
            <p class="text-slate-500 text-sm mt-0.5">Welcome back, <?= htmlspecialchars($fullName) ?></p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/dashboard/admin" class="text-sm text-slate-500 hover:text-indigo-600 transition-colors">
                <i class="fas fa-arrow-left mr-1"></i> Admin Dashboard
            </a>
            <a href="/logout" class="text-sm text-slate-500 hover:text-red-500 transition-colors">Log out</a>
        </div>
    </div>

    <!-- Institution Picker -->
    <?php if (count($allInstitutions) > 1): ?>
    <div class="mb-6 flex flex-wrap gap-2">
        <?php foreach ($allInstitutions as $inst): ?>
        <a href="/dashboard/institution?inst=<?= (int) $inst['id'] ?>"
           class="px-4 py-2 rounded-full text-sm font-medium transition-all
                  <?= (int) $inst['id'] === $selectedId ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:border-indigo-300' ?>">
            <?= htmlspecialchars($inst['name'] ?? 'Institution #' . $inst['id']) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!$currentInst): ?>
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <i class="fas fa-hospital text-slate-300 text-5xl mb-4"></i>
        <p class="text-slate-500 font-medium">No institution selected or accessible.</p>
        <p class="text-slate-400 text-sm mt-1">Contact a system administrator to assign you to an institution.</p>
    </div>
    <?php else: ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ── Overview card ── -->
        <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-hospital text-indigo-500 text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-slate-800"><?= htmlspecialchars($currentInst['name'] ?? '—') ?></h2>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                            <?= htmlspecialchars(ucwords(strtolower(str_replace('_', ' ', $currentInst['institution_type'] ?? '')))) ?>
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-x-8 gap-y-1 text-sm text-right flex-shrink-0">
                    <?php if (!empty($currentInst['phone'])): ?>
                    <span class="text-slate-400">Phone</span><span class="text-slate-700"><?= htmlspecialchars($currentInst['phone']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($currentInst['email'])): ?>
                    <span class="text-slate-400">Email</span><span class="text-slate-700"><?= htmlspecialchars($currentInst['email']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($currentInst['address'])): ?>
                    <span class="text-slate-400">Address</span><span class="text-slate-700"><?= htmlspecialchars($currentInst['address']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 mt-5 pt-5 border-t border-slate-100">
                <div class="text-center">
                    <p class="text-2xl font-semibold text-slate-800"><?= count($staffRoster) ?></p>
                    <p class="text-xs text-slate-400 mt-0.5">Staff Members</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-semibold text-slate-800"><?= count($recentVisits) ?></p>
                    <p class="text-xs text-slate-400 mt-0.5">Recent Visits</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-semibold text-slate-800"><?= count($myInstitutions) ?></p>
                    <p class="text-xs text-slate-400 mt-0.5">Your Institutions</p>
                </div>
            </div>
        </div>

        <!-- ── Staff Roster ── -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-slate-800 mb-4">Staff Roster</h3>
            <?php if (empty($staffRoster)): ?>
                <div class="flex flex-col items-center justify-center py-10 text-center">
                    <i class="fas fa-users text-slate-300 text-3xl mb-3"></i>
                    <p class="text-slate-500 text-sm">No staff assigned yet.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left">
                                <th class="pb-3 pr-4 font-semibold text-slate-600">Name</th>
                                <th class="pb-3 pr-4 font-semibold text-slate-600">Role</th>
                                <th class="pb-3 pr-4 font-semibold text-slate-600">Email</th>
                                <th class="pb-3 font-semibold text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        <?php foreach ($staffRoster as $s): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-3 pr-4 font-medium text-slate-800">
                                    <?= htmlspecialchars(($s['firstname'] ?? '') . ' ' . ($s['lastname'] ?? '')) ?>
                                </td>
                                <td class="py-3 pr-4">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                        <?= htmlspecialchars(ucwords(strtolower(str_replace('_', ' ', $s['role'] ?? '')))) ?>
                                    </span>
                                </td>
                                <td class="py-3 pr-4 text-slate-500"><?= htmlspecialchars($s['email'] ?? '—') ?></td>
                                <td class="py-3">
                                    <form method="POST" action="/dashboard/institution"
                                          onsubmit="return confirm('Remove this staff member from the institution?')">
                                        <input type="hidden" name="csrf_token"           value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="action"               value="unlink_staff">
                                        <input type="hidden" name="institution_id"       value="<?= $selectedId ?>">
                                        <input type="hidden" name="institution_user_id"  value="<?= (int) $s['institution_user_id'] ?>">
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── Link Staff form ── -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-slate-800 mb-4">Link Staff Member</h3>
            <form method="POST" action="/dashboard/institution" class="space-y-4">
                <input type="hidden" name="csrf_token"      value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action"          value="link_staff">
                <input type="hidden" name="institution_id"  value="<?= $selectedId ?>">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Employee ID</label>
                    <input type="text" name="employ_id" required placeholder="e.g. abc123-..."
                           class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Role</label>
                    <select name="role" required class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 outline-none transition text-sm bg-white">
                        <option value="">Select role...</option>
                        <option value="PHYSICIAN">Physician</option>
                        <option value="SURGEON">Surgeon</option>
                        <option value="NURSE">Nurse</option>
                        <option value="PHARMACIST">Pharmacist</option>
                        <option value="LAB_TECH">Lab Technician</option>
                        <option value="RADIOLOGIST">Radiologist</option>
                        <option value="THERAPIST">Therapist</option>
                        <option value="EMS">EMS</option>
                        <option value="RECEPTIONIST">Receptionist</option>
                        <option value="BILLING">Billing</option>
                    </select>
                </div>
                <button type="submit" class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors text-sm">
                    Link Staff Member
                </button>
            </form>
        </div>

        <!-- ── Patient Visits ── -->
        <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-slate-800 mb-4">Recent Patient Visits</h3>
            <?php if (empty($recentVisits)): ?>
                <p class="text-sm text-slate-400 italic">No visits recorded for this institution.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left">
                                <th class="pb-3 pr-4 font-semibold text-slate-600">Patient</th>
                                <th class="pb-3 pr-4 font-semibold text-slate-600">Type</th>
                                <th class="pb-3 pr-4 font-semibold text-slate-600">Scheduled</th>
                                <th class="pb-3 pr-4 font-semibold text-slate-600">Reason</th>
                                <th class="pb-3 font-semibold text-slate-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        <?php foreach ($recentVisits as $v): ?>
                            <?php
                            $vStatus = strtolower($v['status'] ?? '');
                            $vCls = match($vStatus) {
                                'completed'  => 'bg-emerald-100 text-emerald-700',
                                'scheduled'  => 'bg-blue-100 text-blue-700',
                                'cancelled'  => 'bg-red-100 text-red-700',
                                default      => 'bg-slate-100 text-slate-500',
                            };
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-3 pr-4 font-medium text-slate-800"><?= htmlspecialchars($v['patient_name'] ?? '—') ?></td>
                                <td class="py-3 pr-4 text-slate-500"><?= htmlspecialchars($v['visit_type'] ?? '—') ?></td>
                                <td class="py-3 pr-4 text-slate-500">
                                    <?= !empty($v['scheduled_at']) ? date('M j, Y', strtotime($v['scheduled_at'])) : '—' ?>
                                </td>
                                <td class="py-3 pr-4 text-slate-500"><?= htmlspecialchars($v['reason'] ?? '—') ?></td>
                                <td class="py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $vCls ?>">
                                        <?= htmlspecialchars(ucfirst($vStatus)) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/utils/drug_interaction_widget.php'; ?>
</body>
</html>
