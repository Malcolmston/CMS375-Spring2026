<?php

namespace account;

require_once __DIR__ . '/Account.php';
require_once __DIR__ . '/EditableUserTrait.php';

class Patient extends Account
{
    use EditableUserTrait;
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
}
