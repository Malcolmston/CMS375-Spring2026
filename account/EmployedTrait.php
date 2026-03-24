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

    protected function fetchEmployeeIds(): void
    {
        $col  = ($this->role === role::ADMIN) ? 'adminid' : 'employid';
        $stmt = $this->getConnection()->prepare("UPDATE users SET $col = UUID() WHERE id = ?");
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->getConnection()->prepare("SELECT employid, adminid FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $stmt->bind_result($employid, $adminid);
        $stmt->fetch();
        $stmt->close();
        $this->employid = $employid;
        $this->adminid  = $adminid;
    }

    public static function resolveRole(string $email, string $employid): ?string
    {
        $sql  = "SELECT role FROM view_user_role_pwd
                 WHERE email    = ?
                   AND employid = ?
                 LIMIT 1";

        $conn = (new \Connect())->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return null;
        }
        $stmt->bind_param('ss', $email, $employid);
        $stmt->execute();
        $stmt->bind_result($role);
        $found = $stmt->fetch();
        $stmt->close();

        return $found ? $role : null;
    }
}
