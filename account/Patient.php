<?php

require_once __DIR__ . '/Account.php';

class Patient extends Account
{


    /**
     * @inheritDoc
     */
    function login(string $username, string $password): bool
    {

    }

    /**
     * @inheritDoc
     */
    function register(): bool
    {
        // TODO: Implement register() method.
    }
}
