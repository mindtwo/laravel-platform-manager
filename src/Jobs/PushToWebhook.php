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
use mindtwo\LaravelPlatformManager\Models\WebhookRequest;

class PushToWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        $config = $this->platform->webhookConfigurations()->where('hook', $this->hook)->first();
        $url = $config->url;

        $response = Http::withHeaders([
            AuthTokenTypeEnum::Secret() => $config->auth_token,
        ])->post("{$host}{$url}", [
            'hook' => $this->hook,
            'data' => $this->data,
        ])->throw();

        WebhookRequest::create([
            'type' => WebhookTypeEnum::Outgoing(),
            'hook' => $this->hook,
            'response' => $response->body(),
            'request' => $this->data,
            'status' => $response->status(),
            'url' => "{$host}{$url}",
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        $host = $this->platform->hostname;

        $config = $this->platform->webhookConfigurations()->where('hook', $this->hook)->first();
        $url = $config->url ?? '';

        $response = $exception->response ?? false;

        WebhookRequest::create([
            'type' => WebhookTypeEnum::Outgoing(),
            'hook' => $this->hook,
            'response' => $response ? $response->body() : $exception->getMessage(),
            'request' => $this->data,
            'status' => $response ? $response->status() : 999,
            'url' => "{$host}{$url}",
        ]);
    }
}
