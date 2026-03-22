<?php

namespace account;

require_once __DIR__ . '/Account.php';
require_once __DIR__ . '/Employed.php';
require_once __DIR__ . '/EmployedTrait.php';

class Billing extends Account implements Employed
{
    use EmployedTrait;

    /**
     * @inheritDoc
     */
    function login(string $username, string $password): bool
    {
        $role = role::BILLING->value;
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
    function register(): bool
    {
        $this->role     = role::BILLING;
        $this->password = self::encryptPassword($this->password);
        return $this->insert();
    }

    /**
     * @inheritDoc
     */
    public function loginWithId(string $email, string $password, string $id): bool
    {
        $role = $this->role->value;
        $sql  = "SELECT id, password FROM view_user_role_pwd
                 WHERE email   = ?
                   AND adminid = ?
                   AND role    = ?
                 LIMIT 1";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->bind_param('sss', $email, $id, $role);
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
}
