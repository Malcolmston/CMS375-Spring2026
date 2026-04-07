<?php
require_once __DIR__ . '/../account/role.php';
require_once __DIR__ . '/../account/Account.php';
require_once __DIR__ . '/../account/Patient.php';
require_once __DIR__ . '/../account/prefix.php';
require_once __DIR__ . '/../account/suffix.php';
require_once __DIR__ . '/../account/blood.php';
require_once __DIR__ . '/../account/InstitutionType.php';
require_once __DIR__ . '/../account/VisitType.php';
require_once __DIR__ . '/../Point.php';

use account\role;
use account\Patient;
use account\prefix;
use account\suffix;
use account\blood;
use account\InstitutionType;
use account\VisitType;

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit;
}

$error = '';
$success = false;

// Initialize registration data in session
if (!isset($_SESSION['register_data'])) {
    $_SESSION['register_data'] = [];
}

$step = (int) ($_POST['step'] ?? $_SESSION['register_step'] ?? 1);
$institutions = \account\Account::getAllInstitutions();

// Handle step navigation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['next'])) {
        // Validate current step
        if ($step === 1) {
            $institution_id = (int) ($_POST['institution_id'] ?? 0);
            if (!$institution_id) {
                $error = 'Please select an institution.';
            } else {
                $_SESSION['register_data']['institution_id'] = $institution_id;
                $step = 2;
            }
        } elseif ($step === 2) {
            $care_type = trim($_POST['care_type'] ?? '');
            if (!$care_type) {
                $error = 'Please select a type of care.';
            } else {
                $_SESSION['register_data']['care_type'] = $care_type;
                $step = 3;
            }
        } elseif ($step === 3) {
            $firstname = trim($_POST['firstname'] ?? '');
            $lastname = trim($_POST['lastname'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (!$firstname || !$lastname || !$email) {
                $error = 'Please fill in all required fields.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif (\account\Account::emailExists($email)) {
                $error = 'An account with this email already exists.';
            } else {
                $_SESSION['register_data']['firstname'] = $firstname;
                $_SESSION['register_data']['lastname'] = $lastname;
                $_SESSION['register_data']['email'] = $email;
                $_SESSION['register_data']['phone'] = trim($_POST['phone'] ?? '');
                $_SESSION['register_data']['gender'] = trim($_POST['gender'] ?? '');
                $_SESSION['register_data']['age'] = (int) ($_POST['age'] ?? 0);
                $_SESSION['register_data']['prefix'] = $_POST['prefix'] ?? 'Mr';
                $_SESSION['register_data']['blood'] = $_POST['blood'] ?? 'O';
                $step = 4;
            }
        } elseif ($step === 4) {
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match.';
            } else {
                // Create patient account
                try {
                    $data = $_SESSION['register_data'];
                    $patient = new Patient(
                        $data['firstname'],
                        $data['lastname'],
                        '',
                        prefix::tryFrom($data['prefix'] ?? 'Mr') ?? prefix::Mr,
                        null,
                        role::PATIENT,
                        $data['gender'] ?? '',
                        $data['phone'] ?? '',
                        new Point(0, 0),
                        $data['email'],
                        $data['age'] ?? 0,
                        blood::tryFrom($data['blood'] ?? 'O') ?? blood::O,
                        $password
                    );
                    $patient->register();
                    $success = true;
                    $_SESSION['register_data'] = [];
                    $_SESSION['register_step'] = 1;
                } catch (Exception $e) {
                    $error = 'Registration failed: ' . $e->getMessage();
                }
            }
        }
    } elseif (isset($_POST['back'])) {
        $step = max(1, $step - 1);
    }
}

