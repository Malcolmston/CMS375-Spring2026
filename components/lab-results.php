<?php
require_once __DIR__ . '/../account/role.php';
require_once __DIR__ . '/../account/Account.php';
require_once __DIR__ . '/../account/Patient.php';
require_once __DIR__ . '/../Connect.php';

use account\Patient;

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

$patient = Patient::getUserById($user_id);
$initials = strtoupper(substr($patient->getFirstName(), 0, 1) . substr($patient->getLastName(), 0, 1));

// Get visits with LAB type for lab results using Patient class method
$allVisits = $patient->getMyVisits();
$labVisits = array_filter($allVisits, fn($v) => ($v['visit_type'] ?? '') === 'LAB');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Results | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://kit.fontawesome.com/1ad3aa32da.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>* { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="min-h-screen bg-slate-50">

<aside class="fixed left-0 top-0 h-full w-16 bg-white border-r border-slate-200 shadow-sm z-40 flex flex-col transition-all hover:w-56">
    <div class="p-4"><div class="w-9 h-9 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-semibold"><?= $initials ?></div></div>
    <nav class="flex-1 px-2 space-y-1">
        <a href="/dashboard/patient" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-500 hover:bg-slate-50"><i class="fas fa-home w-5"></i><span class="hidden">Home</span></a>
        <a href="/schedule" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-500 hover:bg-slate-50"><i class="fas fa-calendar w-5"></i><span class="hidden">Schedule</span></a>
        <a href="/map" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-500 hover:bg-slate-50"><i class="fas fa-map-marker-alt w-5"></i><span class="hidden">Map</span></a>
    </nav>
    <div class="p-2 border-t border-slate-100">
        <a href="/logout" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-red-500 hover:bg-red-50"><i class="fas fa-sign-out-alt w-5"></i><span class="hidden">Logout</span></a>
    </div>
</aside>

<main class="ml-16 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-light text-slate-800 mb-2" style="font-family:'DM Serif Display',serif;">Lab Results</h1>
        <p class="text-sm text-slate-500 mb-6">View your laboratory test results</p>

        <?php if (empty($labVisits)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <p class="text-slate-400 text-center py-8">No lab results yet.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($labVisits as $visit): ?>
                    <?php
                    $statusColors = ['SCHEDULED' => 'bg-blue-100 text-blue-700', 'COMPLETED' => 'bg-green-100 text-green-700', 'CANCELLED' => 'bg-red-100 text-red-700'];
                    $scheduled = new DateTime($visit['scheduled_at']);
                    ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="font-semibold text-slate-800"><?= htmlspecialchars($visit['institution_name']) ?></p>
                                <p class="text-sm text-slate-500"><?= $scheduled->format('M j, Y') ?></p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $statusColors[$visit['status']] ?? 'bg-slate-100' ?>"><?= $visit['status'] ?></span>
                        </div>
                        <?php if ($visit['diagnosis_summary']): ?>
                            <div class="p-3 bg-slate-50 rounded-lg">
                                <p class="text-sm text-slate-700"><?= htmlspecialchars($visit['diagnosis_summary']) ?></p>
                            </div>
                        <?php else: ?>
                            <p class="text-sm text-slate-400">Results pending...</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>