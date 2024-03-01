<?php

namespace mindtwo\LaravelPlatformManager\Webhooks\Handler;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum;
use mindtwo\LaravelPlatformManager\Enums\DispatchStatusEnum;
use mindtwo\LaravelPlatformManager\Models\DispatchConfiguration;
use mindtwo\LaravelPlatformManager\Models\Platform;
use mindtwo\LaravelPlatformManager\Models\V2\WebhookDispatch;
use mindtwo\LaravelPlatformManager\Services\PlatformResolver;
use mindtwo\LaravelPlatformManager\Webhooks\Concerns\SendsEmptyPayload;
use mindtwo\LaravelPlatformManager\Webhooks\Dispatch;

class DispatchHandler
{
    private WebhookDispatch $dispatchModel;

    public function __construct(
        private Dispatch $dispatchInstance,
    ) {
        $this->dispatchModel = new WebhookDispatch;
    }

    public function send(): bool
    {
        $hookName = $this->dispatchInstance->hook();

        try {
            /** @var DispatchConfiguration $config */
            $config = DispatchConfiguration::where('hook', $hookName)->with(['platform', 'externalPlatform'])->firstOrFail();
        } catch (\Throwable $th) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Dispatch not configured for platform.',
                ], 404));
        }

        try {
            $this->fillDispatchModel($config);

            return $this->sendRequest($hookName, $config);
        } catch (\Throwable $th) {
            $this->dispatchInstance->onError($th);

            return false;
        }
    }

    public function sendToCurrentPlatform(): bool
    {
        $platformResolver = app()->make(PlatformResolver::class);
        return $this->sendToPlatform($platformResolver->getCurrentPlatform());
    }

    public function sendToPlatform(Platform $platform): bool
    {
        $hookName = $this->dispatchInstance->hook();

        try {
            /** @var DispatchConfiguration $config */
            $config = $platform->dispatchConfigurations()
                ->where('hook', $hookName)
                ->where('platform_id', $platform->id)
                ->with('platform')
                ->firstOrFail();
        } catch (\Throwable $th) {
            throw new HttpResponseException(
                response()->json([
                    'message' => "Dispatch not configured with name $hookName.",
                ], 404));
        }

        try {
            $this->fillDispatchModel($config, $platform);

            return $this->sendRequest($hookName, $config);
        } catch (\Throwable $th) {
            $this->dispatchInstance->onError($th);

            return false;
        }
    }

    /**
     * Send the request to the configured endpoint.
     */
    private function sendRequest(string $hook, DispatchConfiguration $dispatchConfiguration): bool
    {
        $requestPayload = $this->dispatchInstance->requestPayload();
        if (! ($this->dispatchInstance instanceof SendsEmptyPayload) && empty($requestPayload)) {
            $this->dispatchModel->update([
                'status' => DispatchStatusEnum::Aborted(),
            ]);

            return false;
        }

        $response = Http::withHeaders([
            AuthTokenTypeEnum::Secret->getHeaderName() => $dispatchConfiguration->auth_token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($dispatchConfiguration->endpoint, [
            'hook' => $hook,
            'ulid' => $this->dispatchModel->ulid,
            'data' => $this->dispatchInstance->requestPayload(),
            'response_url' => route('webhooks.v2.callback'),
        ])->throw();

        // here we get the response from the webhook which is handled via a queue
        if ($response->status() === 202) {
            $this->dispatchModel->update([
                'status' => DispatchStatusEnum::Waiting(),
            ]);

            return false;
        }

        return $this->onResult($response->json('result'));
    }

    /**
     * Fill the dispatch model with the given configuration.
     */
    private function fillDispatchModel(DispatchConfiguration $config, Platform $platform = null): void
    {
        $payload = $this->dispatchInstance->payloadArray();

        $this->dispatchModel->fill([
            'hook' => $this->dispatchInstance->hook(),
            'ulid' => Str::ulid()->toBase58(),
            'url' => $config->endpoint,
            'platform_id' => $platform?->id,
            'dispatch_class' => $this->dispatchInstance::class,
            'status' => DispatchStatusEnum::Dispatched(),
            'payload' => $this->dispatchInstance->payloadToSave($payload),
        ]);
        $this->dispatchModel->save();
    }

    /**
     * Save the response to the database and mark the dispatch as answered.
     */
    private function saveResponse(array $result): void
    {
        $this->dispatchModel->response()->create([
            'payload' => $result,
            'ulid' => $this->dispatchModel->ulid,
            'hook' => $this->dispatchInstance->hook(),
        ]);

        $this->dispatchModel->update([
            'status' => DispatchStatusEnum::Answered(),
        ]);
    }

    /**
     * Directly handle the webhook response.
     */
    public function onResult(array $result): bool
    {
        $this->saveResponse($result);

        $this->dispatchInstance->onResult($result);

        return true;
    }

    /**
     * This method is called from the webhook callback controller.
     */
    public function onAsyncResult(string $ulid, array $result): void
    {
        try {
            /** @var WebhookDispatch $dispatch */
            $dispatch = WebhookDispatch::where('ulid', $ulid)->firstOrFail();
        } catch (\Throwable $th) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Dispatch not found.',
                ], 404));
        }

        $this->dispatchModel = $dispatch;
        $this->saveResponse($result);

        $this->dispatchInstance->onResult($result);
    }
}
