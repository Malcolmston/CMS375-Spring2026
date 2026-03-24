<?php

namespace account;

require_once __DIR__ . '/Account.php';
require_once __DIR__ . '/Employed.php';
require_once __DIR__ . '/EmployedTrait.php';
require_once __DIR__ . '/StaffLoginTrait.php';

class Billing extends Account implements Employed
{
    use EmployedTrait, StaffLoginTrait;

    protected function staffRole(): role
    {
        return role::BILLING;
    }
}
