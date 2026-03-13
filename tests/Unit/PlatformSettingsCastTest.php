<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use mindtwo\LaravelPlatformManager\Settings\PlatformSettings;
use mindtwo\LaravelPlatformManager\Tests\Fake\PlatformFactory;

uses(RefreshDatabase::class);

describe('PlatformSettingsCast', function () {
    describe('PlatformSettings DTO', function () {
        it('fromArray() hydrates declared public properties', function () {
            $settings = PlatformSettings::fromArray(['timezone' => 'Europe/Berlin']);

            // timezone is not a declared property → goes to overflow
            expect($settings->toArray()['timezone'])->toBe('Europe/Berlin');
        });

        it('unknown keys are stored in overflow and round-trip through toArray()', function () {
            $settings = PlatformSettings::fromArray(['foo' => 'bar', 'nested' => ['a' => 1]]);

            $array = $settings->toArray();
            expect($array['foo'])->toBe('bar');
            expect($array['nested'])->toBe(['a' => 1]);
        });

        it('toStorageArray() returns plain values for non-encrypted keys', function () {
            $settings = PlatformSettings::fromArray(['key' => 'value']);

            expect($settings->toStorageArray()['key'])->toBe('value');
        });

        it('encrypted fields are encrypted in toStorageArray() and decrypted in fromArray()', function () {
            $custom = new class extends PlatformSettings
            {
                protected array $encrypted = ['secret'];

                public ?string $secret = null;
            };

            $custom->secret = 'my-secret';

            $stored = $custom->toStorageArray();
            expect($stored['secret'])->not->toBe('my-secret');

            $restored = $custom::fromArray($stored);
            expect($restored->secret)->toBe('my-secret');
        });

        it('encrypted fields do not appear in plain text in toStorageArray()', function () {
            $custom = new class extends PlatformSettings
            {
                protected array $encrypted = ['apiKey'];

                public ?string $apiKey = null;
            };

            $custom->apiKey = 'plain-text-key';

            $stored = $custom->toStorageArray();
            expect($stored['apiKey'])->not->toContain('plain-text-key');
        });
    });

    describe('AsSettings cast via Platform model', function () {
        it('settings column is hydrated as a PlatformSettings instance', function () {
            $platform = (new PlatformFactory)->create(['settings' => ['timezone' => 'UTC']]);

            expect($platform->settings)->toBeInstanceOf(PlatformSettings::class);
            expect($platform->setting('timezone'))->toBe('UTC');
        });

        it('settings persists and reloads correctly via the cast', function () {
            $platform = (new PlatformFactory)->create(['settings' => ['plan' => 'pro']]);

            $fresh = $platform->fresh();

            expect($fresh->setting('plan'))->toBe('pro');
        });

        it('null settings column returns an empty PlatformSettings instance', function () {
            $platform = (new PlatformFactory)->create(['settings' => null]);

            expect($platform->settings)->toBeInstanceOf(PlatformSettings::class);
            expect($platform->settings->toArray())->toBe([]);
        });

        it('overflow keys pass through the cast round-trip', function () {
            $platform = (new PlatformFactory)->create([
                'settings' => ['config' => ['mail' => ['from' => 'test@example.com']]],
            ]);

            $fresh = $platform->fresh();

            expect($fresh->setting('config.mail.from'))->toBe('test@example.com');
        });

        it('assigning settings as array is cast correctly', function () {
            $platform = (new PlatformFactory)->create();
            $platform->update(['settings' => ['locale' => 'de']]);

            expect($platform->fresh()->setting('locale'))->toBe('de');
        });
    });
});
