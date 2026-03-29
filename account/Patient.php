<?php

namespace account;

require_once __DIR__ . '/Account.php';

class Patient extends Account
{
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
     * Soft deletes a diagnosis associated with the currently authenticated user by invoking a stored procedure.
     *
     * @param int $diagnosisId The unique identifier of the diagnosis to be soft deleted.
     * @return bool Returns true if the stored procedure executes successfully, false otherwise.
     */
    public function softDeleteDiagnosis(int $diagnosisId): bool
    {
        $sql = "CALL soft_delete_diagnosis(?, ?)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('ii', $diagnosisId, $this->id);
        return $stmt->execute();
    }
}
