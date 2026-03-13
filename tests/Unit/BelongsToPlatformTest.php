<?php

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use mindtwo\LaravelPlatformManager\Models\Platform as PlatformModel;
use mindtwo\LaravelPlatformManager\Platform;
use mindtwo\LaravelPlatformManager\Tests\Fake\Article;
use mindtwo\LaravelPlatformManager\Tests\Fake\PlatformFactory;

uses(RefreshDatabase::class);

describe('BelongsToPlatform', function () {
    beforeEach(function () {
        Schema::create('articles', function ($table) {
            $table->id();
            $table->unsignedBigInteger('platform_id')->nullable();
            $table->string('title')->default('test');
        });
    });

    afterEach(function () {
        Schema::dropIfExists('articles');
    });

    describe('relationship', function () {
        it('platform() returns a BelongsTo pointing to the correct platform', function () {
            $platformModel = (new PlatformFactory())->create();
            $article = Article::create(['platform_id' => $platformModel->id]);

            $relation = $article->platform();

            expect($relation)->toBeInstanceOf(BelongsTo::class);
            expect($relation->getRelated())->toBeInstanceOf(PlatformModel::class);
            expect($article->platform->id)->toBe($platformModel->id);
        });
    });

    describe('auto-fill', function () {
        it('sets platform_id from the resolved context on create()', function () {
            $platformModel = (new PlatformFactory())->create();
            app(Platform::class)->set($platformModel, 'test');

            $article = Article::create([]);

            expect($article->platform_id)->toBe($platformModel->id);
        });

        it('does not overwrite an explicit platform_id', function () {
            $platform1 = (new PlatformFactory())->create();
            $platform2 = (new PlatformFactory())->create();

            app(Platform::class)->set($platform1, 'test');

            $article = Article::create(['platform_id' => $platform2->id]);

            expect($article->platform_id)->toBe($platform2->id);
        });

        it('silently skips when no platform is resolved', function () {
            $article = Article::create([]);

            expect($article->platform_id)->toBeNull();
        });
    });

    describe('scopes', function () {
        it('forCurrentPlatform() returns only records belonging to the current platform', function () {
            $platform = (new PlatformFactory())->create();
            app(Platform::class)->set($platform, 'test');

            Article::create(['platform_id' => $platform->id]);
            Article::create(['platform_id' => $platform->id]);

            $results = Article::query()->forCurrentPlatform()->get();

            expect($results)->toHaveCount(2);
        });

        it('forCurrentPlatform() excludes records belonging to a different platform', function () {
            $platform1 = (new PlatformFactory())->create();
            $platform2 = (new PlatformFactory())->create();

            app(Platform::class)->set($platform1, 'test');

            Article::create(['platform_id' => $platform1->id]);
            Article::create(['platform_id' => $platform2->id]);

            $results = Article::query()->forCurrentPlatform()->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->platform_id)->toBe($platform1->id);
        });

        it('forPlatform() filters by model instance', function () {
            $platform1 = (new PlatformFactory())->create();
            $platform2 = (new PlatformFactory())->create();

            Article::create(['platform_id' => $platform1->id]);
            Article::create(['platform_id' => $platform2->id]);

            $results = Article::query()->forPlatform($platform1)->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->platform_id)->toBe($platform1->id);
        });

        it('forPlatform() filters by raw integer ID', function () {
            $platform1 = (new PlatformFactory())->create();
            $platform2 = (new PlatformFactory())->create();

            Article::create(['platform_id' => $platform1->id]);
            Article::create(['platform_id' => $platform2->id]);

            $results = Article::query()->forPlatform($platform2->id)->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->platform_id)->toBe($platform2->id);
        });
    });
});
