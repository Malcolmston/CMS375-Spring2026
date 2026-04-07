<?php
require_once __DIR__ . '/../account/Account.php';

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit;
}

$error = '';
$success = false;
$token = $_GET['token'] ?? '';

// Validate token
$userId = $token ? \account\Account::validatePasswordResetToken($token) : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $token = $_POST['token'] ?? '';

    if (!$token) {
        $error = 'Invalid request.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Validate token and update password
        $userId = \account\Account::validatePasswordResetToken($token);
        if ($userId) {
            \account\Account::updatePassword($userId, $password);
            $success = true;
        } else {
            $error = 'Invalid or expired token.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | MedHealth</title>
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
                <i class="fas fa-check text-2xl"></i>
            </div>
            <h1 class="text-2xl font-serif text-slate-800 mb-2">Password Reset</h1>
            <p class="text-slate-500 mb-6">Your password has been reset. You can now sign in.</p>
            <a href="/login" class="inline-block px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors">Sign In</a>
        </div>
    </div>
<?php else: ?>
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-serif font-light text-slate-800">MedHealth</h1>
            <p class="text-slate-500 mt-2">Set your new password</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <form method="POST" class="space-y-4">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">New Password</label>
                    <input type="password" name="password" required minlength="6" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Confirm Password</label>
                    <input type="password" name="confirm_password" required minlength="6" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                </div>
                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors">
                    Reset Password
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

</body>
</html>