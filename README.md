# Laravel Platform Manager

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

Resolve a "platform" (tenant, site, or API client) on every request — by hostname, token, context string, or session — and make it available everywhere via `platform()`.

---

## Installation

```bash
composer require mindtwo/laravel-platform-manager
```

### Publish config

```bash
php artisan vendor:publish --provider="mindtwo\LaravelPlatformManager\LaravelPlatformManagerProvider" --tag=config
```

This publishes `config/platform.php`.

### Publish and run migrations

```bash
php artisan vendor:publish --provider="mindtwo\LaravelPlatformManager\LaravelPlatformManagerProvider" --tag=migrations
php artisan migrate
```

---

## Configuration

```php
// config/platform.php

return [
    // Eloquent model used as the platform. Swap this for your own model that extends Platform.
    'model' => \mindtwo\LaravelPlatformManager\Models\Platform::class,

    // HTTP headers used for M2M token auth.
    'header_names' => [
        'token' => 'X-Platform-Token',

        // Legacy header accepted during a grace period. Set to null to disable.
        'token_legacy' => 'X-Context-Platform-Public-Auth-Token',
    ],

    // Session key used by the session resolver.
    'session_key' => 'platform_id',
];
```

---

## Middleware

Register the middleware alias in your application's middleware stack or use it inline on routes:

```php
// routes/api.php
Route::middleware('resolve-platform:token')->group(function () {
    // platform() is available here
});
```

The `resolve-platform` alias is registered automatically by the service provider.

### Resolver strategies

Pass one or more strategies separated by `|`. The first one that returns a match wins.

| Strategy | Resolves by |
|----------|-------------|
| `host` | `Host` header matched against `hostname` / `additional_hostnames` (supports `*` wildcards) |
| `token` | `X-Platform-Token` header matched against an active, non-expired `auth_tokens` record |
| `context` | `X-Platform-Context` header matched against the `context` column |
| `session` | Platform PK stored in the session via `platform()->saveToSession()` |

```php
// Try token first, fall back to hostname
Route::middleware('resolve-platform:token|host')->group(function () { ... });
```

If no strategy resolves a platform the middleware aborts with a `404`.

---

## The `platform()` helper

The global `platform()` function returns the singleton `Platform` context object.

```php
// Check whether a platform has been resolved
platform()->isResolved(); // bool

// Get the underlying Eloquent model
platform()->get(); // ?PlatformModel

// Read any model attribute directly
platform()->hostname;
platform()->uuid;

// Read platform settings (dot notation)
platform()->setting('mail.from');
platform()->setting('billing.plan', 'free');

// Which resolver matched
platform()->resolver(); // 'token' | 'host' | 'context' | 'session' | ...
```

---

## Scopes

Scopes control what operations a resolved platform is allowed to perform. There are two layers:

1. **Platform baseline scopes** — stored on the `platforms` row itself, always active regardless of how the platform was resolved.
2. **Token scopes** — carried by an `auth_tokens` record, merged on top of the baseline when the platform is resolved via the `token` strategy.

The effective scope set is `platform.scopes ∪ token.scopes`.

### Platform baseline scopes

```php
$platform->update(['scopes' => ['read']]);
```

These scopes apply for every resolver (host, session, context, token).

## M2M token auth & scopes

Auth tokens are M2M (machine-to-machine) credentials stored in the `auth_tokens` table. Token scopes widen the platform's baseline — they cannot narrow it.

### Creating a token

```php
$platform->authTokens()->create([
    'scopes' => ['read', 'write'],
]);
```

### Checking scopes in application code

`platform()->can()` returns `true` when the scope is present in the effective set (platform baseline + token scopes).

```php
// In a controller, middleware, policy, etc.
if (! platform()->can('write')) {
    abort(403);
}
```

### Token model helpers

```php
$token->hasScope('admin');           // bool
$token->scopes;                      // array<string>
$token->isExpired();                 // bool

AuthToken::withScope('read')->get(); // query scope
```

### Expiry

Set `expired_at` to limit a token's lifetime. Expired tokens are ignored by the middleware resolver automatically.

```php
$platform->authTokens()->create([
    'scopes'     => ['read'],
    'expired_at' => now()->addDays(30),
]);
```

---

## Session-based resolution

```php
// Store the current platform in the session (e.g. after an admin selects a platform)
platform()->saveToSession($platformModel);

// Or if it's already set:
platform()->set($model, 'admin');
platform()->saveToSession();

// Clear on logout / platform switch
platform()->clearFromSession();
```

---

## Temporary platform context (`use()`)

Switch platform for the duration of a callback, then restore the previous context automatically — even if the callback throws.

```php
platform()->use($otherPlatform, function () {
    // platform() resolves $otherPlatform here
    Mail::send(...);
});

// platform() is restored here
```

