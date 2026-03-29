<?php

namespace pharmaceutical;

require_once __DIR__ . '/Pharmaceutical.php';
require_once __DIR__ . '/Type.php';
require_once __DIR__ . '/Stage.php';

/**
 * Vaccine class - extends Pharmaceutical
 */
class Vaccine extends Pharmaceutical
{
    protected string $cvxCode;
    protected string $status;
    protected ?string $lastUpdatedDate;
    protected string $type;
    protected string $development;
    protected ?string $recommendedAge;
    protected ?int $doseCount;
    protected ?float $lethalDoseMgPerKg;
    protected ?string $lethalDoseRoute;
    protected ?string $lethalDoseSource;
    protected ?string $extra;

    /**
     * @throws \Exception
     */
    public function __construct(
        string $name,
        string $manufacturer,
        string $cvxCode,
        string $status,
        string $type,
        string $development,
        ?string $recommendedAge = null,
        ?int $doseCount = null
    ) {
        parent::__construct();
        $this->name = $name;
        $this->manufacturer = $manufacturer;
        $this->cvxCode = $cvxCode;
        $this->status = $status;
        $this->type = $type;
        $this->development = $development;
        $this->recommendedAge = $recommendedAge;
        $this->doseCount = $doseCount;
    }

    // ============================================================
    // ENUM Methods
    // ============================================================

    /**
     * Get all vaccine type enum values
     *
     * @return string[]
     */
    public static function getTypes(): array
    {
        return array_map(fn($case) => $case->value, Type::cases());
    }

    /**
     * Get all development stage enum values
     *
     * @return string[]
     */
    public static function getStages(): array
    {
        return array_map(fn($case) => $case->value, Stage::cases());
    }

    /**
     * Validate type value
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, self::getTypes(), true);
    }

    /**
     * Validate stage value
     */
    public static function isValidStage(string $stage): bool
    {
        return in_array($stage, self::getStages(), true);
    }


