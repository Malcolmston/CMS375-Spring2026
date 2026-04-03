<?php
require_once __DIR__ . '/TemplateType.php';

ob_start();
?>

<p class="text-base font-semibold text-slate-800 mb-3">
    Hello, <?= htmlspecialchars($full_name ?? 'User') ?>
</p>

<p class="text-sm text-slate-500 leading-relaxed mb-6">
    We received a request to reset your password. If you didn't make this request, please ignore this email or contact support immediately.
</p>

<p class="text-sm text-slate-600 mb-6">
    To reset your password, click the button below. This link will expire in <?= htmlspecialchars($expiry_hours ?? '1') ?> hour(s).
</p>

<div class="text-center mb-6">
    <a href="<?= htmlspecialchars($reset_link ?? '#') ?>" class="inline-block px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
        Reset Password
    </a>
</div>

<div class="bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 mb-6 text-sm">
    <p class="text-slate-500 mb-2">If the button doesn't work, copy and paste this link into your browser:</p>
    <p class="text-slate-600 break-all"><?= htmlspecialchars($reset_link ?? '') ?></p>
</div>

<hr class="border-t border-slate-100 my-6">

<p class="text-sm text-slate-500 leading-relaxed">
    If you didn't request a password reset, please contact us immediately at
    <a href="mailto:support@medhealth.com" class="text-slate-700 font-medium">support@medhealth.com</a>.
</p>

<?php
$content = ob_get_clean();
$subject = $subject ?? 'Password Reset Request';

// Centralized alert
$alert_type = 'warning';
$alert_message = 'Action Required: This reset link expires in ' . ($expiry_hours ?? '1') . ' hour(s).';
include __DIR__ . '/index.php';