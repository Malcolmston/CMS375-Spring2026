<?php

namespace account;

require_once __DIR__ . '/VisitType.php';

use DateTime;

trait VisitTrait
{
    /**
     * Creates a new visit record in the database.
     *
     * @param int $patientId The ID of the patient for whom the visit is being created.
     * @param int $institutionId The ID of the institution where the visit will take place.
     * @param VisitType $visitType The type of the visit.
     * @param DateTime $scheduledAt The date and time when the visit is scheduled.
     * @param string $reason The reason for the visit.
     * @param string $notes Additional notes related to the visit.
     * @return bool Returns true if the visit is successfully created, false otherwise.
     */
    public function createVisit(int $patientId, int $institutionId, VisitType $visitType, DateTime $scheduledAt, string $reason, string $notes): bool
    {
        $stmt = $this->getConnection()->prepare("CALL create_visit(?, ?, ?, ?, ?, ?)");
        if (!$stmt) return false;
        $typeVal      = $visitType->value;
        $scheduledStr = $scheduledAt->format('Y-m-d H:i:s');
        $stmt->bind_param('iissss', $patientId, $institutionId, $typeVal, $scheduledStr, $reason, $notes);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    /**
     * Creates a doctor visit record in the database using the provided visit ID, doctor ID, notes, and diagnosis summary.
     *
     * @param int $visitId The ID of the visit.
     * @param int $doctorId The ID of the doctor associated with the visit.
     * @param string $notes Additional notes related to the visit.
     * @param string $diagnosisSummary A summary of the diagnosis for the visit.
     *
     * @return bool Returns true if the doctor visit was successfully created, otherwise false.
     */
    public function createDoctorVisit(int $visitId, int $doctorId, string $notes, string $diagnosisSummary): bool
    {
        $stmt = $this->getConnection()->prepare("CALL create_doctor_visit(?, ?, ?, ?)");
        if (!$stmt) return false;
        $stmt->bind_param('iiss', $visitId, $doctorId, $notes, $diagnosisSummary);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    /**
     * Retrieves a list of visits associated with a specific patient.
     *
     * @param int $patientId The ID of the patient whose visits are to be retrieved.
     * @return array An array of visits, where each visit is represented as an associative array. Returns an empty array if no visits are found or if the query fails.
     */
    public function getVisitsByPatient(int $patientId): array
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM view_visits WHERE patient_id = ?");
        if (!$stmt) return [];
        $stmt->bind_param('i', $patientId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Retrieves all visits associated with the current patient.
     *
     * @return array An array of visits, where each visit is represented as an associative array. Returns an empty array if no visits are found or on failure.
     */
    public function getMyVisits(): array
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM view_full_visit WHERE patient_id = ?");
        if (!$stmt) return [];
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Retrieves detailed information about a visit based on the provided visit ID.
     *
     * @param int $visitId The unique identifier of the visit to retrieve.
     * @return array|false Returns an associative array containing visit details if found, or false if the visit does not exist or a database error occurs.
     */
    public function getFullVisit(int $visitId): array|false
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM view_full_visit WHERE visit_id = ? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('i', $visitId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: false;
    }

    /**
     * Counts the number of active visits for a given patient.
     *
     * @param int $patientId The ID of the patient whose active visits are to be counted.
     * @return int The number of active visits for the specified patient. Returns 0 if an error occurs or no active visits are found.
     */
    public function countActiveVisits(int $patientId): int
    {
        $stmt = $this->getConnection()->prepare("CALL count_active_visits(?)");
        if (!$stmt) return 0;
        $stmt->bind_param('i', $patientId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['count'] ?? 0);
    }

    /**
     * Cancels a visit with the specified visit ID by invoking a stored procedure.
     *
     * @param int $visitId The unique identifier of the visit to be canceled.
     * @return bool True if the visit was successfully canceled, false otherwise.
     */
    public function cancelVisit(int $visitId): bool
    {
        $stmt = $this->getConnection()->prepare("CALL cancel_visit(?)");
        if (!$stmt) return false;
        $stmt->bind_param('i', $visitId);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }
}
