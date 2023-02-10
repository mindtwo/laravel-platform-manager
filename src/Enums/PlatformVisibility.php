<?php

namespace mindtwo\LaravelPlatformManager\Enums;

use mindtwo\NativeEnum\BaseEnum;
use mindtwo\NativeEnum\Contracts\LocalizedEnum;

/**
 * @method static static Public()
 * @method static static Private()
 * @method static static Protected()
 */
enum PlatformVisibility: int implements LocalizedEnum
{
    use BaseEnum;

    case Public = 1; // scan:ignore
    case Private = 2; // scan:ignore
    case Protected = 3; // scan:ignore
}
