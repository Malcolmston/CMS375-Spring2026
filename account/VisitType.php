<?php

namespace account;

enum VisitType: string
{
    case CHECKUP   = 'CHECKUP';
    case FOLLOW_UP = 'FOLLOW_UP';
    case EMERGENCY = 'EMERGENCY';
    case SPECIALIST = 'SPECIALIST';
    case LAB       = 'LAB';
    case THERAPY   = 'THERAPY';
    case OTHER     = 'OTHER';
}
