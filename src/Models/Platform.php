<?php

namespace mindtwo\LaravelPlatformManager\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use mindtwo\LaravelAutoCreateUuid\AutoCreateUuid;
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
 * @property array|null $additional_hostnames
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method static query()
 * @method static visible()
 * @method static main()
 * @method static matchHostname()
 */
class Platform extends Model
{
    use SoftDeletes;
    use AutoCreateUuid;

    protected static string $authTokenModel = AuthToken::class;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_main' => 'boolean',
        'visibility' => 'boolean',
        'additional_hostnames' => 'array',
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

    /**
     * @param $query
     * @return PlatformBuilder
     */
    public function newEloquentBuilder($query): PlatformBuilder
    {
        return new PlatformBuilder($query);
    }

    /**
     * @return self
     */
    public static function fromRequest(): self
    {
        // Todo: get platform from api request token
        return self::firstOrFail();
    }

    public function getLogoUrlAttribute(): string
    {
        return $this->logo_file ? asset('storage/'.$this->logo_file) : '';
    }

    /**
     * @param  Builder  $query
     * @param  string  $hostname
     * @return Builder
     */
    public static function scopeByHostname(Builder $query, string $hostname): Builder
    {
        return self::query()->where('hostname', $hostname);
    }

    /**
     * @param  string  $token
     * @return self|null
     */
    public static function resolveByPublicAuthToken(string $token): self|null
    {
        return self::$authTokenModel::query()->where('token', $token)->with(['platform'])->first()?->platform;
    }
}
