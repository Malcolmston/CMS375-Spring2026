<?php

namespace account;

require_once __DIR__ . '/Account.php';
require_once __DIR__ . '/Diagnosible.php';
require_once __DIR__ . '/DiagnosibleTrait.php';
require_once __DIR__ . '/Employed.php';
require_once __DIR__ . '/EmployedTrait.php';
require_once __DIR__ . '/StaffLoginTrait.php';

class Radiologist extends Account implements Diagnosible, Employed
{
    use DiagnosibleTrait, EmployedTrait, StaffLoginTrait;

    protected function staffRole(): role
    {
        return role::RADIOLOGIST;
    }
}
