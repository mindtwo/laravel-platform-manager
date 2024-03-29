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
        if (! Schema::hasTable('auth_tokens')) {
            Schema::create('auth_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignIdFor(config('auth.providers.users.model'))->nullable();
                $platform = $table->foreignIdFor(config('platform-resolver.model'));
                $table->smallInteger('type');
                $table->string('description')->nullable();
                $token = $table->string('token', 75)->unique();
                $table->timestamps();
                $table->softDeletes();
                $table->unique([$platform->name, $token->name]);
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
        Schema::dropIfExists('auth_tokens');
    }
};
