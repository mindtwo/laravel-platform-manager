<?php

namespace mindtwo\LaravelPlatformManager\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Http;
use mindtwo\LaravelPlatformManager\Http\Controllers\CallbackWebhookController;
use mindtwo\LaravelPlatformManager\Http\Controllers\HandleWebhookController;
use mindtwo\LaravelPlatformManager\Http\Requests\ResponseWebhookV2Request;
use mindtwo\LaravelPlatformManager\Http\Requests\StoreWebhookV2Request;
use mindtwo\LaravelPlatformManager\Models\AuthToken;
use mindtwo\LaravelPlatformManager\Tests\Fake\PlatformFactory;

class WebhookTestCase extends TestCase
{

    use RefreshDatabase;

    public function enableHttpFake()
    {

        Http::fake([
            // Stub a JSON response for GitHub endpoints...
            '*/v2/webhooks' => function (Request $request) {
                $storeRequest = StoreWebhookV2Request::create($request->url(), 'POST', $request->data());
                $storeRequest->setContainer(app())->setRedirector(app(Redirector::class))->validateResolved();

                /** @var JsonResponse $response */
                $response = app(HandleWebhookController::class)($storeRequest);

                return Http::response($response->getData(true), $response->status(), $response->headers->all());
            },

            // Stub a string response for Google endpoints...
            '*/v2/callback' => function (Request $request) {
                $storeRequest = ResponseWebhookV2Request::create($request->url(), 'POST', $request->data());
                $storeRequest->setContainer(app())->setRedirector(app(Redirector::class))->validateResolved();

                /** @var JsonResponse $response */
                $response = app(CallbackWebhookController::class)($storeRequest);

                return Http::response($response->getData(true), $response->status(), $response->headers->all());
            },
        ]);

    }

    public function createPlatformAndToken()
    {
        $platform = (new PlatformFactory())->local()->createOne();
        $token = new AuthToken;

        $token->platform_id = $platform->id;
        $token->save();

        return [
            'platform' => $platform,
            'token' => $token->token,
        ];
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            ...parent::getPackageProviders($app),
            \mindtwo\LaravelPlatformManager\Tests\Fake\TestWebhookServiceProvider::class,
        ];
    }
}
