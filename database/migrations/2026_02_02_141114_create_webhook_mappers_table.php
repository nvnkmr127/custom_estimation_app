<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_mappers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Link to the endpoint
            $table->foreignId('webhook_inbound_endpoint_id')
                ->constrained('webhook_inbound_endpoints')
                ->onDelete('cascade');

            $table->string('name'); // e.g. "Create Lead from Typeform"

            // Filtering/Logic: When should this map run? 
            // e.g. {"field": "payload.type", "operator": "=", "value": "submit"}
            $table->json('trigger_rules')->nullable();

            // Action: What system entity to create/update?
            // e.g. "create_lead", "update_estimate", "notify_slack"
            $table->string('action_type');

            // Mapping Configuration: How to map fields?
            // e.g. {"email": "payload.form_response.answers[0].email", "name": "payload.name"}
            $table->json('action_config')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_mappers');
    }
};
