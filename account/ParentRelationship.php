<?php

namespace account;

enum ParentRelationship: string
{
    case MOTHER         = 'Mother';
    case FATHER         = 'Father';
    case LEGAL_GUARDIAN = 'Legal Guardian';
}
