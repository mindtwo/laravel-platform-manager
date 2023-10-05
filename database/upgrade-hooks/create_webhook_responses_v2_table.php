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
        if (! Schema::hasTable('webhook_responses_v2')) {
            Schema::create('webhook_responses_v2', function (Blueprint $table) {
                $table->id();

                $table->morphs('responseable');

                $table->string('hook');
                $table->string('ulid');

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
        Schema::dropIfExists('webhook_responses_v2');
    }
};
