<?php

namespace App\Util;

class NaturalSort
{
    public static function compare(?string $a, ?string $b): int
    {
        return strnatcasecmp($a ?? '', $b ?? '');
    }
}
