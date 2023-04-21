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

    public function getHeaderName(): string
    {
        return config('platform-resolver.headerNames.' . $this->value);
    }

    public static function fromString(string $value): self
    {
        if (!in_array($value, ['secret', 'public'])) {
            throw new \Exception("Token type '$value' is invalid. Valid types are 'public' or 'secret'", 1);
        }

        return match ($value) {
            'public' => AuthTokenTypeEnum::Public,
            'secret' => AuthTokenTypeEnum::Secret,
        };
    }

    /**
     * Get instance from either string representation or value
     *
     * @param integer|string $value
     * @return self
     */
    public static function instance(int|string $value): self
    {
        if (gettype($value) === 'string') {
            return self::fromString($value);
        }

        if (!in_array($value, [1, 2])) {
            throw new \Exception("Token type '$value' is invalid. Valid types are 'public' or 'secret'", 1);
        }

        return self::from($value);
    }
}
