<?php

namespace account;

require_once __DIR__ . '/Account.php';
require_once __DIR__ . '/Employed.php';
require_once __DIR__ . '/EmployedTrait.php';
require_once __DIR__ . '/Role.php';

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
        $sql = "CALL unassign_institution(?, @res)";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->bind_param('i', $institutionUserId);
        $stmt->execute();
        $stmt->close();

        $result = $this->getConnection()->query("SELECT @res AS result");
        $row = $result->fetch_assoc();

        return $row['result'] ?? false;
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
        $sql = "CALL update_employee_role(?, ?, @res)";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->bind_param('is', $institutionUserId, $newRole);
        $stmt->execute();
        $stmt->close();

        $result = $this->getConnection()->query("SELECT @res AS result");
        $row = $result->fetch_assoc();

        return $row['result'] ?? false;
    }

    /**
     * View all staff across all institutions
     *
     * @return array|false Array of all staff data or false on failure
     */
    public function viewAllStaff(): array|false
    {
        $sql = "SELECT * FROM view_all_staff";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $staff;
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
     * View all staff across all institutions
     *
     * @return array|false Array of all staff data or false on failure
     */
    public function viewAllStaff(): array|false
    {
        $sql = "SELECT * FROM view_all_staff";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $staff;
    }

    /**
     * Fire/dismiss an employee from an institution
     *
     * @param int $institutionUserId The institution_user ID to remove
     * @return bool True on success, false on failure
     */
    public function fire(int $institutionUserId): bool
    {
        $sql = "CALL unassign_institution(?, @res)";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->bind_param('i', $institutionUserId);
        $stmt->execute();

        $stmt->close();

        // Get the result from the OUT parameter
        $result = $this->getConnection()->query("SELECT @res AS result");
        $row = $result->fetch_assoc();

        return $row['result'] ?? false;
    }

    /**
     * Update an employee's role at an institution
     *
     * @param int    $institutionUserId The institution_user ID
     * @param string $newRole           The new role to assign
     * @return bool True on success, false on failure
     */
    public function updateEmployeeRole(int $institutionUserId, string $newRole): bool
    {
        $sql = "CALL update_employee_role(?, ?, @res)";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->bind_param('is', $institutionUserId, $newRole);
        $stmt->execute();
        $stmt->close();

        $result = $this->getConnection()->query("SELECT @res AS result");
        $row = $result->fetch_assoc();

        return $row['result'] ?? false;
    }

    /**
     * Get details of a specific employee at an institution
     *
     * @param int $institutionUserId The institution_user ID
     * @return array|false Employee details or false on failure
     */
    public function viewEmployee(int $institutionUserId): array|false
    {
        $sql = "SELECT * FROM view_staff_by_institution WHERE institution_user_id = ? LIMIT 1";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->bind_param('i', $institutionUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $employee = $result->fetch_assoc();
        $stmt->close();

        return $employee;
    }

    /**
     * Assign a role to a specified user
     *
     * @param int $userId The ID of the user to whom the role is being assigned
     * @param Role $role The role to be assigned to the user
     * @return bool True on successful role assignment, false on failure
     */
    public function assignRole(int $userId, Role $role): bool
    {
        $sql = "CALL assign_role(?, ?, ?)";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $roleValue = $role->value;
        $adminId = $this->id;
        $stmt->bind_param('isi', $userId, $roleValue, $adminId);

        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $stmt->close();
        return true;
    }
}
