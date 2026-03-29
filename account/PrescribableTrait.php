<?php

namespace account;

use pharmaceutical\Medicine;
use pharmaceutical\Vaccine;

require_once __DIR__ . '/../pharmaceutical/Medicine.php';
require_once __DIR__ . '/../pharmaceutical/Vaccine.php';

trait PrescribableTrait
{
    public function createPrescription(
        int    $patient_id,
        string $notes,
        string $issue_date,
        string $expire_date
    ): int|false
    {
        $stmt = $this->conn->prepare(
            "CALL create_prescription(?, ?, ?, ?, ?, @new_id)"
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("iisss",
            $patient_id,
            $this->id,
            $notes,
            $issue_date,
            $expire_date
        );
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        $stmt->close();
        $row = $this->conn->query("SELECT @new_id AS id")->fetch_assoc();
        return $row && $row['id'] !== null ? (int)$row['id'] : false;
    }

    public function addPrescriptionItem(
        int      $prescription_id,
        Medicine $medicine,
        string   $route,
        string   $dosage,
        string   $frequency,
        int      $duration_days,
        int      $quantity_prescribed,
        ?string  $instructions = null
    ): bool
    {
        $medicine_id = $medicine->getId();
        $stmt = $this->conn->prepare(
            "CALL add_prescription_item(?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("iisssiis",
            $prescription_id,
            $medicine_id,
            $route,
            $dosage,
            $frequency,
            $duration_days,
            $quantity_prescribed,
            $instructions
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function addVaccineItem(
        int     $prescription_id,
        Vaccine $vaccine,
        string  $route,
        string  $dosage,
        string  $frequency,
        int     $duration_days,
        int     $quantity_prescribed,
        ?string $instructions = null
    ): bool
    {
        $vaccine_id = $vaccine->getId();
        $stmt = $this->conn->prepare(
            "CALL add_vaccine_item(?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("iisssiis",
            $prescription_id,
            $vaccine_id,
            $route,
            $dosage,
            $frequency,
            $duration_days,
            $quantity_prescribed,
            $instructions
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function cancelPrescription(int $prescription_id): bool
    {
        $stmt = $this->conn->prepare(
            "CALL cancel_prescription(?, ?, @affected)"
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("ii", $prescription_id, $this->id);
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        $stmt->close();
        $row = $this->conn->query("SELECT @affected AS ok")->fetch_assoc();
        return (bool)($row['ok'] ?? false);
    }

    public function renewPrescription(int $prescription_id, string $expire_date): bool
    {
        $stmt = $this->conn->prepare(
            "CALL renew_prescription(?, ?, ?, @affected)"
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("iis", $prescription_id, $this->id, $expire_date);
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        $stmt->close();
        $row = $this->conn->query("SELECT @affected AS ok")->fetch_assoc();
        return (bool)($row['ok'] ?? false);
    }

    public function getPrescriptionsByPatient(int $patient_id): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM view_prescription_detail WHERE patient_id = ?"
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getMyPrescriptions(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM view_prescription_detail WHERE doctor_id = ?"
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Retrieves the items associated with a specific prescription from the database.
     *
     * @param int $prescription_id The unique identifier of the prescription for which items are to be retrieved.
     * @return array An array of associative arrays containing the prescription items. Returns an empty array if no items are found or if an error occurs.
     */
    public function getPrescriptionItems(int $prescription_id): array
    {
        $sql = "SELECT * FROM view_prescription_item WHERE prescription_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("i", $prescription_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Checks if a prescription has expired based on the given prescription ID.
     *
     * @param int $prescriptionId The ID of the prescription to check for expiration.
     * @return bool Returns true if the prescription is expired, otherwise false.
     */
    public function checkPrescriptionExpired(int $prescriptionId): bool
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT prescription_is_expired(?) AS result"
        );
        if (!$stmt) return false;
        $stmt->bind_param("i", $prescriptionId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (bool)($row['result'] ?? false);
    }

    /**
     * Safety check before adding a medicine to a prescription.
     * Returns conflicting allergy records if the patient is allergic to the medicine,
     * or an empty array if it is safe to prescribe.
     *
     * @param int      $patientId The patient's user ID
     * @param Medicine $medicine  The medicine to check
     * @return array Conflicting allergy records; empty means no conflict
     */
    public function checkAllergyMedicationConflict(int $patientId, Medicine $medicine): array
    {
        $medicineId = $medicine->getId();
        $stmt = $this->getConnection()->prepare(
            "SELECT check_allergy_medication_conflict(?, ?) AS result"
        );
        if (!$stmt) return [];
        $stmt->bind_param('ii', $patientId, $medicineId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row || $row['result'] === null) return [];
        return json_decode($row['result'], true) ?: [];
    }

    /**
     * Check for a known interaction between two medicines.
     * Returns the interaction record (severity, description, recommendation),
     * or an empty array if no interaction is recorded.
     *
     * @param Medicine $a First medicine
     * @param Medicine $b Second medicine
     * @return array Interaction record; empty means no known conflict
     */
    public function checkDrugInteractions(Medicine $a, Medicine $b): array
    {
        $idA = $a->getId();
        $idB = $b->getId();
        $stmt = $this->getConnection()->prepare(
            "SELECT check_drug_interactions(?, ?) AS result"
        );
        if (!$stmt) return [];
        $stmt->bind_param('ii', $idA, $idB);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row || $row['result'] === null) return [];
        $decoded = json_decode($row['result'], true);
        return (is_array($decoded) && !empty($decoded)) ? $decoded : [];
    }

    /**
     * General interaction check between any two agents (medicine or vaccine).
     * Accepts 'medicine' or 'vaccine' as type strings.
     *
     * @param string $type1 'medicine' or 'vaccine'
     * @param int    $id1   Agent 1 ID
     * @param string $type2 'medicine' or 'vaccine'
     * @param int    $id2   Agent 2 ID
     * @return array Interaction record; empty means no known conflict
     */
    public function checkInteraction(string $type1, int $id1, string $type2, int $id2): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT check_interaction(?, ?, ?, ?) AS result"
        );
        if (!$stmt) return [];
        $stmt->bind_param('sisi', $type1, $id1, $type2, $id2);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row || $row['result'] === null) return [];
        $decoded = json_decode($row['result'], true);
        return (is_array($decoded) && !empty($decoded)) ? $decoded : [];
    }
}
