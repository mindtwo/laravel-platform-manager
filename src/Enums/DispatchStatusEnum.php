<?php

namespace mindtwo\LaravelPlatformManager\Enums;

use mindtwo\NativeEnum\BaseEnum;
use mindtwo\NativeEnum\Contracts\LocalizedEnum;

/**
 * @method static static Aborted()
 * @method static static Dispatched()
 * @method static static Waiting()
 * @method static static Answered()
 */
enum DispatchStatusEnum: int implements LocalizedEnum
{
    use BaseEnum;

    case Aborted = 0;
    case Dispatched = 10;
    case Waiting = 20;
    case Answered = 30;
}
