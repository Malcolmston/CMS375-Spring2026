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
