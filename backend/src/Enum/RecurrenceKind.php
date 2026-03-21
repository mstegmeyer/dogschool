<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * How often a course runs / how customers participate.
 * - RECURRING: regular weekly slot (e.g. 2x per week Junghunde/MH/TK).
 * - ONE_TIME: single session or event (e.g. seminar, workshop).
 * - DROP_IN: no registration needed (e.g. free Welpenkurs "einfach vorbeikommen").
 */
enum RecurrenceKind: string
{
    case RECURRING = 'RECURRING';
    case ONE_TIME = 'ONE_TIME';
    case DROP_IN = 'DROP_IN';
}
