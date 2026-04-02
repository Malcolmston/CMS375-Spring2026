<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Change Notification</title>
    <style>
        body { margin:0; padding:0; background:#f1f5f9; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; }
        .wrapper { max-width:560px; margin:40px auto; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e2e8f0; }
        .header { background:#1e293b; padding:28px 36px; text-align:center; }
        .header span { color:#f8fafc; font-size:20px; font-weight:600; letter-spacing:0.02em; }
        .body { padding:36px; }
        .greeting { font-size:16px; color:#1e293b; font-weight:600; margin:0 0 12px; }
        .message { font-size:14px; color:#475569; line-height:1.7; margin:0 0 24px; }
        .alert-box { background:#fef9c3; border:1px solid #fde047; border-radius:8px; padding:14px 18px; margin-bottom:24px; }
        .alert-box p { margin:0; font-size:13px; color:#854d0e; line-height:1.6; }
        .alert-box a { color:#854d0e; font-weight:600; }
        .divider { border:none; border-top:1px solid #e2e8f0; margin:24px 0; }
        .footer { padding:0 36px 28px; text-align:center; }
        .footer p { font-size:12px; color:#94a3b8; margin:0; line-height:1.6; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <span>MedHealth</span>
    </div>

    <div class="body">
        <p class="greeting">Hello, <?= htmlspecialchars($full_name) ?></p>

        <p class="message">
            Your account <strong><?= htmlspecialchars($change) ?></strong> was recently updated.
            This change was applied on <?= htmlspecialchars($date ?? date('F j, Y \a\t g:i A')) ?>.
        </p>

        <div class="alert-box">
            <p>
                If you did not make this change, please
                <a href="mailto:support@medhealth.com">contact support immediately</a>.
                Your account security is important to us.
            </p>
        </div>

        <hr class="divider">

        <p class="message" style="margin:0;">
            If you have any questions, reply to this email or reach us at
            <a href="mailto:support@medhealth.com" style="color:#1e293b;">support@medhealth.com</a>.
        </p>
    </div>

    <div class="footer">
        <p>MedHealth &mdash; Confidential patient communication.<br>Do not forward this email.</p>
    </div>

</div>
</body>
</html>
