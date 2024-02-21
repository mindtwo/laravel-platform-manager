<?php

namespace mindtwo\LaravelPlatformManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use mindtwo\LaravelPlatformManager\Http\Requests\StoreWebhookV2Request;
use mindtwo\LaravelPlatformManager\Models\V2\WebhookRequest;
use mindtwo\LaravelPlatformManager\Services\PlatformResolver;
use mindtwo\LaravelPlatformManager\Services\WebhookResolver;
use mindtwo\LaravelPlatformManager\Webhooks\Concerns\RespondsSync;
use mindtwo\LaravelPlatformManager\Webhooks\Handler\HandleAsyncWebhookRequest;
use mindtwo\LaravelPlatformManager\Webhooks\Handler\HandleSyncWebhookRequest;

class HandleWebhookController extends Controller
{
    public function __construct(
        private WebhookResolver $resolver,
        private PlatformResolver $platformResolver,
    ) {

    }

    /**
     * Handle incoming webhook request.
     *
     * @param StoreWebhookV2Request $request
     * @return JsonResponse
     */
    public function __invoke(StoreWebhookV2Request $request): JsonResponse
    {
        $hookName = $request->validated('hook');

        // Get current platform
        $platform = $this->platformResolver->getCurrentPlatform();

        // Resolve webhook
        // WebhookResolver::resolve() returns an instance of the webhook class or exits the process with an error 404.
        $webhook = $this->resolver->resolve($hookName);

        /** @var array<string, mixed> $data */
        $data = $request->validated('data');

        // save the request to the database
        /** @var WebhookRequest $requestModel */
        $requestModel = WebhookRequest::create([
            'hook' => $hookName,
            'ulid' => $request->validated('ulid'),
            'requested_from' => $request->host(),
            'response_url' => $request->validated('response_url'),
            'payload' => $webhook->payloadToSave($data),
        ]);

        // TODO check why we need to do it this way. platform_id is fillable???????????
        $requestModel->platform_id = $platform->id;
        $requestModel->save();

        // Handle sync webhook
        if ($webhook instanceof RespondsSync) {
            $result = (new HandleSyncWebhookRequest($webhook, $request->validated('data'), $requestModel))->handle();

            return response()->json([
                'message' => 'Webhook handled successfully.',
                'ulid' => $requestModel->ulid,
                'hook' => $requestModel->hook,
                'result' => $result,
            ], 201);
        }

        // Handle async webhook via queue
        HandleAsyncWebhookRequest::dispatch(
            $webhook::class,
            $request->validated('data'),
            $requestModel,
        );

        return response()->json([
            'message' => 'Webhook queued successfully.',
            'hook' => $hookName,
            'ulid' => $request->validated('ulid'),
        ], 202);
    }
}
