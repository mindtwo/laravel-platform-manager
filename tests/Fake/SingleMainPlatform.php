<?php

namespace mindtwo\LaravelPlatformManager\Tests\Fake;

use mindtwo\LaravelPlatformManager\Models\Platform;

class SingleMainPlatform extends Platform
{
    use \mindtwo\LaravelPlatformManager\Traits\OnlyOneMain;

    protected $table = 'platforms';
}
