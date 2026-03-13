<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use mindtwo\LaravelPlatformManager\Platform;
use mindtwo\LaravelPlatformManager\Tests\Fake\PlatformFactory;

uses(RefreshDatabase::class);

describe('PlatformSettings', function () {
    it('returns null when settings are empty', function () {
        $platform = (new PlatformFactory)->create(['settings' => null]);

        expect($platform->setting('some.key'))->toBeNull();
        expect($platform->setting('some.key', 'default'))->toBe('default');
    });

    it('can read a top-level setting', function () {
        $platform = (new PlatformFactory)->create([
            'settings' => ['timezone' => 'Europe/Berlin'],
        ]);

        expect($platform->setting('timezone'))->toBe('Europe/Berlin');
    });

    it('can read a nested setting using dot notation', function () {
        $platform = (new PlatformFactory)->create([
            'settings' => ['mail' => ['from' => 'hello@example.com']],
        ]);

        expect($platform->setting('mail.from'))->toBe('hello@example.com');
    });

    it('returns default when nested key is missing', function () {
        $platform = (new PlatformFactory)->create([
            'settings' => ['mail' => ['from' => 'hello@example.com']],
        ]);

        expect($platform->setting('mail.reply_to', 'noreply@example.com'))->toBe('noreply@example.com');
    });

    it('can read settings via the platform context class', function () {
        $model = (new PlatformFactory)->create([
            'settings' => ['app_name' => 'My Platform'],
        ]);

        $context = app(Platform::class);
        $context->set($model, 'test');

        expect($context->setting('app_name'))->toBe('My Platform');
        expect($context->setting('missing', 'fallback'))->toBe('fallback');
    });
});
