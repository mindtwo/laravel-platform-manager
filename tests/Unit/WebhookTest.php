<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum;
use mindtwo\LaravelPlatformManager\Models\AuthToken;
use mindtwo\LaravelPlatformManager\Models\Webhook;
use mindtwo\LaravelPlatformManager\Tests\Fake\PlatformFactory;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function () {
    // activate webhooks in config
    config([
        'platform-resolver.webhooks.enabled' => true,
    ]);
});

it('can only create one hook per platform', function () {
    config(['webhooks' => [
        'example' => [

            /**
             * Validation rules for received data
             */
            'rules' => [
                'foo' => 'required|string',
            ],
        ],
    ]]);

    $platform = createPlatformAndToken()['platform'];

    $platform->webhooks()->create([
        'hook' => 'example',
    ]);

    expect($platform->webhooks()->count())->toBe(1);

    expect(fn () => $platform->webhooks()->create([
        'hook' => 'example',
    ]))->toThrow(\Illuminate\Database\QueryException::class);

    expect($platform->webhooks()->count())->toBe(1);
});

it('can only create hooks from config file', function () {
    config(['webhooks' => [
        'example' => [

            /**
             * Validation rules for received data
             */
            'rules' => [
                'foo' => 'required|string',
            ],
        ],
    ]]);

    $platform = createPlatformAndToken()['platform'];

    $platform->webhooks()->create([
        'hook' => 'example',
    ]);

    expect($platform->webhooks()->count())->toBe(1);

    $platform->webhooks()->create([
        'hook' => 'test',
    ]);

    expect($platform->webhooks()->count())->toBe(1);
});

it('can activate or deactivate webhooks', function () {
    $url = config('platform-resolver.webhooks.endpoint', '/v1/webhooks');

    get($url)->assertStatus(403);

    // activate webhooks in config
    config([
        'platform-resolver.webhooks.enabled' => false,
    ]);
    get($url)->assertStatus(404);
});

it('can store webhook requests in DB', function () {
    configureTestHook();

    $test = createPlatformAndToken();

    $platform = $test['platform'];
    $token = $test['token'];

    $url = config('platform-resolver.webhooks.endpoint', '/v1/webhooks');

    post($url, [
        'hook' => 'example',
        'data' => json_encode([
            'foo' => 'bar',
        ]),
    ], [
        AuthTokenTypeEnum::Secret->getHeaderName() => $token,
    ])
    ->assertStatus(200)
    ->assertJson(function (AssertableJson $json) {
        return $json->hasAll(['response', 'message'])->where('response', null);
    });

    $webhook = Webhook::first();

    expect($webhook->platform->id)->toEqual($platform->id);
});

it('can store webhook requests in DB and call closure afterwards', function () {
    configureTestHook(function () {
        return [
            'bar' => 'foofoo',
        ];
    });

    $token = createPlatformAndToken()['token'];

    $url = config('platform-resolver.webhooks.endpoint', '/v1/webhooks');

    post($url, [
        'hook' => 'example',
        'data' => json_encode([
            'foo' => 'bar',
        ]),
    ], [
        AuthTokenTypeEnum::Secret->getHeaderName() => $token,
    ])
    ->assertStatus(200)
    ->assertJson(function (AssertableJson $json) {
        return $json
            ->hasAll(['response', 'message'])
            ->whereNot('response', null)
            ->where('response', ['bar' => 'foofoo']);
    });
});

it('can store webhook requests in DB and call invokeable afterwards', function () {
    configureTestHook(new class
    {
        public function __invoke()
        {
            return ['bar' => 'foofoo'];
        }
    });

    $token = createPlatformAndToken()['token'];

    $url = config('platform-resolver.webhooks.endpoint', '/v1/webhooks');

    post($url, [
        'hook' => 'example',
        'data' => json_encode([
            'foo' => 'bar',
        ]),
    ], [
        AuthTokenTypeEnum::Secret->getHeaderName() => $token,
    ])
    ->assertStatus(200)
    ->assertJson(function (AssertableJson $json) {
        return $json
            ->hasAll(['response', 'message'])
            ->whereNot('response', null)
            ->where('response', ['bar' => 'foofoo']);
    });
});

function configureTestHook($callback = null)
{
    config(['webhooks' => [
        'example' => [

            /**
             * Validation rules for received data
             */
            'rules' => [
                'foo' => 'required|string',
            ],

            'responseCallback' => $callback,
        ],
    ]]);

    Webhook::create([
        'active' => true,
        'hook' => 'example',
        'platform_id' => 1,
    ]);
}

function createPlatformAndToken()
{
    $platform = (new PlatformFactory())->createOne();
    $token = new AuthToken;

    $token->platform_id = $platform->id;
    $token->save();

    return [
        'platform' => $platform,
        'token' => $token->token,
    ];
}
