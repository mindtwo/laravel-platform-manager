<?php

namespace mindtwo\LaravelPlatformManager\Enums;

use mindtwo\NativeEnum\BaseEnum;
use mindtwo\NativeEnum\Contracts\LocalizedEnum;

/**
 * @method static static Secret()
 * @method static static Public()
 */
enum AuthTokenTypeEnum: int implements LocalizedEnum
{
    use BaseEnum;

    case Secret = 1;
    case Public = 2;
}
