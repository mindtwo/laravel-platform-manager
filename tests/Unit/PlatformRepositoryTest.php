<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use mindtwo\LaravelPlatformManager\Models\AuthToken;
use mindtwo\LaravelPlatformManager\Models\Platform as PlatformModel;
use mindtwo\LaravelPlatformManager\Repositories\PlatformRepository;
use mindtwo\LaravelPlatformManager\Tests\Fake\PlatformFactory;

uses(RefreshDatabase::class);

describe('PlatformRepository', function () {
    describe('value-based finders', function () {
        it('findByHostname() returns the active platform for the given hostname', function () {
            $model = (new PlatformFactory())->create(['hostname' => 'example.com']);

            $result = app(PlatformRepository::class)->findByHostname('example.com');

            expect($result)->not->toBeNull();
            expect($result->id)->toBe($model->id);
        });

        it('findByHostname() returns null for an inactive platform', function () {
            (new PlatformFactory())->inactive()->create(['hostname' => 'example.com']);

            expect(app(PlatformRepository::class)->findByHostname('example.com'))->toBeNull();
        });

        it('findByContext() returns the active platform for the given context', function () {
            $model = (new PlatformFactory())->create(['context' => 'my-tenant']);

            $result = app(PlatformRepository::class)->findByContext('my-tenant');

            expect($result)->not->toBeNull();
            expect($result->id)->toBe($model->id);
        });

        it('findByContext() returns null when no match', function () {
            expect(app(PlatformRepository::class)->findByContext('missing'))->toBeNull();
        });

        it('findActiveById() returns the active platform', function () {
            $model = (new PlatformFactory())->create();

            $result = app(PlatformRepository::class)->findActiveById($model->id);

            expect($result)->not->toBeNull();
            expect($result->id)->toBe($model->id);
        });

        it('findActiveById() returns null for an inactive platform', function () {
            $model = (new PlatformFactory())->inactive()->create();

            expect(app(PlatformRepository::class)->findActiveById($model->id))->toBeNull();
        });

        it('findByUuid() returns the platform for the given uuid', function () {
            $model = (new PlatformFactory())->create();

            $result = app(PlatformRepository::class)->findByUuid($model->uuid);

            expect($result)->not->toBeNull();
            expect($result->id)->toBe($model->id);
        });

        it('findByUuid() returns null when no match', function () {
            expect(app(PlatformRepository::class)->findByUuid('non-existent-uuid'))->toBeNull();
        });

        it('findByTokenWithScopes() returns platform and merged scopes', function () {
            $model = (new PlatformFactory())->create(['scopes' => ['read']]);
            $token = AuthToken::create(['platform_id' => $model->id, 'scopes' => ['write']]);

            $result = app(PlatformRepository::class)->findByTokenWithScopes($token->token);

            expect($result)->not->toBeNull();
            expect($result[0]->id)->toBe($model->id);
            expect($result[1])->toContain('read');
            expect($result[1])->toContain('write');
        });

        it('findByTokenWithScopes() deduplicates overlapping scopes', function () {
            $model = (new PlatformFactory())->create(['scopes' => ['read']]);
            $token = AuthToken::create(['platform_id' => $model->id, 'scopes' => ['read', 'write']]);

            $result = app(PlatformRepository::class)->findByTokenWithScopes($token->token);

            expect(array_count_values($result[1])['read'])->toBe(1);
        });

        it('findByTokenWithScopes() returns null for an expired token', function () {
            $model = (new PlatformFactory())->create();
            $token = AuthToken::create([
                'platform_id' => $model->id,
                'expired_at'  => now()->subMinute(),
            ]);

            expect(app(PlatformRepository::class)->findByTokenWithScopes($token->token))->toBeNull();
        });

        it('findByTokenWithScopes() returns null for an inactive platform', function () {
            $model = (new PlatformFactory())->inactive()->create();
            $token = AuthToken::create(['platform_id' => $model->id]);

            expect(app(PlatformRepository::class)->findByTokenWithScopes($token->token))->toBeNull();
        });
    });

    describe('request-aware resolvers', function () {
        it('resolveByToken() reads the X-Platform-Token header', function () {
            $model = (new PlatformFactory())->create();
            $token = AuthToken::create(['platform_id' => $model->id, 'scopes' => ['read']]);

            $request = Request::create('/');
            $request->headers->set('X-Platform-Token', $token->token);

            $result = app(PlatformRepository::class)->resolveByToken($request);

            expect($result)->not->toBeNull();
            expect($result[0]->id)->toBe($model->id);
            expect($result[1])->toContain('read');
        });

        it('resolveByToken() falls back to the legacy header', function () {
            $model = (new PlatformFactory())->create();
            $token = AuthToken::create(['platform_id' => $model->id]);

            config(['platform.header_names.token_legacy' => 'X-Legacy-Token']);

            $request = Request::create('/');
            $request->headers->set('X-Legacy-Token', $token->token);

            $result = app(PlatformRepository::class)->resolveByToken($request);

            expect($result)->not->toBeNull();
            expect($result[0]->id)->toBe($model->id);
        });

        it('resolveByToken() returns null when no token header is present', function () {
            $request = Request::create('/');

            expect(app(PlatformRepository::class)->resolveByToken($request))->toBeNull();
        });

        it('resolveByHostname() reads the host from the request', function () {
            $model = (new PlatformFactory())->create(['hostname' => 'example.com']);

            $request = Request::create('http://example.com/');

            $result = app(PlatformRepository::class)->resolveByHostname($request);

            expect($result)->not->toBeNull();
            expect($result->id)->toBe($model->id);
        });

        it('resolveByContext() reads the X-Platform-Context header', function () {
            $model = (new PlatformFactory())->create(['context' => 'my-tenant']);

            $request = Request::create('/');
            $request->headers->set('X-Platform-Context', 'my-tenant');

            $result = app(PlatformRepository::class)->resolveByContext($request);

            expect($result)->not->toBeNull();
            expect($result->id)->toBe($model->id);
        });

        it('resolveByContext() returns null when header is absent', function () {
            $request = Request::create('/');

            expect(app(PlatformRepository::class)->resolveByContext($request))->toBeNull();
        });

        it('resolveBySession() reads the platform id from the session', function () {
            $model = (new PlatformFactory())->create();

            $request = Request::create('/');
            $request->setLaravelSession(session()->driver());
            session()->put(config('platform.session_key'), $model->id);

            $result = app(PlatformRepository::class)->resolveBySession($request);

            expect($result)->not->toBeNull();
            expect($result->id)->toBe($model->id);
        });

        it('resolveBySession() returns null when session is empty', function () {
            $request = Request::create('/');
            $request->setLaravelSession(session()->driver());

            expect(app(PlatformRepository::class)->resolveBySession($request))->toBeNull();
        });
    });

    describe('collection queries', function () {
        it('allActive() returns only active platforms', function () {
            (new PlatformFactory())->count(2)->create();
            (new PlatformFactory())->inactive()->create();

            $results = app(PlatformRepository::class)->allActive();

            expect($results)->toHaveCount(2);
            expect($results->every(fn (PlatformModel $p) => $p->is_active))->toBeTrue();
        });

        it('count() returns the number of matching platforms', function () {
            (new PlatformFactory())->count(3)->create();
            (new PlatformFactory())->inactive()->create();

            expect(app(PlatformRepository::class)->count(['is_active' => true]))->toBe(3);
            expect(app(PlatformRepository::class)->count(['is_active' => false]))->toBe(1);
        });
    });
});
