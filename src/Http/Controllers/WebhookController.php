<?php

namespace mindtwo\LaravelPlatformManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use mindtwo\LaravelPlatformManager\Enums\WebhookTypeEnum;
use mindtwo\LaravelPlatformManager\Events\WebhookReceivedEvent;
use mindtwo\LaravelPlatformManager\Http\Requests\StoreWebhookRequest;
use mindtwo\LaravelPlatformManager\Models\Webhook;
use mindtwo\LaravelPlatformManager\Models\WebhookRequest;
use mindtwo\LaravelPlatformManager\Services\PlatformResolver;

class WebhookController extends Controller
{
    public function __construct(
        protected PlatformResolver $platformResolver,
    ) {
    }

    /**
     * List all Webhook requests.
     */
    public function index(Request $request): JsonResponse
    {
        abort_if(Gate::denies('view-webhooks'), 403, 'Unauthorized');

        $webhooks = WebhookRequest::query()
            ->forPlatform($this->platformResolver->getCurrentPlatform())
            ->when($request->boolean('processed'), fn ($query) => $query->processed())
            ->get();

        return response()->json([
            'data' => $webhooks,
        ]);
    }

    /**
     * Store webhook request.
     */
    public function store(StoreWebhookRequest $storeWebhookRequest): JsonResponse
    {
        $currentPlatform = $this->platformResolver->getCurrentPlatform();
        $hookName = $storeWebhookRequest->validated('hook');

        try {
            $request = WebhookRequest::create([
                'type' => WebhookTypeEnum::Incoming(),
                'hook' => $storeWebhookRequest->validated('hook'),
                'response' => $storeWebhookRequest->validated('data'),
            ]);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
        }


        // TODO dispatch event
        WebhookReceivedEvent::dispatch($request, $currentPlatform);

        $callback = config("webhooks.{$hookName}.responseCallback");
        if (is_callable($callback) || gettype($callback) === 'string' && is_callable($callback = new $callback)) {
            $response = $callback($request);
        }

        return response()->json([
            'message' => "Data received by platform {$currentPlatform->name}",
            'response' => $response ?? null,
        ]);
    }
}
