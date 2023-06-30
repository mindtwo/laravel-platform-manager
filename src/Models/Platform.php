<?php

namespace mindtwo\LaravelPlatformManager\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use mindtwo\LaravelAutoCreateUuid\AutoCreateUuid;
use mindtwo\LaravelPlatformManager\Builders\PlatformBuilder;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $owner_id
 * @property bool|null $is_main
 * @property bool|null $visibility
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
 * @method static PlatformBuilder query()
 */
class Platform extends Model
{
    use SoftDeletes;
    use AutoCreateUuid;

    protected static string $authTokenModel = AuthToken::class;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
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
        static::saving(function (Platform $platform) {
            /** @var Platform|null $currentPlatform */
            $currentPlatform = DB::table((new self)->getTable())->where('is_main', true)->first();

            if ($platform->is_main && $currentPlatform?->id !== $platform->id) {
                DB::table((new self)->getTable())
                    ->where('is_main', true)
                    ->where('id', '!=', $platform->id)
                    ->update([
                        'is_main' => false,
                    ]);
            }

            if (! $platform->is_main && (empty($currentPlatform) || $currentPlatform->id === $platform->id)) {
                $platform->is_main = true;
            }
        });
    }

    public function getLogoUrlAttribute(): string
    {
        return $this->logo_file ? asset('storage/'.$this->logo_file) : '';
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class, 'platform_id');
    }

    public function webhookConfigurations(): HasMany
    {
        return $this->hasMany(WebhookConfiguration::class, 'platform_id');
    }

    public function newEloquentBuilder($query): PlatformBuilder
    {
        return new PlatformBuilder($query);
    }

    public static function query(): PlatformBuilder|Builder
    {
        return parent::query();
    }
}