    /**
     * Get vaccine by ID using stored procedure
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->getConnection()->prepare("CALL get_vaccine_by_id(?)");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return $row;
        }
        return null;
    }

    /**
     * Get vaccine by CVX code
     */
    public function getByCvxCode(string $cvxCode): ?array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT get_vaccine_by_cvx(?) AS vaccine"
        );
        $stmt->bind_param('s', $cvxCode);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $jsonData = $row['vaccine'];
            if ($jsonData) {
                return json_decode($jsonData, true);
            }
        }
        return null;
    }

    /**
     * Get vaccines by type
     */
    public function getByType(string $type): array
    {
        $stmt = $this->getConnection()->prepare("CALL list_vaccines_by_type(?)");
        $stmt->bind_param('s', $type);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Get vaccines by development status
     */
    public function getByDevelopment(string $development): array
    {
        $stmt = $this->getConnection()->prepare("CALL list_vaccines_by_development(?)");
        $stmt->bind_param('s', $development);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Get active vaccines (not discontinued)
     */
    public function getActive(): array
    {
        $result = $this->getConnection()->query(
            "SELECT * FROM view_active_out_vaccines ORDER BY name"
        );

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Get discontinued vaccines
     */
    public function getDiscontinued(): array
    {
        $result = $this->getConnection()->query(
            "SELECT * FROM view_discontinued_vaccines ORDER BY name"
        );

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Get all vaccines using stored procedure
     */
    public function getAll(): array
    {
        $result = $this->getConnection()->query("CALL list_vaccines()");

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Get vaccines by recommended age
     */
    public function getByRecommendedAge(string $age): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM view_active_vaccines WHERE recommended_age = ?"
        );
        $stmt->bind_param('s', $age);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Search vaccines by name
     */
    public function search(string $searchTerm): array
    {
        $searchTerm = "%{$searchTerm}%";
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM view_active_vaccines WHERE name LIKE ?;"
        );
        $stmt->bind_param('s', $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Count vaccines by type
     */
    public function countByType(string $type): int
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM view_vaccine_count WHERE type = ?"
        );
        $stmt->bind_param('s', $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (int) $row['count'];
    }

    /**
     * Count vaccines by development
     */
    public function countByDevelopment(string $development): int
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM view_vaccine_count_by_development WHERE development = ?"
        );
        $stmt->bind_param('s', $development);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (int) $row['count'];
    }

    /**
     * Create new vaccine using stored procedure
     */
    public function create(array $data): int
    {
        $stmt = $this->getConnection()->prepare("CALL create_vaccine(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            'sssssssiidss',
            $data['name'],
            $data['cvx_code'],
            $data['status'],
            $data['last_updated_date'],
            $data['manufacturer'],
            $data['type'],
            $data['development'],
            $data['recommended_age'],
            $data['dose_count'],
            $data['lethal_dose_mg_per_kg'],
            $data['lethal_dose_route'],
            $data['lethal_dose_source'],
            $data['extra']
        );

        $stmt->execute();
        return (int) $this->getConnection()->insert_id;
    }

    /**
     * Update vaccine using stored procedure
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->getConnection()->prepare("CALL update_vaccine(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            'issssssssi',
            $id,
            $data['name'],
            $data['cvx_code'],
            $data['status'],
            $data['last_updated_date'],
            $data['manufacturer'],
            $data['type'],
            $data['development'],
            $data['recommended_age'],
            $data['dose_count']
        );

        return $stmt->execute();
    }

    /**
     * Check if vaccine is active
     */
    public function isActive(int $id): bool
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT id FROM vaccine WHERE id = ? AND development = 'RELEASED' AND status = 'Active' AND deleted_at IS NULL"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    /**
     * Hard delete vaccine using stored procedure
     */
    public function hardDelete(int $id): bool
    {
        $stmt = $this->getConnection()->prepare("CALL hard_delete_vaccine(?)");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    /**
     * Deactivate a vaccine without losing data (sets deleted_at).
     */
    public function softDelete(int $id): bool
    {
        $stmt = $this->getConnection()->prepare("CALL soft_delete_vaccine(?)");
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    /**
     * Re-activate a soft-deleted vaccine (clears deleted_at).
     */
    public function restore(int $id): bool
    {
        $stmt = $this->getConnection()->prepare("CALL restore_vaccine(?)");
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    /**
     * Guard before prescribing — confirm vaccine exists and is not deleted.
     */
    public function hasVaccine(int $id): bool
    {
        $stmt = $this->getConnection()->prepare("SELECT has_vaccine(?) AS result");
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (bool)($row['result'] ?? false);
    }

    /**
     * Bulk-import vaccines from a JSON array.
     */
    public function insertBatch(array $vaccines): bool
    {
        $json = json_encode($vaccines);
        $stmt = $this->getConnection()->prepare("CALL insert_vaccine_batch(?)");
        if (!$stmt) return false;
        $stmt->bind_param('s', $json);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // Getters
    public function getCvxCode(): string { return $this->cvxCode; }
    public function getStatus(): string { return $this->status; }
    public function getLastUpdatedDate(): ?string { return $this->lastUpdatedDate; }
    public function getType(): string { return $this->type; }
    public function getDevelopment(): string { return $this->development; }
    public function getRecommendedAge(): ?string { return $this->recommendedAge; }
    public function getDoseCount(): ?int { return $this->doseCount; }
    public function getLethalDoseMgPerKg(): ?float { return $this->lethalDoseMgPerKg; }
    public function getLethalDoseRoute(): ?string { return $this->lethalDoseRoute; }
    public function getLethalDoseSource(): ?string { return $this->lethalDoseSource; }
    public function getExtra(): ?string { return $this->extra; }

    /**
     * Check if this vaccine is active (RELEASED and Active status)
     */
    public function isActiveVaccine(): bool
    {
        return $this->development === 'RELEASED' && $this->status === 'Active';
    }

    /**
     * Check if this vaccine is discontinued
     */
    public function isDiscontinued(): bool
    {
        return $this->development === 'DISCONTINUED';
    }
}
