<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('platforms')) {
            Schema::create('platforms', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignIdFor(config('auth.providers.users.model'), 'owner_id')->nullable();
                $table->boolean('is_main')->default(0);
                $table->boolean('is_active')->default(0);
                $table->boolean('is_headless')->default(0);
                $table->string('name')->nullable();
                $table->string('hostname', 50)->nullable()->index();
                $table->json('additional_hostnames')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('platforms');
    }
};
