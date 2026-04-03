<?php
require_once __DIR__ . '/TemplateType.php';

ob_start();
?>

<p class="text-base font-semibold text-slate-800 mb-3">
    Welcome to MedHealth, <?= htmlspecialchars($full_name ?? 'User') ?>!
</p>

<p class="text-sm text-slate-500 leading-relaxed mb-6">
    We're excited to have you as part of our healthcare community. Your account has been successfully created and is ready for use.
</p>

<div class="bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 mb-6">
    <h4 class="font-medium text-slate-700 mb-3">Getting Started</h4>
    <ul class="text-sm text-slate-500 space-y-2">
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-emerald-500 mt-1"></i>
            <span>Complete your profile information</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-emerald-500 mt-1"></i>
            <span>Review your health records</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-emerald-500 mt-1"></i>
            <span>Set up emergency contacts</span>
        </li>
        <li class="flex items-start gap-2">
            <i class="fas fa-check text-emerald-500 mt-1"></i>
            <span>Explore your dashboard</span>
        </li>
    </ul>
</div>

<p class="text-sm text-slate-500 leading-relaxed mb-6">
    Keep your profile updated to receive the best care experience.
</p>

<hr class="border-t border-slate-100 my-6">

<p class="text-sm text-slate-500 leading-relaxed">
    Need help? Contact us at
    <a href="mailto:support@medhealth.com" class="text-slate-700 font-medium">support@medhealth.com</a>.
</p>

<?php
$content = ob_get_clean();
$subject = $subject ?? 'Welcome to MedHealth';

// Centralized alert
$alert_type = 'update';
$alert_message = 'Tip: Keep your profile updated to receive the best care experience.';
include __DIR__ . '/index.php';