<?php

use Illuminate\Testing\Fluent\AssertableJson;
use mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum;
use mindtwo\LaravelPlatformManager\Models\AuthToken;
use mindtwo\LaravelPlatformManager\Tests\Fake\PlatformFactory;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('can activate or deactivate webhooks', function () {
    $url = config('platform-resolver.webhooks.endpoint', '/v1/webhooks');

    get($url)->assertStatus(404);

    // activate webhooks in config
    config([
        'platform-resolver.webhooks.enabled' => true,
    ]);
    get($url)->assertStatus(403);
});

it('can store webhooks in DB', function () {
    // activate webhooks in config
    config([
        'platform-resolver.webhooks.enabled' => true,
    ]);

    config(['webhooks' => [
        'example' => [

            /**
             * Validation rules for received data
             */
            'rules' => [
                'foo' => 'required|string',
            ],

            // TODO maybe exclude platforms?
            'exclude' => [],
        ],
    ]]);

    $platform = (new PlatformFactory())->createOne();
    $token = new AuthToken;

    $token->platform_id = $platform->id;
    $token->save();

    $url = config('platform-resolver.webhooks.endpoint', '/v1/webhooks');

    post($url, [
        'hook' => 'example',
        'data' => json_encode([
            'foo' => 'bar',
        ]),
    ], [
        AuthTokenTypeEnum::Secret->getHeaderName() => $token->token,
    ])
    ->assertStatus(200)
    ->assertJson(function (AssertableJson $json) {
        return $json->has('message');
    });
});
