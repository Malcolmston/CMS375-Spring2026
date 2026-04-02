<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($subject ?? 'MedHealth') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen py-10 px-4">
<div class="max-w-[560px] mx-auto bg-white rounded-2xl overflow-hidden border border-slate-200 shadow-sm">

    <!-- Header -->
    <div class="bg-slate-800 px-9 py-7 text-center">
        <span class="text-slate-50 text-xl font-semibold tracking-wide">MedHealth</span>
    </div>

    <!-- Body -->
    <div class="px-9 py-9">
        <?= $content ?? '' ?>

        <!-- Centralized Alert -->
        <?php if (isset($alert_message)): ?>
        <?php
        $alert_class = match ($alert_type ?? 'warning') {
            'warning'      => 'bg-yellow-50 border border-yellow-200 text-yellow-800',
            'danger'       => 'bg-red-50 border border-red-200 text-red-800',
            'update'       => 'bg-emerald-50 border border-emerald-200 text-emerald-800',
            'change'       => 'bg-blue-50 border border-blue-200 text-blue-800',
            'notification' => 'bg-slate-50 border border-slate-200 text-slate-600',
            default        => 'bg-yellow-50 border border-yellow-200 text-yellow-800',
        };
        $pill_class = match ($alert_type ?? 'warning') {
            'warning'      => 'bg-yellow-100 border border-yellow-300 text-yellow-800',
            'danger'       => 'bg-red-100 border border-red-300 text-red-800',
            'update'       => 'bg-emerald-100 border border-emerald-300 text-emerald-800',
            'change'       => 'bg-blue-100 border border-blue-300 text-blue-800',
            'notification' => 'bg-slate-100 border border-slate-300 text-slate-600',
            default        => 'bg-yellow-100 border border-yellow-300 text-yellow-800',
        };
        $pill_text = match ($alert_type ?? 'warning') {
            'warning'      => 'Warning',
            'danger'       => 'Alert',
            'update'       => 'Updated',
            'change'       => 'Changed',
            'notification' => 'Notice',
            default        => 'Notice',
        };
        ?>
        <div class="<?= $alert_class ?> rounded-xl px-5 py-4 mt-6 text-sm leading-relaxed">
            <p>
                <span class="inline-block <?= $pill_class ?> text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full mr-2"><?= $pill_text ?></span>
                <?= htmlspecialchars($alert_message) ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="px-9 pb-8 pt-4 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-400 leading-relaxed">
            MedHealth &mdash; Confidential patient communication.<br>Do not forward this email.
        </p>
    </div>

</div>
</body>
</html>