<?php
require_once __DIR__ . '/TemplateType.php';

$type = $type ?? TemplateType::Warning;

ob_start();
?>

<p class="text-base font-semibold text-slate-800 mb-3">
    Hello, <?= htmlspecialchars($full_name ?? 'User') ?>
</p>

<p class="text-sm text-slate-500 leading-relaxed mb-6">
    Please verify your email address to complete your MedHealth account registration.
</p>

<?php
$alert_class = match ($type) {
    TemplateType::Warning      => 'bg-yellow-50 border border-yellow-200 text-yellow-800',
    TemplateType::Danger        => 'bg-red-50 border border-red-200 text-red-800',
    TemplateType::Update        => 'bg-emerald-50 border border-emerald-200 text-emerald-800',
    TemplateType::Change        => 'bg-blue-50 border border-blue-200 text-blue-800',
    TemplateType::Notification  => 'bg-slate-50 border border-slate-200 text-slate-600',
    default                     => 'bg-yellow-50 border border-yellow-200 text-yellow-800',
};
?>
<div class="<?= $alert_class ?> rounded-xl px-5 py-4 mb-6 text-sm leading-relaxed">
    <p class="mb-3">
        <span class="inline-block bg-yellow-100 border border-yellow-300 text-yellow-800 text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full mr-2">Action Required</span>
    </p>
    <p class="text-slate-700">
        Click the button below to verify your email. This verification link will expire in <?= htmlspecialchars($expiry_hours ?? '24') ?> hours.
    </p>
</div>

<div class="text-center mb-6">
    <a href="<?= htmlspecialchars($verify_link ?? '#') ?>" class="inline-block px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
        Verify Email
    </a>
</div>

<div class="bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 mb-6 text-sm">
    <p class="text-slate-500 mb-2">If the button doesn't work, copy and paste this link:</p>
    <p class="text-slate-600 break-all"><?= htmlspecialchars($verify_link ?? '') ?></p>
</div>

<hr class="border-t border-slate-100 my-6">

<p class="text-sm text-slate-500 leading-relaxed">
    If you didn't create this account, please ignore this email or contact us at
    <a href="mailto:support@medhealth.com" class="text-slate-700 font-medium">support@medhealth.com</a>.
</p>

<?php
$content = ob_get_clean();
$subject = $subject ?? 'Verify Your Email';
include __DIR__ . '/index.php';