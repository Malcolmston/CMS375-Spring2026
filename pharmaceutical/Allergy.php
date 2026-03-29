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
     * Add a new allergy entry to the master catalogue.
     *
     * @return int|false The new allergy ID, or false on failure
     */
    public function createAllergy(string $allergyName, AllergyType $allergyType, ?string $description = null): int|false
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
     * Link an allergy from the catalogue to a patient record.
     *
     * @return bool True on success, false on failure
     */
    public function addPatientAllergy(
        int      $patientId,
        int      $allergyId,
        string   $reaction,
        Severity $severity,
        ?string  $notes = null
    ): bool
    {
        $severityVal = $severity->value;
        $stmt = $this->getConnection()->prepare("CALL create_user_allergy(?, ?, ?, ?, ?)");
        if (!$stmt) return false;
        $stmt->bind_param('iisss', $patientId, $allergyId, $reaction, $severityVal, $notes);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Review all known allergies for a patient before prescribing.
     * Uses view_active_allergies (excludes soft-deleted catalogue entries).
     *
     * @return array
     */
    public function getPatientAllergies(int $patientId): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM view_active_allergies WHERE user_id = ?"
        );
        if (!$stmt) return [];
        $stmt->bind_param('i', $patientId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Get all active (non-deleted) allergens in the catalogue.
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
     * Soft-delete an allergen (sets deleted_at).
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
