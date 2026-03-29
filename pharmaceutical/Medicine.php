<?php

namespace pharmaceutical;

require_once __DIR__ . '/Pharmaceutical.php';
require_once __DIR__ . '/Form.php';
require_once __DIR__ . '/Store.php';

/**
 * Medicine class - extends Pharmaceutical
 */
class Medicine extends Pharmaceutical
{
    protected string $genericName;
    protected string $brandName;
    protected string $drugClass;
    protected string $form;
    protected string $standardDose;
    protected bool $controlledSubstance;
    protected bool $requiresPrescription;
    protected int $stockQuantity;
    protected string $unitOfMeasure;
    protected string $storageRequirements;

    public function __construct()
    {
        parent::__construct();
    }

    // Enum methods for form
    public static function getFormEnum(): array
    {
        return array_map(fn($c) => $c->value, Form::cases());
    }

    public static function isValidForm(string $form): bool
    {
        return in_array($form, array_map(fn($c) => $c->value, Form::cases()));
    }

    // Enum methods for storage
    public static function getStorageEnum(): array
    {
        return array_map(fn($c) => $c->value, Store::cases());
    }

    public static function isValidStorage(string $storage): bool
    {
        return in_array($storage, array_map(fn($c) => $c->value, Store::cases()));
    }

    /**
     * Get medicine by generic name
     */
    public function getByGenericName(string $genericName): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT get_medicines_by_class(?) AS result"
        );
        $stmt->bind_param('s', $genericName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && $row['result']) {
            return json_decode($row['result'], true) ?: [];
        }
        return [];
    }

    /**
     * Get medicines by drug class
     */
    public function getByDrugClass(string $drugClass): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT get_medicines_by_class(?) AS result"
        );
        $stmt->bind_param('s', $drugClass);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && $row['result']) {
            return json_decode($row['result'], true) ?: [];
        }
        return [];
    }

    /**
     * Get medicines by form
     */
    public function getByForm(string $form): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT get_medicines_by_form(?) AS result"
        );
        $stmt->bind_param('s', $form);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && $row['result']) {
            return json_decode($row['result'], true) ?: [];
        }
        return [];
    }

    /**
     * Get controlled substances
     */
    public function getControlledSubstances(): array
    {
        $result = $this->getConnection()->query(
            "SELECT * FROM medicine WHERE controlled_substance = TRUE AND deleted_at IS NULL"
        );

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Get low stock medicines
     */
    public function getLowStock(int $threshold = 100): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT check_low_stock(?) AS count"
        );
        $stmt->bind_param('i', $threshold);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return [(int) $row['count']];
    }

    /**
     * Search medicines by name using SQL function
     */
    public function search(string $searchTerm): array
    {
        $stmt = $this->getConnection()->prepare("SELECT search_medicine(?) AS result");
        $stmt->bind_param('s', $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($result->num_rows > 0) {
            return json_decode($row['result'], true) ?: [];
        }
        return [];
    }

    /**
     * Update stock quantity
     */
    public function updateStock(int $id, int $quantity): bool
    {
        $stmt = $this->getConnection()->prepare("CALL set_stock(?, ?)");
        $stmt->bind_param('ii', $id, $quantity);
        return $stmt->execute();
    }

    /**
     * Create new medicine
     */
    public function create(array $data): int
    {
        $stmt = $this->getConnection()->prepare(
            "CALL insert_medicine(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            'ssssssiiiss',
            $data['generic_name'],
            $data['brand_name'],
            $data['drug_class'],
            $data['form'],
            $data['standard_dose'],
            $data['controlled_substance'],
            $data['requires_prescription'],
            $data['stock_quantity'],
            $data['unit_of_measure'],
            $data['manufacturer'],
            $data['storage_requirements']
        );

        $stmt->execute();
        return (int) $this->getConnection()->insert_id;
    }

    /**
     * Batch insert medicines using stored procedure
     * @param array $medicines Array of medicine data arrays
     */
    public function createBatch(array $medicines): bool
    {
        $json = json_encode($medicines);
        $stmt = $this->getConnection()->prepare("CALL insert_medicine_batch(?)");
        $stmt->bind_param('s', $json);
        return $stmt->execute();
    }

    /**
     * Update medicine
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->getConnection()->prepare(
            "CALL update_medicine(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            'issssssiiiss',
            $id,
            $data['generic_name'],
            $data['brand_name'],
            $data['drug_class'],
            $data['form'],
            $data['standard_dose'],
            $data['controlled_substance'],
            $data['requires_prescription'],
            $data['stock_quantity'],
            $data['unit_of_measure'],
            $data['manufacturer'],
            $data['storage_requirements']
        );

        return $stmt->execute();
    }

    /**
     * Hard delete medicine using stored procedure
     */
    public function hardDelete(int $id): bool
    {
        $stmt = $this->getConnection()->prepare("CALL hard_delete_medicine(?)");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function getById(int $id): Medicine|null
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM view_active_medicines WHERE id = ? LIMIT 1"
        );
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return null;

        $this->id                   = $row['id'];
        $this->genericName          = $row['generic_name'];
        $this->brandName            = $row['brand_name'];
        $this->drugClass            = $row['drug_class'];
        $this->form                 = $row['form'];
        $this->standardDose         = $row['standard_dose'];
        $this->controlledSubstance  = (bool) $row['controlled_substance'];
        $this->requiresPrescription = (bool) $row['requires_prescription'];
        $this->stockQuantity        = (int)  $row['stock_quantity'];
        $this->unitOfMeasure        = $row['unit_of_measure'];
        $this->manufacturer         = $row['manufacturer'];
        $this->storageRequirements  = $row['storage_requirements'];
        $this->createdAt            = $row['created_at'];
        $this->updatedAt            = $row['updated_at'];
        $this->deletedAt            = $row['deleted_at'];

        return $this;
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->getConnection()->prepare("CALL soft_delete_medicine(?)");
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    // Getters
    public function getGenericName(): string { return $this->genericName; }
    public function getBrandName(): string { return $this->brandName; }
    public function getDrugClass(): string { return $this->drugClass; }
    public function getForm(): string { return $this->form; }
    public function getStandardDose(): string { return $this->standardDose; }
    public function isControlledSubstance(): bool { return $this->controlledSubstance; }
    public function requiresPrescription(): bool { return $this->requiresPrescription; }
    public function getStockQuantity(): int { return $this->stockQuantity; }
    public function getUnitOfMeasure(): string { return $this->unitOfMeasure; }
    public function getStorageRequirements(): string { return $this->storageRequirements; }
}
