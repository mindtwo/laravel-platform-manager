<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use mindtwo\LaravelPlatformManager\Jobs\Concerns\HasPlatformContext;
use mindtwo\LaravelPlatformManager\Platform;
use mindtwo\LaravelPlatformManager\Tests\Fake\PlatformFactory;

uses(RefreshDatabase::class);

describe('QueueContext', function () {
    it('captures platform context id when platform is resolved', function () {
        $model = (new PlatformFactory())->create();

        app(Platform::class)->set($model, 'test');

        $job = new class
        {
            use HasPlatformContext;

            public function __construct()
            {
                $this->capturePlatformContext();
            }
        };

        expect($job->platformContextId)->toBe($model->id);
        expect($job->platformContextResolver)->toBe('test');
    });

    it('does not capture context when platform is not resolved', function () {
        $job = new class
        {
            use HasPlatformContext;

            public function __construct()
            {
                $this->capturePlatformContext();
            }
        };

        expect($job->platformContextId)->toBeNull();
        expect($job->platformContextResolver)->toBeNull();
    });

    it('restores platform context from captured id', function () {
        $model = (new PlatformFactory())->create();

        app(Platform::class)->set($model, 'host');

        $job = new class
        {
            use HasPlatformContext;

            public function __construct()
            {
                $this->capturePlatformContext();
            }

            public function handle(): void
            {
                $this->restorePlatformContext();
            }
        };

        // Reset the singleton so context is lost
        app()->forgetInstance(Platform::class);
        app()->singleton(Platform::class, fn () => new Platform);

        expect(app(Platform::class)->isResolved())->toBeFalse();

        $job->handle();

        expect(app(Platform::class)->isResolved())->toBeTrue();
        expect(app(Platform::class)->get()->id)->toBe($model->id);
        expect(app(Platform::class)->resolver())->toBe('queue-restore');
    });
});
