<?php

namespace account;

require_once __DIR__ . '/../Connect.php';

use Connect;
use AllowDynamicProperties;

/**
 * Base Pharmaceutical class - parent for Medicine and Vaccine
 */
#[AllowDynamicProperties]
abstract class Pharmaceutical extends Connect
{
    protected int $id;
    protected string $name;
    protected string $manufacturer;
    protected string $storageRequirements;
    protected string $createdAt;
    protected string $updatedAt;
    protected ?string $deletedAt;

    public function __construct()
    {
        parent::__construct();
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getManufacturer(): string { return $this->manufacturer; }
    public function getStorageRequirements(): string { return $this->storageRequirements; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getUpdatedAt(): string { return $this->updatedAt; }
    public function getDeletedAt(): ?string { return $this->deletedAt; }
    public function isDeleted(): bool { return $this->deletedAt !== null; }
}
