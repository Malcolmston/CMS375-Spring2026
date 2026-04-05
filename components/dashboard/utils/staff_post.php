<?php
/**
 * Shared POST handler for all staff dashboards.
 *
 * Call this after loading $staff and before reading the flash message.
 * On any POST request, handles the action and exits with a PRG redirect.
 *
 * @param mixed  $staff       Staff account object
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
        $new     = $_POST['new_password']     ?? '';
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

    } elseif ($action === 'create_prescription') {
        if (!method_exists($staff, 'createPrescription')) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Your role cannot create prescriptions.'];
            header('Location: ' . $redirectUrl);
            exit;
        }
        $patientId  = (int) ($_POST['patient_id']  ?? 0);
        $notes      = trim($_POST['rx_notes']      ?? '');
        $issueDate  = trim($_POST['issue_date']    ?? '');
        $expireDate = trim($_POST['expire_date']   ?? '');

        if (!$patientId || !$issueDate || !$expireDate) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Patient, issue date, and expiry date are required.'];
            header('Location: ' . $redirectUrl);
            exit;
        }

        $rxId = $staff->createPrescription($patientId, $notes, $issueDate, $expireDate);
        if (!$rxId) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Failed to create prescription.'];
            header('Location: ' . $redirectUrl);
            exit;
        }

        require_once __DIR__ . '/../../../pharmaceutical/Medicine.php';
        $med    = new \pharmaceutical\Medicine();
        $names  = $_POST['med_name']         ?? [];
        $ids    = $_POST['med_id']           ?? [];
        $routes = $_POST['med_route']        ?? [];
        $dosages= $_POST['med_dosage']       ?? [];
        $freqs  = $_POST['med_frequency']    ?? [];
        $durs   = $_POST['med_duration']     ?? [];
        $qtys   = $_POST['med_quantity']     ?? [];
        $instrs = $_POST['med_instructions'] ?? [];

        $allOk = true;
        foreach ($ids as $i => $medId) {
            $medId = (int) $medId;
            if ($medId <= 0) continue;
            $medicine = $med->getById($medId);
            if (!$medicine) continue;
            $ok = $staff->addPrescriptionItem(
                $rxId,
                $medicine,
                trim($routes[$i]   ?? ''),
                trim($dosages[$i]  ?? ''),
                trim($freqs[$i]    ?? ''),
                (int) ($durs[$i]   ?? 0),
                (int) ($qtys[$i]   ?? 0),
                trim($instrs[$i]   ?? '') ?: null
            );
            if (!$ok) $allOk = false;
        }

        $_SESSION['flash'] = $allOk
            ? ['type' => 'success', 'msg' => 'Prescription created successfully.']
            : ['type' => 'error',   'msg' => 'Prescription created but some items failed to save.'];

    } elseif ($action === 'create_diagnosis') {
        if (!method_exists($staff, 'diagnose')) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Your role cannot create diagnoses.'];
            header('Location: ' . $redirectUrl);
            exit;
        }
        $patientId = (int)   ($_POST['patient_id'] ?? 0);
        $condition = trim($_POST['condition']      ?? '');
        $severity  = trim($_POST['severity']       ?? '');
        $notes     = trim($_POST['diag_notes']     ?? '');

        if (!$patientId || !$condition || !$severity) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Patient, condition, and severity are required.'];
            header('Location: ' . $redirectUrl);
            exit;
        }

        $ok = $staff->diagnose($patientId, $condition, $severity, $notes);
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'msg' => 'Diagnosis recorded.']
            : ['type' => 'error',   'msg' => 'Failed to record diagnosis.'];

    } elseif ($action === 'approve_renewal') {
        if (!method_exists($staff, 'renewPrescription')) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Your role cannot renew prescriptions.'];
            header('Location: ' . $redirectUrl);
            exit;
        }
        $rxId       = (int)   ($_POST['prescription_id'] ?? 0);
        $expireDate = trim($_POST['expire_date']         ?? date('Y-m-d', strtotime('+6 months')));

        $ok = $staff->renewPrescription($rxId, $expireDate);
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'msg' => 'Prescription renewed.']
            : ['type' => 'error',   'msg' => 'Failed to renew prescription.'];

    } elseif ($action === 'dismiss_renewal') {
        if (!method_exists($staff, 'cancelPrescription')) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Your role cannot modify prescriptions.'];
            header('Location: ' . $redirectUrl);
            exit;
        }
        $rxId = (int) ($_POST['prescription_id'] ?? 0);
        $ok   = $staff->cancelPrescription($rxId);
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'msg' => 'Renewal request dismissed.']
            : ['type' => 'error',   'msg' => 'Failed to dismiss request.'];
    }

    header('Location: ' . $redirectUrl);
    exit;
}
