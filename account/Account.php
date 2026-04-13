<?php

namespace account;

require_once __DIR__ . '/../Connect.php';
require_once __DIR__ . '/../Point.php';
require_once __DIR__ . '/blood.php';
require_once __DIR__ . '/prefix.php';
require_once __DIR__ . '/suffix.php';
require_once __DIR__ . '/role.php';

use AllowDynamicProperties;
use Connect;
use DateTime;
use Point;

/**
 * Account management class
 */
#[AllowDynamicProperties]
abstract class Account extends Connect
{
    protected int $id;
    protected string $firstName;
    protected string $lastName;
    protected ?string $middleName;
    protected ?suffix $suffix;
    protected prefix $prefix;
    protected string $gender;
    protected string $phone;
    protected Point $location;
    protected string $email;
    protected int $age;
    protected blood $blood;
    protected string $password;
    protected string $extra;
    protected role $role;
    protected string $status;
    protected ?string $employid = null;
    protected ?string $adminid  = null;

    protected DateTime $createdAt;
    protected DateTime $updatedAt;
    protected DateTime $deletedAt;

    protected bool $isDeleted;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function getSuffix(): ?suffix
    {
        return $this->suffix;
    }

    public function getPrefix(): prefix
    {
        return $this->prefix;
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getLocation(): Point
    {
        return $this->location;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function getBlood(): blood
    {
        return $this->blood;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getExtra(): string
    {
        return $this->extra;
    }

    public function getRole(): role
    {
        return $this->role;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getEmployid(): ?string
    {
        return $this->employid;
    }

    public function getAdminid(): ?string
    {
        return $this->adminid;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): DateTime
    {
        return $this->deletedAt;
    }

    public function getIsDeleted(): bool
    {
        return $this->isDeleted;
    }
    /**
     * @throws \Exception iff SQL con is invalid
     */
    public function __construct(
        ?string $firstName  = null,
        ?string $lastName   = null,
        ?string $middleName = null,
        ?prefix $prefix     = null,
        ?suffix $suffix     = null,
        ?role   $role       = null,
        ?string $gender     = null,
        ?string $phone      = null,
        ?Point  $location   = null,
        ?string $email      = null,
        ?int    $age        = null,
        ?blood  $blood      = null,
        ?string $password   = null,
        ?string $extra      = null,
    ) {
        // Use singleton connection instead of creating new one
        $instance = \Connect::getInstance();
        $this->conn = $instance->getConnection();
        if ($firstName  !== null) $this->firstName  = $firstName;
        if ($lastName   !== null) $this->lastName   = $lastName;
        if ($middleName !== null) $this->middleName = $middleName;
        if ($prefix     !== null) $this->prefix     = $prefix;
        if ($suffix     !== null) $this->suffix     = $suffix;
        if ($role       !== null) $this->role       = $role;
        if ($gender     !== null) $this->gender     = $gender;
        if ($phone      !== null) $this->phone      = $phone;
        if ($location   !== null) $this->location   = $location;
        if ($email      !== null) $this->email      = $email;
        if ($age        !== null) $this->age        = $age;
        if ($blood      !== null) $this->blood      = $blood;
        if ($password   !== null) $this->password   = $password;
        if ($extra      !== null) $this->extra      = $extra;
    }

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
        $sql = "SELECT *, ST_X(location) AS loc_x, ST_Y(location) AS loc_y FROM view_user_roles WHERE id = ? LIMIT 1";
        $stmt = $instance->getConnection()->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        $instance->id         = $row['id'];
        $instance->firstName  = $row['firstname'];
        $instance->lastName   = $row['lastname'];
        $instance->middleName = $row['middlename'] ?? '';
        $instance->suffix     = suffix::tryFrom($row['suffix'] ?? '');
        $instance->prefix     = prefix::from($row['prefix']);
        $instance->gender     = $row['gender'];
        $instance->phone      = $row['phone'];
        $instance->location   = new Point((float) $row['loc_x'], (float) $row['loc_y']);
        $instance->email      = $row['email'];
        $instance->age        = (int) $row['age'];
        $instance->blood      = blood::from($row['blood']);
        $instance->extra      = $row['extra'] ?? '';
        $instance->employid   = $row['employid'] ?? null;
        $instance->adminid    = $row['adminid']  ?? null;
        $instance->role       = role::from($row['role']);
        $instance->status     = $row['status'];
        $instance->createdAt  = new DateTime($row['created_at']);
        $instance->updatedAt  = new DateTime($row['updated_at']);
        $instance->deletedAt  = new DateTime($row['deleted_at'] ?? 'now');
        $instance->isDeleted  = $row['deleted_at'] !== null;

        return $instance;
    }

    /**
     * Check if an active (non-deleted) user exists with the given email.
     *
     * @param string $email The email address to check.
     * @return bool True if an active user exists, false otherwise.
     */
    public static function emailExists(string $email): bool
    {
        $instance = new static();
        $stmt = $instance->getConnection()->prepare("SELECT user_exists_by_email(?) AS exists");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (bool) ($row['exists'] ?? false);
    }

    /**
     * Get a user's role by their ID.
     *
     * @param int $id The user ID.
     * @return string|null The role string (e.g., 'PATIENT'), or null if not found.
     */
    public static function getUserRole(int $id): ?string
    {
        $conn = \Connect::getInstance()->getConnection();
        $stmt = $conn->prepare("SELECT role FROM view_user_roles WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row['role'] ?? null;
    }

    /**
     * Get all active (non-deleted) institutions.
     *
     * @return array List of institutions.
     */
    public static function getAllInstitutions(): array
    {
        $instance = new static();
        $stmt = $instance->getConnection()->prepare("SELECT * FROM view_active_institutions ORDER BY name");
        $stmt->execute();
        $result = $stmt->get_result();
        $institutions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $institutions;
    }

    /**
     * Get nearest institutions to a given location.
     *
     * @param float $lat Latitude.
     * @param float $lng Longitude.
     * @param int $limit Max results.
     * @return array List of institutions with distance.
     */
    public static function getNearestInstitutions(float $lat, float $lng, int $limit = 100): array
    {
        $conn = \Connect::getInstance()->getConnection();
        $stmt = $conn->prepare("SELECT get_nearest_institutions(?, ?, ?) AS result");
        $stmt->bind_param("dii", $lat, $lng, $limit);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $json = $row['result'] ?? '[]';
        return json_decode($json, true) ?: [];
    }

    /**
     * Search patients by name using stored function.
     *
     * @param string $query Search term.
     * @param int $limit Maximum results.
     * @return array List of patients.
     */
    public static function searchPatients(string $query, int $limit = 20): array
    {
        $instance = new static();
        $stmt = $instance->getConnection()->prepare("SELECT search_patients(?, ?) AS result");
        $stmt->bind_param("si", $query, $limit);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $json = $row['result'] ?? '[]';
        return json_decode($json, true) ?: [];
    }

    /**
     * Generate a password reset token and store it (for forgot-password).
     *
     * @param string $email User's email.
     * @return string|false The reset token if user exists, false otherwise.
     */
    public static function generatePasswordResetToken(string $email): string|false
    {
        $instance = new static();
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $instance->getConnection()->prepare("CALL generate_password_reset_token(?, ?, ?)");
        $stmt->bind_param("sss", $email, $token, $expires);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected > 0 ? $token : false;
    }

    /**
     * Validate a password reset token and get user ID.
     *
     * @param string $token The reset token.
     * @return int|false The user ID if valid, false otherwise.
     */
    public static function validatePasswordResetToken(string $token): int|false
    {
        $instance = new static();
        $stmt = $instance->getConnection()->prepare(
            "SELECT user_id, reset_expires FROM view_password_reset_tokens WHERE reset_token = ? LIMIT 1"
        );
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) return false;

        $expires = $row['reset_expires'] ?? '';

        if (strtotime($expires) < time()) return false;

        return (int) $row['user_id'];
    }

    /**
     * Update user password using the procedure.
     *
     * @param int $userId User ID.
     * @param string $newPassword New plain password (will be hashed).
     * @return bool True if successful.
     */
    public static function updatePassword(int $userId, string $newPassword): bool
    {
        $instance = new static();
        $hash = self::encryptPassword($newPassword);
        $stmt = $instance->getConnection()->prepare("CALL update_password(?, ?)");
        $stmt->bind_param("is", $userId, $hash);
        $ok = $stmt->execute();
        $stmt->close();

        // Clear password reset token after successful update
        if ($ok) {
            self::clearPasswordResetToken($userId);
        }

        return $ok;
    }

    /**
     * Clear password reset token (mark as used).
     *
     * @param int $userId User ID.
     */
    public static function clearPasswordResetToken(int $userId): void
    {
        $instance = new static();
        $stmt = $instance->getConnection()->prepare("CALL clear_password_reset_token(?)");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }

    public static function getPatientSummary(int $patient_id): array
    {
        $instance = new static();

        $sql = "SELECT * FROM view_patient_summary WHERE patient_id = ?";
        $stmt = $instance->getConnection()->prepare($sql);
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Check whether a user holds a given role — use before privileged actions.
     *
     * @param int  $userId The user to check.
     * @param role $role   The role to test for.
     * @return bool True if the user has the role, false otherwise.
     */
    public function hasRole(int $userId, role $role): bool
    {
        $roleVal = $role->value;
        $stmt = $this->getConnection()->prepare("SELECT has_role(?, ?) AS result");
        if (!$stmt) return false;
        $stmt->bind_param("is", $userId, $roleVal);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (bool)($row['result'] ?? false);
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

    /**
     * Checks if the specified entity is marked as deleted.
     *
     * @return bool Returns true if the entity is marked as deleted, otherwise false.
     */
    protected function isDeleted (): bool
    {
        $sql = "CALL is_deleted(?)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    /**
     * Inserts a new user record into the database using the provided details.
     *
     * @return bool Returns true if the user was successfully inserted, otherwise false.
     */
    protected function insert(): bool
    {
        $prefix   = $this->prefix->value;
        $suffix   = isset($this->suffix) ? $this->suffix->value : null;
        $role     = $this->role->value;
        $blood    = $this->blood->value;
        $locX     = $this->location->x;
        $locY     = $this->location->y;

        $sql = "CALL insert_user(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @p_user_id)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param(
            "ssssssssddsisss",
            $this->firstName,
            $this->lastName,
            $this->middleName,
            $prefix,
            $suffix,
            $role,
            $this->gender,
            $this->phone,
            $locX,
            $locY,
            $this->email,
            $this->age,
            $blood,
            $this->password,
            $this->extra
        );
        $stmt->execute();

        $row = $this->getConnection()->query("SELECT @p_user_id AS id")->fetch_assoc();
        $this->id = (int) $row['id'];
        return $this->id > 0;
    }

    /**
     * Update this account's own profile fields.
     */
    protected function updateProfile(): bool
    {
        $prefixVal = $this->prefix?->value;
        $suffixVal = $this->suffix?->value;
        $bloodVal  = $this->blood?->value;
        $locX      = $this->location->x;
        $locY      = $this->location->y;

        $stmt = $this->getConnection()->prepare(
            "CALL update_user(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @p_success)"
        );
        if (!$stmt) return false;

        $stmt->bind_param(
            "isssssssddsiss",
            $this->id,
            $this->firstName,
            $this->lastName,
            $this->middleName,
            $prefixVal,
            $suffixVal,
            $this->gender,
            $this->phone,
            $locX,
            $locY,
            $this->email,
            $this->age,
            $bloodVal,
            $this->extra
        );

        if (!$stmt->execute()) { $stmt->close(); return false; }
        $stmt->close();

        $row = $this->getConnection()->query("SELECT @p_success AS ok")->fetch_assoc();
        return (bool)($row['ok'] ?? false);
    }

    /**
     * Authenticates a user using the provided username and password.
     *
     * @param string $username The username of the user attempting to log in.
     * @param string $password The password associated with the provided username.
     * @return bool Returns true if authentication is successful, otherwise false.
     */
    abstract function login(string $username, string $password): bool;

    /**
     * Registers an entity or performs a registration process.
     *
     * @return bool Returns true if the registration is successful, otherwise false.
     */
    abstract function register(): bool;
}
