<?php
require_once __DIR__ . '/TemplateType.php';

$type = $type ?? TemplateType::Warning;

ob_start();
?>

<p class="text-base font-semibold text-slate-800 mb-3">
    Hello, <?= htmlspecialchars($full_name ?? 'User') ?>
</p>

<p class="text-sm text-slate-500 leading-relaxed mb-6">
    We detected a new login to your MedHealth account.
</p>

<div class="bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 mb-6">
    <h4 class="font-medium text-slate-700 mb-3">Login Details</h4>
    <div class="grid grid-cols-1 gap-3">
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Date & Time</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($login_time ?? date('F j, Y \a\t g:i A')) ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">IP Address</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($ip_address ?? 'Unknown') ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Device</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($device ?? 'Unknown') ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Location</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($location ?? 'Unknown') ?></span>
        </div>
    </div>
</div>

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
    <p class="mb-2">
        <span class="inline-block bg-yellow-100 border border-yellow-300 text-yellow-800 text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full mr-2"><?= $type === TemplateType::Danger ? 'Suspicious Activity' : 'Was this you?' ?></span>
    </p>
    <p class="text-slate-700">
        If this wasn't you, please change your password immediately and contact support.
    </p>
</div>

<hr class="border-t border-slate-100 my-6">

<p class="text-sm text-slate-500 leading-relaxed">
    If you have any concerns, contact us at
    <a href="mailto:support@medhealth.com" class="text-slate-700 font-medium">support@medhealth.com</a>.
</p>

<?php
$content = ob_get_clean();
$subject = $subject ?? 'New Login Detected';
include __DIR__ . '/index.php';