<?php

namespace account;

use pharmaceutical\Medicine;
use pharmaceutical\Vaccine;

interface Perscribable
{
    /**
     * Open a new prescription header for a patient.
     * Returns the new prescription ID on success, or false on failure.
     */
    public function createPrescription(
        int    $patient_id,
        string $notes,
        string $issue_date,
        string $expire_date
    ): int|false;

    /**
     * Add a medicine line item to an existing prescription.
     */
    public function addPrescriptionItem(
        int      $prescription_id,
        Medicine $medicine,
        string   $route,
        string   $dosage,
        string   $frequency,
        int      $duration_days,
        int      $quantity_prescribed,
        ?string  $instructions = null
    ): bool;

    /**
     * Add a vaccine line item to an existing prescription.
     */
    public function addVaccineItem(
        int     $prescription_id,
        Vaccine $vaccine,
        string  $route,
        string  $dosage,
        string  $frequency,
        int     $duration_days,
        int     $quantity_prescribed,
        ?string $instructions = null
    ): bool;

    /**
     * Set a prescription's status to cancelled.
     */
    public function cancelPrescription(int $prescription_id): bool;

    /**
     * Extend a prescription by updating its expire_date and resetting status to active.
     */
    public function renewPrescription(int $prescription_id, string $expire_date): bool;

    /**
     * Get all prescriptions (with their items) written for a specific patient.
     */
    public function getPrescriptionsByPatient(int $patient_id): array;

    /**
     * Get all prescriptions written by this provider.
     */
    public function getMyPrescriptions(): array;
}
