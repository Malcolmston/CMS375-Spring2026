<?php

namespace account;
interface Perscribable
{
    /**
     * Open a new prescription header for a patient.
     * Returns the new prescription ID on success, or false on failure.
     */
    public function createPrescription(
        int    $patient_id,
        ?int   $visit_id,
        string $notes,
        string $start_date,
        string $end_date
    ): int|false;

    /**
     * Add a medicine line item to an existing prescription.
     */
    public function addPrescriptionItem(
        int    $prescription_id,
        int    $medicine_id,
        string $route,
        string $dose,
        string $frequency,
        int    $duration_days,
        int    $refills,
        string $quantity
    ): bool;

    /**
     * Set a prescription's status to DISCONTINUED.
     */
    public function cancelPrescription(int $prescription_id): bool;

    /**
     * Extend a prescription by updating its end date and resetting status to ACTIVE.
     */
    public function renewPrescription(int $prescription_id, string $end_date): bool;

    /**
     * Get all prescriptions (with their items) written for a specific patient.
     */
    public function getPrescriptionsByPatient(int $patient_id): array;

    /**
     * Get all prescriptions written by this provider.
     */
    public function getMyPrescriptions(): array;
}
