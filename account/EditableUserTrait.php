<?php

namespace account;

require_once __DIR__ . '/blood.php';
require_once __DIR__ . '/prefix.php';
require_once __DIR__ . '/suffix.php';

trait EditableUserTrait
{
    /**
     * Soft-delete a user account by ID (sets deleted_at, preserves all data).
     * Admin: pass any userId. Patient: pass $this->id for self-deletion.
     */
    public function softDeleteUser(int $userId): bool
    {
        $stmt = $this->getConnection()->prepare("CALL soft_delete_user(?)");
        if (!$stmt) return false;
        $stmt->bind_param("i", $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Update any user's profile fields.
     * Admin: pass any userId. Patient: pass $this->id to edit own profile.
     *
     * @param int     $userId
     * @param string  $firstName
     * @param string  $lastName
     * @param string  $middleName
     * @param prefix  $prefix
     * @param suffix|null $suffix
     * @param string  $gender
     * @param string  $phone
     * @param float   $locX      Longitude (WGS-84)
     * @param float   $locY      Latitude  (WGS-84)
     * @param string  $email
     * @param int     $age
     * @param blood   $blood
     * @param string|null $extra
     * @return bool
     */
    public function updateUserProfile(
        int     $userId,
        string  $firstName,
        string  $lastName,
        string  $middleName,
        prefix  $prefix,
        ?suffix $suffix,
        string  $gender,
        string  $phone,
        float   $locX,
        float   $locY,
        string  $email,
        int     $age,
        blood   $blood,
        ?string $extra = null
    ): bool
    {
        $prefixVal = $prefix->value;
        $suffixVal = $suffix?->value;
        $bloodVal  = $blood->value;

        $stmt = $this->getConnection()->prepare(
            "CALL update_user(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @p_success)"
        );
        if (!$stmt) return false;

        $stmt->bind_param(
            "isssssssddsiss",
            $userId,
            $firstName,
            $lastName,
            $middleName,
            $prefixVal,
            $suffixVal,
            $gender,
            $phone,
            $locX,
            $locY,
            $email,
            $age,
            $bloodVal,
            $extra
        );

        if (!$stmt->execute()) { $stmt->close(); return false; }
        $stmt->close();

        $row = $this->getConnection()->query("SELECT @p_success AS ok")->fetch_assoc();
        return (bool)($row['ok'] ?? false);
    }

    /**
     * Change the current user's own password.
     * Verifies the old password before applying the new one.
     * Uses $this->id and $this->password — self-only operation.
     *
     * @param string $old  Plain-text current password
     * @param string $new  Plain-text new password
     * @return bool
     */
    public function changePassword(string $old, string $new): bool
    {
        if (!self::verifyPassword($old, $this->password)) return false;

        $hash = self::encryptPassword($new);
        $stmt = $this->getConnection()->prepare(
            "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL"
        );
        if (!$stmt) return false;
        $stmt->bind_param("si", $hash, $this->id);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        if ($ok) $this->password = $hash;
        return $ok;
    }
}
