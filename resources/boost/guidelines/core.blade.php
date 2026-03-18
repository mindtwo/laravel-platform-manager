## Laravel Platform Manager

Laravel Platform Manager resolves and manages platforms (tenants, sites, or API clients) per HTTP request. It provides a `platform()` helper to access the current platform context anywhere in your application.

### Platform Resolution

Register the `resolve-platform` middleware on routes that need tenant context. Pass resolver strategies as pipe-separated names:

@verbatim
<code-snippet name="Register platform middleware on routes" lang="php">
// routes/web.php – resolve by hostname, fall back to session
Route::middleware('resolve-platform:host|session')->group(function () {
    Route::get('/dashboard', DashboardController::class);
});

// routes/api.php – resolve by token header, fall back to hostname
Route::middleware('resolve-platform:token|host')->group(function () {
    Route::get('/api/data', DataController::class);
});
</code-snippet>
@endverbatim

Available resolvers (tried in order until one matches):
- **host** – matches request hostname against `hostname` and `additional_hostnames` columns (supports `*.app.tld` wildcards)
- **token** – validates `X-Platform-Token` header against `auth_tokens` table
- **context** – matches `X-Platform-Context` header against `context` column
- **session** – reads platform ID from the user session

### Accessing the Current Platform

Use the `platform()` helper anywhere (controllers, models, jobs, policies):

@verbatim
<code-snippet name="Using the platform helper" lang="php">
// Check if a platform was resolved
if (platform()->isResolved()) {
    $model = platform()->get();           // Eloquent Platform model
    $resolver = platform()->resolver();   // 'host', 'token', etc.
}

// Read platform settings with dot notation
$mailHost = platform()->setting('mail.host', 'smtp.example.com');

// Check scopes (permissions)
if (platform()->can('write')) {
    // platform has write scope
}

// Access model properties directly via magic proxy
$hostname = platform()->hostname;
$uuid = platform()->uuid;
</code-snippet>
@endverbatim

### Enforcing Scopes

Use the `platform-scope` middleware to require specific scopes on routes:

@verbatim
<code-snippet name="Enforce platform scopes on routes" lang="php">
Route::middleware(['resolve-platform:token', 'platform-scope:read,write'])
    ->group(function () {
        Route::post('/api/records', StoreRecordController::class);
    });
</code-snippet>
@endverbatim

### Scoping Models to a Platform

#### Single-Platform Ownership (BelongsToPlatform)

For models that belong to one platform. Requires a `platform_id` column on the table.

@verbatim
<code-snippet name="Model with BelongsToPlatform trait" lang="php">
use Mindtwo\LaravelPlatformManager\Traits\BelongsToPlatform;

class Order extends Model
{
    use BelongsToPlatform;
}

// platform_id is auto-filled on creation when a platform is resolved
$order = Order::create(['total' => 100]);

// Query only current platform's records
$orders = Order::forCurrentPlatform()->get();

// Query a specific platform's records
$orders = Order::forPlatform($platform)->get();
</code-snippet>
@endverbatim

#### Multi-Platform Membership (BelongsToManyPlatforms)

For models shared across platforms via a pivot table. The pivot table name is auto-generated as `platform_{model_plural}` (e.g., `platform_users`).

@verbatim
<code-snippet name="Model with BelongsToManyPlatforms trait" lang="php">
use Mindtwo\LaravelPlatformManager\Traits\BelongsToManyPlatforms;

class User extends Authenticatable
{
    use BelongsToManyPlatforms;
}

// Query users for the current platform
$users = User::forCurrentPlatform()->get();

// Access the platforms relationship
$platforms = $user->platforms;
</code-snippet>
@endverbatim

### Custom Platform Settings

Extend `PlatformSettings` to add typed properties, encryption, and config overrides:

@verbatim
<code-snippet name="Define custom platform settings DTO" lang="php">
use Mindtwo\LaravelPlatformManager\Settings\PlatformSettings;

class AppPlatformSettings extends PlatformSettings
{
    public ?string $mailHost = null;
    public ?string $mailPassword = null;
    public ?string $appName = null;

