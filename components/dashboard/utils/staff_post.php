<?php
/**
 * Shared POST handler for all staff dashboards.
 *
 * Call this after loading $staff and before reading the flash message.
 * On any POST request, handles the action and exits with a PRG redirect.
 *
 * @param mixed  $staff       Staff account object (must have updateStaffProfile() / changeMyPassword())
 * @param string $redirectUrl Dashboard URL to redirect back to after handling
 */
function handle_staff_post(mixed $staff, string $redirectUrl): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid request. Please try again.'];
        header('Location: ' . $redirectUrl);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $ok = $staff->updateStaffProfile(
            trim($_POST['firstname']  ?? ''),
            trim($_POST['lastname']   ?? ''),
            trim($_POST['middlename'] ?? ''),
            trim($_POST['email']      ?? ''),
            trim($_POST['phone']      ?? ''),
            trim($_POST['gender']     ?? ''),
            (int)  ($_POST['age']     ?? 0),
            trim($_POST['extra']      ?? '') ?: null
        );
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'msg' => 'Profile updated successfully.']
            : ['type' => 'error',   'msg' => 'Update failed. Please check your details.'];

    } elseif ($action === 'change_password') {
        $new = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new !== $confirm) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'New passwords do not match.'];
        } elseif (strlen($new) < 8) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Password must be at least 8 characters.'];
        } else {
            $ok = $staff->changeMyPassword($_POST['old_password'] ?? '', $new);
            $_SESSION['flash'] = $ok
                ? ['type' => 'success', 'msg' => 'Password changed successfully.']
                : ['type' => 'error',   'msg' => 'Current password is incorrect.'];
        }
    }

    header('Location: ' . $redirectUrl);
    exit;
}
