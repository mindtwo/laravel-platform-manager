<?php

namespace mindtwo\LaravelPlatformManager\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use mindtwo\LaravelPlatformManager\Builders\PlatformBuilder;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $owner_id
 * @property int|null $is_main
 * @property int|null $visibility
 * @property string|null $name
 * @property string|null $email
 * @property string|null $hostname
 * @property string|null $logo_file
 * @property string|null $primary_color
 * @property Collection $courses
 * @property Collection $instructors
 * @property Collection $users
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method static visible()
 * @method static main()
 * @method static matchHostname()
 */
class Platform extends Model
{
    use SoftDeletes;
    use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_main' => 'boolean',
        'visibility' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saving(function ($platform) {
            if ($platform->is_main) {
                DB::table((new self)->getTable())
                    ->where('is_main', true)
                    ->where('id', '!=', $platform->id)
                    ->update([
                        'is_main' => false,
                    ]);
            }
        });
    }

    public static function query(): PlatformBuilder|Builder
    {
        return parent::query();
    }

    public function newEloquentBuilder($query): PlatformBuilder
    {
        return new PlatformBuilder($query);
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return ['uuid'];
    }
}
