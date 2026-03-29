<?php

namespace pharmaceutical;

enum AllergyType: string
{
    case MEDICATION   = 'MEDICATION';
    case FOOD         = 'FOOD';
    case ENVIRONMENTAL = 'ENVIRONMENTAL';
    case INSECT       = 'INSECT';
    case LATEX        = 'LATEX';
    case OTHER        = 'OTHER';
}
