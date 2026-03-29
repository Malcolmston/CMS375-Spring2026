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
}
