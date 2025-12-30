<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * B2B payment terms (Net 30, Net 60, etc).
     * Allows customers to pay by invoice with credit limits.
     */
    public function up(): void
    {
        Schema::create('customer_payment_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            
            // Payment term type
            $table->enum('term_type', ['cod', 'net_15', 'net_30', 'net_60', 'net_90'])
                ->default('cod');
            
            // Credit management
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0); // Outstanding amount
            
            // Early payment incentives
            $table->decimal('early_payment_discount_percent', 5, 2)->nullable();
            $table->integer('early_payment_days')->nullable(); // e.g., 2% off if paid within 10 days
            
            // Approval workflow
            $table->boolean('is_approved')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payment_terms');
    }
};
