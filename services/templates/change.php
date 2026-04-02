<?php
require_once __DIR__ . '/TemplateType.php';

$type = $type ?? TemplateType::Warning;

ob_start();
?>

<!-- Greeting -->
<p class="text-base font-semibold text-slate-800 mb-3">
    Hello, <?= htmlspecialchars($full_name ?? 'User') ?>
</p>

<!-- Message -->
<p class="text-sm text-slate-500 leading-relaxed mb-6">
    Your account <strong class="text-slate-700"><?= htmlspecialchars($change ?? 'information') ?></strong>
    was recently updated on
    <span class="text-slate-700"><?= htmlspecialchars($date ?? date('F j, Y \a\t g:i A')) ?></span>.
</p>

<?php
// Alert box based on type
$alert_class = match ($type) {
    TemplateType::Warning      => 'bg-yellow-50 border border-yellow-200 text-yellow-800',
    TemplateType::Danger       => 'bg-red-50 border border-red-200 text-red-800',
    TemplateType::Update       => 'bg-emerald-50 border border-emerald-200 text-emerald-800',
    TemplateType::Change      => 'bg-blue-50 border border-blue-200 text-blue-800',
    TemplateType::Notification => 'bg-slate-50 border border-slate-200 text-slate-600',
};
?>
<div class="<?= $alert_class ?> rounded-xl px-5 py-4 mb-6 text-sm leading-relaxed">
    <?php if ($type === TemplateType::Warning || $type === TemplateType::Danger): ?>
    <p class="mb-2">
        <span class="inline-block bg-yellow-100 border border-yellow-300 text-yellow-800 text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full mr-2"><?= $type === TemplateType::Danger ? 'Action Required' : 'Warning' ?></span>
        If you did not make this change, please
        <a href="mailto:support@medhealth.com" class="font-semibold underline">contact support immediately</a>.
        Your account security is important to us.
    </p>
    <?php elseif ($type === TemplateType::Update): ?>
    <p>
        <span class="inline-block bg-emerald-100 border border-emerald-300 text-emerald-800 text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full mr-2">Updated</span>
        Your account has been successfully updated.
    </p>
    <?php elseif ($type === TemplateType::Change): ?>
    <p>
        <span class="inline-block bg-blue-100 border border-blue-300 text-blue-800 text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full mr-2">Changed</span>
        A change was made to your account settings.
    </p>
    <?php else: ?>
    <p>
        <span class="inline-block bg-slate-100 border border-slate-300 text-slate-600 text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full mr-2">Notice</span>
        This is a notification about your account.
    </p>
    <?php endif; ?>
</div>

<hr class="border-t border-slate-100 my-6">

<p class="text-sm text-slate-500 leading-relaxed">
    Questions? Reply to this email or reach us at
    <a href="mailto:support@medhealth.com" class="text-slate-700 font-medium">support@medhealth.com</a>.
</p>

<?php
$content = ob_get_clean();
$subject = $subject ?? 'Account Change Notification';
include __DIR__ . '/index.php';