    // Encrypt sensitive fields at rest
    protected array $encrypted = ['mailPassword'];

    // Override Laravel config per platform
    protected array $configKeys = [
        'mailHost' => 'mail.mailers.smtp.host',
        'mailPassword' => 'mail.mailers.smtp.password',
        'appName' => 'app.name',
    ];
}
</code-snippet>
@endverbatim

Register your custom settings class in `config/platform.php`:

@verbatim
<code-snippet name="Register custom settings class" lang="php">
// config/platform.php
return [
    'model' => \App\Models\Platform::class,
    'settings' => \App\Settings\AppPlatformSettings::class,
    // ...
];
</code-snippet>
@endverbatim

### Preserving Platform Context in Queue Jobs

Use the `HasPlatformContext` trait so queued jobs execute with the correct platform:

@verbatim
<code-snippet name="Queue job with platform context" lang="php">
use Mindtwo\LaravelPlatformManager\Jobs\Concerns\HasPlatformContext;

class SendInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use HasPlatformContext;

    public function __construct(public Invoice $invoice)
    {
        $this->capturePlatformContext();
    }

    public function handle(): void
    {
        $this->restorePlatformContext();

        // platform() now returns the same platform as when the job was dispatched
        Mail::to($this->invoice->email)->send(new InvoiceMail($this->invoice));
    }
}
</code-snippet>
@endverbatim

### Session-Based Platform Switching

For admin panels where users switch between platforms:

@verbatim
<code-snippet name="Save and clear platform from session" lang="php">
// Save current platform to session (e.g., after admin selects a tenant)
platform()->saveToSession();

// Clear platform from session (e.g., on logout)
platform()->clearFromSession();
</code-snippet>
@endverbatim

### Temporary Platform Context

Execute code under a different platform without affecting the current request:

@verbatim
<code-snippet name="Temporarily switch platform context" lang="php">
$result = platform()->use($otherPlatform, function () {
    // platform() returns $otherPlatform inside this closure
    return Order::forCurrentPlatform()->count();
});
// platform() is restored to the original platform here
</code-snippet>
@endverbatim

### M2M Auth Tokens

Create machine-to-machine tokens with scoped permissions and optional expiration:

@verbatim
<code-snippet name="Create auth tokens for a platform" lang="php">
$token = $platform->authTokens()->create([
    'scopes' => ['read', 'write'],
    'expired_at' => now()->addYear(),
]);

// The raw token value is available after creation
$rawToken = $token->token;

// Query tokens by scope
$readTokens = AuthToken::withScope('read')->get();
</code-snippet>
@endverbatim

### Testing

Use the `InteractsWithPlatform` trait in tests to set up fake platform context without a database:

@verbatim
<code-snippet name="Testing with fake platform context" lang="php">
use Mindtwo\LaravelPlatformManager\Testing\InteractsWithPlatform;

class OrderTest extends TestCase
{
    use InteractsWithPlatform;

    public function test_order_belongs_to_platform(): void
    {
        $this->setPlatform([
            'hostname' => 'tenant-a.app.test',
            'scopes' => ['read', 'write'],
        ]);

        $this->assertPlatformResolved();
        $this->assertPlatformCan('read');
        $this->assertPlatformCannot('admin');
        $this->assertPlatformResolver('fake');

        // Models will auto-fill platform_id from the fake platform
        $order = Order::factory()->create();
        $this->assertEquals(platform()->get()->getKey(), $order->platform_id);
    }

    protected function tearDown(): void
    {
        $this->clearPlatform();
        parent::tearDown();
    }
}
</code-snippet>
@endverbatim

### Configuration

Publish the config file and customize:

@verbatim
<code-snippet name="Platform configuration options" lang="php">
// config/platform.php
return [
    'model' => \App\Models\Platform::class,          // Your custom Platform model
    'settings' => \App\Settings\AppPlatformSettings::class, // Your custom settings DTO
    'header_names' => [
        'token' => 'X-Platform-Token',               // Token auth header
    ],
    'session_key' => 'platform_id',                   // Session storage key
];
</code-snippet>
@endverbatim
