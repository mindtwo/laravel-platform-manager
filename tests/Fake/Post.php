<?php

namespace mindtwo\LaravelPlatformManager\Tests\Fake;

use Illuminate\Database\Eloquent\Model;
use mindtwo\LaravelPlatformManager\Traits\BelongsToManyPlatforms;

class Post extends Model
{
    use BelongsToManyPlatforms;

    protected $table = 'posts';

    protected $guarded = [];

    public $timestamps = false;
}
