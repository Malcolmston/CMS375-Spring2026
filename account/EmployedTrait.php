<?php

namespace account;

trait EmployedTrait
{
    public function loginWithId(string $email, string $password, string $id): bool
    {
        $role = $this->role->value;
        $sql  = "SELECT id, password FROM view_user_role_pwd
                 WHERE email    = ?
                   AND employid = ?
                   AND role     = ?
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
