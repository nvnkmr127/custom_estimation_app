<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('estimate_items', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('estimate_sections', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('estimate_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('estimate_sections', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
