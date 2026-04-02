<?php

/**
 * Represents the various types of templates that can be used.
 * Each case corresponds to a specific type.
 */
enum TemplateType: int
{
    case Warning = 1;
    case Danger = 2;
    case Update = 3;
    case Change = 4;
    case Notification = 5;
}
