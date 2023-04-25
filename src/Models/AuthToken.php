<?php

namespace mindtwo\LaravelPlatformManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum;

/**
 * @property int $id
 * @property int $user_id
 * @property int $platform_id
 * @property string $description
 * @property Platform $platform
 * @property AuthTokenTypeEnum $type
 * @property string $token
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class AuthToken extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => AuthTokenTypeEnum::class,
    ];

    /**
     * Add start date on publish.
     */
    protected static function booted()
    {
        parent::booted();

        static::creating(function ($model) {
            $model->user_id = Auth::user()?->id;

            if (empty($model->type)) {
                $model->type = AuthTokenTypeEnum::Secret();
            }

            if (empty($model->token)) {
                $model->token = Hash::make(Str::random(75));
            }
        });
    }

    /**
     * Plattform.
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(config('platform-resolver.model'), 'platform_id');
    }

    /**
     * User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }
}
