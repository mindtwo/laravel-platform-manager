<?php

namespace mindtwo\LaravelPlatformManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use mindtwo\LaravelPlatformManager\Http\Requests\ResponseWebhookV2Request;
use mindtwo\LaravelPlatformManager\Services\DispatchHandlerService;

class CallbackWebhookController extends Controller
{
    public function __construct(
        private DispatchHandlerService $dispatchHandlerService,
    ) {

    }

    /**
     * Handle incoming webhook request.
     */
    public function __invoke(ResponseWebhookV2Request $request): JsonResponse
    {
        $dispatchHandler = $this->dispatchHandlerService->makeForUlid($request->validated('ulid'));

        $dispatchHandler->onAsyncResult($request->validated('ulid'), $request->validated('result'));

        return response()->json([
            'message' => 'Webhook callback received.',
            'ulid' => $request->validated('ulid'),
            'hook' => $request->validated('hook'),
        ], 201);
    }
}
