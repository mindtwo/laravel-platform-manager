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
                $table->boolean('is_main')->default(0);
                $table->unsignedSmallInteger('visibility')->default(0);
                $table->string('name')->nullable();
                $table->string('email', 50)->nullable();
                $table->string('hostname', 50)->nullable()->index();
                $table->string('primary_color', 7)->default('#1E9FDA')->nullable();
                $table->string('logo_file')->nullable();
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
