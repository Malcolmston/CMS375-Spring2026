<?php

namespace pharmaceutical;

enum Stage: string
{
    case RELEASED = "RELEASED";
    case TESTING = "TESTING";
    case PRE_CLINICAL = "PRECLINICAL";
    case DISCONTINUED = "DISCONTINUED";
    case UNKNOWN = "UNKNOWN";
}
