<?php

namespace mindtwo\LaravelPlatformManager\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use mindtwo\LaravelAutoCreateUuid\AutoCreateUuid;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $platform_id
 * @property int|null $external_platform_id
 * @property string $hook
 * @property string $auth_token
 * @property ?string $url
 * @property string $endpoint
 * @property ?Platform $platform
 * @property ?ExternalPlatform $external_platform
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static query()
 */
class DispatchConfiguration extends Model
{
    use AutoCreateUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'hook',
        'active',
        'url',
        'auth_token',
        'platform_id',
        'external_platform_id',
    ];

    public function endpoint(): Attribute
    {
        return Attribute::make(function () {
            if ($this->created_at === null) {
                return '';
            }

            if (!is_null($this->url) && str_starts_with($this->url, 'https://')) {
                return $this->url;
            }
            if (! is_null($this->externalPlatform)) {
                return $this->externalPlatform->webhook_endpoint;
            }

            if (! is_null($this->platform)) {
                return Str::of($this->url)
                    ->trim('/')
                    ->prepend('/')
                    ->prepend($this->platform->hostname)
                    ->when(! str_starts_with($this->hostname, 'https://'), function ($string) {
                        return $string->prepend('https://');
                    })
                    ->toString();
            }

            throw new \Exception("Invalid configuration exception. The configuration for {$this->hook} has no valid endpoint configured.", 1);
        });
    }

    public function authToken(): Attribute
    {
        return Attribute::make(function (?string $value) {
            if ($this->created_at === null) {
                return $value;
            }

            if (! is_null($value)) {
                return $value;
            }

            if (! is_null($this->externalPlatform)) {
                return $this->externalPlatform->webhook_auth_token;
            }

            throw new \Exception("Invalid configuration exception. The configuration for {$this->hook} has no valid auth token configured.", 1);
        });
    }

    /**
     * Platform that received hook call.
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(config('platform-resolver.model'), 'platform_id');
    }

    public function externalPlatform(): BelongsTo
    {
        return $this->belongsTo(ExternalPlatform::class);
    }
}
