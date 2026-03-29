<?php

namespace pharmaceutical;

require_once __DIR__ . '/Pharmaceutical.php';
require_once __DIR__ . '/Medicine.php';
require_once __DIR__ . '/Vaccine.php';

/**
 * DrugInteraction class - handles drug_interaction table
 */
class DrugInteraction extends Pharmaceutical
{
    protected string $agent1Type;      // 'medicine' or 'vaccine'
    protected int $agent1Id;
    protected string $agent2Type;
    protected int $agent2Id;
    protected string $severity;        // severity level
    protected string $description;
    protected string $recommendation;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check interaction between two medicines
     */
    public static function checkMedicineInteraction(Medicine $med1, Medicine $med2): ?array
    {
        $instance = new static();

        $sql = "SELECT check_interaction('medicine', ?, 'medicine', ?) AS result";

        $stmt = $instance->getConnection()->prepare($sql);
        $id1 = $med1->getId();
        $id2 = $med2->getId();
        $stmt->bind_param('ii', $id1, $id2);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $data = json_decode($row['result'], true);
        return !empty($data) ? $data : null;
    }

    /**
     * Check interaction between a medicine and a vaccine
     */
    public static function checkMedicineVaccineInteraction(Medicine $med, Vaccine $vaccine): ?array
    {
        $instance = new static();

        $medId = $med->getId();
        $vaccineId = $vaccine->getId();
        $sql = "SELECT check_interaction('medicine', ?, 'vaccine', ?) AS result";

        $stmt = $instance->getConnection()->prepare($sql);
        $stmt->bind_param('ii', $medId, $vaccineId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $data = json_decode($row['result'], true);
        return !empty($data) ? $data : null;
    }

    /**
     * Check interaction between two vaccines
     */
    public static function checkVaccineInteraction(Vaccine $vacc1, Vaccine $vacc2): ?array
    {
        $instance = new static();

        $id1 = $vacc1->getId();
        $id2 = $vacc2->getId();
        $sql = "SELECT check_vaccine_interaction(?, ?) AS result";

        $stmt = $instance->getConnection()->prepare($sql);
        $stmt->bind_param('ii', $id1, $id2);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $data = json_decode($row['result'], true);
        return !empty($data) ? $data : null;
    }

    /**
     * Get all interactions for a medicine
     */
    public static function getInteractionsForMedicine(Medicine $med): array
    {
        $instance = new static();
        $conn = $instance->getConnection();
        $medId = $med->getId();
        $sql = "SELECT get_interactions_for_medicine(?) AS result";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $medId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return json_decode($row['result'], true) ?? [];
    }

    /**
     * Get all interactions for a vaccine
     */
    public static function getInteractionsForVaccine(Vaccine $vaccine): array
    {
        $instance = new static();
        $conn = $instance->getConnection();
        $vaccineId = $vaccine->getId();
        $sql = "SELECT get_interactions_for_vaccine(?) AS result";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $vaccineId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return json_decode($row['result'], true) ?? [];
    }

    // Getters
    public function getAgent1Type(): string { return $this->agent1Type; }
    public function getAgent1Id(): int { return $this->agent1Id; }
    public function getAgent2Type(): string { return $this->agent2Type; }
    public function getAgent2Id(): int { return $this->agent2Id; }
    public function getSeverity(): string { return $this->severity; }
    public function getDescription(): string { return $this->description; }
    public function getRecommendation(): string { return $this->recommendation; }

    // Setters
    public function setAgent1Type(string $type): void { $this->agent1Type = $type; }
    public function setAgent1Id(int $id): void { $this->agent1Id = $id; }
    public function setAgent2Type(string $type): void { $this->agent2Type = $type; }
    public function setAgent2Id(int $id): void { $this->agent2Id = $id; }
    public function setSeverity(string $severity): void { $this->severity = $severity; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setRecommendation(string $recommendation): void { $this->recommendation = $recommendation; }
}
