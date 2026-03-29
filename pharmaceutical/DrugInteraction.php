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
        $conn = self::getConnection();
        $stmt = $conn->prepare("
            SELECT severity, description, recommendation
            FROM drug_interaction
            WHERE agent_1_type = 'medicine'
              AND agent_1_id = ?
              AND agent_2_type = 'medicine'
              AND agent_2_id = ?
              AND deleted_at IS NULL
        ");
        $id1 = $med1->getId();
        $id2 = $med2->getId();
        // Ensure consistent order
        if ($id1 > $id2) {
            [$id1, $id2] = [$id2, $id1];
        }
        $stmt->bind_param('ii', $id1, $id2);
        $stmt->execute();
        $result = $stmt->get_result();
        $interaction = $result->fetch_assoc();
        $stmt->close();
        return $interaction ?: null;
    }

    /**
     * Check interaction between a medicine and a vaccine
     */
    public static function checkMedicineVaccineInteraction(Medicine $med, Vaccine $vaccine): ?array
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare("
            SELECT severity, description, recommendation
            FROM drug_interaction
            WHERE (agent_1_type = 'medicine' AND agent_1_id = ? AND agent_2_type = 'vaccine' AND agent_2_id = ?)
               OR (agent_1_type = 'vaccine' AND agent_1_id = ? AND agent_2_type = 'medicine' AND agent_2_id = ?)
              AND deleted_at IS NULL
        ");
        $medId = $med->getId();
        $vaccineId = $vaccine->getId();
        $stmt->bind_param('iiii', $medId, $vaccineId, $vaccineId, $medId);
        $stmt->execute();
        $result = $stmt->get_result();
        $interaction = $result->fetch_assoc();
        $stmt->close();
        return $interaction ?: null;
    }

    /**
     * Check interaction between two vaccines
     */
    public static function checkVaccineInteraction(Vaccine $vacc1, Vaccine $vacc2): ?array
    {
        $conn = self::getConnection();
        $id1 = $vacc1->getId();
        $id2 = $vacc2->getId();
        if ($id1 > $id2) {
            [$id1, $id2] = [$id2, $id1];
        }
        $stmt = $conn->prepare("
            SELECT severity, description, recommendation
            FROM drug_interaction
            WHERE agent_1_type = 'vaccine'
              AND agent_1_id = ?
              AND agent_2_type = 'vaccine'
              AND agent_2_id = ?
              AND deleted_at IS NULL
        ");
        $stmt->bind_param('ii', $id1, $id2);
        $stmt->execute();
        $result = $stmt->get_result();
        $interaction = $result->fetch_assoc();
        $stmt->close();
        return $interaction ?: null;
    }

    /**
     * Get all interactions for a medicine
     */
    public static function getInteractionsForMedicine(Medicine $med): array
    {
        $conn = self::getConnection();
        $medId = $med->getId();
        $stmt = $conn->prepare("
            SELECT di.*,
                   m1.generic_name AS agent_1_name,
                   m2.generic_name AS agent_2_name
            FROM drug_interaction di
            LEFT JOIN medicine m1 ON di.agent_1_type = 'medicine' AND di.agent_1_id = m1.id
            LEFT JOIN medicine m2 ON di.agent_2_type = 'medicine' AND di.agent_2_id = m2.id
            WHERE (di.agent_1_type = 'medicine' AND di.agent_1_id = ?)
               OR (di.agent_2_type = 'medicine' AND di.agent_2_id = ?)
              AND di.deleted_at IS NULL
        ");
        $stmt->bind_param('ii', $medId, $medId);
        $stmt->execute();
        $result = $stmt->get_result();
        $interactions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $interactions;
    }

    /**
     * Get all interactions for a vaccine
     */
    public static function getInteractionsForVaccine(Vaccine $vaccine): array
    {
        $conn = self::getConnection();
        $vaccineId = $vaccine->getId();
        $stmt = $conn->prepare("
            SELECT * FROM view_all_interactions
                WHERE (agent_1_type = 'vaccine' AND agent_1_id = ?)
                   OR (agent_2_type = 'vaccine' AND agent_2_id = ?)
        ");
        $stmt->bind_param('ii', $vaccineId, $vaccineId);
        $stmt->execute();
        $result = $stmt->get_result();
        $interactions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $interactions;
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
