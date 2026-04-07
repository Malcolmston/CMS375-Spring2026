<?php

namespace services;

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

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
    }

    public static function getById(int $id): ?Institution
    {
        $inst = new Institution();
        $stmt = $inst->getConnection()->prepare(
            "SELECT * FROM view_active_institutions WHERE id = ? LIMIT 1"
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

    /**
     * Look up a user's ID by their employee ID string.
     * Returns null if no matching active user is found.
     */
    public static function findUserIdByEmployeeId(string $employId): ?int
    {
        $inst = new Institution();
        $stmt = $inst->getConnection()->prepare(
            "SELECT id FROM view_users WHERE employid = ? LIMIT 1"
        );
        if (!$stmt) return null;
        $stmt->bind_param('s', $employId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? (int) $row['id'] : null;
    }

    /**
     * Get all staff members assigned to this institution.
     */
    public function getStaff(): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM view_staff_by_institution WHERE institution_id = ?"
        );
        if (!$stmt) return [];
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Get recent patient visits at this institution.
     */
    public function getRecentVisits(int $limit = 25): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM view_visits WHERE institution_id = ? ORDER BY scheduled_at DESC LIMIT ?"
        );
        if (!$stmt) return [];
        $stmt->bind_param('ii', $this->id, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}
