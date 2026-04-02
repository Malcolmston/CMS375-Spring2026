<?php

namespace account;

require_once __DIR__ . '/Account.php';
require_once __DIR__ . '/Employed.php';
require_once __DIR__ . '/EmployedTrait.php';
require_once __DIR__ . '/EditableUserTrait.php';
require_once __DIR__ . '/AdminEditableTrait.php';
require_once __DIR__ . '/role.php';

class Admin extends Account implements Employed
{
    use EmployedTrait, EditableUserTrait, AdminEditableTrait;

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

    /**
     * Revokes a specific role from a user by executing a stored procedure.
     *
     * @param int $userId The unique identifier of the user from whom the role is being revoked.
     * @param Role $role The role object representing the role to be revoked.
     * @return bool Returns true if the role was successfully revoked, or false on failure.
     */
    public function revokeRole(int $userId, Role $role): bool
    {
        $sql = "CALL revoke_role(?, ?, ?)";
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

    /**
     * Creates a new institution by executing the corresponding stored procedure.
     *
     * @param string $name The name of the institution to be created.
     * @param InstitutionType $type The type of the institution, represented as an InstitutionType object.
     * @param string $phone The phone number associated with the institution.
     * @param string $email The email address associated with the institution.
     * @param string $address The physical address of the institution.
     * @return void
     */
    public function createInstitution(string $name, InstitutionType $type, string $phone, string $email, string $address): void
    {
        $sql = "CALL create_institution(?, ?, ?, ?, ?)";

        $institutionId = $type->value;

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('sisss', $name, $institutionId, $phone, $email, $address);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Retrieves a list of doctors associated with a specific institution.
     *
     * @param int $institutionId The ID of the institution whose doctors are to be retrieved.
     * @return array|false An array of doctors if the query is successful, or false if an error occurs.
     */
    public function viewInstitutionDoctors(int $institutionId): array|false
    {
        $sql = "SELECT * FROM view_institution_doctors WHERE institution_id = ?";
        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }
        $stmt->bind_param('i', $institutionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $doctors = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $doctors;
    }

    /**
     * Retrieves a list of nurses associated with a specific institution.
     *
     * @param int $institutionId The ID of the institution whose nurses are to be retrieved.
     * @return array|false An array of nurses if the query succeeds, or false if an error occurs.
     */
    public function viewInstitutionNurses(int $institutionId): array|false
    {
        $sql = "SELECT * FROM view_institution_nurses WHERE institution_id = ?";
        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }
        $stmt->bind_param('i', $institutionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $nurses = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $nurses;
    }

    /**
     * Retrieves a list of pharmacists associated with a specific institution.
     *
     * @param int $institutionId The ID of the institution whose pharmacists are to be retrieved.
     * @return array|false Returns an array of pharmacists as associative arrays if successful, or false on failure.
     */
    public function viewInstitutionPharmacists(int $institutionId): array|false {
        $sql = "SELECT * FROM view_institution_pharmacists WHERE institution_id = ?";
        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }
        $stmt->bind_param('i', $institutionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $pharmacists = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $pharmacists;
    }

    /**
     * Retrieves a list of expired prescriptions.
     *
     * @return array An array of expired prescriptions retrieved from the database.
     */
    public function getExpiredPrescriptions(): array
    {
        $sql = "SELECT * FROM view_expired_prescriptions";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

