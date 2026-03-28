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
     * View all doctors (physicians and surgeons)
     *
     * @return array|false Array of doctor data or false on failure
     */
    public function viewDoctors(): array|false
    {
        $sql = "SELECT * FROM view_doctors";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $doctors = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $doctors;
    }

    /**
     * View all nurses
     *
     * @return array|false Array of nurse data or false on failure
     */
    public function viewNurses(): array|false
    {
        $sql = "SELECT * FROM view_nurses";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $nurses = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $nurses;
    }

    /**
     * View all pharmacists
     *
     * @return array|false Array of pharmacist data or false on failure
     */
    public function viewPharmacists(): array|false
    {
        $sql = "SELECT * FROM view_pharmacists";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $pharmacists = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $pharmacists;
    }

    /**
     * View staff at a specific institution
     *
     * @param int $institutionId The institution ID
     * @return array|false Array of staff data or false on failure
     */
    public function viewStaffByInstitution(int $institutionId): array|false
    {
        $sql = "SELECT * FROM view_staff_by_institution WHERE institution_id = ?";

        if (!($stmt = $this->getConnection()->prepare($sql))) {
            return false;
        }

        $stmt->bind_param('i', $institutionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $staff;
    }
}
