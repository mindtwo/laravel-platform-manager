<?php

namespace mindtwo\LaravelPlatformManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum;
use mindtwo\LaravelPlatformManager\Enums\WebhookTypeEnum;
use mindtwo\LaravelPlatformManager\Models\Platform;
use mindtwo\LaravelPlatformManager\Models\WebhookConfiguration;
use mindtwo\LaravelPlatformManager\Models\WebhookRequest;

class PushToWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ?WebhookRequest $request = null;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Platform $platform,
        public string $hook,
        public array $data,
    ) {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $host = $this->platform->hostname;

        /** @var ?WebhookConfiguration $config */
        $config = $this->platform->webhookConfigurations()->where('hook', $this->hook)->first();
        if ($config === null) {
            return;
        }

        $url = $config->url;

        /** @phpstan-ignore-next-line */
        $this->request = WebhookRequest::create([
            'type' => WebhookTypeEnum::Outgoing(),
            'hook' => $this->hook,
            'request' => $this->data,
            'url' => "https://{$host}{$url}",
        ]);

        $response = Http::withHeaders([
            AuthTokenTypeEnum::Secret->getHeaderName() => $config->auth_token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post("https://{$host}{$url}", [
            'hook' => $this->hook,
            'data' => $this->data,
        ])->throw();

        $this->request->update([
            'response' => $response->body(),
            'status' => $response->status(),
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        if ($this->request === null) {
            return;
        }

        $response = $exception->response ?? false;

        $this->request->update([
            'status' => $response ? $response->status() : 999,
            'response' => $response ? $response->body() : $exception->getMessage(),
        ]);
    }
}
