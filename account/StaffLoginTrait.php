<?php

namespace account;

trait StaffLoginTrait
{
    abstract protected function staffRole(): role;

    function login(string $username, string $password): bool
    {
        $role = $this->staffRole()->value;
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

    function register(): bool
    {
        $this->role     = $this->staffRole();
        $this->password = self::encryptPassword($this->password);
        $ok = $this->insert();
        if ($ok) $this->fetchEmployeeIds();
        return $ok;
    }

    /**
     * Change this staff member's password after verifying the current one.
     * Fetches the stored hash fresh from the DB — safe regardless of how the instance was loaded.
     */
    public function changeMyPassword(string $old, string $new): bool
    {
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
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    /**
     * Update this staff member's editable profile fields and persist via CALL update_user().
     * Prefix, suffix, blood type, and location remain unchanged (not collected in the staff form).
     */
    public function updateStaffProfile(
        string  $firstName,
        string  $lastName,
        string  $middleName,
        string  $email,
        string  $phone,
        string  $gender,
        int     $age,
        ?string $extra = null
    ): bool {
        $this->firstName  = $firstName;
        $this->lastName   = $lastName;
        $this->middleName = $middleName;
        $this->email      = $email;
        $this->phone      = $phone;
        $this->gender     = $gender;
        $this->age        = $age;
        $this->extra      = $extra ?? '';
        return $this->updateProfile();
    }

    public function loginWithId(string $email, string $password, string $id): bool
    {
        $role = $this->staffRole()->value;
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
