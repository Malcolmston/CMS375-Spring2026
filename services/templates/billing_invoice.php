<?php
require_once __DIR__ . '/TemplateType.php';

$type = $type ?? TemplateType::Notification;

ob_start();
?>

<p class="text-base font-semibold text-slate-800 mb-3">
    Hello, <?= htmlspecialchars($full_name ?? 'Patient') ?>
</p>

<p class="text-sm text-slate-500 leading-relaxed mb-6">
    A new invoice has been generated for your recent visit.
</p>

<div class="bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 mb-6">
    <h4 class="font-medium text-slate-700 mb-3">Invoice Details</h4>
    <div class="grid grid-cols-1 gap-3">
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Invoice #</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($invoice_number ?? 'N/A') ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Service Date</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($service_date ?? date('F j, Y')) ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Visit Type</span>
            <span class="text-slate-800 font-semibold"><?= htmlspecialchars($visit_type ?? 'N/A') ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Total Amount</span>
            <span class="text-slate-800 font-semibold">$<?= htmlspecialchars($total_amount ?? '0.00') ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500 text-sm">Insurance Coverage</span>
            <span class="text-slate-800 font-semibold">-$<?= htmlspecialchars($insurance_coverage ?? '0.00') ?></span>
        </div>
        <div class="flex justify-between pt-3 border-t border-slate-200">
            <span class="text-slate-700 font-semibold">Amount Due</span>
            <span class="text-slate-800 font-bold">$<?= htmlspecialchars($amount_due ?? '0.00') ?></span>
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
    default                     => 'bg-slate-50 border border-slate-200 text-slate-600',
};
?>
<div class="<?= $alert_class ?> rounded-xl px-5 py-4 mb-6 text-sm leading-relaxed">
    <p>
        <span class="inline-block bg-slate-100 border border-slate-300 text-slate-600 text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full mr-2">Due Date</span>
        Payment is due by <?= htmlspecialchars($due_date ?? '30 days from invoice date') ?>.
    </p>
</div>

<div class="text-center mb-6">
    <a href="<?= htmlspecialchars($pay_link ?? '#') ?>" class="inline-block px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
        Pay Now
    </a>
</div>

<hr class="border-t border-slate-100 my-6">

<p class="text-sm text-slate-500 leading-relaxed">
    Questions about your bill? Contact us at
    <a href="mailto:billing@medhealth.com" class="text-slate-700 font-medium">billing@medhealth.com</a>.
</p>

<?php
$content = ob_get_clean();
$subject = $subject ?? 'New Invoice';
include __DIR__ . '/index.php';