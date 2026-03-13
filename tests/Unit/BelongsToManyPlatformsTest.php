<?php

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use mindtwo\LaravelPlatformManager\Platform;
use mindtwo\LaravelPlatformManager\Tests\Fake\PlatformFactory;
use mindtwo\LaravelPlatformManager\Tests\Fake\Post;

uses(RefreshDatabase::class);

describe('BelongsToManyPlatforms', function () {
    beforeEach(function () {
        Schema::create('posts', function ($table) {
            $table->id();
            $table->string('title')->default('test');
        });

        Schema::create('platform_posts', function ($table) {
            $table->unsignedBigInteger('platform_id');
            $table->unsignedBigInteger('post_id');
        });
    });

    afterEach(function () {
        Schema::dropIfExists('platform_posts');
        Schema::dropIfExists('posts');
    });

    describe('relationship', function () {
        it('platforms() returns a BelongsToMany relationship', function () {
            $post = Post::create([]);

            expect($post->platforms())->toBeInstanceOf(BelongsToMany::class);
        });

        it('getPlatformPivotTable() derives the pivot table name from the model class', function () {
            expect((new Post)->getPlatformPivotTable())->toBe('platform_posts');
        });

        it('returns the attached platforms', function () {
            $platform = (new PlatformFactory())->create();
            $post = Post::create([]);
            $post->platforms()->attach($platform->id);

            expect($post->platforms)->toHaveCount(1);
            expect($post->platforms->first()->id)->toBe($platform->id);
        });
    });

    describe('scopes', function () {
        it('forCurrentPlatform() returns only records attached to the current platform', function () {
            $platform1 = (new PlatformFactory())->create();
            $platform2 = (new PlatformFactory())->create();

            $post1 = Post::create([]);
            $post1->platforms()->attach($platform1->id);

            $post2 = Post::create([]);
            $post2->platforms()->attach($platform2->id);

            app(Platform::class)->set($platform1, 'test');

            $results = Post::query()->forCurrentPlatform()->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($post1->id);
        });

        it('forCurrentPlatform() returns records attached to multiple platforms', function () {
            $platform = (new PlatformFactory())->create();

            $post1 = Post::create([]);
            $post1->platforms()->attach($platform->id);

            $post2 = Post::create([]);
            $post2->platforms()->attach($platform->id);

            Post::create([]); // no platform attached

            app(Platform::class)->set($platform, 'test');

            expect(Post::query()->forCurrentPlatform()->get())->toHaveCount(2);
        });

        it('forPlatform() filters by model instance', function () {
            $platform1 = (new PlatformFactory())->create();
            $platform2 = (new PlatformFactory())->create();

            $post1 = Post::create([]);
            $post1->platforms()->attach($platform1->id);

            $post2 = Post::create([]);
            $post2->platforms()->attach($platform2->id);

            $results = Post::query()->forPlatform($platform1)->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($post1->id);
        });

        it('forPlatform() filters by raw integer id', function () {
            $platform1 = (new PlatformFactory())->create();
            $platform2 = (new PlatformFactory())->create();

            $post1 = Post::create([]);
            $post1->platforms()->attach($platform1->id);

            $post2 = Post::create([]);
            $post2->platforms()->attach($platform2->id);

            $results = Post::query()->forPlatform($platform2->id)->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($post2->id);
        });
    });
});
