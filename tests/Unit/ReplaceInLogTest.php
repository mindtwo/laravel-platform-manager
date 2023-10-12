<?php

use mindtwo\LaravelPlatformManager\Enums\DispatchStatusEnum;
use mindtwo\LaravelPlatformManager\Models\V2\WebhookDispatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use mindtwo\LaravelPlatformManager\Models\V2\WebhookRequest;
use mindtwo\LaravelPlatformManager\Tests\Fake\Dispatches\ExampleDispatchExcludes;
use mindtwo\LaravelPlatformManager\Tests\Fake\Webhooks\ExampleWebhookExcludes;

uses(RefreshDatabase::class);

it('excludes keys from log for dispatch', function () {

    $dispatch = new ExampleDispatchExcludes(1, 'log me', 'but not me');

    expect(WebhookDispatch::count())->toBe(0);

    WebhookDispatch::create([
        'hook' => $dispatch->hook(),
        'ulid' => Str::ulid()->toBase58(),
        'url' => fake()->url(),
        'platform_id' => 1,
        'dispatch_class' => $dispatch::class,
        'status' => DispatchStatusEnum::Dispatched(),
        'payload' => $dispatch->payloadToSave($dispatch->payloadArray()),
    ]);

    $model = WebhookDispatch::first();

    expect($model->payload)->toContain('log me')
        ->and($model->payload)->not()->toContain('but not me')
        ->and($model->payload['another_payload_str'])->toBe('[EXCLUDED]');

});

it('excludes keys from log for request', function () {

    $data = [
        'payload_str' => 'log me',
        'another_payload_str' => 'but not me',
    ];

    $webhook = new ExampleWebhookExcludes();

    expect(WebhookRequest::count())->toBe(0);

    WebhookRequest::create([
        'hook' => $webhook->name(),
        'ulid' => Str::ulid()->toBase58(),
        'requested_from' => fake()->url(),
        'payload' => $webhook->payloadToSave($data),
    ]);

    $request = WebhookRequest::first();

    expect($request->payload)->toContain('log me')
        ->and($request->payload)->not()->toContain('but not me')
        ->and($request->payload['another_payload_str'])->toBe('[EXCLUDED]');

});
