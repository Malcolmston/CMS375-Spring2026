<?php

namespace pharmaceutical;

require_once __DIR__ . '/../Connect.php';
require_once __DIR__ . '/AllergyType.php';
require_once __DIR__ . '/Severity.php';

use Connect;

class Allergy extends Connect
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create a new allergy record in the catalog.
     *
     * @return int|false The new allergy ID, or false on failure
     */
    public function create(string $allergyName, AllergyType $allergyType, ?string $description = null): int|false
    {
        $typeVal = $allergyType->value;
        $stmt = $this->getConnection()->prepare("CALL create_allergy(?, ?, ?)");
        if (!$stmt) return false;
        $stmt->bind_param('sss', $allergyName, $typeVal, $description);
        if (!$stmt->execute()) { $stmt->close(); return false; }
        $id = $this->getConnection()->insert_id;
        $stmt->close();
        return $id > 0 ? $id : false;
    }

    /**
     * Record an allergy for a specific user.
     *
     * @return bool True on success, false on failure
     */
    public function addToUser(
        int      $userId,
        int      $allergyId,
        string   $reaction,
        Severity $severity,
        ?string  $notes = null
    ): bool
    {
        $severityVal = $severity->value;
        $stmt = $this->getConnection()->prepare("CALL create_user_allergy(?, ?, ?, ?, ?)");
        if (!$stmt) return false;
        $stmt->bind_param('iisss', $userId, $allergyId, $reaction, $severityVal, $notes);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Remove a user_allergy record (hard delete — no soft delete on this join table).
     *
     * @param int $userAllergyId The user_allergy.id to remove
     * @return bool
     */
    public function removeFromUser(int $userAllergyId): bool
    {
        $stmt = $this->getConnection()->prepare("DELETE FROM user_allergy WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param('i', $userAllergyId);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    /**
     * Get all active (non-deleted) allergens in the catalog.
     *
     * @return array
     */
    public function getAll(): array
    {
        $result = $this->getConnection()->query(
            "SELECT * FROM allergy WHERE deleted_at IS NULL ORDER BY allergy_name"
        );
        if (!$result) return [];
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get a single allergen by ID.
     *
     * @return array|false
     */
    public function getById(int $id): array|false
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM allergy WHERE id = ? AND deleted_at IS NULL LIMIT 1"
        );
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: false;
    }

    /**
     * Get all allergens of a given type.
     *
     * @return array
     */
    public function getByType(AllergyType $allergyType): array
    {
        $typeVal = $allergyType->value;
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM allergy WHERE allergy_type = ? AND deleted_at IS NULL ORDER BY allergy_name"
        );
        if (!$stmt) return [];
        $stmt->bind_param('s', $typeVal);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Get all allergies recorded for a patient.
     * Returns rows from view_user_allergies (includes reaction, severity, notes).
     *
     * @return array
     */
    public function getPatientAllergies(int $userId): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM view_user_allergies WHERE user_id = ?"
        );
        if (!$stmt) return [];
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Check whether a patient's recorded allergies conflict with a medicine.
     * Returns the conflict JSON decoded as an array, or an empty array if no conflict.
     *
     * @return array Conflicting allergy records, empty if safe
     */
    public function checkMedicationConflict(int $patientId, int $medicineId): array
    {
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
     * Soft-delete an allergen (sets deleted_at via trigger).
     *
     * @return bool
     */
    public function softDelete(int $id): bool
    {
        $stmt = $this->getConnection()->prepare(
            "UPDATE allergy SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL"
        );
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    /**
     * Permanently remove an allergen record.
     * Sets the session flag expected by the BEFORE DELETE trigger.
     *
     * @return bool
     */
    public function hardDelete(int $id): bool
    {
        $this->getConnection()->query("SET @hard_delete_allergy = TRUE");
        $stmt = $this->getConnection()->prepare("DELETE FROM allergy WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        $this->getConnection()->query("SET @hard_delete_allergy = NULL");
        return $ok;
    }
}
