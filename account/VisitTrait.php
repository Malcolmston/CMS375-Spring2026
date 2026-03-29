<?php

/**
 *
 */

namespace account;

require_once __DIR__ . '/VisitType.php';

use DateTime;

/**
 * Provides functionalities for handling medical visit-related database operations.
 */
trait VisitTrait
{
    /**
     * Creates a new visit record in the database.
     *
     * @param int $patient_id The unique identifier of the patient.
     * @param int $institutionId The unique identifier of the institution where the visit is scheduled.
     * @param VisitType $visitType The type of visit being created.
     * @param DateTime $scheduledAt The date and time when the visit is scheduled.
     * @param string $reason The reason for the visit.
     * @param string $notes Additional notes or remarks for the visit.
     * @return bool Returns true if the visit was successfully created, otherwise false.
     */
    public function createVisit(int $patient_id, int $institutionId, VisitType $visitType, DateTime $scheduledAt, string $reason, string $notes): bool
    {
        $sql = "CALL create_visit(?, ?, ?, ?, ?, ?)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('iissss', $patient_id, $institutionId, $visitType->value, $scheduledAt->format('Y-m-d H:i:s'), $reason, $notes);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Creates a new record for a doctor's visit in the database.
     *
     * @param int $visitId The unique identifier for the visit.
     * @param int $doctorId The unique identifier for the doctor.
     * @param string $notes Detailed notes about the visit.
     * @param string $diagnosisSummary A brief summary of the diagnosis.
     * @return bool Returns true if the record was successfully created, false otherwise.
     */
    public function createDoctorVisit(int $visitId, int $doctorId, string $notes, string $diagnosisSummary): bool
    {
        $sql = "CALL create_doctor_visit(?, ?, ?, ?)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('iiss', $visitId, $doctorId, $notes, $diagnosisSummary);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Retrieves a list of visits associated with a specific patient.
     *
     * @param int $patient_id The unique identifier of the patient whose visits are to be retrieved.
     * @return array An array of visits, where each visit is represented as an associative array.
     */
    public function getVisitsByPatient(int $patient_id): array
    {
        $sql = "SELECT * FROM view_visits WHERE patient_id = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $visits = [];
        while ($row = $result->fetch_assoc()) {
            $visits[] = $row;
        }
        return $visits;
    }

    /**
     * Retrieves a list of visits for the current user.
     *
     * @return array An array containing the visits associated with the current user, where each visit is represented as an associative array.
     */
    public function getMyVisits(): array
    {
        $sql = "SELECT * FROM view_full_visit WHERE patient_id = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $this->getUserId());
        $stmt->execute();
        $result = $stmt->get_result();
        $visits = [];
        while ($row = $result->fetch_assoc()) {
            $visits[] = $row;
        }
        return $visits;
    }

    /**
     *
     * @param int $patient_id The ID of the patient for whom to count active visits.
     */
    public function countActiveVisits(int $patient_id)
    {
        $sql = "CALL count_active_visits(?)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'];
    }

    /**
     * Cancels a scheduled visit by calling the stored procedure 'cancel_visit'
     * with the provided visit identifier.
     *
     * @param int $visitId The unique identifier of the visit to be canceled.
     * @return bool Returns true if the visit was successfully canceled,
     *              or false if no rows were affected.
     */
    public function cancelVisit(int $visitId)
    {
        $sql = "CALL cancel_visit(?)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $visitId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}

