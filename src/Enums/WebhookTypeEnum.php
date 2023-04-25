<?php

namespace mindtwo\LaravelPlatformManager\Enums;

use mindtwo\NativeEnum\BaseEnum;
use mindtwo\NativeEnum\Contracts\LocalizedEnum;

/**
 * @method static static Secret()
 * @method static static Public()
 */
enum WebhookTypeEnum: int implements LocalizedEnum
{
    use BaseEnum;

    case Incoming = 1;
    case Outgoing = 2;
}
