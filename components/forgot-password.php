<?php
require_once __DIR__ . '/../account/Account.php';

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Don't reveal if email exists - always show success
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>* { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-slate-200 flex items-center justify-center p-4">

<?php if ($success): ?>
    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center text-green-600 mx-auto mb-4">
                <i class="fas fa-envelope text-2xl"></i>
            </div>
            <h1 class="text-2xl font-serif text-slate-800 mb-2">Check Your Email</h1>
            <p class="text-slate-500 mb-6">If an account exists with that email, we've sent password reset instructions.</p>
            <a href="/login" class="text-indigo-600 hover:underline">Back to Sign In</a>
        </div>
    </div>
<?php else: ?>
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-serif font-light text-slate-800">MedHealth</h1>
            <p class="text-slate-500 mt-2">Reset your password</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <p class="text-slate-500 mb-4 text-sm">Enter your email address and we'll send you instructions to reset your password.</p>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Email</label>
                    <input type="email" name="email" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                </div>
                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors">
                    Send Reset Link
                </button>
            </form>
        </div>

        <p class="text-center mt-6 text-sm text-slate-500">
            Remember your password? <a href="/login" class="text-indigo-600 hover:underline">Sign in</a>
        </p>
    </div>
<?php endif; ?>

</body>
</html>
