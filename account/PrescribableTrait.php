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
}
