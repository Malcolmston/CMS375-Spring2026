<?php

namespace account;

require_once __DIR__ . '/Account.php';
require_once __DIR__ . '/EditableUserTrait.php';
require_once __DIR__ . '/VisitTrait.php';

class Patient extends Account
{
    use EditableUserTrait, VisitTrait;
    /**
     * @inheritDoc
     */
    public function login(string $username, string $password): bool
    {
        $role = role::PATIENT->value;
        $sql  = "SELECT id, password FROM view_user_role_pwd
                 WHERE email = ?
                   AND role  = ?
                 LIMIT 1";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->bind_param('ss', $username, $role);
        $stmt->execute();
        $stmt->bind_result($userId, $hash);
        if (!$stmt->fetch()) {
            $stmt->close();
            return false;
        }
        $stmt->close();

        if (!self::verifyPassword($password, $hash)) {
            return false;
        }

        $this->id = $userId;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function register(): bool
    {
        $this->role     = role::PATIENT;
        $this->password = self::encryptPassword($this->password);
        return $this->insert();
    }

    public function getMyPrescriptions(): array
    {
        $sql = "SELECT * FROM view_prescriptions WHERE patient_id = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Retrieves all active allergies recorded for this patient.
     *
     * @return array
     */
    public function getMyAllergies(): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM view_active_allergies WHERE user_id = ?"
        );
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Retrieves a list of active diagnoses for the current patient.
     *
     * @return array An array of associative arrays representing the diagnoses data.
     */
    public function getMyDiagnoses(): array
    {
        $sql = "SELECT * FROM view_active_diagnoses WHERE patient_id = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Retrieves all prescription line items (medicine-level detail) for this patient.
     *
     * @return array
     */
    public function getMyPrescriptionDetails(): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM view_prescription_detail WHERE patient_id = ? ORDER BY issue_date DESC"
        );
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Retrieves parent and legal guardian relationships for this patient.
     *
     * @return array
     */
    public function getMyGuardians(): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM view_parent_relationships WHERE patient_id = ?"
        );
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Changes the password for this patient after verifying the current password.
     * Loads the stored hash directly so it works regardless of how the instance was constructed.
     *
     * @param string $old  Plain-text current password.
     * @param string $new  Plain-text new password.
     * @return bool
     */
    public function changeMyPassword(string $old, string $new): bool
    {
        // Verify old password against view_users (soft-deleted users excluded)
        $stmt = $this->getConnection()->prepare(
            "SELECT password FROM view_users WHERE id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !self::verifyPassword($old, $row['password'])) {
            return false;
        }

        $hash = self::encryptPassword($new);
        $stmt = $this->getConnection()->prepare("CALL update_password(?, ?)");
        $stmt->bind_param('is', $this->id, $hash);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    /**
     * Request renewal of an active prescription owned by this patient.
     * Uses stored procedure to set status to 'renewal_requested'.
     */
    public function requestRenewal(int $prescriptionId): bool
    {
        $stmt = $this->getConnection()->prepare(
            "CALL request_prescription_renewal(?, ?, @affected)"
        );
        if (!$stmt) return false;
        $stmt->bind_param('ii', $prescriptionId, $this->id);
        $stmt->execute();
        $stmt->close();

        $result = $this->getConnection()->query("SELECT @affected AS ok");
        $row = $result->fetch_assoc();
        return (bool) ($row['ok'] ?? false);
    }
}