---

## Queue jobs

Use the `HasPlatformContext` trait to capture and restore platform context across queue boundaries.

```php
use mindtwo\LaravelPlatformManager\Jobs\Concerns\HasPlatformContext;

class ProcessOrder implements ShouldQueue
{
    use HasPlatformContext;

    public function __construct(private Order $order)
    {
        $this->capturePlatformContext(); // call at end of constructor
    }

    public function handle(): void
    {
        $this->restorePlatformContext(); // call at start of handle
        // platform() is now resolved
    }
}
```

---

## Extending the Platform model

Publish the config and point `platform.model` at your own model:

```php
// app/Models/Platform.php
use mindtwo\LaravelPlatformManager\Models\Platform as BasePlatform;

class Platform extends BasePlatform
{
    // add columns, relationships, scopes ...
}
```

```php
// config/platform.php
'model' => \App\Models\Platform::class,
```

---

## `BelongsToPlatform` trait

Add the trait to any Eloquent model that belongs to a platform. It auto-fills `platform_id` on create and provides two query scopes.

```php
use mindtwo\LaravelPlatformManager\Traits\BelongsToPlatform;

class Article extends Model
{
    use BelongsToPlatform;
}

// Scopes
Article::forCurrentPlatform()->get();
Article::forPlatform($platform)->get();
Article::forPlatform(42)->get();
```

---

## Scope middleware

The `platform-scope` middleware aborts with `403` if the resolved platform does not hold the required scope(s). Apply it after `resolve-platform`.

```php
Route::middleware(['resolve-platform:token', 'platform-scope:write'])->group(function () {
    // platform must have the 'write' scope
});

// Multiple scopes — all must be present
Route::middleware(['resolve-platform:token', 'platform-scope:read,write'])->group(function () {
    // ...
});
```

---

## `BelongsToManyPlatforms` trait

For models that belong to multiple platforms via a pivot table. Provides the same scopes as `BelongsToPlatform` but uses `whereHas` under the hood.

```php
use mindtwo\LaravelPlatformManager\Traits\BelongsToManyPlatforms;

class Article extends Model
{
    use BelongsToManyPlatforms;
}

// Scopes
Article::forCurrentPlatform()->get();
Article::forPlatform($platform)->get();
Article::forPlatform(42)->get();

// Relationship
$article->platforms; // Collection of Platform models
```

The pivot table is derived automatically as `platform_{models}` (e.g. `platform_articles`). Override `getPlatformPivotTable()` on the model to use a different name:

```php
public function getPlatformPivotTable(): string
{
    return 'article_platform';
}
```

---

## Typed platform settings

Platform settings are stored as JSON in the `settings` column and hydrated into a `PlatformSettings` DTO. Declare known properties as typed public fields and list any that should be encrypted at rest in `$encrypted`.

### Extending `PlatformSettings`

```php
// app/Settings/PlatformSettings.php
use mindtwo\LaravelPlatformManager\Settings\PlatformSettings as BaseSettings;

class PlatformSettings extends BaseSettings
{
    protected array $encrypted = ['apiSecret', 'smtpPassword'];

    public ?string $appName = null;
    public ?string $apiSecret = null;   // encrypted at rest
    public ?string $smtpPassword = null; // encrypted at rest
    public ?string $billingPlan = null;
}
```

Point the config at your class:

```php
// config/platform.php
'settings' => \App\Settings\PlatformSettings::class,
```

### Reading settings

```php
// Via the helper (dot notation, works for any depth)
platform()->setting('appName');
platform()->setting('mail.host', 'localhost'); // nested via overflow

// Via the model directly
$platform->settings->appName;
$platform->setting('appName');
```

### Writing settings

```php
// Assign properties directly
$platform->settings->appName = 'My App';
$platform->settings->apiSecret = 's3cr3t'; // stored encrypted
$platform->save();

// Or replace the whole DTO
$platform->update(['settings' => ['appName' => 'My App', 'apiSecret' => 's3cr3t']]);
```

Unknown keys (no matching declared property) are stored transparently in an overflow bag so existing data and config overrides continue to work without any changes.

---

## Config overrides

A platform can override arbitrary Laravel config values by storing them under `settings.config`:

```php
$platform->update([
    'settings' => [
        'config' => [
            'mail.default' => 'ses',
            'app.name'     => 'My Platform',
        ],
    ],
]);
```

These overrides are applied automatically whenever the platform is resolved.

---

## `PlatformRepository`

All platform lookups go through `PlatformRepository`, which extends `chiiya/laravel-utilities`'s `AbstractRepository`. The middleware resolves it automatically, but you can also inject it directly.

