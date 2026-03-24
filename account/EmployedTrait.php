<?php

namespace account;

trait EmployedTrait
{
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
