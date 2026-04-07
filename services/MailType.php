<?php

namespace services;

enum MailType: string
{
    case ACCOUNT = 'account';
    case SUPPORT = 'support';
    case BILLING = 'billing';
    case SCHEDULE = 'schedule';
    case LAB = 'lab';
    case PHARMACY = 'pharmacy';
    case PRESCRIPTION = 'prescription';
    case PRIVACY = 'privacy';
    case DEFAULT = 'default';

    public function toString(): string
    {
        return $this->value . "@medhealth.com";
    }
}
