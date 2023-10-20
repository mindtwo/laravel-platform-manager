<?php

namespace mindtwo\LaravelPlatformManager\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use mindtwo\LaravelAutoCreateUuid\AutoCreateUuid;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $owner_id
 * @property string $name
 * @property string $hostname
 * @property string $webhook_path
 * @property string $webhook_endpoint
 * @property string $webhook_auth_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ExternalPlatform extends Model
{
    use AutoCreateUuid;

    protected $guarded = ['id'];

    public function webhookEndpoint(): Attribute
    {
        return Attribute::make(function () {
            return "https://{$this->hostname}{$this->webhook_path}";
        });
    }

    /**
     * Platform dispatches.
     */
    public function dispatchConfigurations(): HasMany
    {
        return $this->hasMany(DispatchConfiguration::class);
    }
}
