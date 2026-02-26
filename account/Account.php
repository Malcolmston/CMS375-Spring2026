<?php

namespace account;

use Connect;
use DateTime;

/**
 * Account management class
 */
abstract class Account extends Connect
{
    protected int $id;
    protected string $firstName;
    protected string $lastName;
    protected string $middleName;
    protected suffix $suffix;
    protected prefix $prefix;
    protected string $gender;
    protected string $phone;
    protected string $location;
    protected string $email;
    protected int $age;
    protected string $password;
    protected role $role;
    protected string $status;

    protected DateTime $createdAt;
    protected DateTime $updatedAt;
    protected DateTime $deletedAt;

    protected bool $isDeleted;

    /**
     * Encrypts the provided plain-text password using the bcrypt hashing algorithm.
     *
     * @param string $password The plain-text password to be encrypted.
     * @return string The hashed password.
     */
    public static function encryptPassword (string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verifies if the provided password matches the given hashed value.
     *
     * @param string $password The plain-text password to be verified.
     * @param string $hash The hashed password to compare against.
     * @return bool Returns true if the password matches the hash, false otherwise.
     */
    public static function verifyPassword (string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Retrieves a user record by its unique identifier.
     *
     * @param int $id The unique identifier of the user to retrieve.
     * @return static Returns an instance of the class populated with the user's data.
     * @throws \DateMalformedStringException
     */
    public static function getUserById(int $id): static
    {
        $instance = new static();
        $sql = "SELECT * FROM view_user_roles WHERE id = ? LIMIT 1";
        $stmt = $instance->getConnection()->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        $instance->id         = $row['id'];
        $instance->firstName  = $row['first_name'];
        $instance->lastName   = $row['last_name'];
        $instance->middleName = $row['middle_name'];
        $instance->suffix     = suffix::from($row['suffix']);
        $instance->prefix     = prefix::from($row['prefix']);
        $instance->gender     = $row['gender'];
        $instance->phone      = $row['phone'];
        $instance->location   = $row['location'];
        $instance->email      = $row['email'];
        $instance->age        = (int) $row['age'];
        $instance->password   = $row['password'];
        $instance->role       = role::from($row['role']);
        $instance->status     = $row['status'];
        $instance->createdAt  = new DateTime($row['created_at']);
        $instance->updatedAt  = new DateTime($row['updated_at']);
        $instance->deletedAt  = new DateTime($row['deleted_at']);
        $instance->isDeleted  = (bool) $row['is_deleted'];

        return $instance;
    }

    /**
     * Determines whether a user exists based on the specified criteria.
     *
     * @return bool Returns true if a user exists, otherwise false.
     */
    protected function hasUser (): bool
    {
       $sql = "CALL has_user(?)";
       $stmt = $this->getConnection()->prepare($sql);
       $stmt->bind_param("i", $this->id);
       $stmt->execute();
       $result = $stmt->get_result();
       return $result->num_rows > 0;
    }

}
