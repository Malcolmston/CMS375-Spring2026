<?php
require_once __DIR__ . '/TemplateType.php';

$type = $type ?? TemplateType::Notification;

ob_start();
?>

<p class="text-base font-semibold text-slate-800 mb-3">
    Hello, <?= htmlspecialchars($full_name ?? 'User') ?>
</p>

<p class="text-sm text-slate-500 leading-relaxed mb-6">
    You have a new message from <?= htmlspecialchars($sender_name ?? 'your healthcare provider') ?>.
</p>

<div class="bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 mb-6">
    <div class="grid grid-cols-1 gap-3">
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">From</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($sender_name ?? 'N/A') ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Subject</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($subject ?? 'New Message') ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Received</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($received_at ?? date('F j, Y g:i A')) ?></span>
        </div>
    </div>
    <div class="mt-4 pt-4 border-t border-slate-200">
        <p class="text-slate-600 text-sm line-clamp-3"><?= htmlspecialchars($message_preview ?? 'Click to view the full message...') ?></p>
    </div>
</div>

<?php
$alert_class = match ($type) {
    TemplateType::Warning      => 'bg-yellow-50 border border-yellow-200 text-yellow-800',
    TemplateType::Danger        => 'bg-red-50 border border-red-200 text-red-800',
    TemplateType::Update        => 'bg-emerald-50 border border-emerald-200 text-emerald-800',
    TemplateType::Change        => 'bg-blue-50 border border-blue-200 text-blue-800',
    TemplateType::Notification  => 'bg-slate-50 border border-slate-200 text-slate-600',
    default                     => 'bg-slate-50 border border-slate-200 text-slate-600',
};
?>
<div class="<?= $alert_class ?> rounded-xl px-5 py-4 mb-6 text-sm leading-relaxed">
    <p>
        <span class="inline-block bg-slate-100 border border-slate-300 text-slate-600 text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full mr-2">New Message</span>
        Log in to view and respond to your message.
    </p>
</div>

<div class="text-center mb-6">
    <a href="<?= htmlspecialchars($message_link ?? '/dashboard/patient') ?>" class="inline-block px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
        View Message
    </a>
</div>

<hr class="border-t border-slate-100 my-6">

<p class="text-sm text-slate-500 leading-relaxed">
    Questions? Contact us at
    <a href="mailto:support@medhealth.com" class="text-slate-700 font-medium">support@medhealth.com</a>.
</p>

<?php
$content = ob_get_clean();
$subject = $subject ?? 'New Message Notification';
include __DIR__ . '/index.php';