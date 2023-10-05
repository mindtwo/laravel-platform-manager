<?php

namespace mindtwo\LaravelPlatformManager\Enums;

use mindtwo\NativeEnum\BaseEnum;
use mindtwo\NativeEnum\Contracts\LocalizedEnum;

/**
 * @method static static Dispatched()
 * @method static static Waiting()
 * @method static static Answered()
 */
enum DispatchStatusEnum: string implements LocalizedEnum
{
    use BaseEnum;

    case Dispatched = 'dispatched';
    case Waiting = 'waiting';
    case Answered = 'answered';
}
