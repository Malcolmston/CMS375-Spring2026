<?php

namespace account;

enum blood: string
{
    case O    = "O";
    case OPos = "O+";
    case ONeg = "O-";
    case A    = "A";
    case APos = "A+";
    case ANeg = "A-";
    case B    = "B";
    case BPos = "B+";
    case BNeg = "B-";
    case AB   = "AB";
    case ABPos = "AB+";
    case ABNeg = "AB-";
}
