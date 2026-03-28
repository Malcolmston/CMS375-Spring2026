<?php

namespace account;

require_once __DIR__ . '/Account.php';
require_once __DIR__ . '/Employed.php';
require_once __DIR__ . '/EmployedTrait.php';

class Admin extends Account implements Employed
{
    use EmployedTrait;

    private string $department;

    /**
     * @inheritDoc
     */
    public function login(string $username, string $password): bool
    {
        $role = role::ADMIN->value;
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
     * Overrides EmployedTrait to use adminid instead of employid.
     */
    public function loginWithId(string $email, string $password, string $id): bool
    {
        $role = role::ADMIN->value;
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

    /**
     * @inheritDoc
     */
    public function register(): bool
    {
        $this->role     = role::ADMIN;
        $this->password = self::encryptPassword($this->password);
        $ok = $this->insert();
        if ($ok) $this->fetchEmployeeIds();
        return $ok;
    }

    /**
     * Hire a new employee by assigning a role at an institution
     *
     * @param int    $userId        The user ID to hire
     * @param int    $institutionId The institution to assign them to
     * @param string $role          The role to assign (e.g., 'PHYSICIAN', 'NURSE')
     * @return bool True on success, false on failure
     */
    public function hire(int $userId, int $institutionId, string $role): bool
    {
        $sql = "CALL assign_institution(?, ?, ?, @success)";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->bind_param('iis', $userId, $institutionId, $role);

        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $stmt->close();

        // Retrieve the OUT parameter
        $result = $this->getConnection()->query("SELECT @success AS success");
        if (!$result) {
            return false;
        }

        $row = $result->fetch_assoc();
        $result->free();

        return (bool)($row['success'] ?? false);
    }

    /**
     * View all employees across all institutions
     *
     * @return array|false Array of employee data or false on failure
     */
    public function viewEmployees(): array|false
    {
        $sql = "SELECT * FROM view_all_employees";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $employees = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $employees;
    }

    /**
     * View all administrators
     *
     * @return array|false Array of admin data or false on failure
     */
    public function viewOtherAdmins(): array|false
    {
        $sql = "SELECT id, firstname, lastname, email, adminid, created_at FROM view_admins WHERE id != ?";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $admins = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $admins;
    }
    /**
     * Terminate an employee from an institution (soft delete)
     *
     * @param int $institutionUserId The institution_user record ID to terminate
     * @return bool True on success, false on failure
     */
    public function terminateEmployee(int $institutionUserId): bool
    {
        $sql = "UPDATE institution_user SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->bind_param('i', $institutionUserId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
    /**
     * Update an employee's role at an institution
     *
     * @param int    $institutionUserId The institution_user record ID
     * @param string $newRole           The new role to assign
     * @return bool True on success, false on failure
     */
    public function updateEmployeeRole(int $institutionUserId, string $newRole): bool
    {
        $sql = "UPDATE institution_user SET role = ? WHERE id = ? AND deleted_at IS NULL";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->bind_param('si', $newRole, $institutionUserId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
    /**
     * View all institutions
     *
     * @return array|false Array of institution data or false on failure
     */
    public function viewAllInstitutions(): array|false
    {
        $sql = "SELECT * FROM view_active_institutions";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $institutions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $institutions;
    }
    /**
     * Fire/dismiss an employee from an institution
     *
     * @param int $institutionUserId The institution_user ID to remove
     * @return bool True on success, false on failure
     */
    public function fire(int $institutionUserId): bool
    {
        $sql = "UPDATE institution_user SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->bind_param('i', $institutionUserId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
}
