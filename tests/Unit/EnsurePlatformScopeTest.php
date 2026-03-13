<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use mindtwo\LaravelPlatformManager\Middleware\EnsurePlatformScope;
use mindtwo\LaravelPlatformManager\Platform;
use mindtwo\LaravelPlatformManager\Tests\Fake\PlatformFactory;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

describe('EnsurePlatformScope', function () {
    describe('passing requests', function () {
        it('passes when the platform has the required scope', function () {
            $model = (new PlatformFactory)->create();
            app(Platform::class)->set($model, 'host', ['read']);

            $called = false;
            app(EnsurePlatformScope::class)->handle(
                Request::create('/'),
                function () use (&$called) {
                    $called = true;

                    return new Response;
                },
                'read',
            );

            expect($called)->toBeTrue();
        });

        it('passes when all required scopes are present', function () {
            $model = (new PlatformFactory)->create();
            app(Platform::class)->set($model, 'host', ['read', 'write']);

            $called = false;
            app(EnsurePlatformScope::class)->handle(
                Request::create('/'),
                function () use (&$called) {
                    $called = true;

                    return new Response;
                },
                'read',
                'write',
            );

            expect($called)->toBeTrue();
        });

        it('passes when scope comes from the platform baseline', function () {
            $model = (new PlatformFactory)->create(['scopes' => ['read']]);
            app(Platform::class)->set($model, 'host');

            $called = false;
            app(EnsurePlatformScope::class)->handle(
                Request::create('/'),
                function () use (&$called) {
                    $called = true;

                    return new Response;
                },
                'read',
            );

            expect($called)->toBeTrue();
        });
    });

    describe('blocked requests', function () {
        it('aborts with 403 when the platform lacks the required scope', function () {
            $model = (new PlatformFactory)->create();
            app(Platform::class)->set($model, 'host', ['read']);

            app(EnsurePlatformScope::class)->handle(
                Request::create('/'),
                fn () => new Response,
                'write',
            );
        })->throws(HttpException::class);

        it('aborts with 403 when one of multiple required scopes is missing', function () {
            $model = (new PlatformFactory)->create();
            app(Platform::class)->set($model, 'host', ['read']);

            app(EnsurePlatformScope::class)->handle(
                Request::create('/'),
                fn () => new Response,
                'read',
                'write',
            );
        })->throws(HttpException::class);
    });
});
