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
        if (! Schema::hasTable('dispatch_configurations')) {
            Schema::create('dispatch_configurations', function (Blueprint $table) {
                $table->id();
                $table->uuid();

                $hook = $table->string('hook');
                $table->string('description')->nullable();
                $url = $table->string('url');
                $table->string('auth_token');

                $table->foreignIdFor(config('platform-resolver.model'), 'platform_id')->nullable();

                $table->timestamps();

                $table->unique([$hook->name, $url->name]);
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
        Schema::dropIfExists('dispatch_configurations');
    }
};
