<?php

namespace account;

require_once __DIR__ . '/Account.php';
require_once __DIR__ . '/Employed.php';
require_once __DIR__ . '/EmployedTrait.php';
require_once __DIR__ . '/StaffLoginTrait.php';
require_once __DIR__ . '/VisitTrait.php';

class Receptionist extends Account implements Employed
{
    use EmployedTrait, VisitTrait, StaffLoginTrait;

    protected function staffRole(): role
    {
        return role::RECEPTIONIST;
    }
}
