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
        if (! Schema::hasTable('external_platforms')) {
            Schema::create('external_platforms', function (Blueprint $table) {
                $table->id();
                $table->uuid();

                $table->unsignedBigInteger('owner_id')->nullable();
                $table->string('name');
                $table->string('hostname');
                $table->string('webhook_path');
                $table->string('webhook_auth_token');

                $table->timestamps();
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
        Schema::dropIfExists('external_platforms');
    }
};
