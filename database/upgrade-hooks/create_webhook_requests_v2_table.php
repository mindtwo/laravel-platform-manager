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
        if (! Schema::hasTable('webhook_requests_v2')) {
            Schema::create('webhook_requests_v2', function (Blueprint $table) {
                $table->id();

                $table->ulid('ulid')->index();

                $table->string('hook');

                $table->string('requested_from')->nullable();
                $table->string('response_url')->nullable();

                $table->json('payload')->nullable();

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
        Schema::dropIfExists('webhook_requests_v2');
    }
};