$_SESSION['register_step'] = $step;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://kit.fontawesome.com/1ad3aa32da.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; }
        .step { display: none; }
        .step.active { display: block; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-slate-200 flex items-center justify-center p-4">

<?php if ($success): ?>
    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center text-green-600 mx-auto mb-4">
                <i class="fas fa-check text-2xl"></i>
            </div>
            <h1 class="text-2xl font-serif text-slate-800 mb-2">Account Created!</h1>
            <p class="text-slate-500 mb-6">You can now sign in to your patient portal.</p>
            <a href="/login" class="inline-block px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors">Sign In</a>
        </div>
    </div>
<?php else: ?>
    <div class="max-w-lg w-full">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-serif font-light text-slate-800">MedHealth</h1>
            <p class="text-slate-500 mt-2">Create your patient account</p>
        </div>

        <!-- Progress Steps -->
        <div class="flex items-center justify-center mb-8">
            <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium <?= $i <= $step ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-500' ?>">
                        <?= $i ?>
                    </div>
                    <?php if ($i < 4): ?>
                        <div class="w-12 h-0.5 <?= $i < $step ? 'bg-indigo-600' : 'bg-slate-200' ?>"></div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
        <div class="flex justify-between text-xs text-slate-500 mb-6 px-4">
            <span class="text-center w-8">Institution</span>
            <span class="text-center w-8">Care</span>
            <span class="text-center w-8">Info</span>
            <span class="text-center w-8">Password</span>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <form method="POST" id="registerForm">
                <input type="hidden" name="step" value="<?= $step ?>">

                <!-- Step 1: Institution -->
                <div class="step <?= $step === 1 ? 'active' : '' ?>" data-step="1">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Select Your Institution</h2>
                    <div class="space-y-3 max-h-80 overflow-y-auto">
                        <?php foreach ($institutions as $inst): ?>
                            <label class="flex items-center p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors">
                                <input type="radio" name="institution_id" value="<?= $inst['id'] ?>" class="w-4 h-4 text-indigo-600" <?= ($_SESSION['register_data']['institution_id'] ?? '') == $inst['id'] ? 'checked' : '' ?>>
                                <div class="ml-3">
                                    <p class="font-medium text-slate-800"><?= htmlspecialchars($inst['name']) ?></p>
                                    <p class="text-sm text-slate-500"><?= htmlspecialchars($inst['institution_type']) ?></p>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Step 2: Type of Care -->
                <div class="step <?= $step === 2 ? 'active' : '' ?>" data-step="2">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Select Type of Care</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <?php foreach (VisitType::cases() as $type): ?>
                            <label class="flex items-center p-4 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors">
                                <input type="radio" name="care_type" value="<?= $type->value ?>" class="w-4 h-4 text-indigo-600" <?= ($_SESSION['register_data']['care_type'] ?? '') == $type->value ? 'checked' : '' ?>>
                                <span class="ml-3 font-medium text-slate-700"><?= $type->value ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Step 3: User Information -->
                <div class="step <?= $step === 3 ? 'active' : '' ?>" data-step="3">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Your Information</h2>
                    <div class="space-y-4">
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Prefix</label>
                                <select name="prefix" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                                    <?php foreach (prefix::cases() as $p): ?>
                                        <option value="<?= $p->value ?>" <?= ($_SESSION['register_data']['prefix'] ?? 'Mr') == $p->value ? 'selected' : '' ?>><?= $p->value ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">First Name *</label>
                                <input type="text" name="firstname" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= htmlspecialchars($_SESSION['register_data']['firstname'] ?? '') ?>">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Last Name *</label>
                            <input type="text" name="lastname" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= htmlspecialchars($_SESSION['register_data']['lastname'] ?? '') ?>">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Email *</label>
                            <input type="email" name="email" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= htmlspecialchars($_SESSION['register_data']['email'] ?? '') ?>">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Phone</label>
                                <input type="tel" name="phone" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= htmlspecialchars($_SESSION['register_data']['phone'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Age</label>
                                <input type="number" name="age" min="1" max="150" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= htmlspecialchars($_SESSION['register_data']['age'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Gender</label>
                                <select name="gender" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                                    <option value="">Select...</option>
                                    <option value="Male" <?= ($_SESSION['register_data']['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($_SESSION['register_data']['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= ($_SESSION['register_data']['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Blood Type</label>
                                <select name="blood" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                                    <?php foreach (blood::cases() as $b): ?>
                                        <option value="<?= $b->value ?>" <?= ($_SESSION['register_data']['blood'] ?? 'O') == $b->value ? 'selected' : '' ?>><?= $b->value ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Password -->
                <div class="step <?= $step === 4 ? 'active' : '' ?>" data-step="4">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Create Your Password</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Password *</label>
                            <input type="password" name="password" required minlength="6" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Confirm Password *</label>
                            <input type="password" name="confirm_password" required minlength="6" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between mt-6">
                    <?php if ($step > 1): ?>
                        <button type="submit" name="back" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium rounded-xl transition-colors">
                            Back
                        </button>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>
                    <button type="submit" name="next" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors">
                        <?= $step === 4 ? 'Create Account' : 'Next' ?>
                    </button>
                </div>
            </form>
        </div>

        <p class="text-center mt-6 text-sm text-slate-500">
            Already have an account? <a href="/login" class="text-indigo-600 hover:underline">Sign in</a>
        </p>
    </div>
<?php endif; ?>

</body>
</html>