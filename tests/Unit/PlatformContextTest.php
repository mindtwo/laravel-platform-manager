<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use mindtwo\LaravelPlatformManager\Middleware\ResolvePlatform;
use mindtwo\LaravelPlatformManager\Models\AuthToken;
use mindtwo\LaravelPlatformManager\Models\Platform as PlatformModel;
use mindtwo\LaravelPlatformManager\Platform;
use mindtwo\LaravelPlatformManager\Tests\Fake\PlatformFactory;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

describe('PlatformContext', function () {
    describe('context singleton', function () {
        it('is not resolved by default', function () {
            $platform = app(Platform::class);

            expect($platform->isResolved())->toBeFalse();
            expect($platform->get())->toBeNull();
        });

        it('can set and resolve a platform on the context', function () {
            $model = (new PlatformFactory)->create();

            $platform = app(Platform::class);
            $platform->set($model, 'test');

            expect($platform->isResolved())->toBeTrue();
            expect($platform->get()->id)->toBe($model->id);
            expect($platform->resolver())->toBe('test');
        });

        it('can proxy property reads to the underlying model', function () {
            $model = (new PlatformFactory)->create(['hostname' => 'example.com']);

            $platform = app(Platform::class);
            $platform->set($model, 'test');

            expect($platform->hostname)->toBe('example.com');
            expect($platform->uuid)->toBe($model->uuid);
        });

        it('can restore from id', function () {
            $model = (new PlatformFactory)->create();

            $platform = app(Platform::class);
            $platform->restoreFromId($model->id);

            expect($platform->isResolved())->toBeTrue();
            expect($platform->get()->id)->toBe($model->id);
            expect($platform->resolver())->toBe('queue-restore');
        });

        it('can serialize for queue', function () {
            $model = (new PlatformFactory)->create();

            $platform = app(Platform::class);
            $platform->set($model, 'host');

            $serialized = $platform->serializeForQueue();

            expect($serialized['platform_id'])->toBe($model->id);
            expect($serialized['resolver'])->toBe('host');
        });
    });

    describe('use()', function () {
        it('temporarily switches the platform and restores it after', function () {
            $original = (new PlatformFactory)->create(['hostname' => 'original.com']);
            $temporary = (new PlatformFactory)->create(['hostname' => 'temporary.com']);

            $platform = app(Platform::class);
            $platform->set($original, 'host');

            $seenInside = null;

            $platform->use($temporary, function () use ($platform, &$seenInside) {
                $seenInside = $platform->hostname;
            });

            expect($seenInside)->toBe('temporary.com');
            expect($platform->hostname)->toBe('original.com');
            expect($platform->resolver())->toBe('host');
        });

        it('returns the callback return value', function () {
            $model = (new PlatformFactory)->create();

            $result = app(Platform::class)->use($model, fn () => 'result');

            expect($result)->toBe('result');
        });

        it('restores previous platform even when the callback throws', function () {
            $original = (new PlatformFactory)->create(['hostname' => 'original.com']);
            $temporary = (new PlatformFactory)->create(['hostname' => 'temporary.com']);

            $platform = app(Platform::class);
            $platform->set($original, 'host');

            try {
                $platform->use($temporary, fn () => throw new RuntimeException('boom'));
            } catch (RuntimeException) {
            }

            expect($platform->hostname)->toBe('original.com');
        });
    });

    describe('middleware resolution', function () {
        it('resolves by hostname', function () {
            $model = (new PlatformFactory)->create(['hostname' => 'example.com']);

            $middleware = app(ResolvePlatform::class);
            $request = Request::create('http://example.com/');
            $called = false;

            $middleware->handle($request, function () use (&$called) {
                $called = true;

                return new Response;
            }, 'host');

            $platform = app(Platform::class);

            expect($called)->toBeTrue();
            expect($platform->isResolved())->toBeTrue();
            expect($platform->get()->id)->toBe($model->id);
        });

        it('throws when no platform matches', function () {
            $middleware = app(ResolvePlatform::class);
            $request = Request::create('http://example.com/');

            $middleware->handle($request, fn () => new Response, 'host');
        })->throws(HttpException::class);
    });

    describe('token resolver', function () {
        it('resolves platform by token', function () {
            $model = (new PlatformFactory)->create();

            $token = new AuthToken;
            $token->platform_id = $model->id;
            $token->save();

            $platform = app(Platform::class);
            $platform->set(
                PlatformModel::query()->isActive()->byToken($token->token)->firstOrFail(),
                'token'
            );

            expect($platform->isResolved())->toBeTrue();
            expect($platform->get()->id)->toBe($model->id);
        });

        it('resolves platform by token with read scope', function () {
            $model = (new PlatformFactory)->create();

            $token = new AuthToken;
            $token->platform_id = $model->id;
            $token->scopes = ['read'];
            $token->save();

            $platform = app(Platform::class);
            $platform->set(
                PlatformModel::query()->isActive()->byToken($token->token)->firstOrFail(),
                'token',
                $token->scopes,
            );

            expect($platform->isResolved())->toBeTrue();
            expect($platform->get()->id)->toBe($model->id);
            expect($platform->can('read'))->toBeTrue();
        });

        it('can() returns false when resolver is not token', function () {
            $model = (new PlatformFactory)->create();

            $platform = app(Platform::class);
            $platform->set($model, 'host');

            expect($platform->can('read'))->toBeFalse();
        });

        it('can() returns false when token lacks the requested scope', function () {
            $model = (new PlatformFactory)->create();

            $platform = app(Platform::class);
            $platform->set($model, 'token', ['read']);

            expect($platform->can('write'))->toBeFalse();
        });

        it('resolves via middleware by token header', function () {
            $model = (new PlatformFactory)->create();
            $token = AuthToken::create(['platform_id' => $model->id, 'scopes' => ['read']]);

            $middleware = app(ResolvePlatform::class);
            $request = Request::create('/');
            $request->headers->set('X-Platform-Token', $token->token);

            $middleware->handle($request, fn () => new Response, 'token');

            $platform = app(Platform::class);
            expect($platform->isResolved())->toBeTrue();
            expect($platform->get()->id)->toBe($model->id);
            expect($platform->resolver())->toBe('token');
            expect($platform->can('read'))->toBeTrue();
        });

        it('merges platform baseline scopes with token scopes', function () {
            $model = (new PlatformFactory)->create(['scopes' => ['read']]);
            $token = AuthToken::create(['platform_id' => $model->id, 'scopes' => ['write']]);

            $middleware = app(ResolvePlatform::class);
            $request = Request::create('/');
            $request->headers->set('X-Platform-Token', $token->token);

            $middleware->handle($request, fn () => new Response, 'token');

            $platform = app(Platform::class);
            expect($platform->can('read'))->toBeTrue();
            expect($platform->can('write'))->toBeTrue();
        });

        it('rejects an expired token', function () {
            $model = (new PlatformFactory)->create();
            $token = AuthToken::create([
                'platform_id' => $model->id,
                'expired_at' => now()->subMinute(),
            ]);

            $middleware = app(ResolvePlatform::class);
            $request = Request::create('/');
            $request->headers->set('X-Platform-Token', $token->token);

            $middleware->handle($request, fn () => new Response, 'token');
        })->throws(HttpException::class);
    });

    describe('hostname scopes', function () {
        it('resolves by hostname scope', function () {
            $model = (new PlatformFactory)->create(['hostname' => 'example.com']);

            $resolved = PlatformModel::query()->isActive()->byHostname('example.com')->first();

            expect($resolved)->not->toBeNull();
            expect($resolved->id)->toBe($model->id);
        });

        it('resolves by wildcard hostname (*.tld)', function () {
            $model = (new PlatformFactory)->create(['hostname' => '*.example.com']);

            $resolved = PlatformModel::query()->isActive()->byHostname('foo.example.com')->first();

            expect($resolved)->not->toBeNull();
            expect($resolved->id)->toBe($model->id);
        });

        it('resolves by wildcard hostname (prefix.*)', function () {
            $model = (new PlatformFactory)->create(['hostname' => 'my.app.*']);

            $resolved = PlatformModel::query()->isActive()->byHostname('my.app.com')->first();

            expect($resolved)->not->toBeNull();
            expect($resolved->id)->toBe($model->id);
        });

        it('resolves by wildcard pattern in additional_hostnames', function () {
            $model = (new PlatformFactory)->create([
                'hostname' => 'example.com',
                'additional_hostnames' => ['*.staging.example.com'],
            ]);

            $resolved = PlatformModel::query()->isActive()->byHostname('app.staging.example.com')->first();

            expect($resolved)->not->toBeNull();
            expect($resolved->id)->toBe($model->id);
        });

        it('does not match a wildcard hostname pattern against a non-matching hostname', function () {
            (new PlatformFactory)->create(['hostname' => '*.example.com']);

            $resolved = PlatformModel::query()->isActive()->byHostname('foo.other.com')->first();

            expect($resolved)->toBeNull();
        });
    });

    describe('context scope', function () {
        it('resolves by context scope', function () {
            $model = (new PlatformFactory)->create(['context' => 'my-tenant']);

            $resolved = PlatformModel::query()->isActive()->byContext('my-tenant')->first();

            expect($resolved)->not->toBeNull();
            expect($resolved->id)->toBe($model->id);
        });

        it('does not resolve inactive platforms', function () {
            (new PlatformFactory)->inactive()->create(['hostname' => 'example.com']);

            $resolved = PlatformModel::query()->isActive()->byHostname('example.com')->first();

            expect($resolved)->toBeNull();
        });
    });

    describe('session resolver', function () {
        it('saveToSession() stores the platform PK in the session', function () {
            $model = (new PlatformFactory)->create();

            app(Platform::class)->set($model, 'host');
            app(Platform::class)->saveToSession();

            expect(session()->get(config('platform.session_key')))->toBe($model->id);
        });

        it('saveToSession() accepts a model and sets it before saving', function () {
            $model = (new PlatformFactory)->create();

            app(Platform::class)->saveToSession($model);

            expect(app(Platform::class)->isResolved())->toBeTrue();
            expect(app(Platform::class)->get()->id)->toBe($model->id);
            expect(app(Platform::class)->resolver())->toBe('session');
            expect(session()->get(config('platform.session_key')))->toBe($model->id);
        });

        it('saveToSession() throws when no platform is resolved', function () {
            app(Platform::class)->saveToSession();
        })->throws(LogicException::class);

        it('clearFromSession() removes the platform from the session', function () {
            $model = (new PlatformFactory)->create();

            app(Platform::class)->saveToSession($model);
            app(Platform::class)->clearFromSession();

            expect(session()->has(config('platform.session_key')))->toBeFalse();
        });

        it('resolves platform from session via middleware', function () {
            $model = (new PlatformFactory)->create();

            $middleware = app(ResolvePlatform::class);
            $request = Request::create('/');
            $request->setLaravelSession(session()->driver());
            session()->put(config('platform.session_key'), $model->id);

            $middleware->handle($request, fn () => new Response, 'session');

            expect(app(Platform::class)->isResolved())->toBeTrue();
            expect(app(Platform::class)->get()->id)->toBe($model->id);
            expect(app(Platform::class)->resolver())->toBe('session');
        });

        it('returns null when session is empty', function () {
            $middleware = app(ResolvePlatform::class);
            $request = Request::create('/');
            $request->setLaravelSession(session()->driver());

            // Falls through to host, which also fails → 404
            $middleware->handle($request, fn () => new Response, 'session');
        })->throws(HttpException::class);

        it('skips inactive platforms', function () {
            $model = (new PlatformFactory)->inactive()->create();

            $middleware = app(ResolvePlatform::class);
            $request = Request::create('/');
            $request->setLaravelSession(session()->driver());
            session()->put(config('platform.session_key'), $model->id);

            $middleware->handle($request, fn () => new Response, 'session');
        })->throws(HttpException::class);
    });

    describe('baseline scopes', function () {
        it('can() returns true for a platform baseline scope on a non-token resolver', function () {
            $model = (new PlatformFactory)->create(['scopes' => ['read']]);

            app(Platform::class)->set($model, 'host');

            expect(app(Platform::class)->can('read'))->toBeTrue();
        });

        it('set() merges platform model scopes with extra scopes passed as third argument', function () {
            $model = (new PlatformFactory)->create(['scopes' => ['read']]);

            app(Platform::class)->set($model, 'token', ['write']);

            $platform = app(Platform::class);
            expect($platform->can('read'))->toBeTrue();
            expect($platform->can('write'))->toBeTrue();
        });
    });
});
