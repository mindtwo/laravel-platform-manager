<?php

namespace mindtwo\LaravelPlatformManager\Webhooks\Handler;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use mindtwo\LaravelPlatformManager\Models\V2\WebhookRequest;
use mindtwo\LaravelPlatformManager\Webhooks\Webhook;

class HandleAsyncWebhookRequest
{
    use Dispatchable;
    use ValidatesPayload;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use SavesResponse;

    private Webhook $webhook;

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
    ) {
        $this->webhook = app()->make($webhookClz);
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

        try {
            $payload = $this->validatePayload();

            $result = $this->webhook->handle($payload);

            // save result value to database
            $this->saveWebhookResponse($result);

            // send result via response url
            Http::retry(5, 100, function (\Exception $exception, PendingRequest $request) {
                return $exception instanceof ConnectionException;
            })->post($this->request->response_url, [
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
