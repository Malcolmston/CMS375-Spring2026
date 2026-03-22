<?php

namespace account;

require_once __DIR__ . '/Account.php';
require_once __DIR__ . '/Employed.php';
require_once __DIR__ . '/EmployedTrait.php';

class Billing extends Account implements Employed
{
    use EmployedTrait;

    /**
     * @inheritDoc
     */
    function login(string $username, string $password): bool
    {
        // TODO: Implement login() method.
    }

    /**
     * @inheritDoc
     */
    function register(): bool
    {
        // TODO: Implement register() method.
    }

    /**
     * @inheritDoc
     */
    public function loginWithId(string $email, string $password, string $id): bool
    {
        // TODO: Implement loginWithId() method.
    }
}
