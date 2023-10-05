<?php

namespace mindtwo\LaravelPlatformManager\Webhooks\Handler;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum;
use mindtwo\LaravelPlatformManager\Enums\DispatchStatusEnum;
use mindtwo\LaravelPlatformManager\Models\Platform;
use mindtwo\LaravelPlatformManager\Models\V2\WebhookDispatch;
use mindtwo\LaravelPlatformManager\Models\WebhookConfiguration;
use mindtwo\LaravelPlatformManager\Webhooks\Dispatch;

class DispatchHandler
{
    private WebhookDispatch $dispatchModel;

    public function __construct(
        private Dispatch $dispatchInstance,
    ) {
        $this->dispatchModel = new WebhookDispatch;
    }

    public function sendToPlatform(Platform $platform): bool
    {
        try {
            $hookName = $this->dispatchInstance->hook();

            try {
                /** @var WebhookConfiguration $config */
                $config = $platform->webhookConfigurations()->where('hook', $hookName)->firstOrFail();
            } catch (\Throwable $th) {
                throw new HttpResponseException(
                    response()->json([
                        'message' => 'Webhook not configured.',
                    ], 404));
            }

            $this->dispatchModel->fill([
                'hook' => $this->dispatchInstance->hook(),
                'payload' => $this->dispatchInstance->requestPayload(),
                'ulid' => Str::ulid()->toBase58(),
                'platform_id' => $platform->id,
                'url' => $config->webhook_url,
                'dispatch_class' => $this->dispatchInstance::class,
                'status' => DispatchStatusEnum::Dispatched(),
            ]);
            $this->dispatchModel->save();

            $response = Http::withHeaders([
                AuthTokenTypeEnum::Secret->getHeaderName() => $config->auth_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($config->webhook_url, [
                'hook' => $hookName,
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
        } catch (\Throwable $th) {
            $this->dispatchInstance->onError($th);

            return false;
        }
    }

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
