<?php
use Illuminate\Testing\Fluent\AssertableJson;
use mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum;
use mindtwo\LaravelPlatformManager\Enums\WebhookTypeEnum;
use mindtwo\LaravelPlatformManager\Models\Webhook;
use mindtwo\LaravelPlatformManager\Models\WebhookRequest;

beforeEach(function () {
    $this->refreshDatabase();
});

it('can only create one hook per platform', function () {
    $platform = $this->createPlatformAndToken()['platform'];

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
    $platform = $this->createPlatformAndToken()['platform'];

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
    $url = config('platform-resolver.webhooks.endpoint');

    $this->get($url)->assertStatus(403);

    // activate webhooks in config
    config([
        'platform-resolver.webhooks.enabled' => false,
    ]);
    $this->get($url)->assertStatus(404);
});

it('can activate or deactivate single webhooks', function () {
    $url = config('platform-resolver.webhooks.endpoint');
    $webhook = Webhook::create([
        'active' => true,
        'hook' => 'example',
        'platform_id' => 1,
    ]);

    expect($webhook->active)->toBeTrue();

    $test = $this->createPlatformAndToken();
    $token = $test['token'];

    $this->postJson(
        $url,
        [
            'hook' => $webhook->hook,
            'data' => [
                'foo' => 'bar',
            ],
        ],
        [AuthTokenTypeEnum::Secret->getHeaderName() => $token]
    )
    ->assertStatus(200);

    $webhook->update([
        'active' => false,
    ]);

    $this->postJson(
        $url,
        [
            'hook' => $webhook->hook,
            'data' => [
                'foo' => 'bar',
            ],
        ],
        [AuthTokenTypeEnum::Secret->getHeaderName() => $token]
    )->assertStatus(404);

    expect($webhook->active)->toBeFalse();
});

it('can store webhook requests in DB', function () {
    $test = $this->createPlatformAndToken();
    $token = $test['token'];

    $url = config('platform-resolver.webhooks.endpoint');

    $this->postJson(
        $url,
        [
            'hook' => 'example',
            'data' => [
                'foo' => 'bar',
            ],
        ],
        [AuthTokenTypeEnum::Secret->getHeaderName() => $token]
    )
    ->assertStatus(200)
    ->assertJson(function (AssertableJson $json) {
        return $json->hasAll(['response', 'message'])->where('response', null);
    });

    $webhookRequest = WebhookRequest::first();

    expect($webhookRequest)->toMatchArray([
        'hook' => 'example',
        'type' => WebhookTypeEnum::Incoming(),
        'request' => [
            'foo' => 'bar',
        ],
        'response' => null,
    ]);
});

it('can store webhook requests in DB and call closure afterwards', function () {
    config()->set('webhooks.example.responseCallback', function () {
        return [
            'bar' => 'foofoo',
        ];
    });

    $token = $this->createPlatformAndToken()['token'];
    $url = config('platform-resolver.webhooks.endpoint');

    $this->postJson(
        $url,
        [
            'hook' => 'example',
            'data' => [
                'foo' => 'bar',
            ],
        ],
        [AuthTokenTypeEnum::Secret->getHeaderName() => $token]
    )
    ->assertStatus(200)
    ->assertJson(function (AssertableJson $json) {
        return $json
            ->hasAll(['response', 'message'])
            ->whereNot('response', null)
            ->where('response', ['bar' => 'foofoo']);
    });

    $webhookRequest = WebhookRequest::first();
    expect($webhookRequest)->toMatchArray([
        'hook' => 'example',
        'type' => WebhookTypeEnum::Incoming(),
        'request' => [
            'foo' => 'bar',
        ],
        'response' => [
            'bar' => 'foofoo',
        ],
    ]);
});

it('can store webhook requests in DB and call invokeable afterwards', function () {
    config()->set('webhooks.example.responseCallback', new class {
        public function __invoke()
        {
            return ['bar' => 'foofoo'];
        }
    });

    $token = $this->createPlatformAndToken()['token'];

    $url = config('platform-resolver.webhooks.endpoint');

    $this->postJson(
        $url,
        [
            'hook' => 'example',
            'data' => [
                'foo' => 'bar',
            ],
        ],
        [AuthTokenTypeEnum::Secret->getHeaderName() => $token]
    )
    ->assertStatus(200)
    ->assertJson(function (AssertableJson $json) {
        return $json
            ->hasAll(['response', 'message'])
            ->whereNot('response', null)
            ->where('response', ['bar' => 'foofoo']);
    });

    $webhookRequest = WebhookRequest::first();
    expect($webhookRequest)->toMatchArray([
        'hook' => 'example',
        'type' => WebhookTypeEnum::Incoming(),
        'request' => [
            'foo' => 'bar',
        ],
        'response' => [
            'bar' => 'foofoo',
        ],
    ]);
});
