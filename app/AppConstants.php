<?php

namespace App;

/** Runtime application settings cannot be declared as PHP constants. */
class AppConstants
{
    public static function get(string $name): mixed
    {
        return config('app.'.$name);
    }
}
