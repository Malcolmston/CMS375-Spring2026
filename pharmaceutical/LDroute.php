<?php

namespace pharmaceutical;

enum LDroute: string
{
    case ORAL = "ORAL";
    case IV = "IV";
    case IM = "IM";
    case INHALATION = "INHALATION";
    case DERMAL = "DERMAL";
    case UNKNOWN = "UNKNOWN";
}
