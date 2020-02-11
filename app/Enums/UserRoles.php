<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Administrator()
 * @method static static Subscriber()
 */
final class UserRoles extends Enum
{
    const Administrator = 0;
    const Subscriber    = 1;
}
