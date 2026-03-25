<?php

namespace pharmaceutical;

enum Form: string
{
    case TABLET = "tablet";
    case CAPSULE = "capsule";
    case LIQUID = "liquid";
    case INJECTION = "injection";
    case PATCH = "patch";
    case INHALER = "inhaler";
    case CREAM = "cream";
    case OINTMENT = "ointment";
    case DROPS = "drops";
    case SUPPOSITORY = "suppository";
}
