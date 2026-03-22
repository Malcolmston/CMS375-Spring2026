<?php

namespace account;

require_once __DIR__ . '/Account.php';
require_once __DIR__ . '/Diagnosible.php';
require_once __DIR__ . '/DiagnosibleTrait.php';
require_once __DIR__ . '/Employed.php';
require_once __DIR__ . '/EmployedTrait.php';

class LabTech extends Account implements Diagnosible, Employed
{
    use DiagnosibleTrait, EmployedTrait;

    public function login(string $username, string $password): bool
    {
        $role = role::LAB_TECH->value;
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

    public function register(): bool
    {
        $this->role     = role::LAB_TECH;
        $this->password = self::encryptPassword($this->password);
        return $this->insert();
    }
}
