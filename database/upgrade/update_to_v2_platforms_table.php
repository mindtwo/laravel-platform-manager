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
        // --- remove this section if you want to keep columns
        Schema::table('platforms', function (Blueprint $table) {
            $table->dropColumn('logo');
        });

        Schema::table('platforms', function (Blueprint $table) {
            $table->dropColumn('primary_color');
        });

        Schema::table('platforms', function (Blueprint $table) {
            $table->dropColumn('email');
        });
        // --- remove up to here

        Schema::table('platforms', function (Blueprint $table) {
            $table->renameColumn('visibility', 'is_active');
        });

        Schema::table('platforms', function (Blueprint $table) {
            $table->boolean('is_headless')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('platforms', function (Blueprint $table) {
            $table->string('email', 50)->nullable();
            $table->string('primary_color', 7)->default('#1E9FDA')->nullable();
            $table->string('logo_file')->nullable();
        });

        Schema::table('platforms', function (Blueprint $table) {
            $table->renameColumn('is_active', 'visibility');
        });

        Schema::table('platforms', function (Blueprint $table) {
            $table->dropColumn('is_headless');
        });
    }
};
