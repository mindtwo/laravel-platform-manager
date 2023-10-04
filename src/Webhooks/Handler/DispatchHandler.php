<?php

namespace mindtwo\LaravelPlatformManager\Webhooks\Handler;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum;
use mindtwo\LaravelPlatformManager\Enums\DispatchStatusEnum;
use mindtwo\LaravelPlatformManager\Models\Platform;
use mindtwo\LaravelPlatformManager\Models\V2\WebhookDispatch;
use mindtwo\LaravelPlatformManager\Webhooks\Dispatch;

class DispatchHandler
{
    private WebhookDispatch $dispatchModel;

    public function __construct(
        private Dispatch $dispatchInstance,
    ) {
    }

    public function sendToPlatform(Platform $platform): mixed
    {
        try {
            $host = $platform->hostname;
            $hookName = $this->dispatchInstance->hook();

            /** @var ?WebhookConfiguration $config */
            $config = $platform->webhookConfigurations()->where('hook', $hookName)->first();
            if ($config === null) {
                return null;
            }

            $url = $config->url;

            $this->dispatchModel = WebhookDispatch::create([
                'hook' => $this->dispatchInstance->hook(),
                'payload' => $this->dispatchInstance->requestPayload(),
                'ulid' => Str::ulid(),
                'platform_id' => $platform->id,
                'url' => "https://{$host}{$url}",
                'dispatch_class' => $this->dispatchInstance::class,
                'status' => DispatchStatusEnum::Dispatched(),
            ]);

            $response = Http::withHeaders([
                AuthTokenTypeEnum::Secret->getHeaderName() => $config->auth_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("https://{$host}{$url}", [
                'hook' => $hookName,
                'ulid' => $this->dispatchModel->ulid,
                'data' => $this->dispatchInstance->requestPayload(),
                'response_url' => route('platform.webhooks.callback_url'),
            ])->throw();

            // here we get the response from the webhook which is handled via a queue
            if ($response->status() === 202) {
                $this->dispatchModel->update([
                    'status' => DispatchStatusEnum::Waiting(),
                ]);

                return null;
            }

            return $this->dispatchInstance->onResult($response->json('result'));
        } catch (\Throwable $th) {
            $this->dispatchInstance->onError($th);
        }
    }

    /**
     * Directly handle the webhook response.
     *
     * @param array $result
     * @return mixed
     */
    public function onResult(array $result): mixed
    {
        $this->dispatchModel->response()->create([
            'payload' => $result,
            'hook' => $this->dispatchInstance->hook(),
        ]);

        $this->dispatchModel->update([
            'status' => DispatchStatusEnum::Answered(),
        ]);

        return $this->dispatchInstance->onResult($result);
    }

    /**
     * This method is called from the webhook callback controller.
     */
    public function onAsyncResult(WebhookDispatch $dispatchModel, array $result): void
    {
        $this->dispatchModel = $dispatchModel;

        $this->dispatchInstance->onResult($result);
    }
}
