<?php

namespace mindtwo\LaravelPlatformManager\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use mindtwo\LaravelPlatformManager\Models\V2\WebhookDispatch;
use mindtwo\LaravelPlatformManager\Webhooks\Dispatch;
use mindtwo\LaravelPlatformManager\Webhooks\Handler\DispatchHandler;

class DispatchHandlerService
{
    public function makeWith(string|Dispatch $dispatch, array $payload): DispatchHandler
    {
        $dispatchInstance = app()->makeWith(is_string($dispatch) ? $dispatch : $dispatch::class, $payload);

        return new DispatchHandler($dispatchInstance);
    }

    public function make(string|Dispatch $dispatch): DispatchHandler
    {
        $dispatchInstance = app()->make(is_string($dispatch) ? $dispatch : $dispatch::class);

        return new DispatchHandler($dispatchInstance);
    }

    public function makeForUlid(string $ulid): DispatchHandler
    {
        try {
            /** @var WebhookDispatch $dispatchModel */
            $dispatchModel = WebhookDispatch::where('ulid', $ulid)->firstOrFail();

        } catch (ModelNotFoundException $e) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Dispatch not found.',
                ], 404)
            );
        }

        $dispatchInstance = app()->makeWith($dispatchModel->dispatch_class, $dispatchModel->payload ?? []);

        return new DispatchHandler($dispatchInstance);
    }
}
