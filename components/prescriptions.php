<?php
require_once __DIR__ . '/../account/role.php';
require_once __DIR__ . '/../account/Account.php';
require_once __DIR__ . '/../account/Patient.php';

use account\role;
use account\Patient;

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

// Handle refill request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_refill') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid request.'];
        header('Location: /prescriptions');
        exit;
    }

    $prescription_id = (int) ($_POST['prescription_id'] ?? 0);
    $patient->requestPrescriptionRefill($prescription_id, $user_id);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Refill request submitted. Your pharmacy will contact you.'];
    header('Location: /prescriptions');
    exit;
}

// Load prescriptions with items using Patient class method
$rows = $patient->getPrescriptionsByPatient($user_id);

// Group by prescription
$prescriptions = [];
foreach ($rows as $row) {
    $pid = $row['id'];
    if (!isset($prescriptions[$pid])) {
        $prescriptions[$pid] = [
            'id' => $row['id'],
            'issue_date' => $row['issue_date'],
            'expire_date' => $row['expire_date'],
            'status' => $row['status'],
            'notes' => $row['prescription_notes'],
            'doctor_name' => $row['doctor_name'],
            'items' => []
        ];
    }
    if ($row['medicine_id']) {
        $prescriptions[$pid]['items'][] = [
            'medicine_id' => $row['medicine_id'],
            'generic_name' => $row['generic_name'],
            'brand_name' => $row['brand_name'],
            'form' => $row['form'],
            'dosage' => $row['dosage'],
            'quantity' => $row['quantity_prescribed'],
            'instructions' => $row['instructions']
        ];
    }
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$initials = strtoupper(substr($patient->getFirstName(), 0, 1) . substr($patient->getLastName(), 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescriptions | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://kit.fontawesome.com/1ad3aa32da.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; }
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
<aside class="fixed left-0 top-0 h-full w-16 bg-white border-r border-slate-200 shadow-sm z-40 flex flex-col transition-all hover:w-56">
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

<main class="ml-16 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-light text-slate-800 mb-2" style="font-family:'DM Serif Display',serif;">Prescriptions</h1>
        <p class="text-sm text-slate-500 mb-6">View your medications and request refills</p>

        <?php if (empty($prescriptions)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <p class="text-slate-400 text-center py-8">No active prescriptions.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($prescriptions as $rx): ?>
                    <?php
                    $statusColors = [
                        'active' => 'bg-green-100 text-green-700',
                        'filled' => 'bg-blue-100 text-blue-700',
                        'partially filled' => 'bg-amber-100 text-amber-700',
                        'renewal_requested' => 'bg-purple-100 text-purple-700',
                    ];
                    $formLabels = [
                        'tablet' => 'Tablet', 'capsule' => 'Capsule', 'liquid' => 'Liquid',
                        'injection' => 'Injection', 'patch' => 'Patch', 'inhaler' => 'Inhaler',
                        'cream' => 'Cream', 'ointment' => 'Ointment', 'drops' => 'Drops', 'suppository' => 'Suppository'
                    ];
                    $canRefill = $rx['status'] === 'active' || $rx['status'] === 'partially filled';
                    ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="font-semibold text-slate-800">Prescribed by <?= htmlspecialchars($rx['doctor_name']) ?></p>
                                <p class="text-sm text-slate-500">
                                    Issued: <?= date('M j, Y', strtotime($rx['issue_date'])) ?>
                                    <?php if ($rx['expire_date']): ?>
                                        · Expires: <?= date('M j, Y', strtotime($rx['expire_date'])) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $statusColors[$rx['status']] ?? 'bg-slate-100' ?>">
                                <?= ucfirst($rx['status']) ?>
                            </span>
                        </div>

                        <?php if (!empty($rx['items'])): ?>
                            <div class="space-y-2 mb-4">
                                <?php foreach ($rx['items'] as $item): ?>
                                    <div class="flex items-start gap-3 p-3 bg-slate-50 rounded-lg">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 flex-shrink-0">
                                            <i class="fas fa-pills text-sm"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-medium text-slate-800"><?= htmlspecialchars($item['brand_name'] ?: $item['generic_name']) ?></p>
                                            <p class="text-sm text-slate-500">
                                                <?= htmlspecialchars($item['dosage']) ?> · <?= $formLabels[$item['form']] ?? $item['form'] ?> · Qty: <?= $item['quantity'] ?>
                                            </p>
                                            <?php if ($item['instructions']): ?>
                                                <p class="text-xs text-slate-400 mt-1"><?= htmlspecialchars($item['instructions']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($rx['notes']): ?>
                            <p class="text-sm text-slate-500 mb-4 border-t border-slate-100 pt-3">
                                <strong>Notes:</strong> <?= htmlspecialchars($rx['notes']) ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($canRefill): ?>
                            <form method="POST" class="pt-3 border-t border-slate-100">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="request_refill">
                                <input type="hidden" name="prescription_id" value="<?= $rx['id'] ?>">
                                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors">
                                    <i class="fas fa-redo mr-2"></i>Request Refill
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>