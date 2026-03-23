<?php

namespace account;

enum role: string
{
    case PATIENT      = "PATIENT";
    case PHYSICIAN    = "PHYSICIAN";
    case NURSE        = "NURSE";
    case PHARMACIST   = "PHARMACIST";
    case RADIOLOGIST  = "RADIOLOGIST";
    case LAB_TECH     = "LAB_TECH";
    case SURGEON      = "SURGEON";
    case RECEPTIONIST = "RECEPTIONIST";
    case ADMIN        = "ADMIN";
    case BILLING      = "BILLING";
    case EMS          = "EMS";
    case THERAPIST    = "THERAPIST";
}
