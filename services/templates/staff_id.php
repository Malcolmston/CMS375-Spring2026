<?php
require_once __DIR__ . '/TemplateType.php';

ob_start();
?>

<p class="text-base font-semibold text-slate-800 mb-3">
    Hello, <?= htmlspecialchars($full_name ?? 'Staff Member') ?>
</p>

<p class="text-sm text-slate-500 leading-relaxed mb-6">
    Welcome to MedHealth! Your staff account has been created. Here are your login credentials:
</p>

<div class="bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 mb-6">
    <div class="grid grid-cols-1 gap-3">
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Employee ID</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($staff_id ?? 'N/A') ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Role</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($role ?? 'Staff') ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Email</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($email ?? '') ?></span>
        </div>
    </div>
</div>

<p class="text-sm text-slate-500 leading-relaxed mb-6">
    Please save your Employee ID for your records. You will need it along with your email to log in.
    Your temporary password has been set. We recommend changing it after your first login.
</p>

<hr class="border-t border-slate-100 my-6">

<p class="text-sm text-slate-500 leading-relaxed">
    Questions? Reply to this email or reach us at
    <a href="mailto:support@medhealth.com" class="text-slate-700 font-medium">support@medhealth.com</a>.
</p>

<?php
$content = ob_get_clean();
$subject = $subject ?? 'Your MedHealth Staff Credentials';

// Use centralized alert in index.php
$alert_type = 'update';
$alert_message = 'Important: Please save your Employee ID for your records.';
include __DIR__ . '/index.php';