<?php
require_once __DIR__ . '/TemplateType.php';

$type = $type ?? TemplateType::Change;

ob_start();
?>

<p class="text-base font-semibold text-slate-800 mb-3">
    Hello, <?= htmlspecialchars($full_name ?? 'Patient') ?>
</p>

<p class="text-sm text-slate-500 leading-relaxed mb-6">
    Your appointment has been cancelled as requested.
</p>

<div class="bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 mb-6">
    <h4 class="font-medium text-slate-700 mb-3">Cancelled Appointment</h4>
    <div class="grid grid-cols-1 gap-3">
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Date</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($appointment_date ?? 'TBD') ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Time</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($appointment_time ?? 'TBD') ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Provider</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($provider ?? 'TBD') ?></span>
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
    default                     => 'bg-blue-50 border border-blue-200 text-blue-800',
};
?>
<div class="<?= $alert_class ?> rounded-xl px-5 py-4 mb-6 text-sm leading-relaxed">
    <p>
        <span class="inline-block bg-blue-100 border border-blue-300 text-blue-800 text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full mr-2">Cancelled</span>
        To reschedule, please contact us or book a new appointment online.
    </p>
</div>

<hr class="border-t border-slate-100 my-6">

<p class="text-sm text-slate-500 leading-relaxed">
    Questions? Contact us at
    <a href="mailto:support@medhealth.com" class="text-slate-700 font-medium">support@medhealth.com</a>.
</p>

<?php
$content = ob_get_clean();
$subject = $subject ?? 'Appointment Cancelled';
include __DIR__ . '/index.php';