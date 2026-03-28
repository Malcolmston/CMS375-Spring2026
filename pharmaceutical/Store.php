<?php

namespace pharmaceutical;

enum Store: string
{
    case TEMP = "room temperature";
    case REF  = "refrigerator";
    case FREEZER = "freezer";
    case NO_LIGHT = "keep away from light";
    case DRY = "keep dry";
    case COLD_DARK = "refrigerate, keep away from light";
}
