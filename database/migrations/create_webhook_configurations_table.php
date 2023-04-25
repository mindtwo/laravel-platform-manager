<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('webhook_configurations')) {
            Schema::create('webhook_configurations', function (Blueprint $table) {
                $table->id();
                $table->uuid();

                $hook = $table->string('hook');
                $table->string('description');
                $table->string('url');
                $table->string('auth_token');

                $platform = $table->foreignIdFor(config('platform-resolver.model'), 'platform_id');

                $table->timestamps();

                $table->unique([$hook->name, $platform->name]);
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
        Schema::dropIfExists('webhook_configurations');
    }
};
