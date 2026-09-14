<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleEnum: string
{
    case SuperAdmin = 'SuperAdmin';
    case Admin = 'Admin';
}
