<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('auth_tokens')) {
            Schema::create('auth_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignIdFor(config('platform.model'), 'platform_id')
                    ->constrained('platforms')
                    ->cascadeOnDelete();
                $table->json('scopes')->default('[]');
                $table->string('token', 75)->unique();
                $table->datetime('expired_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_tokens');
    }
};
