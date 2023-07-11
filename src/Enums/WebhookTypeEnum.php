<?php

namespace mindtwo\LaravelPlatformManager\Enums;

use mindtwo\NativeEnum\BaseEnum;
use mindtwo\NativeEnum\Contracts\LocalizedEnum;

/**
 * @method static static Incoming()
 * @method static static Outgoing()
 */
enum WebhookTypeEnum: int implements LocalizedEnum
{
    use BaseEnum;

    case Incoming = 1;
    case Outgoing = 2;
}
