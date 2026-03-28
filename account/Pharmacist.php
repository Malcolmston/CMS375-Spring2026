<?php

namespace account;

require_once __DIR__ . '/Account.php';
require_once __DIR__ . '/Employed.php';
require_once __DIR__ . '/EmployedTrait.php';
require_once __DIR__ . '/Perscribable.php';
require_once __DIR__ . '/PrescribableTrait.php';
require_once __DIR__ . '/StaffLoginTrait.php';

class Pharmacist extends Account implements Employed, Perscribable
{
    use EmployedTrait, PrescribableTrait, StaffLoginTrait;

    protected function staffRole(): role
    {
        return role::PHARMACIST;
    }
}
