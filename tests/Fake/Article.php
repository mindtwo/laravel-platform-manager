<?php

namespace mindtwo\LaravelPlatformManager\Tests\Fake;

use Illuminate\Database\Eloquent\Model;
use mindtwo\LaravelPlatformManager\Traits\BelongsToPlatform;

class Article extends Model
{
    use BelongsToPlatform;

    protected $table = 'articles';
    protected $guarded = [];
    public $timestamps = false;
}
