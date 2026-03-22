<?php

namespace account;

trait DiagnosibleTrait
{
    public function diagnose(
        int    $patient_id,
        string $condition,
        string $severity,
        string $notes
    ): bool {
        $sql = "CALL create_diagnosis(?, ?, ?, ?, @p_diagnosis_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issis', $patient_id, $condition, $severity, $notes);
        $stmt->execute();
        $stmt->close();
        return $stmt->affected_rows > 0;
    }

    public function updateDiagnosis(int $diagnosis_id, string $severity, string $notes): bool
    {
        $sql = "UPDATE diagnosis SET severity = ?, notes = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssi', $severity, $notes, $diagnosis_id);
        $stmt->execute();
        $stmt->close();
        return $stmt->affected_rows > 0;
    }

    public function getDiagnosesByPatient(int $patient_id): array
    {
        $sql = "SELECT my_diagnosis(?) AS diagnoses";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $patient_id);
        $stmt->execute();
        $stmt->bind_result($json);
        $stmt->fetch();
        $stmt->close();
        return json_decode($json, true) ?? [];
    }
}