```php
use mindtwo\LaravelPlatformManager\Repositories\PlatformRepository;

class PlatformController extends Controller
{
    public function __construct(protected PlatformRepository $platforms) {}
}
```

### Request-aware resolvers

These map directly to the middleware strategies and read from the incoming request:

```php
$repository->resolveByToken($request);    // ?array{PlatformModel, array<string>}
$repository->resolveByHostname($request); // ?PlatformModel
$repository->resolveByContext($request);  // ?PlatformModel
$repository->resolveBySession($request);  // ?PlatformModel
```

`resolveByToken` returns a tuple of `[PlatformModel, effectiveScopes]` where scopes are the platform baseline merged with the token's scopes.

### Value-based finders

```php
$repository->findByHostname('example.com'); // ?PlatformModel
$repository->findByContext('my-context');   // ?PlatformModel
$repository->findByUuid('uuid-string');     // ?PlatformModel
$repository->findActiveById(1);            // ?PlatformModel

// Returns [PlatformModel, effectiveScopes]|null
$repository->findByTokenWithScopes($rawToken);
```

### Collection queries

```php
$repository->allActive();                     // Collection<PlatformModel>
$repository->index(['is_active' => true]);    // Collection<PlatformModel>
$repository->index(['hostname' => 'app.io']); // Collection<PlatformModel>
$repository->count(['is_active' => true]);    // int
$repository->search('app', ['is_active' => true]); // LengthAwarePaginator
```

Supported `applyFilters` parameters: `is_active`, `hostname`, `context`.

---

## Upgrade Guide

### Upgrading from v2 to v4

v4 is a full rewrite. Every section below is a breaking change — work through them in order.

#### Step 1 — Service provider

The provider moved out of the `Providers` sub-namespace.

```php
// Before (config/app.php or auto-discovery override)
mindtwo\LaravelPlatformManager\Providers\LaravelPlatformManagerProvider::class

// After
mindtwo\LaravelPlatformManager\LaravelPlatformManagerProvider::class
```

#### Step 2 — Config file rename and restructure

The config file was renamed from `platform-resolver.php` to `platform.php`, and the config key changed from `platform-resolver` to `platform`.

```bash
# Republish the config
php artisan vendor:publish --provider="mindtwo\LaravelPlatformManager\LaravelPlatformManagerProvider" --tag=config
```

Key mapping:

```php
// Before (config/platform-resolver.php)
'model'       => Platform::class,
'headerNames' => [
    AuthTokenTypeEnum::Public()  => 'X-Context-Platform-Public-Auth-Token',
    AuthTokenTypeEnum::Secret()  => 'X-Context-Platform-Secret-Auth-Token',
],
'webhooks'    => [ ... ],

// After (config/platform.php)
'model'        => Platform::class,
'header_names' => [
    'token' => 'X-Platform-Token',
],
'session_key'  => 'platform_id',
```

Update any `config('platform-resolver.*')` calls in your own code to `config('platform.*')`.

#### Step 3 — Platforms table migration

Several columns were removed and three were added. Create a migration in your application:

```php
Schema::table('platforms', function (Blueprint $table) {
    // Remove v2-only columns (skip any you wish to keep in your own schema)
    $table->dropColumn([
        'owner_id',
        'is_main',
        'is_headless',
        'name',
        'default_locale',
        'available_locales',
    ]);

    // Widen hostname to 100 chars
    $table->string('hostname', 100)->nullable()->change();

    // Add new columns
    $table->string('context')->nullable()->unique()->after('additional_hostnames');
    $table->json('scopes')->nullable()->after('context');
    $table->json('settings')->nullable()->after('scopes');
});
```

#### Step 4 — Auth tokens table migration

The `type` column is replaced by `scopes`, `expired_at` is added, and several v2 columns are dropped:

```php
Schema::table('auth_tokens', function (Blueprint $table) {
    // Drop v2 columns
    $table->dropForeign(['user_id']);
    $table->dropUnique(['platform_id', 'token']); // composite unique
    $table->dropColumn(['user_id', 'description']);
    $table->dropSoftDeletes();
    $table->dropColumn('type');

    // Add v4 columns
    $table->json('scopes')->default('[]')->after('platform_id');
    $table->datetime('expired_at')->nullable()->after('token');
});
```

Migrate existing token types to scopes before dropping `type` if you need to preserve access levels:

```php
// Run before dropping 'type'
DB::table('auth_tokens')->where('type', 1)->update(['scopes' => '["read","write"]']); // Secret → full access
DB::table('auth_tokens')->where('type', 2)->update(['scopes' => '["read"]']);          // Public → read only
```

#### Step 5 — Replace `PlatformResolver` with `platform()`

The `PlatformResolver` service is gone. Replace all usages with the `platform()` helper or `app(Platform::class)`.

