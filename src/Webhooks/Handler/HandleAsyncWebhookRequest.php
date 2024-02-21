<?php

namespace mindtwo\LaravelPlatformManager\Webhooks\Handler;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use mindtwo\LaravelPlatformManager\Models\V2\WebhookRequest;
use mindtwo\LaravelPlatformManager\Webhooks\Webhook;

/**
 * @property Webhook $webhook
 */
class HandleAsyncWebhookRequest implements ShouldQueue
{
    use Dispatchable;
    use ValidatesPayload;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use SavesResponse;

    private ?Webhook $webhook = null;

    /**
     * Create a new job instance.
     *
     * @param class-string<Webhook> $webhookClz
     * @param array $payload
     */
    public function __construct(
        private string $webhookClz,
        private array $payload,
        private WebhookRequest $request,
        public $timeout,
    ) {
    }

    public function request(): WebhookRequest
    {
        return $this->request;
    }

    /**
     * Resolve the webhook instance.
     *
     * @return Webhook
     */
    private function resolveWebhook(): Webhook
    {
        if ($this->webhook === null) {
            $this->webhook = app()->make($this->webhookClz);
        }

        $this->webhook->setPlatform($this->request->platform);
        return $this->webhook;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->request->response_url) {
            $this->fail(new \Exception('No response url provided.'));

            return;
        }

        $this->resolveWebhook();

        /** @var string $responseUrl */
        $responseUrl = $this->request->response_url;

        try {
            $payload = $this->validatePayload();

            $result = $this->webhook->handle($payload);

            // save result value to database
            $this->saveWebhookResponse($result);

            // send result via response url
            Http::retry(5, 100, function (\Exception $exception, PendingRequest $request) {
                return $exception instanceof ConnectionException;
            })->post($responseUrl, [
                'message' => 'Webhook handled successfully.',
                'ulid' => $this->request->ulid,
                'hook' => $this->request->hook,
                'result' => $result,
            ])->throw();
        } catch (\Throwable $th) {
            if ($th instanceof RequestException) {
                $this->fail($th);

                return;
            }

            // save error value to database
            $this->saveWebhookResponse($this->webhook->onError($th));
        }
    }
}
