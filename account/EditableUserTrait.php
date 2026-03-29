<?php

namespace account;

trait EditableUserTrait
{
    /**
     * Soft deletes a user by executing a stored procedure.
     *
     * @param int $userId The unique identifier of the user to be soft deleted.
     * @return bool Returns true if the user was successfully soft deleted, or false otherwise.
     */
    public function softDeleteUser(int $userId): bool
    {
        $sql = "CALL soft_delete_user(?)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
}
