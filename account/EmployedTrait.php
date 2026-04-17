<?php

namespace account;

use services\Institution;

require_once __DIR__ . '/../services/Institution.php';

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

        $conn = \Connect::getInstance()->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return null;
        }
        $stmt->bind_param('ss', $email, $employid);
        $stmt->execute();
        $stmt->bind_result($role);
        $found = $stmt->fetch();
        $result = $found ? $role : null;
        $stmt->close();

        return $result;
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

    public function viewMyInstitutions(): array|false
    {
        $sql = "SELECT * FROM view_my_institutions WHERE user_id = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $institutions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $institutions;
    }

    public function getPatients(): array
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM view_patients");
        if (!$stmt) return [];
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getPatientById(int $id): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM view_patient_summary WHERE patient_id = ? LIMIT 1"
        );
        if (!$stmt) return [];
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?? [];
    }

    public function getInstitution(int $institutionId): ?Institution
    {
        return Institution::getById($institutionId);
    }

    /**
     * Get appointments for the staff member's institutions
     *
     * @param array $institutions Array of institution rows with 'institution_id' key
     * @param string $status Filter by status (default: SCHEDULED)
     * @param int $limit Maximum number of appointments to return
     * @return array Array of appointment data
     */
    public function getAppointments(array $institutions, string $status = 'SCHEDULED', int $limit = 50): array
    {
        if (empty($institutions)) {
            return [];
        }

        $instIds = array_column($institutions, 'institution_id');
        $placeholders = implode(',', array_fill(0, count($instIds), '?'));
        $conn = $this->getConnection();

        $stmt = $conn->prepare("
            SELECT v.id, v.patient_id, v.patient_name, v.institution_id, v.institution_name,
                   v.visit_type, v.scheduled_at, v.status, v.reason
            FROM view_visits v
            WHERE v.institution_id IN ($placeholders)
              AND v.status = ?
            ORDER BY v.scheduled_at ASC
            LIMIT $limit
        ");
        if (!$stmt) return [];

        $params = array_merge($instIds, [$status]);
        $stmt->bind_param(str_repeat('i', count($instIds)) . 's', ...$params);
        $stmt->execute();
        $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $appointments;
    }

    /**
     * Get prescriptions to fill at pharmacist's institution (pharmacy)
     *
     * @param array $institutions Array of institution rows with 'institution_id' key
     * @param int $limit Maximum number of prescriptions to return
     * @return array Array of prescription data
     */
    public function getPrescriptionsToFill(array $institutions, int $limit = 50): array
    {
        if (empty($institutions)) {
            return [];
        }

        $instIds = array_column($institutions, 'institution_id');
        $placeholders = implode(',', array_fill(0, count($instIds), '?'));
        $conn = $this->getConnection();

        $stmt = $conn->prepare("
            SELECT p.id AS prescription_id, p.issue_date, p.expire_date, p.status,
                   CONCAT(u.firstname, ' ', u.lastname) AS patient_name,
                   p.patient_id
            FROM prescription p
            JOIN users u ON p.patient_id = u.id
            WHERE p.institution_id IN ($placeholders)
              AND p.status = 'active'
              AND p.deleted_at IS NULL
            ORDER BY p.issue_date DESC
            LIMIT $limit
        ");
        if (!$stmt) return [];

        $stmt->bind_param(str_repeat('i', count($instIds)), ...$instIds);
        $stmt->execute();
        $prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $prescriptions;
    }
}
