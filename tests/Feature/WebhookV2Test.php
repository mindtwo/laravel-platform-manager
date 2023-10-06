<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use mindtwo\LaravelPlatformManager\Enums\DispatchStatusEnum;
use mindtwo\LaravelPlatformManager\Models\DispatchConfiguration;
use mindtwo\LaravelPlatformManager\Models\V2\WebhookDispatch;
use mindtwo\LaravelPlatformManager\Models\V2\WebhookRequest;
use mindtwo\LaravelPlatformManager\Services\DispatchHandlerService;
use mindtwo\LaravelPlatformManager\Services\WebhookResolver;
use mindtwo\LaravelPlatformManager\Tests\Fake\Dispatches\ExampleDispatch;
use mindtwo\LaravelPlatformManager\Tests\Fake\Dispatches\ExampleSyncDispatch;
use mindtwo\LaravelPlatformManager\Webhooks\Handler\DispatchHandler;
use mindtwo\LaravelPlatformManager\Webhooks\Handler\HandleAsyncWebhookRequest;

beforeEach(function () {
    $this->refreshDatabase();

    $this->enableHttpFake();
});

it('can execute a sync webhook', function () {
    $test = $this->createPlatformAndToken();
    $platform = $test['platform'];

    $platform->dispatchConfigurations()->create([
        'hook' => 'example-sync',
        'url' => '/v2/webhooks',
        'auth_token' => $test['token'],
    ]);

    $dispatchHandler = app(DispatchHandlerService::class)->make(ExampleSyncDispatch::class);

    expect($dispatchHandler)->toBeInstanceOf(DispatchHandler::class);

    $result = $dispatchHandler->sendToPlatform($test['platform']);

    expect($result)->toBeTrue();

    $dispatch = WebhookDispatch::first();
    expect($dispatch->status->value)->toBe(DispatchStatusEnum::Answered());

    expect($dispatch->payload['number'])->toBe(1)
        ->and($dispatch->response->payload['doubled'])->toBe(2);
});

it('can execute a sync without platform webhook', function () {
    $test = $this->createPlatformAndToken();
    $platform = $test['platform'];

    DispatchConfiguration::create([
        'hook' => 'example-sync',
        'url' => "https://{$platform->hostname}/v2/webhooks",
        'auth_token' => $test['token'],
    ]);

    $dispatchHandler = app(DispatchHandlerService::class)->make(ExampleSyncDispatch::class);

    expect($dispatchHandler)->toBeInstanceOf(DispatchHandler::class);

    $result = $dispatchHandler->send();

    expect($result)->toBeTrue();

    $dispatch = WebhookDispatch::first();
    expect($dispatch->status->value)->toBe(DispatchStatusEnum::Answered());

    expect($dispatch->payload['number'])->toBe(1)
        ->and($dispatch->response->payload['doubled'])->toBe(2);
});

it('can execute a sync webhook with parameter', function ($number, $doubled) {
    $test = $this->createPlatformAndToken();
    $platform = $test['platform'];

    $platform->dispatchConfigurations()->create([
        'hook' => 'example-sync',
        'url' => '/v2/webhooks',
        'auth_token' => $test['token'],
    ]);

    $dispatchHandler = app(DispatchHandlerService::class)->makeWith(ExampleSyncDispatch::class, [
        'number' => $number,
    ]);

    expect($dispatchHandler)->toBeInstanceOf(DispatchHandler::class);

    $result = $dispatchHandler->sendToPlatform($test['platform']);

    expect($result)->toBeTrue();

    $dispatch = WebhookDispatch::first();
    expect($dispatch->status->value)->toBe(DispatchStatusEnum::Answered());

    expect($dispatch->payload['number'])->toBe($number)
        ->and($dispatch->response->payload['doubled'])->toBe($doubled);

})->with([
    [1, 2],
    [3, 6],
    [6, 12],
]);


it('can execute an async webhook with parameter', function ($number, $doubled) {
    Bus::fake();

    $test = $this->createPlatformAndToken();
    $platform = $test['platform'];

    $platform->dispatchConfigurations()->create([
        'hook' => 'example',
        'url' => '/v2/webhooks',
        'auth_token' => $test['token'],
    ]);

    $dispatchHandler = app(DispatchHandlerService::class)->makeWith(ExampleDispatch::class, [
        'number' => $number,
    ]);

    expect($dispatchHandler)->toBeInstanceOf(DispatchHandler::class);

    $result = $dispatchHandler->sendToPlatform($test['platform']);

    Bus::assertDispatched(HandleAsyncWebhookRequest::class, function ($job) use ($number) {
        return $job->request()->payload['number'] === $number;
    });

    expect($result)->toBeFalse();

    $dispatch = WebhookDispatch::first();
    expect($dispatch->status->value)->toBe(DispatchStatusEnum::Waiting());

    expect($dispatch->payload['number'])->toBe($number)
        ->and($dispatch->response)->toBeNull();

    expect(WebhookRequest::count())->toBe(1);
})->with([
    [1, 2],
    [3, 6],
    [6, 12],
]);

it('can answer an async webhook with parameter', function ($number, $doubled) {
    $fakeBus = Bus::fake();

    $test = $this->createPlatformAndToken();
    $platform = $test['platform'];

    $platform->dispatchConfigurations()->create([
        'hook' => 'example',
        'url' => '/v2/webhooks',
        'auth_token' => $test['token'],
    ]);

    $dispatchHandler = app(DispatchHandlerService::class)->makeWith(ExampleDispatch::class, [
        'number' => $number,
    ]);

    expect($dispatchHandler)->toBeInstanceOf(DispatchHandler::class);

    $result = $dispatchHandler->sendToPlatform($test['platform']);

    expect($result)->toBeFalse();
    Bus::assertDispatched(HandleAsyncWebhookRequest::class, function ($job) use ($number) {
        return $job->request()->payload['number'] === $number;
    });

    $request = WebhookRequest::first();
    (new HandleAsyncWebhookRequest(app(WebhookResolver::class)->resolve('example')::class, $request->payload, $request))->handle();

    $dispatch = WebhookDispatch::first();
    expect($dispatch->status->value)->toBe(DispatchStatusEnum::Answered());

    expect($dispatch->payload['number'])->toBe($number)
        ->and($dispatch->response->payload['doubled'])->toBe($doubled);

    expect(WebhookRequest::count())->toBe(1);
})->with([
    [1, 2],
    [3, 6],
    [6, 12],
]);