```php
// Before
app(PlatformResolver::class)->getCurrentPlatform()
resolve(PlatformResolver::class)->getCurrentPlatform()

// After
platform()->get()
```

```php
// Before — auth check
app(PlatformResolver::class)->checkAuth(AuthTokenTypeEnum::Secret())

// After — scope check
platform()->can('write')
```

#### Step 6 — Replace middleware

The old middleware classes are removed. Replace them with the new `resolve-platform` middleware.

```php
// Before
\mindtwo\LaravelPlatformManager\Middleware\PlatformSession::class

// After
'resolve-platform:session'
```

```php
// Before — token-based routes
\mindtwo\LaravelPlatformManager\Middleware\ResolveBySecretToken::class
\mindtwo\LaravelPlatformManager\Middleware\ResolveByPublicToken::class

// After
'resolve-platform:token'
```

Multiple strategies can be chained:

```php
Route::middleware('resolve-platform:token|host|session')->group(...);
```

Remove `StatefulPlatformDomains` — it is no longer part of this package.

#### Step 7 — Update API clients

Send the single `X-Platform-Token` header instead of the separate public/secret headers:

```
# Before
X-Context-Platform-Secret-Auth-Token: <token>
X-Context-Platform-Public-Auth-Token: <token>

# After
X-Platform-Token: <token>
```

#### Step 8 — Remove webhooks

The full webhook system (tables, jobs, routes, Nova resources) has been removed. If your application used webhooks:

- Drop the `webhooks` and `webhook_requests` tables
- Remove any references to `PushToWebhook`, `WebhookController`, `WebhookConfiguration`, `WebhookRequest`, `EnsureWebhooksAreEnabled`
- Remove the `platform-resolver.webhooks` config section (gone with step 2)

#### Step 9 — Nova resources

All built-in Nova resources have been removed. If you extended them, copy the field definitions into your own resource classes.

---

### Upgrading to v4 (from v3)

v4 is a breaking release. The changes below are required.

#### 1. Auth token type → scopes

The `type` column (`Public`/`Secret`) has been replaced with a `scopes` JSON array.

**Migration** — if you published the migration previously, update your `create_auth_tokens_table` migration (or create a new migration on existing tables):

```php
// Before
$table->smallInteger('type');

// After
$table->json('scopes')->default('[]');
```

For existing tables, create a new migration:

```php
Schema::table('auth_tokens', function (Blueprint $table) {
    $table->json('scopes')->default('[]')->after('platform_id');
    $table->dropColumn('type');
});
```

**Code** — replace all `AuthTokenTypeEnum` references:

```php
// Before
$token->type = AuthTokenTypeEnum::Secret;
$token->type = AuthTokenTypeEnum::Public;

// After — just assign scopes
$token->scopes = ['read', 'write'];
```

#### 2. Header names config

```php
// Before
'header_names' => [
    'public' => 'X-Context-Platform-Public-Auth-Token',
    'secret' => 'X-Context-Platform-Secret-Auth-Token',
],

// After
'header_names' => [
    'token' => 'X-Platform-Token',
],
```

Update any API clients to send `X-Platform-Token` (or whatever you configure) instead of the old public/secret headers.

#### 3. Middleware resolver names

```php
// Before
Route::middleware('resolve-platform:public-token|secret-token|host')->group(...);

// After
Route::middleware('resolve-platform:token|host')->group(...);
```

#### 4. Platform model scopes

```php
// Before
Platform::query()->byPublicAuthToken($token)->first();
Platform::query()->bySecretAuthToken($token)->first();

// After — single scope, expiry checked automatically
Platform::query()->byToken($token)->first();
```

#### 5. `AuthTokenTypeEnum` removed

Delete any imports or references to `mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum`. The enum no longer exists.

#### 6. Nova resource removed

The built-in `AuthToken` Nova resource has been removed. If you extended it, update your subclass to work without the base class or reimplement the fields directly. The `scopes` field is a JSON array — a `Tag` or `Text` field works well.

#### 7. New: `platform()->can()`

Scope authorization is now available for all resolvers, not just token:

```php
if (platform()->can('write')) {
    // scope is in platform baseline or widened by the resolved token
}
```

Platform baseline scopes (`platforms.scopes`) apply for every resolver. Token scopes are additive on top.

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email info@mindtwo.de instead of using the issue tracker.

## Credits

- [mindtwo GmbH][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/mindtwo/laravel-platform-manager.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/mindtwo/laravel-platform-manager.svg?style=flat-square
[link-packagist]: https://packagist.org/packages/mindtwo/laravel-platform-manager
[link-downloads]: https://packagist.org/packages/mindtwo/laravel-platform-manager
[link-author]: https://github.com/mindtwo
[link-contributors]: ../../contributors
