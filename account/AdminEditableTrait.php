<?php

namespace account;

trait AdminEditableTrait
{
    /**
     * Restore a soft-deleted user account (clears deleted_at).
     * Admin only — throws a DB-level error if user does not exist or is not deleted.
     */
    public function restoreUser(int $userId): bool
    {
        $stmt = $this->getConnection()->prepare("CALL restore_user(?)");
        if (!$stmt) return false;
        $stmt->bind_param("i", $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Permanently remove a user and all their data. Irreversible.
     * Admin only — procedure sets @hard_delete_user = TRUE on success, NULL on failure.
     */
    public function hardDeleteUser(int $userId): bool
    {
        $stmt = $this->getConnection()->prepare("CALL hard_delete_user(?)");
        if (!$stmt) return false;
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) { $stmt->close(); return false; }
        $stmt->close();
        $row = $this->getConnection()->query("SELECT @hard_delete_user AS ok")->fetch_assoc();
        return isset($row['ok']) && $row['ok'] !== null;
    }
}
