<?php

interface Diagnosible
{
    /**
     * Record a new diagnosis for a patient during a visit.
     */
    public function diagnose(
        int    $patient_id,
        //int    $visit_id,
        string $condition,
        string $severity,
        string $notes
    ): bool;

    /**
     * Update the severity or notes on an existing diagnosis.
     */
    public function updateDiagnosis(int $diagnosis_id, string $severity, string $notes): bool;

    /**
     * Get all diagnoses recorded for a specific patient.
     */
    public function getDiagnosesByPatient(int $patient_id): array;

    /**
     * Get all diagnoses recorded by this provider.
     */
    //public function getMyDiagnoses(): array;
}
