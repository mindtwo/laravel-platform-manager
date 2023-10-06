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
        if (! Schema::hasTable('webhook_dispatches_v2')) {
            Schema::create('webhook_dispatches_v2', function (Blueprint $table) {
                $table->id();

                $table->ulid('ulid')->index();

                $table->unsignedBigInteger('platform_id')->nullable();

                $table->string('hook');
                $table->string('dispatch_class');

                $table->string('url')->nullable();
                $table->integer('status')->nullable();

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
        Schema::dropIfExists('webhook_dispatches_v2');
    }
};
