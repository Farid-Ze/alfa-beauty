<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Payment Logs Table - Required for Tax Compliance (UU KUP)
     * Stores all payment transactions for 10-year retention per Indonesian tax law.
     */
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            
            // Payment Method
            $table->string('payment_method'); // whatsapp, manual_transfer, cash, bank_transfer
            $table->string('provider')->nullable(); // BCA, Mandiri, BRI, etc.
            
            // Financial Details
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('IDR');
            
            // Status Tracking
            $table->string('status')->default('pending'); // pending, confirmed, failed, refunded
            
            // Confirmation Details
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            
            // Reference & Traceability
            $table->string('reference_number')->nullable(); // Bank transfer ref, invoice number
            $table->string('external_id')->nullable(); // For future payment gateway integration
            
            // Notes & Metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Flexible JSON for additional data
            
            // Audit Fields
            $table->timestamps();
            $table->softDeletes(); // Never hard delete for compliance
            
            // Indexes for reporting
            $table->index(['order_id', 'status']);
            $table->index(['payment_method', 'created_at']);
            $table->index('reference_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
