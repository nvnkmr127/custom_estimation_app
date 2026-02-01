<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->string('http_method')->default('POST')->after('url');
            $table->unsignedInteger('rate_limit')->nullable()->comment('Requests per minute')->after('concurrency_limit');
        });
    }

    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropColumn(['http_method', 'rate_limit']);
        });
    }
};
