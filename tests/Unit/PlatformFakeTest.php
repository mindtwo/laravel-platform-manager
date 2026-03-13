<?php

use mindtwo\LaravelPlatformManager\Models\Platform as PlatformModel;
use mindtwo\LaravelPlatformManager\Platform;
use mindtwo\LaravelPlatformManager\Testing\InteractsWithPlatform;
use mindtwo\LaravelPlatformManager\Testing\PlatformFake;

uses(InteractsWithPlatform::class);

afterEach(fn () => $this->clearPlatform());

describe('PlatformFake', function () {
    it('make() resolves the platform singleton without a database', function () {
        PlatformFake::make(['hostname' => 'test.com']);

        expect(app(Platform::class)->isResolved())->toBeTrue();
        expect(app(Platform::class)->hostname)->toBe('test.com');
    });

    it('make() sets the resolver', function () {
        PlatformFake::make([], resolver: 'token');

        expect(app(Platform::class)->resolver())->toBe('token');
    });

    it('make() applies extra scopes', function () {
        PlatformFake::make([], scopes: ['read', 'write']);

        expect(app(Platform::class)->can('read'))->toBeTrue();
        expect(app(Platform::class)->can('write'))->toBeTrue();
    });

    it('make() merges model-level scopes with extra scopes', function () {
        PlatformFake::make(['scopes' => ['read']], scopes: ['write']);

        expect(app(Platform::class)->can('read'))->toBeTrue();
        expect(app(Platform::class)->can('write'))->toBeTrue();
    });

    it('make() returns a PlatformModel instance', function () {
        $platform = PlatformFake::make(['hostname' => 'example.com']);

        expect($platform->get())->toBeInstanceOf(PlatformModel::class);
        expect($platform->get()->hostname)->toBe('example.com');
    });

    it('reset() clears the resolved platform', function () {
        PlatformFake::make(['hostname' => 'test.com']);
        PlatformFake::reset();

        expect(app(Platform::class)->isResolved())->toBeFalse();
    });
});

describe('InteractsWithPlatform', function () {
    it('setPlatform() helper resolves the platform', function () {
        $this->setPlatform(['hostname' => 'example.com']);

        $this->assertPlatformResolved();
        $this->assertPlatformResolver('fake');
    });

    it('assertPlatformCan() and assertPlatformCannot() check scopes', function () {
        $this->setPlatform(scopes: ['read']);

        $this->assertPlatformCan('read');
        $this->assertPlatformCannot('write');
    });

    it('assertPlatformResolver() checks the resolver name', function () {
        $this->setPlatform(resolver: 'token');

        $this->assertPlatformResolver('token');
    });
});
