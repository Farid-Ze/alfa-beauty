<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->nullable()->index();
            $table->string('idempotency_key', 128)->nullable()->index();

            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action', 64);
            $table->string('entity_type', 128);
            $table->unsignedBigInteger('entity_id')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['entity_type', 'entity_id'], 'idx_audit_events_entity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
