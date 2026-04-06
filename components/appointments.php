<?php
require_once __DIR__ . '/../account/role.php';
require_once __DIR__ . '/../account/Account.php';
require_once __DIR__ . '/../account/Patient.php';
require_once __DIR__ . '/../Connect.php';
require_once __DIR__ . '/../services/Institution.php';

use account\role;
use account\Patient;
use account\VisitType;

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';

if ($role !== 'PATIENT') {
    header('Location: /dashboard');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$patient = Patient::getUserById($user_id);
if (!$patient) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Unable to load profile.'];
    header('Location: /login');
    exit;
}

// Handle new appointment booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book_appointment') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid request.'];
        header('Location: /appointments');
        exit;
    }

    $institution_id = (int) ($_POST['institution_id'] ?? 0);
    $visit_type = trim($_POST['visit_type'] ?? '');
    $scheduled_at = trim($_POST['scheduled_at'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if (!$institution_id || !$visit_type || !$scheduled_at) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Please fill in all required fields.'];
        header('Location: /appointments');
        exit;
    }

    $visitTypeEnum = VisitType::tryFrom($visit_type) ?? VisitType::OTHER;
    $scheduledDt = new DateTime($scheduled_at);
    $ok = $patient->createVisit($user_id, $institution_id, $visitTypeEnum, $scheduledDt, $reason, '');

    $_SESSION['flash'] = $ok
        ? ['type' => 'success', 'msg' => 'Appointment booked successfully!']
        : ['type' => 'error', 'msg' => 'Failed to book appointment.'];
    header('Location: /appointments');
    exit;
}

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_appointment') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid request.'];
        header('Location: /appointments');
        exit;
    }

    $visit_id = (int) ($_POST['visit_id'] ?? 0);
    $ok = $patient->cancelVisit($visit_id);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Appointment cancelled.'];
    header('Location: /appointments');
    exit;
}

// Load patient's appointments using Patient class method
$appointments = $patient->getMyVisits();

// Load available institutions using Account class method
$institutions = \account\Account::getAllInstitutions();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$initials = strtoupper(substr($patient->getFirstName(), 0, 1) . substr($patient->getLastName(), 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://kit.fontawesome.com/1ad3aa32da.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; }
        #sidebar { width:64px; transition:width 0.3s ease; }
        #sidebar.expanded { width:224px; }
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
    <button onclick="this.parentElement.remove()" class="ml-2 opacity-50 hover:opacity-100">&times;</button>
</div>
<script>setTimeout(() => document.getElementById('flash-banner')?.remove(), 5000);</script>
<?php endif; ?>

<!-- Sidebar -->
<aside id="sidebar" class="fixed left-0 top-0 h-full bg-white border-r border-slate-200 shadow-sm z-40 flex flex-col">
    <div class="p-4">
        <div class="w-9 h-9 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-semibold">
            <?= $initials ?>
        </div>
    </div>
    <nav class="flex-1 px-2 space-y-1">
        <a href="/dashboard/patient" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-500 hover:bg-slate-50 transition-colors">
            <i class="fas fa-home w-5"></i><span class="hidden">Home</span>
        </a>
        <a href="/schedule" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-500 hover:bg-slate-50 transition-colors">
            <i class="fas fa-calendar w-5"></i><span class="hidden">Schedule</span>
        </a>
        <a href="/map" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-500 hover:bg-slate-50 transition-colors">
            <i class="fas fa-map-marker-alt w-5"></i><span class="hidden">Map</span>
        </a>
    </nav>
    <div class="p-2 border-t border-slate-100">
        <a href="/logout" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-red-500 hover:bg-red-50 transition-colors">
            <i class="fas fa-sign-out-alt w-5"></i><span class="hidden">Logout</span>
        </a>
    </div>
</aside>

<!-- Main -->
<main id="main" class="ml-16 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-light text-slate-800" style="font-family:'DM Serif Display',serif;">Appointments</h1>
                <p class="text-sm text-slate-500 mt-0.5">Book and manage your healthcare visits</p>
            </div>
        </div>

        <!-- Book New Appointment -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Book New Appointment</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="book_appointment">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Institution</label>
                        <select name="institution_id" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                            <option value="">Select institution...</option>
                            <?php foreach ($institutions as $inst): ?>
                                <option value="<?= $inst['id'] ?>"><?= htmlspecialchars($inst['name']) ?> (<?= htmlspecialchars($inst['institution_type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Visit Type</label>
                        <select name="visit_type" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                            <option value="">Select type...</option>
                            <option value="CHECKUP">Check-up</option>
                            <option value="FOLLOW_UP">Follow-up</option>
                            <option value="SPECIALIST">Specialist</option>
                            <option value="LAB">Lab</option>
                            <option value="THERAPY">Therapy</option>
                            <option value="OTHER">Other</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Date & Time</label>
                        <input type="datetime-local" name="scheduled_at" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Reason</label>
                        <input type="text" name="reason" placeholder="Reason for visit" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                    </div>
                </div>

                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors">
                    <i class="fas fa-plus mr-2"></i>Book Appointment
                </button>
            </form>
        </div>

        <!-- Upcoming Appointments -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Your Appointments</h2>

            <?php if (empty($appointments)): ?>
                <p class="text-slate-400 text-center py-8">No appointments yet. Book one above!</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($appointments as $appt): ?>
                        <?php
                        $statusColors = [
                            'SCHEDULED' => 'bg-blue-100 text-blue-700',
                            'COMPLETED' => 'bg-green-100 text-green-700',
                            'CANCELLED' => 'bg-red-100 text-red-700',
                            'NO_SHOW' => 'bg-amber-100 text-amber-700',
                        ];
                        $typeLabels = [
                            'CHECKUP' => 'Check-up',
                            'FOLLOW_UP' => 'Follow-up',
                            'EMERGENCY' => 'Emergency',
                            'SPECIALIST' => 'Specialist',
                            'LAB' => 'Lab',
                            'THERAPY' => 'Therapy',
                            'OTHER' => 'Other',
                        ];
                        $scheduled = new DateTime($appt['scheduled_at']);
                        $isPast = $scheduled < new DateTime();
                        ?>
                        <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 <?= $isPast ? 'bg-slate-50' : 'bg-white' ?>">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600">
                                    <i class="fas fa-calendar-check text-lg"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-800"><?= htmlspecialchars($typeLabels[$appt['visit_type']] ?? $appt['visit_type']) ?></p>
                                    <p class="text-sm text-slate-500"><?= htmlspecialchars($appt['institution_name']) ?></p>
                                    <p class="text-xs text-slate-400"><?= $scheduled->format('M j, Y \a\t g:i A') ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $statusColors[$appt['visit_status']] ?? 'bg-slate-100' ?>">
                                    <?= $appt['visit_status'] ?>
                                </span>
                                <?php if ($appt['visit_status'] === 'SCHEDULED' && !$isPast): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="cancel_appointment">
                                        <input type="hidden" name="visit_id" value="<?= $appt['visit_id'] ?>">
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700" onclick="return confirm('Cancel this appointment?')">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
$('#sidebar').hover(function() { $(this).toggleClass('expanded'); });
</script>
</body>
</html>