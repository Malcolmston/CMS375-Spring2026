<?php

namespace account;

use Connect;

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
}
