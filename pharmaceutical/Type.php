<?php

namespace pharmaceutical;

enum Type: string
{
    case MRNA = "mRNA";
    case LIVE_ATTENUATED = "Live Attenuated";
    case INACTIVATED = "Inactivated";
    case TOXOID = "Toxoid";
    case SUBUNIT = "Subunit";
    case VECTOR = "Vector";
    case DNA = "DNA";
    case PROTEIN = "Protein";
    case UNKNOWN = "Unknown";
}
