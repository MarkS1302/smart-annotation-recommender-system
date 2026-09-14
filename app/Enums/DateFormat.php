<?php

declare(strict_types=1);

namespace App\Enums;

enum DateFormat: string
{
    case DATE_GREEK           = 'd-m-Y';
    case DATE_GREEK_WITH_DOT  = 'd.m.Y';
    case DATE_GREEK_SLASHES   = 'd/m/Y';
    case DATE_TIME_GREEK      = 'd-m-Y H:i:s';
    case DATE_DB              = 'Y-m-d';
    case DATE_TIME_DB         = 'Y-m-d H:i:s';
    case TIME_WITH_SECONDS    = 'H:i:s';
    case TIME_WITH_NO_SECONDS = 'H:i';
}
