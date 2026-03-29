<?php

namespace account;

require_once __DIR__ . '/../Connect.php';
require_once __DIR__ . '/InstitutionType.php';

use Connect;

class Institution extends Connect
{
    public int $id;
    public string $name;
    public InstitutionType $type;
    public ?string $phone;
    public ?string $email;
    public ?string $address;
    public string $createdAt;
    public string $updatedAt;
    public ?string $deletedAt;

    public function __construct()
    {
        parent::__construct();
    }

    public static function getById(int $id): ?Institution
    {
        $inst = new Institution();
        $stmt = $inst->getConnection()->prepare(
            "SELECT id, name, institution_type, phone, email, address, created_at, updated_at, deleted_at
             FROM institution WHERE id = ? AND deleted_at IS NULL LIMIT 1"
        );
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return null;

        $inst->id        = $row['id'];
        $inst->name      = $row['name'];
        $inst->type      = InstitutionType::from($row['institution_type']);
        $inst->phone     = $row['phone'];
        $inst->email     = $row['email'];
        $inst->address   = $row['address'];
        $inst->createdAt = $row['created_at'];
        $inst->updatedAt = $row['updated_at'];
        $inst->deletedAt = $row['deleted_at'];

        return $inst;
    }

    public static function getAll(): array
    {
        $inst = new Institution();
        $stmt = $inst->getConnection()->prepare(
            "SELECT * FROM view_active_institutions"
        );
        if (!$stmt) return [];
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}
