<?php

namespace account;

require_once __DIR__ . '/Patient.php';
require_once __DIR__ . '/ParentRelationship.php';

class Guardian extends Patient
{
    private ParentRelationship $relationship;

    public function __construct()
    {
        parent::__construct();
    }

    public function getRelationship(): ParentRelationship
    {
        return $this->relationship;
    }

    public function setRelationship(ParentRelationship $relationship): void
    {
        $this->relationship = $relationship;
    }

    /**
     * Link a patient to this guardian in the parent_relationship table.
     *
     * @param int                $patientId   The patient (child) user ID
     * @param ParentRelationship $relationship Mother / Father / Legal Guardian
     * @return bool
     */
    public function addChild(int $patientId, ParentRelationship $relationship): bool
    {
        $relVal = $relationship->value;
        $stmt = $this->getConnection()->prepare("CALL create_parent_relationship(?, ?, ?)");
        if (!$stmt) return false;
        $stmt->bind_param('iis', $this->id, $patientId, $relVal);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Get all patients this guardian is responsible for.
     *
     * @return Patient[]
     */
    public function getMyPatients(): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT patient_id FROM parent_relationship WHERE parent_id = ?"
        );
        if (!$stmt) return [];
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return array_map(
            fn($row) => Patient::getUserById($row['patient_id']),
            $rows
        );
    }

    /**
     * Get all guardians registered for a given patient.
     *
     * @param int $patientId The patient's user ID
     * @return Guardian[]
     * @throws \DateMalformedStringException
     */
    public function getGuardiansForPatient(int $patientId): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT parent_id FROM parent_relationship WHERE patient_id = ?"
        );
        if (!$stmt) return [];
        $stmt->bind_param('i', $patientId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return array_map(
            fn($row) => Guardian::getUserById($row['parent_id']),
            $rows
        );
    }
}
