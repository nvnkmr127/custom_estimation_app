<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pdf_templates', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('is_active');
            $table->string('watermark_text')->nullable()->after('is_locked');
            $table->float('watermark_opacity')->default(0.1)->after('watermark_text');
            $table->boolean('is_password_protected')->default(false)->after('watermark_opacity');
        });

        Schema::create('pdf_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pdf_template_id')->constrained()->onDelete('cascade');
            $table->integer('version');
            $table->longText('html_content');
            $table->longText('css_content')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_template_versions');

        Schema::table('pdf_templates', function (Blueprint $table) {
            $table->dropColumn(['is_locked', 'watermark_text', 'watermark_opacity', 'is_password_protected']);
        });
    }
};
