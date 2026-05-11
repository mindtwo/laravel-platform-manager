<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platforms')) {
            Schema::create('platforms', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name')->nullable();
                $table->boolean('is_active')->default(false)->index();
                $table->string('hostname', 100)->nullable()->index();
                $table->json('additional_hostnames')->nullable();
                $table->string('context')->nullable()->unique();
                $table->json('scopes')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platforms');
    }
};
