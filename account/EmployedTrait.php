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
