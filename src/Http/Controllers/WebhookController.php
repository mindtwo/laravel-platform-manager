<?php

namespace mindtwo\LaravelPlatformManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use mindtwo\LaravelPlatformManager\Http\Requests\StoreWebhookRequest;
use mindtwo\LaravelPlatformManager\Models\Webhook;
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

        $webhooks = Webhook::query()
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
        $currentPlatform->webhooks()->create($storeWebhookRequest->only(['hook', 'data']));

        // TODO dispatch event

        return response()->json([
            'message' => "Data received by platform {$currentPlatform->name}",
        ]);
    }
}
