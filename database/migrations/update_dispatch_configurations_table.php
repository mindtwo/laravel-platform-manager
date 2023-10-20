<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use mindtwo\LaravelPlatformManager\Models\ExternalPlatform;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('dispatch_configurations')) {
            Schema::table('dispatch_configurations', function (Blueprint $table) {
                $table->string('url')->nullable()->change();
                $table->string('auth_token')->nullable()->change();
            });

            Schema::table('dispatch_configurations', function (Blueprint $table) {
                $table->foreignIdFor(ExternalPlatform::class, 'external_platform_id')->after('platform_id')->nullable();
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
        if (! Schema::hasTable('dispatch_configurations')) {
            Schema::table('dispatch_configurations', function (Blueprint $table) {
                $table->dropColumn('external_platform_id');
            });
        }
    }
};
