<?php

namespace account;

enum InstitutionType: string
{
    case HOSPITAL    = "HOSPITAL";
    case CLINIC      = "CLINIC";
    case URGENT_CARE = "URGENT_CARE";
    case PHARMACY    = "PHARMACY";
    case LAB         = "LAB";
    case OTHER       = "OTHER";

    public static function isValid(mixed $type): bool
    {
        return InstitutionType::tryFrom($type) !== null;
    }
}